<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Auth\EmailVerificationCodes;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VerifyEmailCodeController extends Controller
{
    public function __invoke(Request $request, EmailVerificationCodes $codes): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255'], 'code' => ['required', 'regex:/^[0-9]{6}$/']]);
        $user = $codes->verify(strtolower(trim($data['email'])), $data['code']);
        if (!$user) return response()->json(['message' => 'Invalid or expired code. After 5 incorrect attempts, request a new code.'], 422);
        return response()->json(['message' => $user->isGuest()
            ? 'Email verified. You can now sign in and submit a visitor request. A QR pass requires approval.'
            : ($user->account_status === 'pending' ? 'Email verified. Your account is still pending administrator approval.' : 'Email verified. You can now try signing in.')]);
    }
}
