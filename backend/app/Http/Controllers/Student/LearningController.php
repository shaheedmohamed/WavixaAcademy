<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Event;
use App\Models\Lesson;
use App\Services\EnrollmentService;
use App\Services\ProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LearningController extends Controller
{
    public function __construct(
        private EnrollmentService $enrollments,
        private ProgressService $progress,
        private \App\Services\CertificateService $certificates,
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        $enrollments = $user->enrollments()
            ->whereIn('status', [Enrollment::STATUS_ACTIVE, Enrollment::STATUS_COMPLETED])
            ->with(['course.instructor:id,name', 'course.modules.lessons' => fn ($q) => $q->where('is_published', true)->select('id', 'module_id')])
            ->latest('activated_at')
            ->get()
            ->map(function (Enrollment $enrollment) use ($user) {
                $course = $enrollment->course;
                $lessonIds = $course->modules->flatMap->lessons->pluck('id');
                $completed = $user->lessonProgress()
                    ->whereIn('lesson_id', $lessonIds)->where('completed', true)->count();
                $lastProgress = $user->lessonProgress()
                    ->whereIn('lesson_id', $lessonIds)
                    ->with('lesson:id,module_id,title,slug')
                    ->latest('updated_at')->first();

                return [
                    'enrollment' => $enrollment->only('id', 'status', 'activated_at', 'expires_at'),
                    'course' => $course->only('id', 'title', 'slug', 'thumbnail', 'level'),
                    'total_lessons' => $lessonIds->count(),
                    'completed_lessons' => $completed,
                    'percent' => $lessonIds->count() ? round($completed / $lessonIds->count() * 100) : 0,
                    'last_lesson' => $lastProgress?->lesson,
                ];
            });

        $courseIds = $enrollments->pluck('course.id');
        $events = Event::published()
            ->where('start_at', '>', now())
            ->where(fn ($q) => $q->where('visibility', 'public')
                ->orWhere('visibility', 'students_only')
                ->orWhereIn('course_id', $courseIds))
            ->orderBy('start_at')->limit(5)->get();

        return response()->json([
            'enrollments' => $enrollments,
            'upcoming_events' => $events,
            'notifications' => $user->notifications()->latest()->limit(8)->get(),
            'unread_notifications' => $user->unreadNotifications()->count(),
            'certificates' => $user->certificates()->with('course:id,title,slug')->latest()->limit(5)->get(),
        ]);
    }

    public function courses(Request $request): JsonResponse
    {
        $enrollments = $request->user()->enrollments()
            ->with('course.instructor:id,name')
            ->latest()
            ->get()
            ->map(fn (Enrollment $e) => [
                'enrollment' => $e->only('id', 'status', 'activated_at', 'expires_at'),
                'course' => $e->course,
                'progress' => $e->course ? $this->enrollments->progressFor($request->user(), $e->course) : null,
            ]);

        return response()->json(['enrollments' => $enrollments]);
    }

    public function course(Request $request, Course $course): JsonResponse
    {
        $user = $request->user();
        $enrollment = $this->enrollments->enrollmentFor($user, $course);
        abort_if(! $enrollment?->isAccessible(), 403, 'هذا الكورس غير مفعل على حسابك.');

        $course->load([
            'instructor:id,name,avatar',
            'modules' => fn ($q) => $q->where('status', 'published'),
            'modules.lessons' => fn ($q) => $q->where('is_published', true)
                ->with(['videoAsset:id,lesson_id,status,playback_id,duration_seconds'])
                ->orderBy('sort_order'),
            'projects' => fn ($q) => $q->where('status', 'published')
                ->with(['submissions' => fn ($s) => $s->where('user_id', $user->id)->latest()]),
            'quizzes' => fn ($q) => $q->where('status', 'published'),
        ]);

        $progressRows = $user->lessonProgress()
            ->whereIn('lesson_id', $course->modules->flatMap->lessons->pluck('id'))
            ->get()->keyBy('lesson_id');

        foreach ($course->modules as $module) {
            foreach ($module->lessons as $lesson) {
                $p = $progressRows->get($lesson->id);
                $lesson->user_progress = [
                    'completed' => (bool) $p?->completed,
                    'last_position' => $p?->last_position ?? 0,
                    'percent' => $p && $p->duration_seconds > 0
                        ? min(100, round($p->watched_seconds / $p->duration_seconds * 100)) : 0,
                ];
                $lesson->has_video = (bool) $lesson->videoAsset?->isReady();
                unset($lesson->videoAsset);
            }
        }

        $certificate = null;
        if ($course->certificate_enabled !== false) {
            $certificate = \App\Models\Certificate::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first();
            if (! $certificate) {
                $certificate = $this->certificates->issueIfEligible($user, $course);
            }
        }

        return response()->json([
            'course' => $course,
            'enrollment' => $enrollment,
            'progress' => $this->enrollments->progressFor($user, $course),
            'certificate' => $certificate ? [
                'id' => $certificate->id,
                'number' => $certificate->certificate_number,
                'verification_token' => $certificate->verification_token,
                'issued_at' => $certificate->issued_at,
                'verify_url' => url('/certificates/'.$certificate->verification_token),
            ] : null,
            'certificate_eligible' => $course->certificate_enabled !== false && $this->certificates->eligibility($user, $course)['eligible'],
        ]);
    }

    public function lesson(Request $request, Course $course, Lesson $lesson): JsonResponse
    {
        $user = $request->user();
        abort_if($lesson->module->course_id !== $course->id, 404);

        $isPreview = (bool) $lesson->is_preview;
        if (! $isPreview) {
            $enrollment = $this->enrollments->enrollmentFor($user, $course);
            abort_if(! $enrollment?->isAccessible(), 403, 'هذا الكورس غير مفعل على حسابك.');
        }
        abort_if(! $lesson->is_published, 404);

        $lesson->load('resources');
        $progress = $lesson->progressFor($user->id);
        $quiz = $lesson->quiz()->where('status', 'published')->first();
        $quizAttempt = null;
        if ($quiz) {
            $quizAttempt = $quiz->attempts()->where('user_id', $user->id)->latest('completed_at')->first();
        }

        return response()->json([
            'lesson' => $lesson,
            'progress' => $progress,
            'quiz' => $quiz?->only('id', 'title', 'description', 'passing_score', 'max_attempts'),
            'quiz_attempt' => $quizAttempt ? [
                'id' => $quizAttempt->id,
                'score' => $quizAttempt->score,
                'passed' => (bool) $quizAttempt->passed,
                'completed_at' => $quizAttempt->completed_at,
            ] : null,
            'has_video' => (bool) $lesson->videoAsset?->isReady(),
        ]);
    }

    public function recordProgress(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lesson_id' => ['required', 'exists:lessons,id'],
            'position_seconds' => ['required', 'integer', 'min:0'],
            'duration_seconds' => ['required', 'integer', 'min:0'],
        ]);

        $lesson = Lesson::findOrFail($data['lesson_id']);
        $course = $lesson->module->course;

        if (! $lesson->is_preview) {
            abort_if(! $this->enrollments->canAccess($request->user(), $course), 403);
        }

        $progress = $this->progress->recordPosition(
            $request->user(), $lesson,
            $data['position_seconds'], $data['duration_seconds']
        );

        $certificate = null;
        if ($progress->completed && $course->certificate_enabled !== false) {
            $certificate = $this->certificates->issueIfEligible($request->user(), $course);
        }

        return response()->json([
            'progress' => $progress,
            'certificate_issued' => (bool) $certificate,
        ]);
    }
}
