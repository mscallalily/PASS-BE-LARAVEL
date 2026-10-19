<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AccountAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        // A pending account may only inspect its own application and sign out.
        if ($request->is('api/me', 'api/logout')) return $next($request);
        $user = $request->user();
        if ($user->account_status !== 'active') {
            return response()->json(['message' => 'Your account is awaiting approval or is not active.', 'code' => 'account_not_active'], 403);
        }
        if (($user->isGuest() || in_array($user->user_type, ['student', 'faculty', 'employee', 'other'], true)) && !$user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Please verify your email address.', 'code' => 'email_unverified'], 403);
        }
        return $next($request);
    }
}
