<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Quiz;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InstructorQuizController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function index(Request $request): JsonResponse
    {
        $courseIds = Course::where('instructor_id', auth()->id())->pluck('id');

        $query = Quiz::with('course:id,title')
            ->withCount(['questions', 'attempts'])
            ->whereIn('course_id', $courseIds)
            ->latest();

        if ($courseId = $request->integer('course_id')) {
            $query->where('course_id', $courseId);
        }

        return response()->json($query->paginate(20));
    }

    public function store(Request $request, Course $course): JsonResponse
    {
        $this->authorizeCourse($course);

        $quiz = DB::transaction(fn () => $this->saveQuiz($course->quizzes()->make(), $request));
        $this->audit->log('quiz.created', $quiz);

        return response()->json(['quiz' => $quiz->load('questions.options')], 201);
    }

    public function show(Quiz $quiz): JsonResponse
    {
        $this->authorizeQuiz($quiz);

        return response()->json(['quiz' => $quiz->load('questions.options', 'course:id,title')], 200);
    }

    public function update(Request $request, Quiz $quiz): JsonResponse
    {
        $this->authorizeQuiz($quiz);

        DB::transaction(fn () => $this->saveQuiz($quiz, $request));
        $this->audit->log('quiz.updated', $quiz);

        return response()->json(['quiz' => $quiz->load('questions.options')]);
    }

    public function destroy(Quiz $quiz): JsonResponse
    {
        $this->authorizeQuiz($quiz);

        $this->audit->log('quiz.deleted', $quiz);
        $quiz->delete();

        return response()->json(['ok' => true]);
    }

    public function attempts(Quiz $quiz): JsonResponse
    {
        $this->authorizeQuiz($quiz);

        $attempts = $quiz->attempts()
            ->with('user:id,name,student_number')
            ->latest('completed_at')
            ->paginate(30);

        return response()->json($attempts);
    }

    private function saveQuiz(Quiz $quiz, Request $request): Quiz
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:3000'],
            'module_id' => ['nullable', 'exists:course_modules,id'],
            'lesson_id' => ['nullable', 'exists:lessons,id'],
            'passing_score' => ['required', 'integer', 'min:1', 'max:100'],
            'max_attempts' => ['required', 'integer', 'min:1', 'max:10'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.question' => ['required', 'string', 'max:2000'],
            'questions.*.image_url' => ['nullable', 'string', 'max:500'],
            'questions.*.description' => ['nullable', 'string', 'max:3000'],
            'questions.*.explanation' => ['nullable', 'string', 'max:2000'],
            'questions.*.points' => ['nullable', 'integer', 'min:1', 'max:100'],
            'questions.*.options' => ['required', 'array', 'min:2'],
            'questions.*.options.*.text' => ['required', 'string', 'max:500'],
            'questions.*.options.*.is_correct' => ['boolean'],
        ]);

        if (isset($data['module_id'])) {
            $course = $quiz->course ?? $request->route('course');
            abort_if($course && $course->modules()->whereKey($data['module_id'])->doesntExist(),
                422, 'الوحدة المحددة لا تتبع هذا الكورس.');
        }
        if (isset($data['lesson_id'])) {
            $course = $quiz->course ?? $request->route('course');
            abort_if($course && $course->modules()->whereHas('lessons', fn ($q) => $q->whereKey($data['lesson_id']))->doesntExist(),
                422, 'الدرس المحدد لا يتبع هذا الكورس.');
        }

        $quiz->fill(collect($data)->except('questions')->toArray());
        $quiz->save();

        $keepQuestionIds = [];
        foreach ($data['questions'] as $i => $qData) {
            $question = $quiz->questions()->updateOrCreate(
                ['id' => $qData['id'] ?? null],
                [
                    'question' => $qData['question'],
                    'image_url' => $qData['image_url'] ?? null,
                    'description' => $qData['description'] ?? null,
                    'explanation' => $qData['explanation'] ?? null,
                    'points' => $qData['points'] ?? 1,
                    'sort_order' => $i,
                ]
            );
            $keepQuestionIds[] = $question->id;

            $keepOptionIds = [];
            foreach ($qData['options'] as $j => $oData) {
                $option = $question->options()->updateOrCreate(
                    ['id' => $oData['id'] ?? null],
                    ['text' => $oData['text'], 'is_correct' => (bool) ($oData['is_correct'] ?? false), 'sort_order' => $j]
                );
                $keepOptionIds[] = $option->id;
            }
            $question->options()->whereNotIn('id', $keepOptionIds)->delete();
        }
        $quiz->questions()->whereNotIn('id', $keepQuestionIds)->delete();

        return $quiz;
    }

    private function authorizeCourse(Course $course): void
    {
        if ($course->instructor_id !== auth()->id()) {
            abort(403, 'غير مصرح بهذا الكورس.');
        }
    }

    private function authorizeQuiz(Quiz $quiz): void
    {
        if (! $quiz->course || $quiz->course->instructor_id !== auth()->id()) {
            abort(403, 'غير مصرح بهذا الاختبار.');
        }
    }
}
