<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SecurityEvent;
use App\Models\UserDevice;
use App\Models\VideoSession;
use App\Services\CourseShield;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSecurityController extends Controller
{
    public function __construct(private CourseShield $shield) {}

    public function sessions(Request $request): JsonResponse
    {
        $query = VideoSession::with(['user:id,name,student_number', 'lesson:id,title', 'course:id,title', 'device'])
            ->latest('started_at');

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        if ($request->boolean('active_only')) {
            $query->where('status', 'active')->where('expires_at', '>', now());
        }
        if ($request->boolean('high_risk')) {
            $query->where('risk_score', '>=', 50);
        }

        return response()->json($query->paginate(25));
    }

    public function devices(Request $request): JsonResponse
    {
        $query = UserDevice::with('user:id,name,student_number')->latest('last_seen_at');
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return response()->json($query->paginate(25));
    }

    public function events(Request $request): JsonResponse
    {
        $query = SecurityEvent::with('user:id,name,student_number')->latest();
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        if ($severity = $request->string('severity')->toString()) {
            $query->where('severity', $severity);
        }

        return response()->json($query->paginate(25));
    }

    public function resolve(Request $request, SecurityEvent $event): JsonResponse
    {
        $event->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => $request->user()->id,
        ]);

        return response()->json(['event' => $event]);
    }

    public function terminateSession(VideoSession $session): JsonResponse
    {
        $this->shield->terminate($session, 'admin_terminated');

        return response()->json(['ok' => true]);
    }

    public function removeDevice(UserDevice $device): JsonResponse
    {
        $device->update(['status' => 'revoked']);
        VideoSession::where('user_device_id', $device->id)
            ->where('status', 'active')->update(['status' => 'terminated']);

        return response()->json(['ok' => true]);
    }
}
