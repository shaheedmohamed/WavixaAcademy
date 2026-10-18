<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Event;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Models\VideoAsset;
use App\Models\VideoSession;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        return response()->json([
            'students' => User::role('student')->count(),
            'active_enrollments' => Enrollment::where('status', 'active')->count(),
            'courses' => Course::count(),
            'published_courses' => Course::where('status', 'published')->count(),
            'upcoming_events' => Event::where('status', 'published')->where('start_at', '>', now())->count(),
            'completed_enrollments' => Enrollment::where('status', 'completed')->count(),
            'certificates' => Certificate::count(),
            'open_security_events' => SecurityEvent::where('status', 'open')->count(),
            'active_video_sessions' => VideoSession::where('status', 'active')->where('expires_at', '>', now())->count(),
            'recent_students' => User::role('student')->latest()->limit(8)
                ->get(['id', 'name', 'email', 'student_number', 'created_at']),
            'recent_imports' => VideoAsset::with('lesson:id,title')->latest()->limit(8)->get(),
            'recent_security' => SecurityEvent::with('user:id,name,student_number')->latest()->limit(8)->get(),
            'recent_enrollments' => Enrollment::with(['user:id,name,student_number', 'course:id,title'])
                ->latest()->limit(8)->get(),
        ]);
    }
}
