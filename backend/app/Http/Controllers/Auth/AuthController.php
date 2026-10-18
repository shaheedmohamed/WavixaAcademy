<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\WhatsAppNotifications;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\s\-()]{7,30}$/'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'status' => 'active',
            'locale' => 'ar',
        ]);
        $user->assignRole('student');

        Auth::login($user);
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        $this->audit->log('auth.registered', $user);
        WhatsAppNotifications::welcome($user);

        return response()->json(['user' => $this->payload($user)], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key = 'login:'.$data['email'];
        $ipKey = 'login-ip:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            abort(429, 'محاولات دخول كثيرة لهذا البريد. حاول بعد '.$seconds.' ثانية.');
        }

        if (RateLimiter::tooManyAttempts($ipKey, 30)) {
            abort(429, 'تم رصد نشاط مريب من هذا الجهاز. حاول لاحقًا.');
        }

        if (! Auth::attempt($data, $request->boolean('remember'))) {
            RateLimiter::hit($key, 300);
            RateLimiter::hit($ipKey, 300);

            throw ValidationException::withMessages([
                'email' => ['بيانات الدخول غير صحيحة.'],
            ]);
        }

        RateLimiter::clear($key);

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        $user = $request->user();
        abort_if(! $user->isActive(), 403, 'حسابك موقوف. تواصل مع الدعم.');

        $user->update(['last_login_at' => now()]);
        $this->audit->log('auth.login', $user);

        return response()->json(['user' => $this->payload($user)]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->audit->log('auth.logout', $request->user());

        Auth::guard('web')->logout();
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json(['ok' => true]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->payload($request->user())]);
    }

    public function resendVerification(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['ok' => true]);
        }
        $request->user()->sendEmailVerificationNotification();

        return response()->json(['ok' => true]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $request->user()->update($data);

        return response()->json(['user' => $this->payload($request->user())]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        if (! Hash::check($data['current_password'], $request->user()->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['كلمة المرور الحالية غير صحيحة.'],
            ]);
        }

        $request->user()->update(['password' => $data['password']]);
        $this->audit->log('auth.password_changed', $request->user());

        return response()->json(['ok' => true]);
    }

    private function payload(User $user): array
    {
        $user->loadMissing('roles');

        return [
            'id' => $user->id,
            'student_number' => $user->student_number,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar,
            'status' => $user->status,
            'locale' => $user->locale,
            'email_verified' => (bool) $user->email_verified_at,
            'roles' => $user->roles->pluck('name'),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'is_admin' => $user->isAdmin(),
            'is_instructor' => $user->isInstructor(),
        ];
    }
}
