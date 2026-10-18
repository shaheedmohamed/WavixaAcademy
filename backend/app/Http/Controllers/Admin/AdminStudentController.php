<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\UserDevice;
use App\Notifications\PlatformNotification;
use App\Services\CourseShield;
use App\Services\EnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminStudentController extends Controller
{
    public function __construct(
        private EnrollmentService $enrollments,
        private CourseShield $shield,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = User::role('student')
            ->withCount(['enrollments', 'certificates'])
            ->latest();

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('student_number', 'like', "%{$search}%"));
        }
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return response()->json($query->paginate(20));
    }

    public function show(User $user): JsonResponse
    {
        $user->load([
            'enrollments.course:id,title,slug',
            'enrollments.cohort:id,name',
            'certificates.course:id,title',
            'devices' => fn ($q) => $q->latest('last_seen_at')->limit(20),
            'projectSubmissions.project:id,title',
            'quizAttempts.quiz:id,title',
            'eventRegistrations.event:id,title,start_at',
        ]);

        $progress = [];
        foreach ($user->enrollments as $enrollment) {
            if ($enrollment->course) {
                $progress[$enrollment->course_id] = $this->enrollments->progressFor($user, $enrollment->course);
            }
        }

        return response()->json([
            'student' => $user->only('id', 'name', 'email', 'phone', 'student_number', 'status', 'created_at', 'last_login_at'),
            'enrollments' => $user->enrollments,
            'progress' => $progress,
            'certificates' => $user->certificates,
            'devices' => $user->devices,
            'submissions' => $user->projectSubmissions,
            'quiz_attempts' => $user->quizAttempts,
            'event_registrations' => $user->eventRegistrations,
            'active_sessions' => $user->videoSessions()->where('status', 'active')->where('expires_at', '>', now())->get(),
            'security_events' => $user->hasMany(\App\Models\SecurityEvent::class)->latest()->limit(20)->get(),
        ]);
    }

    public function activateEnrollment(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'expires_at' => ['nullable', 'date', 'after:today'],
            'cohort_id' => ['nullable', 'exists:course_cohorts,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $course = Course::findOrFail($data['course_id']);
        $enrollment = $this->enrollments->activate(
            $user, $course, $request->user(),
            isset($data['expires_at']) ? \Illuminate\Support\Carbon::parse($data['expires_at']) : null,
            $data['cohort_id'] ?? null,
            $data['notes'] ?? null
        );

        $user->notify(new PlatformNotification(
            'course_activated',
            'تم تفعيل الكورس',
            'تم تفعيل كورس "'.$course->title.'" على حسابك.',
            '/dashboard/courses/'.$course->slug
        ));

        return response()->json(['enrollment' => $enrollment], 201);
    }

    public function updateStatus(Request $request, User $user): JsonResponse
    {
        $data = $request->validate(['status' => ['required', 'in:active,suspended']]);
        $user->update(['status' => $data['status']]);

        if ($data['status'] === 'suspended') {
            $this->shield->terminateUserSessions($user, 'account_suspended');
        }

        return response()->json(['ok' => true]);
    }

    public function terminateSessions(User $user): JsonResponse
    {
        $count = $this->shield->terminateUserSessions($user, 'admin_terminated');

        return response()->json(['terminated' => $count]);
    }

    public function removeDevice(User $user, UserDevice $device): JsonResponse
    {
        abort_if($device->user_id !== $user->id, 404);
        $device->update(['status' => 'revoked']);

        return response()->json(['ok' => true]);
    }

    public function destroy(User $user): JsonResponse
    {
        abort_if(! $user->hasRole('student'), 403, 'لا يمكن حذف مستخدم غير طالب من هذه الصفحة.');

        try {
            $user->delete();
        } catch (\Exception $e) {
            abort(422, 'لا يمكن حذف الطالب لوجود بيانات مرتبطة به.');
        }

        return response()->json(['ok' => true]);
    }
}
