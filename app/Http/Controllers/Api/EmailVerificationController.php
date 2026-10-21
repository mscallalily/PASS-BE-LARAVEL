<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class EmailVerificationController extends Controller
{
    public function verify(Request $request, string $id, string $hash): JsonResponse
    {
        if (! $request->hasValidSignature()) {
            return response()->json([
                'message' => 'This verification link is invalid or has expired.',
            ], 403);
        }

        $user = User::find($id);

        if (! $user || ! hash_equals(
            sha1($user->getEmailForVerification()),
            $hash
        )) {
            return response()->json([
                'message' => 'This verification link is invalid.',
            ], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Your email address has already been verified.',
            ]);
        }

        $user = app(\App\Services\Registration\RegistrationActivation::class)->complete($user);

        return response()->json([
            'message' => $user->isGuest()
                ? 'Email verified successfully. You can now sign in and submit a visitor parking request. A QR pass requires request approval.'
                : ($user->account_status === 'pending' ? 'Email verified. Your ID needs staff review; your account remains pending.' : ($user->account_status === 'active' ? 'Email verified. Your account is active. You can now sign in.' : 'Email verified. Your account is not active. Contact PASS staff.')),
        ]);
    }
}