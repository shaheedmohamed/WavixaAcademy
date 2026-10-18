<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InstructorProfile;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AdminInstructorController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function dropdown(): JsonResponse
    {
        return response()->json([
            'instructors' => User::role('instructor')->get(['id', 'name']),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = User::role('instructor')
            ->with('instructorProfile')
            ->withCount('taughtCourses')
            ->latest();

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return response()->json($query->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateInstructor($request);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'status' => $data['status'] ?? 'active',
            'locale' => 'ar',
        ]);

        $user->assignRole('instructor');

        InstructorProfile::create([
            'user_id' => $user->id,
            'title' => $data['title'] ?? null,
            'bio' => $data['bio'] ?? null,
            'is_public' => $data['is_public'] ?? true,
        ]);

        $this->audit->log('instructor.created', $user);

        return response()->json(['user' => $user->load('instructorProfile')], 201);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json([
            'user' => $user->load(['instructorProfile', 'taughtCourses:id,title,slug,status']),
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $this->validateInstructor($request, $user->id);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'status' => $data['status'] ?? $user->status,
        ]);

        if (! empty($data['password'])) {
            $user->update(['password' => Hash::make($data['password'])]);
        }

        $user->instructorProfile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'title' => $data['title'] ?? null,
                'bio' => $data['bio'] ?? null,
                'is_public' => $data['is_public'] ?? true,
            ]
        );

        $this->audit->log('instructor.updated', $user);

        return response()->json(['user' => $user->load('instructorProfile')]);
    }

    public function destroy(User $user): JsonResponse
    {
        $this->audit->log('instructor.deleted', $user);
        $user->delete();

        return response()->json(['ok' => true]);
    }

    private function validateInstructor(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($ignoreId)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => [($ignoreId ? 'nullable' : 'required'), 'string', Password::min(8)->mixedCase()->numbers()],
            'status' => ['nullable', Rule::in(['active', 'suspended'])],
            'title' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string'],
            'is_public' => ['nullable', 'boolean'],
        ]);
    }
}
