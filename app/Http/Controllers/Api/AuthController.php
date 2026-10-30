<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Throwable;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        return app(RegistrationSessionController::class)->complete($request);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:1024'],
        ]);

        $identifier = trim($data['identifier']);
        $user = User::where('email', $identifier)->orWhere('user_code', $identifier)->first();
        $numericId = \App\Services\Registration\InstitutionalIdRules::digits($identifier);
        if (!$user && preg_match('/^(?:\d{7}|\d{9}|\d{12})$/D', $numericId)) {
            // Both printed and digits-only institutional numbers may sign in.
            // Legacy ambiguous normalized IDs require the exact saved ID or email.
            $matches = User::whereRaw("REPLACE(REPLACE(user_code, '-', ''), ' ', '') = ?", [$numericId])->limit(2)->get();
            if ($matches->count() === 1) $user = $matches->first();
        }

        if (! $user || ! Hash::check(
            $data['password'],
            $user->password
        )) {
            $this->logAttempt(
                $request,
                $user,
                $data['identifier'],
                false,
                'invalid_credentials'
            );

            return response()->json([
                'message' => 'Invalid email/ID number or password.',
            ], 401);
        }

        // PASS_PRIVILEGED_ACCOUNT_SETUP: restricted first-login session.
        if ($setup = app(\App\Services\Auth\PrivilegedAccounts::class)->setupLogin($request, $user)) return $setup;

        if (($user->isGuest() || in_array($user->user_type, ['student', 'faculty', 'employee', 'other'], true)) && !$user->hasVerifiedEmail()) {
            $this->logAttempt($request, $user, $data['identifier'], false, 'email_unverified');
            return response()->json([
                'message' => 'Please verify your email address before signing in. Open Verify email to request and enter your verification code.',
                'code' => 'email_unverified',
            ], 403);
        }

        if ($user->account_status !== 'active' && !($user->account_status === 'pending' && in_array($user->user_type, ['student', 'faculty', 'employee', 'other'], true) && !$user->isGuest())) {
            $this->logAttempt(
                $request,
                $user,
                $data['identifier'],
                false,
                'account_' . $user->account_status
            );

            $messages = [
                'pending' =>
                    'Your account is still pending approval by the PASS administrator.',
                'suspended' =>
                    'Your account is suspended. Please contact the PASS office.',
                'deactivated' =>
                    'Your account is deactivated. Please contact the PASS office.',
            ];

            return response()->json([
                'message' => $messages[$user->account_status]
                    ?? 'Account not active.',
            ], 403);
        }

        $this->logAttempt(
            $request,
            $user,
            $data['identifier'],
            true
        );

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        \App\Support\ActivityAudit::record($request, $user, 'login', 'users', (int) $user->user_id);

        return response()->json([
            'token' => $user->createToken('web')->plainTextToken,
            'user' => $this->userPayload($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->userPayload($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        \App\Support\ActivityAudit::record($request, $request->user(), 'logout', 'users', (int) $request->user()->user_id);
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out.',
        ]);
    }

    /**
     * Keep the response compatible with AuthContext.tsx.
     */
    private function userPayload(User $user): array
    {
        $user->loadMissing('profile.department', 'roles');

        $profile = $user->profile;

        return [
            'id' => (string) $user->user_id,
            'name' => trim(
                ($profile->first_name ?? '') . ' ' .
                ($profile->last_name ?? '') . ' ' . ($profile->suffix ?? '')
            ),
            'email' => $user->email,
            'role' => $user->roles->first()?->role_code,
            'idNum' => $user->user_code,
            'dept' => $profile?->registration_unit ?: $profile?->department?->department_name,
            'registrationCategory' => $profile?->registration_category,
            'yearLevel' => $profile?->year_level,
            'section' => $profile?->section,
            'schoolIdCheckStatus' => $profile?->school_id_check_status,
            'accountStatus' => $user->account_status,
            'emailVerified' => $user->hasVerifiedEmail(),
            'accountSetup' => app(\App\Services\Auth\PrivilegedAccounts::class)->state($user),
            'schoolIdSubmitted' => !empty($profile?->school_id_document_path),
        ];
    }

    private function logAttempt(
        Request $request,
        ?User $user,
        string $identifier,
        bool $success,
        ?string $reason = null
    ): void {
        DB::table('login_attempts')->insert([
            'user_id' => $user?->user_id,
            'identifier' => $identifier,
            'ip_address' => $request->ip(),
            'user_agent' => substr(
                (string) $request->userAgent(),
                0,
                255
            ),
            'was_successful' => $success,
            'failure_reason' => $reason,
            'attempted_at' => now(),
        ]);
    }


public function forgotPassword(Request $request): JsonResponse
{
    $data = $request->validate([
        'email' => ['required', 'email', 'max:255'],
    ]);

    try {
        $status = Password::sendResetLink(['email' => $data['email']]);
    } catch (Throwable $error) {
        report($error);
        return response()->json(['message' => 'Email delivery is unavailable. Please try again later.'], 503);
    }

    // Avoid revealing whether an email exists in the database.
    if ($status === Password::RESET_LINK_SENT ||
        $status === Password::INVALID_USER) {
        return response()->json([
            'message' =>
                'If an account exists for that email, a password reset link will be sent.',
        ]);
    }

    if ($status === Password::RESET_THROTTLED) {
        return response()->json([
            'message' =>
                'Please wait before requesting another reset link.',
        ], 429);
    }

    report(new \RuntimeException(
        'Password reset link could not be sent: ' . $status
    ));

    return response()->json([
        'message' =>
            'Unable to process your request right now. Please try again later.',
    ], 500);
}

public function resetPassword(Request $request): JsonResponse
{
    $data = $request->validate([
        'token' => ['required', 'string'],
        'email' => ['required', 'email', 'max:255'],
        'password' => ['bail', 'string', new \App\Rules\PasswordBytes, 'required', 'string', 'min:8', 'confirmed'],
    ]);

    $status = DB::transaction(function () use ($data, $request) {
        // Serialize token consumption with the account password update. A
        // second reset waits, then sees the already-consumed database token.
        User::where('email', $data['email'])->lockForUpdate()->first();
        return Password::reset($data, function (User $user, string $password) use ($request): void {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            $user->tokens()->delete();
            \App\Support\ActivityAudit::record($request, $user, 'password_reset', 'users', (int) $user->user_id);
        });
    }, 3);

    if ($status === Password::PASSWORD_RESET) {
        return response()->json([
            'message' => 'Your password has been reset successfully.',
        ]);
    }

    return response()->json([
        'message' => match ($status) {
            Password::INVALID_USER =>
                'Unable to reset the password for this account.',
            Password::INVALID_TOKEN =>
                'This reset link is invalid or has expired. Please request a new one.',
            Password::RESET_THROTTLED =>
                'Please wait before trying again.',
            default =>
                'Unable to reset your password. Please request a new link.',
        },
    ], 422);
}

}
