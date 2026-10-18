<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInstructorAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('instructor')) {
            abort(403, 'غير مصرح بالوصول إلى لوحة المدرب.');
        }

        return $next($request);
    }
}
