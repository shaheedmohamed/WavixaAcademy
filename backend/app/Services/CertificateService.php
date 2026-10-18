<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;

class CertificateService
{
    public function __construct(private EnrollmentService $enrollments) {}

    /**
     * Eligibility: all published lessons completed + all projects approved
     * + all quizzes passed (if the course defines any).
     */
    public function eligibility(User $user, Course $course): array
    {
        if ($course->certificate_enabled === false) {
            return [
                'eligible' => false,
                'disabled' => true,
                'lessons_percent' => 0,
                'pending_projects' => 0,
                'unpassed_quizzes' => 0,
            ];
        }

        $progress = $this->enrollments->progressFor($user, $course);

        $pendingProjects = $course->projects()->where('status', 'published')
            ->whereDoesntHave('submissions', fn ($q) => $q->where('user_id', $user->id)->where('status', 'approved'))
            ->count();

        $failedQuizzes = $course->quizzes()->where('status', 'published')
            ->whereDoesntHave('attempts', fn ($q) => $q->where('user_id', $user->id)->where('passed', true))
            ->count();

        return [
            'eligible' => $progress['percent'] >= 100 && $pendingProjects === 0 && $failedQuizzes === 0,
            'lessons_percent' => $progress['percent'],
            'pending_projects' => $pendingProjects,
            'unpassed_quizzes' => $failedQuizzes,
        ];
    }

    public function issue(User $user, Course $course, ?User $issuer = null): Certificate
    {
        $existing = Certificate::where('user_id', $user->id)->where('course_id', $course->id)->first();
        if ($existing) {
            return $existing;
        }

        $certificate = Certificate::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'issued_at' => now(),
            'issued_by' => $issuer?->id,
        ]);

        $user->notify(new \App\Notifications\PlatformNotification(
            'certificate_issued',
            'صدرت شهادتك المعتمدة 🏆',
            'تهانينا! صدرت شهادتك المعتمدة من Wavixa لكورس "'.$course->title.'".',
            '/dashboard/certificates'
        ));

        \App\Services\WhatsAppNotifications::certificateIssued($user, $certificate);

        return $certificate;
    }

    public function issueIfEligible(User $user, Course $course): ?Certificate
    {
        return $this->eligibility($user, $course)['eligible']
            ? $this->issue($user, $course)
            : null;
    }
}
