<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    public function forgot(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $email = $request->input('email');
        $ipKey = 'forgot-password:'.$request->ip();
        $emailKey = 'forgot-password-email:'.$email;

        if (RateLimiter::tooManyAttempts($emailKey, 3)) {
            abort(429, 'محاولات استعادة كثيرة لهذا البريد. حاول بعد '.RateLimiter::availableIn($emailKey).' ثانية.');
        }

        if (RateLimiter::tooManyAttempts($ipKey, 10)) {
            abort(429, 'محاولات استعادة كثيرة من هذا الجهاز. حاول لاحقًا.');
        }

        RateLimiter::hit($emailKey, 3600);
        RateLimiter::hit($ipKey, 3600);

        // Always return the same response to avoid account enumeration.
        Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => 'إن كان البريد مسجلًا لدينا، ستصلك رسالة استعادة كلمة المرور.',
        ]);
    }

    public function reset(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()],
        ]);

        $ipKey = 'reset-password:'.$request->ip();
        if (RateLimiter::tooManyAttempts($ipKey, 10)) {
            abort(429, 'محاولات إعادة تعيين كثيرة من هذا الجهاز. حاول لاحقًا.');
        }
        RateLimiter::hit($ipKey, 3600);

        $status = Password::reset($data, function ($user, $password) {
            $user->forceFill(['password' => $password])->save();
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => ['رابط الاستعادة غير صالح أو منتهي.'],
            ]);
        }

        return response()->json(['message' => 'تم تحديث كلمة المرور بنجاح.']);
    }
}
