<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use App\Notifications\PlatformNotification;
use App\Services\AuditLogger;
use App\Services\CertificateService;
use App\Services\WhatsAppNotifications;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCertificateController extends Controller
{
    public function __construct(
        private CertificateService $certificates,
        private AuditLogger $audit,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Certificate::with(['user:id,name,student_number,email', 'course:id,title'])->latest('issued_at');

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(fn ($q) => $q->where('certificate_number', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")));
        }

        return response()->json($query->paginate(20));
    }

    /**
     * Manual issue. Set ?force=0 (default) to enforce eligibility check.
     */
    public function issue(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'course_id' => ['required', 'exists:courses,id'],
            'force' => ['nullable', 'boolean'],
        ]);

        $user = User::findOrFail($data['user_id']);
        $course = Course::findOrFail($data['course_id']);

        if (! ($data['force'] ?? false)) {
            $eligibility = $this->certificates->eligibility($user, $course);
            if (! $eligibility['eligible']) {
                return response()->json([
                    'message' => 'الطالب لم يستوفِ شروط الشهادة بعد.',
                    'eligibility' => $eligibility,
                ], 422);
            }
        }

        $certificate = $this->certificates->issue($user, $course, $request->user());
        $this->audit->log('certificate.issued', $certificate);

        $user->notify(new PlatformNotification(
            'certificate_issued',
            'صدرت شهادتك',
            'تهانينا! صدرت شهادتك لكورس "'.$course->title.'".',
            '/dashboard/certificates'
        ));
        WhatsAppNotifications::certificateIssued($user, $certificate);

        return response()->json(['certificate' => $certificate], 201);
    }

    public function destroy(Certificate $certificate): JsonResponse
    {
        $this->audit->log('certificate.revoked', $certificate);
        $certificate->delete();

        return response()->json(['ok' => true]);
    }
}
