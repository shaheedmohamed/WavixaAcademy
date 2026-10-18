<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Services\AuditLogger;
use App\Services\EnrollmentService;
use App\Services\WhatsAppNotifications;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    public function __construct(
        private EnrollmentService $enrollments,
        private AuditLogger $audit,
    ) {}

    public function register(Request $request, Event $event): JsonResponse
    {
        $user = $request->user();

        abort_if($event->status !== 'published', 404);
        abort_if($event->start_at->isPast(), 422, 'انتهت هذه الفعالية.');

        if ($event->visibility === 'course_students') {
            abort_if(! $event->course_id || ! $this->enrollments->canAccess($user, $event->course_id),
                403, 'هذه الفعالية مخصصة لطلاب الكورس.');
        }

        if (! $event->hasCapacity()) {
            throw ValidationException::withMessages(['event' => ['اكتمل العدد المسموح للفعالية.']]);
        }

        $registration = EventRegistration::firstOrCreate(
            ['event_id' => $event->id, 'user_id' => $user->id],
            ['status' => 'registered']
        );

        $this->audit->log('event.registered', $registration, ['event' => $event->title]);
        WhatsAppNotifications::eventRegistered($user, $event);

        return response()->json(['registration' => $registration], 201);
    }

    public function cancel(Request $request, Event $event): JsonResponse
    {
        $registration = $event->registrations()->where('user_id', $request->user()->id)->firstOrFail();
        abort_if($registration->checked_in_at, 422, 'لا يمكن الإلغاء بعد تسجيل الحضور.');

        $registration->update(['status' => 'cancelled']);
        $this->audit->log('event.registration_cancelled', $registration);

        return response()->json(['ok' => true]);
    }

    public function myEvents(Request $request): JsonResponse
    {
        $registrations = $request->user()->eventRegistrations()
            ->with('event:id,title,slug,type,start_at,end_at,timezone,location,online_url')
            ->latest('registered_at')
            ->get();

        return response()->json(['registrations' => $registrations]);
    }

    public function ticket(Request $request, EventRegistration $registration): JsonResponse
    {
        abort_if($registration->user_id !== $request->user()->id, 403);

        $svg = $this->qrSvg(json_encode([
            't' => $registration->qr_token,
            'e' => $registration->event_id,
        ]));

        return response()->json([
            'ticket_code' => $registration->ticket_code,
            'qr_svg' => $svg,
            'event' => $registration->event->only('title', 'start_at', 'location', 'online_url'),
            'status' => $registration->status,
            'checked_in_at' => $registration->checked_in_at,
        ]);
    }

    private function qrSvg(string $payload): string
    {
        $renderer = new ImageRenderer(new RendererStyle(220), new SvgImageBackEnd);

        return (new Writer($renderer))->writeString($payload);
    }
}
