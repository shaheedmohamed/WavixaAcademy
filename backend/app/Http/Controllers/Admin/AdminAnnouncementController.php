<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Enrollment;
use App\Models\User;
use App\Notifications\PlatformNotification;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class AdminAnnouncementController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function index(): JsonResponse
    {
        return response()->json(Announcement::with('course:id,title')->latest()->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $announcement = Announcement::create($this->validateAnnouncement($request) + [
            'created_by' => $request->user()->id,
        ]);

        if ($announcement->status === 'published') {
            $announcement->update(['published_at' => now()]);
            $this->dispatchNotifications($announcement);
        }

        $this->audit->log('announcement.created', $announcement);

        return response()->json(['announcement' => $announcement], 201);
    }

    public function show(Announcement $announcement): JsonResponse
    {
        return response()->json(['announcement' => $announcement]);
    }

    public function update(Request $request, Announcement $announcement): JsonResponse
    {
        $wasDraft = $announcement->status !== 'published';
        $announcement->update($this->validateAnnouncement($request));

        if ($wasDraft && $announcement->status === 'published') {
            $announcement->update(['published_at' => now()]);
            $this->dispatchNotifications($announcement);
        }

        $this->audit->log('announcement.updated', $announcement);

        return response()->json(['announcement' => $announcement]);
    }

    public function destroy(Announcement $announcement): JsonResponse
    {
        $this->audit->log('announcement.deleted', $announcement);
        $announcement->delete();

        return response()->json(['ok' => true]);
    }

    private function dispatchNotifications(Announcement $announcement): void
    {
        $query = match ($announcement->audience) {
            'students' => User::role('student')->where('status', 'active'),
            'course_students' => User::whereIn('id',
                Enrollment::where('course_id', $announcement->course_id)->where('status', 'active')->select('user_id')),
            default => User::where('status', 'active'),
        };

        Notification::send($query->cursor(), new PlatformNotification(
            'announcement',
            $announcement->title,
            $announcement->body,
            '/dashboard'
        ));
    }

    private function validateAnnouncement(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'body' => ['required', 'string', 'max:10000'],
            'audience' => ['required', Rule::in(['all', 'students', 'course_students'])],
            'course_id' => ['nullable', 'exists:courses,id', 'required_if:audience,course_students'],
            'status' => ['required', Rule::in(['draft', 'published'])],
        ]);
    }
}
