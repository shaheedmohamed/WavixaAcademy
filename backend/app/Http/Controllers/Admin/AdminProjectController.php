<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Project;
use App\Models\ProjectSubmission;
use App\Notifications\PlatformNotification;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminProjectController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function projects(Request $request): JsonResponse
    {
        $query = Project::with('course:id,title')->withCount('submissions')->latest();
        if ($courseId = $request->integer('course_id')) {
            $query->where('course_id', $courseId);
        }

        return response()->json($query->paginate(20));
    }

    public function storeProject(Request $request, Course $course): JsonResponse
    {
        $data = $this->validateProject($request);
        $this->assertBelongsToCourse($request, $course);
        $data = $this->handleAttachment($request, $data);
        $project = $course->projects()->create($data);

        return response()->json(['project' => $project], 201);
    }

    public function updateProject(Request $request, Project $project): JsonResponse
    {
        $data = $this->validateProject($request, true);
        $this->assertBelongsToCourse($request, $project->course);
        $data = $this->handleAttachment($request, $data, $project);
        $project->update($data);

        return response()->json(['project' => $project]);
    }

    public function destroyProject(Project $project): JsonResponse
    {
        if ($project->attachment_url && str_starts_with($project->attachment_url, '/storage/')) {
            Storage::disk('public')->delete(Str::after($project->attachment_url, '/storage/'));
        }
        $this->audit->log('project.deleted', $project);
        $project->delete();

        return response()->json(['ok' => true]);
    }

    public function submissions(Request $request): JsonResponse
    {
        $query = ProjectSubmission::with(['project:id,title,course_id,module_id', 'project.module:id,title', 'user:id,name,email,student_number'])
            ->latest('submitted_at');

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return response()->json($query->paginate(20));
    }

    public function review(Request $request, ProjectSubmission $submission): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['under_review', 'approved', 'needs_changes', 'rejected'])],
            'feedback' => ['nullable', 'string', 'max:5000'],
        ]);

        $submission->update([
            'status' => $data['status'],
            'feedback' => $data['feedback'] ?? null,
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
        ]);

        $submission->user->notify(new PlatformNotification(
            'project_reviewed',
            'تمت مراجعة مشروعك',
            'مشروع "'.$submission->project->title.'": '.$this->statusLabel($data['status']),
            '/dashboard/courses/'.$submission->project->course->slug
        ));

        $this->audit->log('project.reviewed', $submission, ['status' => $data['status']]);

        return response()->json(['submission' => $submission]);
    }

    public function download(ProjectSubmission $submission): StreamedResponse
    {
        abort_if(! $submission->file_path || ! Storage::disk('local')->exists($submission->file_path), 404);

        return Storage::disk('local')->download(
            $submission->file_path,
            $submission->file_name ?: 'submission',
        );
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'approved' => 'مقبول',
            'needs_changes' => 'يحتاج تعديلات',
            'rejected' => 'مرفوض',
            default => 'قيد المراجعة',
        };
    }

    private function validateProject(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'module_id' => ['nullable', 'exists:course_modules,id'],
            'after_lesson_id' => ['nullable', 'exists:lessons,id'],
            'title' => [$required, 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:3000'],
            'instructions' => ['nullable', 'string'],
            'attachment_type' => ['nullable', Rule::in(['image', 'video', 'link'])],
            'attachment_url' => ['nullable', 'string', 'max:1000', 'required_if:attachment_type,link', 'url'],
            'attachment' => ['nullable', 'file', 'max:512000',
                'mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime'],
            'deadline' => ['nullable', 'date'],
            'requirements' => ['nullable', 'array'],
            'status' => ['nullable', Rule::in(['draft', 'published'])],
        ]);
    }

    private function assertBelongsToCourse(Request $request, Course $course): void
    {
        if ($request->filled('module_id')) {
            abort_if(! $course->modules()->whereKey($request->integer('module_id'))->exists(),
                422, 'الوحدة المحددة لا تتبع هذا الكورس.');
        }
        if ($request->filled('after_lesson_id')) {
            abort_if(! $course->modules()->whereHas('lessons',
                fn ($q) => $q->whereKey($request->integer('after_lesson_id')))->exists(),
                422, 'الدرس المحدد لا يتبع هذا الكورس.');
        }
    }

    private function handleAttachment(Request $request, array $data, ?Project $project = null): array
    {
        // Partial updates that don't touch the attachment keep the existing one.
        if (! $request->has('attachment_type') && ! $request->hasFile('attachment')) {
            unset($data['attachment_type'], $data['attachment_url']);

            return $data;
        }

        $type = $request->string('attachment_type')->toString() ?: null;

        if (! $type && $request->hasFile('attachment')) {
            $type = str_starts_with($request->file('attachment')->getMimeType(), 'video/') ? 'video' : 'image';
            $data['attachment_type'] = $type;
        }

        if ($type === 'link') {
            $data['attachment_url'] = $request->string('attachment_url')->toString() ?: null;
        } elseif ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            abort_if($type === 'image' && ! str_starts_with($file->getMimeType(), 'image/'), 422, 'المرفق يجب أن يكون صورة.');
            abort_if($type === 'video' && ! str_starts_with($file->getMimeType(), 'video/'), 422, 'المرفق يجب أن يكون فيديو.');

            if ($project?->attachment_url && str_starts_with($project->attachment_url, '/storage/')) {
                Storage::disk('public')->delete(Str::after($project->attachment_url, '/storage/'));
            }
            $path = $file->store('project-attachments', 'public');
            $data['attachment_url'] = Storage::disk('public')->url($path);
        } elseif ($type === 'image' || $type === 'video') {
            // Keep the existing uploaded file; ignore any raw URL sent for media types.
            unset($data['attachment_url']);
        }

        if (! $type) {
            $data['attachment_type'] = null;
            $data['attachment_url'] = null;
        }

        return $data;
    }
}
