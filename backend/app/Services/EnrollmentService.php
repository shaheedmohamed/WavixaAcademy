<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Carbon;

class EnrollmentService
{
    public function __construct(private AuditLogger $audit) {}

    public function enrollmentFor(User $user, Course|int $course): ?Enrollment
    {
        $courseId = $course instanceof Course ? $course->id : $course;

        return Enrollment::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->first();
    }

    public function canAccess(User $user, Course|int $course): bool
    {
        $enrollment = $this->enrollmentFor($user, $course);

        return $enrollment?->isAccessible() ?? false;
    }

    public function activate(
        User $user,
        Course $course,
        User $admin,
        ?Carbon $expiresAt = null,
        ?int $cohortId = null,
        ?string $notes = null
    ): Enrollment {
        $enrollment = Enrollment::updateOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            [
                'cohort_id' => $cohortId,
                'status' => Enrollment::STATUS_ACTIVE,
                'activated_at' => now(),
                'expires_at' => $expiresAt,
                'activated_by' => $admin->id,
                'notes' => $notes,
            ]
        );

        $this->audit->log('enrollment.activated', $enrollment, [
            'course' => $course->title,
            'student' => $user->email,
        ]);
        WhatsAppNotifications::enrollmentActivated($user, $course);

        return $enrollment;
    }

    public function updateStatus(Enrollment $enrollment, string $status, ?string $notes = null): Enrollment
    {
        $enrollment->update([
            'status' => $status,
            'notes' => $notes ?? $enrollment->notes,
        ]);

        $this->audit->log('enrollment.'.$status, $enrollment);

        return $enrollment;
    }

    public function expireDueEnrollments(): int
    {
        return Enrollment::where('status', Enrollment::STATUS_ACTIVE)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['status' => Enrollment::STATUS_EXPIRED]);
    }

    public function progressFor(User $user, Course $course): array
    {
        $lessonIds = $course->lessons()->where('is_published', true)->pluck('lessons.id');
        $total = $lessonIds->count();
        $completed = $user->lessonProgress()
            ->whereIn('lesson_id', $lessonIds)
            ->where('completed', true)
            ->count();

        return [
            'total_lessons' => $total,
            'completed_lessons' => $completed,
            'percent' => $total > 0 ? round($completed / $total * 100) : 0,
        ];
    }
}
