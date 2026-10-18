<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Notifications\PlatformNotification;
use App\Services\EnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class InstructorStudentController extends Controller
{
    public function __construct(private EnrollmentService $enrollments) {}

    public function index(Request $request): JsonResponse
    {
        $courseIds = Course::where('instructor_id', auth()->id())->pluck('id');

        $query = Enrollment::with(['user:id,name,email,phone,student_number', 'course:id,title'])
            ->whereIn('course_id', $courseIds)
            ->latest();

        if ($courseId = $request->integer('course_id')) {
            $query->where('course_id', $courseId);
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($search = $request->string('q')->trim()->toString()) {
            $query->whereHas('user', fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('student_number', 'like', "%{$search}%"));
        }

        return response()->json($query->paginate(20));
    }

    public function search(Request $request): JsonResponse
    {
        $search = $request->string('q')->trim()->toString();
        abort_if(strlen($search) < 2, 422, 'نص البحث قصير جدًا.');

        $users = User::where(fn ($q) => $q
            ->where('email', 'like', "%{$search}%")
            ->orWhere('phone', 'like', "%{$search}%")
            ->orWhere('student_number', 'like', "%{$search}%")
            ->orWhere('name', 'like', "%{$search}%"))
            ->limit(10)
            ->get(['id', 'name', 'email', 'phone', 'student_number']);

        return response()->json(['users' => $users]);
    }

    public function activateEnrollment(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'expires_at' => ['nullable', 'date', 'after:today'],
            'cohort_id' => ['nullable', 'exists:course_cohorts,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $course = Course::where('id', $data['course_id'])
            ->where('instructor_id', auth()->id())
            ->firstOrFail();

        if ($data['cohort_id'] ?? null) {
            abort_if($course->cohorts()->whereKey($data['cohort_id'])->doesntExist(), 422, 'الدفعة غير تابعة للكورس.');
        }

        $enrollment = $this->enrollments->activate(
            $user,
            $course,
            $request->user(),
            isset($data['expires_at']) ? Carbon::parse($data['expires_at']) : null,
            $data['cohort_id'] ?? null,
            $data['notes'] ?? null
        );

        $user->notify(new PlatformNotification(
            'course_activated',
            'تم تفعيل الكورس',
            'تم تفعيل كورس "'.$course->title.'" على حسابك.',
            '/dashboard/courses/'.$course->slug
        ));

        return response()->json(['enrollment' => $enrollment->load('course:id,title')], 201);
    }

    public function updateEnrollment(Request $request, Enrollment $enrollment): JsonResponse
    {
        $this->authorizeEnrollment($enrollment);

        $data = $request->validate([
            'status' => ['required', Rule::in([
                Enrollment::STATUS_PENDING, Enrollment::STATUS_ACTIVE,
                Enrollment::STATUS_SUSPENDED, Enrollment::STATUS_EXPIRED,
                Enrollment::STATUS_CANCELLED, Enrollment::STATUS_COMPLETED,
            ])],
            'expires_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $enrollment->update([
            'status' => $data['status'],
            'expires_at' => $data['expires_at'] ?? $enrollment->expires_at,
            'notes' => $data['notes'] ?? $enrollment->notes,
        ]);

        return response()->json(['enrollment' => $enrollment->load('user:id,name,email,phone', 'course:id,title')]);
    }

    private function authorizeEnrollment(Enrollment $enrollment): void
    {
        $course = $enrollment->course;
        if (! $course || $course->instructor_id !== auth()->id()) {
            abort(403, 'غير مصرح لك بهذا الاشتراك.');
        }
    }
}
