<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\ProjectSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class InstructorDashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $instructorId = auth()->id();
        $courseIds = Course::where('instructor_id', $instructorId)->pluck('id');

        return response()->json([
            'courses' => $courseIds->count(),
            'published_courses' => Course::where('instructor_id', $instructorId)->where('status', 'published')->count(),
            'active_enrollments' => Enrollment::whereIn('course_id', $courseIds)->where('status', 'active')->count(),
            'completed_enrollments' => Enrollment::whereIn('course_id', $courseIds)->where('status', 'completed')->count(),
            'pending_submissions' => ProjectSubmission::whereHas('project', fn ($q) => $q->whereIn('course_id', $courseIds))->where('status', 'pending')->count(),
            'recent_submissions' => ProjectSubmission::with('user:id,name', 'project:id,title')
                ->whereHas('project', fn ($q) => $q->whereIn('course_id', $courseIds))
                ->latest()
                ->limit(10)
                ->get(),
            'recent_enrollments' => Enrollment::with('user:id,name,email', 'course:id,title')
                ->whereIn('course_id', $courseIds)
                ->latest()
                ->limit(10)
                ->get(),
        ]);
    }
}
