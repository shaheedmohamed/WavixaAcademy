<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Services\EnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminEnrollmentController extends Controller
{
    public function __construct(private EnrollmentService $enrollments) {}

    public function index(Request $request): JsonResponse
    {
        $query = Enrollment::with(['user:id,name,email,student_number', 'course:id,title,slug', 'cohort:id,name'])
            ->latest();

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        if ($courseId = $request->integer('course_id')) {
            $query->where('course_id', $courseId);
        }
        if ($search = $request->string('q')->trim()->toString()) {
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('student_number', 'like', "%{$search}%"));
        }

        return response()->json($query->paginate(20));
    }

    public function update(Request $request, Enrollment $enrollment): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([
                Enrollment::STATUS_PENDING, Enrollment::STATUS_ACTIVE,
                Enrollment::STATUS_SUSPENDED, Enrollment::STATUS_EXPIRED,
                Enrollment::STATUS_CANCELLED, Enrollment::STATUS_COMPLETED,
            ])],
            'expires_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($data['status'] === Enrollment::STATUS_ACTIVE && $enrollment->status !== Enrollment::STATUS_ACTIVE) {
            $enrollment->update([
                'activated_at' => now(),
                'activated_by' => $request->user()->id,
            ]);
        }

        $enrollment = $this->enrollments->updateStatus($enrollment, $data['status'], $data['notes'] ?? null);
        if (array_key_exists('expires_at', $data)) {
            $enrollment->update(['expires_at' => $data['expires_at']]);
        }

        return response()->json(['enrollment' => $enrollment]);
    }

    public function destroy(Enrollment $enrollment): JsonResponse
    {
        $enrollment->update(['status' => Enrollment::STATUS_CANCELLED]);

        return response()->json(['ok' => true]);
    }
}
