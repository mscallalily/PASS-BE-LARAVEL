<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ResendEmailVerificationController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $user = User::where('email', strtolower(trim($validated['email'])))->first();

        // Return the same response whether the account exists or not.
        // This helps prevent account enumeration.
        if ($user && ! $user->hasVerifiedEmail()) {
            try {
                app(\App\Services\Auth\EmailVerificationCodes::class)->send($user);
            } catch (\Illuminate\Validation\ValidationException $exception) {
                throw $exception;
            } catch (Throwable $exception) {
                report($exception);

                return response()->json([
                    'message' => 'We could not send the verification email right now. Please try again later.',
                ], 503);
            }
        }

        return response()->json([
            'message' => 'If your account exists and its email is not yet verified, a verification code has been requested. Check your inbox and spam folder.',
        ]);
    }
}
