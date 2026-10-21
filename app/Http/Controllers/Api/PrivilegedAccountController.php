<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\PrivilegedAccounts;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class PrivilegedAccountController extends Controller
{
    public function __construct(private readonly PrivilegedAccounts $accounts) {}

    public function store(Request $request): JsonResponse
    {
        $this->accounts->assertAdmin($request->user());
        $request->merge(['email' => Str::lower(trim((string) $request->input('email'))), 'user_code' => Str::upper(trim((string) $request->input('user_code')))]);
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'], 'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'user_code' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9][A-Z0-9-]*$/D', Rule::unique('users', 'user_code')],
            'role' => ['required', Rule::in(['admin', 'parking_staff'])],
            'contact_number' => ['nullable', 'regex:/^(?:09\d{9}|\+639\d{9})$/D'],
            'password' => ['prohibited'], 'password_confirmation' => ['prohibited'], 'account_status' => ['prohibited'],
            'email_verified_at' => ['prohibited'], 'roles' => ['prohibited'], 'user_type' => ['prohibited'],
        ]);
        try {
            [$user, $sent] = $this->accounts->create($request, $data);
        } catch (UniqueConstraintViolationException $exception) {
            throw ValidationException::withMessages(['email' => 'An account already uses this email or account ID. Check both values.']);
        }
        return $this->invitationResponse($user, $sent, 201);
    }

    public function showInvitation(Request $request, int $id): JsonResponse
    {
        $this->accounts->assertAdmin($request->user());
        $user = User::findOrFail($id);
        return response()->json(['accountSetup' => $this->accounts->state($user)])->header('Cache-Control', 'no-store, private');
    }

    public function resendInvitation(Request $request, int $id): JsonResponse
    {
        [$user, $sent] = $this->accounts->resendInvitation($request, $id);
        return $this->invitationResponse($user, $sent);
    }

    public function status(Request $request): JsonResponse
    {
        return response()->json(['accountSetup' => $this->accounts->state($request->user())])->header('Cache-Control', 'no-store, private');
    }

    public function resendCode(Request $request): JsonResponse
    {
        $sent = $this->accounts->resendCode($request);
        return response()->json(['message' => $sent ? 'A new code was accepted by the mail server. Check your inbox and spam folder.' : 'The activation email could not be sent. Please try again after 60 seconds.',
            'accountSetup' => $this->accounts->state($request->user())], $sent ? 200 : 503);
    }

    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'regex:/^[0-9]{6}$/D']]);
        if (!$this->accounts->verify($request, $data['code'])) return response()->json(['message' => 'The code is invalid, expired or unavailable. Request a new code if needed.'], 422);
        return response()->json(['message' => 'Email activated. Choose your own password next.', 'accountSetup' => $this->accounts->state($request->user())]);
    }

    public function complete(Request $request): JsonResponse
    {
        $data = $request->validate(['password' => ['required', 'string', 'confirmed', Password::min(12)->mixedCase()->numbers(),
            function ($attribute, $value, $fail) { if (strlen($value) > 72) $fail('The password must not exceed 72 UTF-8 bytes.'); }]]);
        return response()->json(['message' => 'Account setup complete.', 'token' => $this->accounts->complete($request, $data['password'])])->header('Cache-Control', 'no-store, private');
    }

    private function invitationResponse(User $user, bool $sent, int $status = 200): JsonResponse
    {
        return response()->json(['message' => $sent ? 'Invitation accepted by the mail server. The recipient must activate email and choose a password before accessing PASS.'
            : 'Account saved, but the invitation could not be sent. Use Resend invitation after 60 seconds.',
            'invitation_sent' => $sent, 'user' => ['id' => (string) $user->getKey(), 'email' => $user->email, 'idNum' => $user->user_code],
            'accountSetup' => $this->accounts->state($user)], $status)->header('Cache-Control', 'no-store, private');
    }
}
