<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Project;
use App\Models\ProjectSubmission;
use App\Services\AuditLogger;
use App\Services\EnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct(
        private EnrollmentService $enrollments,
        private AuditLogger $audit,
    ) {}

    public function index(Request $request, Course $course): JsonResponse
    {
        abort_if(! $this->enrollments->canAccess($request->user(), $course), 403);

        $projects = $course->projects()->where('status', 'published')
            ->with(['submissions' => fn ($q) => $q->where('user_id', $request->user()->id)->latest()])
            ->orderBy('sort_order')->get();

        return response()->json(['projects' => $projects]);
    }

    /**
     * Unlocked activities across the student's enrolled courses — shown on
     * the events page as "pending work".
     */
    public function pending(Request $request): JsonResponse
    {
        $user = $request->user();

        // If user is Admin or Instructor, allow access to all their courses / all courses
        if ($user->isAdmin()) {
            $courseIds = Course::pluck('id');
        } elseif ($user->hasRole('instructor')) {
            $courseIds = Course::where('instructor_id', $user->id)
                ->pluck('id')
                ->merge($user->enrollments()->whereIn('status', [Enrollment::STATUS_ACTIVE, Enrollment::STATUS_COMPLETED])->pluck('course_id'))
                ->unique();
        } else {
            $courseIds = $user->enrollments()
                ->whereIn('status', [Enrollment::STATUS_ACTIVE, Enrollment::STATUS_COMPLETED])
                ->pluck('course_id');
        }

        $projects = Project::where('status', 'published')
            ->whereIn('course_id', $courseIds)
            ->with([
                'course:id,title,slug',
                'module:id,title',
                'module.lessons' => fn ($q) => $q->where('is_published', true)->select('id', 'module_id'),
                'submissions' => fn ($q) => $q->where('user_id', $user->id)->latest(),
            ])
            ->orderBy('sort_order')->get();

        $lessonIds = $projects->flatMap(fn ($p) => $p->module?->lessons->pluck('id') ?? collect())
            ->merge($projects->pluck('after_lesson_id')->filter())
            ->unique();

        $done = $user->lessonProgress()
            ->whereIn('lesson_id', $lessonIds)->where('completed', true)
            ->pluck('lesson_id')->flip();

        $activities = $projects->map(function (Project $p) use ($done) {
            if ($p->after_lesson_id) {
                $unlocked = $done->has($p->after_lesson_id);
            } elseif ($p->module_id) {
                $ids = $p->module->lessons->pluck('id');
                $unlocked = $ids->isEmpty() || $ids->every(fn ($id) => $done->has($id));
            } else {
                $unlocked = true;
            }
            if (! $unlocked) {
                return null;
            }

            $sub = $p->submissions->first();

            return [
                'id' => $p->id,
                'title' => $p->title,
                'description' => $p->description,
                'attachment_type' => $p->attachment_type,
                'deadline' => $p->deadline,
                'course' => $p->course->only('id', 'title', 'slug'),
                'module' => $p->module?->only('id', 'title'),
                'submitted' => (bool) $sub,
                'submission_status' => $sub?->status,
                'feedback' => $sub?->feedback,
                'type' => 'project',
            ];
        })->filter()->values();

        // Also fetch published quizzes from available courses so student/instructor/admin sees them on events/activities
        $quizzes = \App\Models\Quiz::whereIn('course_id', $courseIds)
            ->where('status', 'published')
            ->with(['course:id,title,slug', 'module:id,title', 'attempts' => fn ($q) => $q->where('user_id', $user->id)->latest('completed_at')])
            ->get();

        $quizItems = $quizzes->map(function ($q) {
            $latest = $q->attempts->first();
            return [
                'id' => $q->id,
                'title' => $q->title,
                'description' => $q->description,
                'course' => $q->course ? $q->course->only('id', 'title', 'slug') : null,
                'module' => $q->module ? $q->module->only('id', 'title') : null,
                'submitted' => (bool) $latest,
                'submission_status' => $latest ? ($latest->status ?? ($latest->passed ? 'approved' : 'submitted')) : null,
                'feedback' => $latest?->feedback,
                'score' => $latest?->score,
                'passed' => (bool) $latest?->passed,
                'type' => 'quiz',
            ];
        });

        $activitiesList = $activities->values()->all();
        $quizList = $quizItems->values()->all();
        $merged = array_merge($activitiesList, $quizList);

        return response()->json(['activities' => $merged]);
    }

    public function submit(Request $request, Project $project): JsonResponse
    {
        abort_if(! $this->enrollments->canAccess($request->user(), $project->course_id), 403);

        $data = $request->validate([
            'github_url' => ['nullable', 'url', 'max:500'],
            'live_demo_url' => ['nullable', 'url', 'max:500'],
            'link_url' => ['nullable', 'url', 'max:500'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'file' => ['nullable', 'file', 'max:204800',
                'mimes:jpg,jpeg,png,webp,mp4,webm,mov,zip,pdf,txt,md'],
        ]);

        abort_if(
            ! $request->filled('github_url') && ! $request->filled('live_demo_url')
                && ! $request->filled('link_url') && ! $request->hasFile('file'),
            422, 'أرفق ملفًا أو رابطًا على الأقل.'
        );

        $existing = ProjectSubmission::where('project_id', $project->id)
            ->where('user_id', $request->user()->id)->first();

        $filePath = $existing?->file_path;
        $fileName = $existing?->file_name;
        $fileMime = $existing?->file_mime;
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filePath = $file->store('submissions', 'local');
            $fileName = $file->getClientOriginalName();
            $fileMime = $file->getMimeType();
        }

        $submission = ProjectSubmission::updateOrCreate(
            ['project_id' => $project->id, 'user_id' => $request->user()->id],
            [
                'github_url' => $data['github_url'] ?? null,
                'live_demo_url' => $data['live_demo_url'] ?? null,
                'link_url' => $data['link_url'] ?? null,
                'file_path' => $filePath,
                'file_name' => $fileName,
                'file_mime' => $fileMime,
                'notes' => $data['notes'] ?? null,
                'status' => 'submitted',
                'submitted_at' => now(),
                'reviewed_at' => null,
                'reviewed_by' => null,
            ]
        );

        $this->audit->log('project.submitted', $submission, ['project' => $project->title]);

        return response()->json(['submission' => $submission], 201);
    }
}
