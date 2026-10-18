<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\EnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class QuizController extends Controller
{
    public function __construct(private EnrollmentService $enrollments) {}

    public function show(Request $request, Quiz $quiz): JsonResponse
    {
        abort_if($quiz->status !== 'published', 404);
        abort_if(! $this->enrollments->canAccess($request->user(), $quiz->course_id), 403);

        $quiz->load(['questions' => fn ($q) => $q->with([
            'options' => fn ($o) => $o->select('id', 'question_id', 'text', 'sort_order'),
        ])]);

        $attemptsUsed = $quiz->attempts()->where('user_id', $request->user()->id)->count();
        $latestAttempt = $quiz->attempts()->where('user_id', $request->user()->id)->latest('completed_at')->first();

        return response()->json([
            'quiz' => $quiz,
            'attempts_used' => $attemptsUsed,
            'attempts_left' => max(0, $quiz->max_attempts - $attemptsUsed),
            'latest_attempt' => $latestAttempt,
        ]);
    }

    public function attempt(Request $request, Quiz $quiz): JsonResponse
    {
        abort_if($quiz->status !== 'published', 404);
        abort_if(! $this->enrollments->canAccess($request->user(), $quiz->course_id), 403);

        $attemptsUsed = $quiz->attempts()->where('user_id', $request->user()->id)->count();
        if ($attemptsUsed >= $quiz->max_attempts) {
            throw ValidationException::withMessages(['quiz' => ['استنفدت عدد المحاولات المسموح.']]);
        }

        // Support both Task Submission (practical site submission) and standard MCQ
        if ($request->filled('student_notes') || $request->filled('image_url') || $request->filled('link_url') || $request->input('submission_type') === 'task') {
            $data = $request->validate([
                'student_notes' => ['nullable', 'string', 'max:5000'],
                'image_url' => ['nullable', 'string', 'max:500'],
                'link_url' => ['nullable', 'string', 'max:500'],
            ]);

            abort_if(! ($data['student_notes'] ?? null) && ! ($data['image_url'] ?? null) && ! ($data['link_url'] ?? null),
                422, 'يرجى كتابة رسالة أو إرفاق صورة/رابط للمهمة.');

            $attempt = QuizAttempt::create([
                'quiz_id' => $quiz->id,
                'user_id' => $request->user()->id,
                'score' => 100,
                'passed' => true,
                'status' => 'submitted',
                'submission_type' => 'task',
                'student_notes' => $data['student_notes'] ?? null,
                'image_url' => $data['image_url'] ?? null,
                'link_url' => $data['link_url'] ?? null,
                'answers' => [],
                'started_at' => now(),
                'completed_at' => now(),
            ]);

            return response()->json([
                'attempt' => $attempt,
                'score' => 100,
                'passed' => true,
                'is_task' => true,
                'message' => 'تم استلام تسليمك للمهمة بنجاح، وستصلك ملاحظات وتعديلات المدرب هنا.',
            ], 201);
        }

        $data = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*' => ['integer', 'exists:quiz_options,id'],
        ]);

        $quiz->load('questions.options');

        $earned = 0;
        $total = max(1, $quiz->questions->sum('points'));
        $review = [];

        foreach ($quiz->questions as $question) {
            $chosen = $data['answers'][$question->id] ?? null;
            $correct = $question->options->where('is_correct', true)->pluck('id')->all();
            $isRight = $chosen && in_array((int) $chosen, array_map('intval', $correct), true);
            if ($isRight) {
                $earned += $question->points;
            }
            $review[] = [
                'question_id' => $question->id,
                'chosen' => $chosen,
                'correct_options' => $correct,
                'is_correct' => (bool) $isRight,
                'explanation' => $question->explanation,
            ];
        }

        $score = (int) round($earned / $total * 100);
        $passed = $score >= $quiz->passing_score;

        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => $request->user()->id,
            'score' => $score,
            'passed' => $passed,
            'answers' => $data['answers'],
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        return response()->json([
            'attempt' => $attempt,
            'score' => $score,
            'passed' => $passed,
            'review' => $review,
        ], 201);
    }

    public function myAttempts(Request $request): JsonResponse
    {
        $attempts = $request->user()->quizAttempts()
            ->with('quiz:id,title,course_id')
            ->latest()->paginate(20);

        return response()->json($attempts);
    }
}
