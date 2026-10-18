<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\VideoSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * CourseShield — playback authorization, session & device control,
 * risk monitoring and watermark payload generation.
 *
 * Defense-in-depth model: enrollment check → device check → concurrent
 * session policy → short-lived signed playback token → heartbeat
 * validation → dynamic watermark → audit trail.
 */
class CourseShield
{
    public function __construct(
        private EnrollmentService $enrollments,
        private AuditLogger $audit,
    ) {}

    public function providerFor(\App\Models\VideoAsset $asset): VideoProviderInterface
    {
        return $asset->provider === 'local'
            ? new LocalVideoProvider
            : app(VideoProviderInterface::class);
    }

    /**
     * Authorize a playback session for a lesson. Returns everything the
     * player needs, or throws HttpException with the reason.
     */
    public function authorizePlayback(User $user, Lesson $lesson, ?string $deviceId, Request $request): array
    {
        $lesson->loadMissing('module.course', 'videoAsset');
        $course = $lesson->module->course;

        if (! $user->isActive()) {
            throw new HttpException(403, 'حسابك موقوف. تواصل مع الدعم.');
        }

        $enrollment = $this->enrollments->enrollmentFor($user, $course);
        $isPreview = (bool) $lesson->is_preview;
        $isStaff = $user->isAdmin() || $course->instructor_id === $user->id;

        if (! $isPreview && ! $isStaff && ! $enrollment?->isAccessible()) {
            throw new HttpException(403, 'هذا الكورس غير مفعل على حسابك.');
        }

        $asset = $lesson->videoAsset;
        if (! $asset || ! $asset->isReady()) {
            throw new HttpException(409, 'الفيديو غير جاهز بعد.');
        }

        $device = null;
        if (! $isStaff) {
            $device = $this->registerDevice($user, $deviceId, $request);
            $this->enforceConcurrentPolicy($user, $device);
        }

        $ttl = (int) config('academy.video_session_ttl', 15) * 60;
        $plainToken = Str::random(48);

        $session = VideoSession::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'lesson_id' => $lesson->id,
            'user_device_id' => $device?->id,
            'token_hash' => hash('sha256', $plainToken),
            'ip_hash' => hash('sha256', (string) $request->ip()),
            'user_agent_hash' => hash('sha256', (string) $request->userAgent()),
            'started_at' => now(),
            'last_seen_at' => now(),
            'expires_at' => now()->addSeconds($ttl),
            'status' => 'active',
            'risk_score' => $this->assessRisk($user, $request),
        ]);

        $provider = $this->providerFor($asset);
        $playbackToken = $provider->playbackToken($asset, $user->id, $session->id, $ttl);

        $this->audit->log('video.playback_authorized', $lesson, [
            'session_id' => $session->id,
            'preview' => $isPreview,
        ]);

        return [
            'session_id' => $session->id,
            'session_token' => $plainToken,
            'expires_at' => $session->expires_at->toIso8601String(),
            'heartbeat_interval' => (int) config('academy.heartbeat_interval', 20),
            'manifest_url' => $provider->manifestUrl($asset->playback_id, $playbackToken),
            'provider' => $asset->provider,
            'drm' => (bool) ($asset->meta['drm'] ?? false),
            'watermark' => $this->watermarkPayload($user, $session),
            'resume_position' => $lesson->progressFor($user->id)?->last_position ?? 0,
        ];
    }

    public function heartbeat(string $token, int $sessionId, int $position, Request $request): VideoSession
    {
        $session = VideoSession::where('id', $sessionId)
            ->where('token_hash', hash('sha256', $token))
            ->first();

        if (! $session || ! $session->isValid()) {
            throw new HttpException(440, 'انتهت جلسة التشغيل.');
        }

        if ($session->user->status !== 'active') {
            $this->terminate($session, 'account_suspended');
            throw new HttpException(403, 'حسابك موقوف.');
        }

        $session->loadMissing('course', 'lesson');
        $isStaff = $session->user->isAdmin() || $session->course->instructor_id === $session->user_id;

        if (! $isStaff
            && ! $session->lesson->is_preview
            && ! $this->enrollments->canAccess($session->user, $session->course_id)) {
            $this->terminate($session, 'enrollment_revoked');
            throw new HttpException(403, 'لم يعد لديك صلاحية الوصول.');
        }

        $session->update([
            'last_seen_at' => now(),
            'expires_at' => now()->addSeconds((int) config('academy.video_session_ttl', 15) * 60),
            'position_seconds' => max($session->position_seconds, $position),
        ]);

        return $session;
    }

    public function endSession(string $token, int $sessionId): void
    {
        $session = VideoSession::where('id', $sessionId)
            ->where('token_hash', hash('sha256', $token))
            ->first();

        if ($session && $session->status === 'active') {
            $this->terminate($session, 'ended_by_client');
        }
    }

    public function terminate(VideoSession $session, string $reason): void
    {
        $session->update(['status' => 'terminated']);
        $this->audit->log('video.session_terminated', $session, ['reason' => $reason]);
    }

    public function terminateUserSessions(User $user, string $reason = 'admin'): int
    {
        $count = 0;
        foreach ($user->videoSessions()->where('status', 'active')->get() as $session) {
            $this->terminate($session, $reason);
            $count++;
        }

        return $count;
    }

    public function registerDevice(User $user, ?string $deviceId, Request $request): ?UserDevice
    {
        if (! $deviceId || strlen($deviceId) > 64) {
            return null;
        }

        $device = UserDevice::firstOrNew([
            'user_id' => $user->id,
            'device_id' => $deviceId,
        ]);

        if (! $device->exists) {
            $limit = (int) config('academy.max_devices', 2);
            $activeCount = $user->devices()->where('status', 'active')->count();

            if ($activeCount >= $limit) {
                $this->raiseSecurityEvent($user, 'device_limit_exceeded', 'medium',
                    'محاولة تشغيل من جهاز جديد بعد بلوغ حد الأجهزة.',
                    ['device_id' => $deviceId, 'limit' => $limit], $request);

                throw new HttpException(429, "وصلت لحد الأجهزة المسموح ({$limit}). تواصل مع الدعم لإعادة تعيين أجهزتك.");
            }

            $device->fill([
                'name' => $this->deviceName($request),
                'browser' => $this->browserName($request),
                'os' => $this->osName($request),
                'first_seen_at' => now(),
                'status' => 'active',
            ]);
        }

        $device->ip_hash = hash('sha256', (string) $request->ip());
        $device->last_seen_at = now();
        $device->save();

        return $device;
    }

    public function watermarkPayload(User $user, VideoSession $session): array
    {
        return [
            'enabled' => (bool) config('academy.watermark_enabled', true),
            'lines' => [
                $user->name,
                'طالب #'.ltrim((string) ($user->student_number ?? $user->id), 'ST-0'),
                'جلسة '.$session->id,
            ],
            'interval_seconds' => (int) config('academy.watermark_interval', 45),
        ];
    }

    private function enforceConcurrentPolicy(User $user, ?UserDevice $device): void
    {
        $max = (int) config('academy.max_concurrent_sessions', 1);
        $active = $user->videoSessions()
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->orderBy('last_seen_at')
            ->get();

        if ($active->count() < $max) {
            return;
        }

        $sameDevice = $device && $active->every(fn ($s) => $s->user_device_id === $device->id);
        $policy = config('academy.concurrent_policy', 'terminate_oldest');

        if ($sameDevice) {
            // Same device re-requesting (refresh/new lesson) — drop old sessions quietly.
            foreach ($active as $s) {
                $this->terminate($s, 'superseded_same_device');
            }

            return;
        }

        $this->raiseSecurityEvent($user, 'concurrent_sessions', 'high',
            'جلسات تشغيل متزامنة من أجهزة مختلفة.',
            ['active_sessions' => $active->count(), 'policy' => $policy]);

        match ($policy) {
            'block_new' => throw new HttpException(409, 'يوجد تشغيل نشط على جهاز آخر. أوقفه أولًا.'),
            'alert' => null,
            default => $active->each(fn ($s) => $this->terminate($s, 'concurrent_terminated')),
        };
    }

    private function assessRisk(User $user, Request $request): int
    {
        $score = 0;
        $recentIps = $user->videoSessions()
            ->where('created_at', '>', now()->subHours(24))
            ->distinct()->pluck('ip_hash');

        $currentIpHash = hash('sha256', (string) $request->ip());
        if ($recentIps->isNotEmpty() && ! $recentIps->contains($currentIpHash)) {
            $score += 30;
        }

        $activeSessions = $user->videoSessions()->where('status', 'active')
            ->where('expires_at', '>', now())->count();
        $score += min($activeSessions * 20, 40);

        return min($score, 100);
    }

    public function raiseSecurityEvent(
        User $user, string $type, string $severity, string $description,
        array $context = [], ?Request $request = null
    ): SecurityEvent {
        $request ??= request();

        return SecurityEvent::create([
            'user_id' => $user->id,
            'type' => $type,
            'severity' => $severity,
            'title' => $type,
            'description' => $description,
            'context' => $context,
            'ip_hash' => hash('sha256', (string) $request?->ip()),
        ]);
    }

    private function deviceName(Request $request): string
    {
        return trim(($this->browserName($request) ?: 'متصفح').' / '.($this->osName($request) ?: 'نظام'));
    }

    private function browserName(Request $request): ?string
    {
        $ua = (string) $request->userAgent();
        return match (true) {
            stripos($ua, 'Edg') !== false => 'Edge',
            stripos($ua, 'Chrome') !== false => 'Chrome',
            stripos($ua, 'Firefox') !== false => 'Firefox',
            stripos($ua, 'Safari') !== false => 'Safari',
            default => null,
        };
    }

    private function osName(Request $request): ?string
    {
        $ua = (string) $request->userAgent();
        return match (true) {
            stripos($ua, 'Windows') !== false => 'Windows',
            stripos($ua, 'Android') !== false => 'Android',
            stripos($ua, 'iPhone') !== false || stripos($ua, 'iPad') !== false => 'iOS',
            stripos($ua, 'Mac OS') !== false => 'macOS',
            stripos($ua, 'Linux') !== false => 'Linux',
            default => null,
        };
    }
}
