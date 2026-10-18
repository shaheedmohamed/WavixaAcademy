<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\ContactMessage;
use App\Models\Course;
use App\Models\Event;
use App\Models\Faq;
use App\Models\InstructorProfile;
use App\Models\Lesson;
use App\Services\EnrollmentService;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PublicController extends Controller
{
    public function __construct(
        private SettingsService $settings,
        private EnrollmentService $enrollments,
    ) {}

    public function publicSettings(): JsonResponse
    {
        return response()->json($this->settings->publicSettings());
    }

    public function courses(Request $request): JsonResponse
    {
        $query = Course::published()
            ->with(['instructor:id,name,avatar', 'instructor.instructorProfile:user_id,title'])
            ->withCount(['lessons' => fn ($q) => $q->where('is_published', true)])
            ->orderByDesc('is_featured')->orderBy('sort_order');

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")
                ->orWhere('short_description', 'like', "%{$search}%"));
        }
        if ($level = $request->string('level')->toString()) {
            $query->where('level', $level);
        }

        return response()->json($query->paginate(12));
    }

    public function course(Request $request, Course $course): JsonResponse
    {
        abort_if($course->status !== 'published', 404);

        $course->load([
            'instructor:id,name,avatar',
            'instructor.instructorProfile',
            'modules' => fn ($q) => $q->where('status', 'published'),
            'modules.lessons' => fn ($q) => $q->where('is_published', true)
                ->select('id', 'module_id', 'title', 'slug', 'duration_seconds', 'is_preview', 'sort_order'),
        ]);

        $user = $request->user('sanctum');
        $enrollment = $user ? $this->enrollments->enrollmentFor($user, $course) : null;

        $whatsapp = $this->settings->get('whatsapp_number');
        $template = $this->settings->get('whatsapp_message_template', 'مرحبًا، أريد التسجيل في كورس: {course_name}');

        return response()->json([
            'course' => $course,
            'enrollment' => $enrollment,
            'can_access' => (bool) $enrollment?->isAccessible(),
            'whatsapp_url' => $whatsapp
                ? 'https://wa.me/'.preg_replace('/\D/', '', $whatsapp).'?text='.urlencode(str_replace('{course_name}', $course->title, $template))
                : null,
        ]);
    }

    public function lessonPreview(Request $request, Course $course, Lesson $lesson): JsonResponse
    {
        abort_if($course->status !== 'published' || ! $lesson->is_published, 404);
        abort_if(! $lesson->is_preview, 403, 'هذا الدرس غير متاح كمعاينة مجانية.');
        abort_if($lesson->module->course_id !== $course->id, 404);

        return response()->json([
            'lesson' => $lesson->only('id', 'title', 'description', 'duration_seconds'),
            'has_video' => (bool) $lesson->videoAsset?->isReady(),
        ]);
    }

    public function events(Request $request): JsonResponse
    {
        $query = Event::published()
            ->where('visibility', 'public')
            ->with('course:id,title,slug')
            ->orderBy('start_at');

        if ($request->string('filter')->toString() === 'upcoming') {
            $query->where('start_at', '>=', now());
        }

        return response()->json($query->paginate(12));
    }

    public function event(Request $request, Event $event): JsonResponse
    {
        abort_if($event->status !== 'published', 404);

        $user = $request->user('sanctum');
        $visible = $event->visibility === 'public'
            || ($user && $event->visibility === 'students_only')
            || ($user && $event->visibility === 'course_students'
                && $event->course_id && $this->enrollments->canAccess($user, $event->course_id));

        abort_if(! $visible, 404);

        $registration = $user
            ? $event->registrations()->where('user_id', $user->id)->first()
            : null;

        return response()->json([
            'event' => $event,
            'registration' => $registration,
            'remaining_capacity' => $event->capacity
                ? max(0, $event->capacity - $event->registrations()->where('status', 'registered')->count())
                : null,
        ]);
    }

    public function instructors(): JsonResponse
    {
        $instructors = InstructorProfile::where('is_public', true)
            ->with('user:id,name,avatar')
            ->withCount(['user as courses_count' => fn ($q) => $q->whereHas('taughtCourses', fn ($c) => $c->published())])
            ->get();

        return response()->json(['instructors' => $instructors]);
    }

    public function faqs(): JsonResponse
    {
        $faqs = Cache::remember('faqs:public', 300, fn () => Faq::published()->get());

        return response()->json(['faqs' => $faqs]);
    }

    public function verifyCertificate(string $token): JsonResponse
    {
        $certificate = Certificate::where('verification_token', $token)
            ->with(['user:id,name,student_number', 'course:id,title,instructor_id,certificate_title,certificate_language', 'course.instructor:id,name'])
            ->first();

        if (! $certificate) {
            return response()->json(['valid' => false], 404);
        }

        return response()->json([
            'valid' => true,
            'certificate' => [
                'number' => $certificate->certificate_number,
                'student_name' => $certificate->user->name,
                'student_number' => $certificate->user->student_number,
                'course' => $certificate->course->title,
                'certificate_title' => $certificate->course->certificate_title,
                'certificate_language' => $certificate->course->certificate_language,
                'instructor_name' => $certificate->course->instructor?->name,
                'issued_at' => $certificate->issued_at->toDateString(),
                'issued_at_formatted' => $certificate->issued_at->format('Y/m/d'),
                'verification_token' => $certificate->verification_token,
            ],
        ]);
    }

    public function contact(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'subject' => ['nullable', 'string', 'max:190'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        ContactMessage::create($data);

        return response()->json(['message' => 'وصلتنا رسالتك وسنرد عليك قريبًا.'], 201);
    }
}
