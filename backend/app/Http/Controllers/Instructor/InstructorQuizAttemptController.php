<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstructorQuizAttemptController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $courseIds = Course::where('instructor_id', auth()->id())->pluck('id');

        $query = QuizAttempt::with(['quiz:id,title,course_id', 'quiz.course:id,title', 'user:id,name,email,student_number'])
            ->whereHas('quiz', fn ($q) => $q->whereIn('course_id', $courseIds))
            ->latest('completed_at');

        if ($quizId = $request->integer('quiz_id')) {
            $query->where('quiz_id', $quizId);
        }
        if ($courseId = $request->integer('course_id')) {
            $query->whereHas('quiz', fn ($q) => $q->where('course_id', $courseId));
        }
        if ($search = $request->string('q')->trim()->toString()) {
            $query->whereHas('user', fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('student_number', 'like', "%{$search}%"));
        }

        return response()->json($query->paginate(20));
    }

    public function review(Request $request, QuizAttempt $attempt): JsonResponse
    {
        $this->authorizeAttempt($attempt);

        $data = $request->validate([
            'status' => ['required', 'string', 'in:submitted,changes_requested,approved'],
            'score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'feedback' => ['nullable', 'string', 'max:5000'],
        ]);

        $attempt->update([
            'status' => $data['status'],
            'score' => $data['score'] ?? $attempt->score,
            'passed' => ($data['status'] === 'approved') || (($data['score'] ?? $attempt->score) >= ($attempt->quiz->passing_score ?? 70)),
            'feedback' => $data['feedback'] ?? $attempt->feedback,
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        return response()->json([
            'attempt' => $attempt->fresh(['user:id,name,email', 'quiz:id,title,course_id']),
            'message' => 'تم حفظ الملاحظات والتقييم بنجاح.',
        ]);
    }

    public function destroy(QuizAttempt $attempt): JsonResponse
    {
        $this->authorizeAttempt($attempt);
        $attempt->delete();

        return response()->json(['ok' => true]);
    }

    private function authorizeAttempt(QuizAttempt $attempt): void
    {
        if (! $attempt->quiz?->course || $attempt->quiz->course->instructor_id !== auth()->id()) {
            abort(403, 'غير مصرح بهذه المحاولة.');
        }
    }
}
