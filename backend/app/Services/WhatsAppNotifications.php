<?php

namespace App\Services;

use App\Jobs\SendWhatsAppMessageJob;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Event;
use App\Models\User;

/**
 * Academy → Wavixa WhatsApp notification hooks. Every send is queued and
 * silently skipped when the user has no phone or the API key isn't set.
 */
class WhatsAppNotifications
{
    private static function dispatchTo(User $user, string $message): void
    {
        $to = WavixaWhatsApp::normalizePhone($user->phone);
        if (! $to) {
            return;
        }

        SendWhatsAppMessageJob::dispatch(
            $to, $message, config('services.wavixa.fallback_template')
        );
    }

    public static function welcome(User $user): void
    {
        static::dispatchTo($user,
            "أهلًا {$user->name} 🎓\n\nتم إنشاء حسابك في Wavixa Academy بنجاح.\n"
            ."رقم طالبك: {$user->student_number}\n\n"
            .'تصفح الكورسات: '.config('app.url').'/courses'
        );
    }

    public static function enrollmentActivated(User $user, Course $course): void
    {
        static::dispatchTo($user,
            "تم تفعيل اشتراكك ✅\n\nالكورس: {$course->title}\n\n"
            .'ابدأ التعلم الآن: '.config('app.url')."/dashboard/courses/{$course->slug}"
        );
    }

    public static function eventRegistered(User $user, Event $event): void
    {
        $when = $event->start_at?->format('Y-m-d H:i');
        $where = $event->location ?: ($event->online_url ? 'أونلاين' : '');

        static::dispatchTo($user,
            "تم تسجيلك في الفعالية 🎟️\n\n{$event->title}\n"
            .($when ? "الموعد: {$when}\n" : '')
            .($where ? "المكان: {$where}\n" : '')
            ."\n".'تذكرتك: '.config('app.url').'/dashboard/events'
        );
    }

    public static function certificateIssued(User $user, Certificate $certificate): void
    {
        static::dispatchTo($user,
            "مبروك! صدرت شهادتك 🏆\n\n{$certificate->course?->title}\n"
            ."رقم الشهادة: {$certificate->certificate_number}\n\n"
            .'تحقق/حمّل: '.config('app.url')."/certificates/{$certificate->verification_token}"
        );
    }
}
