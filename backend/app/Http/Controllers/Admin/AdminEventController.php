<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminEventController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function index(Request $request): JsonResponse
    {
        $query = Event::with('course:id,title')->withCount('registrations')->latest('start_at');
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        if ($type = $request->string('type')->toString()) {
            $query->where('type', $type);
        }

        return response()->json($query->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $event = Event::create($this->validateEvent($request));
        $this->audit->log('event.created', $event);

        return response()->json(['event' => $event], 201);
    }

    public function show(Event $event): JsonResponse
    {
        $event->load('course:id,title');
        $event->loadCount(['registrations', 'registrations as attended_count' => fn ($q) => $q->whereNotNull('checked_in_at')]);

        return response()->json(['event' => $event]);
    }

    public function update(Request $request, Event $event): JsonResponse
    {
        $event->update($this->validateEvent($request, $event->id));
        $this->audit->log('event.updated', $event);

        return response()->json(['event' => $event]);
    }

    public function destroy(Event $event): JsonResponse
    {
        $this->audit->log('event.deleted', $event);
        $event->delete();

        return response()->json(['ok' => true]);
    }

    public function registrations(Event $event): JsonResponse
    {
        $registrations = $event->registrations()
            ->with('user:id,name,email,student_number')
            ->latest('registered_at')->paginate(50);

        return response()->json($registrations);
    }

    /**
     * QR check-in: the scanned payload contains the qr_token.
     */
    public function checkin(Request $request): JsonResponse
    {
        $data = $request->validate(['qr_token' => ['required', 'string', 'max:128']]);

        $registration = EventRegistration::where('qr_token', $data['qr_token'])
            ->with(['user:id,name,student_number', 'event:id,title,start_at'])
            ->first();

        if (! $registration) {
            return response()->json(['found' => false, 'message' => 'تذكرة غير صالحة.'], 404);
        }
        if ($registration->status === 'cancelled') {
            return response()->json(['found' => true, 'ok' => false, 'message' => 'هذا التسجيل ملغي.', 'registration' => $registration], 422);
        }
        if ($registration->checked_in_at) {
            return response()->json(['found' => true, 'ok' => false, 'message' => 'تم تسجيل الحضور مسبقًا.', 'registration' => $registration], 422);
        }

        $registration->update([
            'checked_in_at' => now(),
            'checked_in_by' => $request->user()->id,
            'status' => 'attended',
        ]);

        $this->audit->log('event.checkin', $registration);

        return response()->json(['found' => true, 'ok' => true, 'registration' => $registration]);
    }

    private function validateEvent(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'slug' => ['nullable', 'string', 'max:190', Rule::unique('events', 'slug')->ignore($ignoreId)],
            'description' => ['nullable', 'string'],
            'cover' => ['nullable', 'string', 'max:500'],
            'type' => ['required', Rule::in(['workshop', 'live_session', 'webinar', 'qna', 'meetup', 'graduation', 'other'])],
            'visibility' => ['required', Rule::in(['public', 'students_only', 'course_students', 'invite_only'])],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after:start_at'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'location' => ['nullable', 'string', 'max:190'],
            'online_url' => ['nullable', 'url', 'max:500'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['draft', 'published', 'cancelled', 'completed'])],
            'speaker' => ['nullable', 'string', 'max:190'],
            'course_id' => ['nullable', 'exists:courses,id'],
            'recording_url' => ['nullable', 'url', 'max:500'],
        ]);
    }
}
