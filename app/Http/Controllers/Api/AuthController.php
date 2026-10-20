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
        $category = $request->input('registration_category');
        $userType = $category === 'employee' ? 'employee' : 'student';
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'registration_category' => ['required', Rule::in(\App\Services\Registration\InstitutionalIdRules::CATEGORIES)],
            'first_name' => ['required', 'string', 'max:100', "regex:/^[\\pL\\pM][\\pL\\pM .'-]*$/u"],
            'last_name' => ['required', 'string', 'max:100', "regex:/^[\\pL\\pM][\\pL\\pM .'-]*$/u"],
            'suffix' => ['nullable', 'string', 'regex:/^(?:Jr\.?|Sr\.?|I|II|III|IV|V|VI|VII|VIII|IX|X)$/i'],
            'middle_initial' => ['nullable', 'string', 'regex:/^\pL\.?$/u'],
            'user_code' => ['required', 'string', 'max:50', function ($attr, $value, $fail) use ($category) {
                if (in_array($category, \App\Services\Registration\InstitutionalIdRules::CATEGORIES, true)
                    && ($problem = \App\Services\Registration\InstitutionalIdRules::idProblem($category, $value))) $fail($problem);
            }],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['bail', 'string', new \App\Rules\PasswordBytes, 'required', 'string', 'min:8', 'confirmed'],
            'user_type' => ['sometimes', Rule::in([$userType])],
            'department' => ['required', 'string', 'max:150'],
            'contact_number' => ['required', 'string', 'max:30'],
            'school_id_file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'mimetypes:image/jpeg,image/png,application/pdf', 'max:5120'],
            'id_check_receipt' => [Rule::requiredIf($category !== 'shs'), 'nullable', 'string', 'max:30000'],
            'agree' => ['required', 'accepted'],
            'request_manual_review' => ['sometimes', 'boolean'],
            // Vehicle enrollment belongs to the signed-in vehicle workflow.
            'has_vehicle' => ['sometimes', 'boolean', Rule::in([false, 0, '0'])],
            'vehicle_type' => ['prohibited'], 'plate_number' => ['prohibited'], 'make' => ['prohibited'],
            'model' => ['prohibited'], 'color' => ['prohibited'], 'license_number' => ['prohibited'],
            'license_type' => ['prohibited'], 'license_expiry' => ['prohibited'], 'license_file' => ['prohibited'],
        ]);
        $number = \App\Services\Registration\InstitutionalIdRules::format($category, $data['user_code']);
        // Keep normalized IDs unique even if older records used another separator.
        if (DB::table('users')->whereRaw("REPLACE(REPLACE(UPPER(user_code), '-', ''), ' ', '') = ?", [strtoupper(\App\Services\Registration\InstitutionalIdRules::digits($number))])->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['user_code' => 'This institutional ID number is already registered. Sign in or recover your account.']);
        }
        $department = Department::where('department_name', trim($data['department']))->where('is_active', true)->first();
        if ($category === 'college' && (!$department || in_array(strtoupper($department->department_code), ['SHS', 'JHS', 'BED'], true))) {
            throw \Illuminate\Validation\ValidationException::withMessages(['department' => 'Choose an active college department.']);
        }
        $unit = $category === 'shs' ? 'Senior High School' : trim($data['department']);
        if ($category === 'shs') $department = Department::where('department_code', 'SHS')->where('is_active', true)->first();
        $file = $request->file('school_id_file');
        $size = @getimagesize($file->getRealPath());
        if ($size && $size[0]*$size[1] > 20000000) throw \Illuminate\Validation\ValidationException::withMessages(['school_id_file' => 'Use a photo with at most 20 megapixels.']);
        $evidence = $category === 'shs' ? [] : app(\App\Services\Registration\InstitutionalIdReceipt::class)->read($data['id_check_receipt'], $file, $category);
        $rules = app(\App\Services\Registration\InstitutionalIdRules::class);
        $check = $rules->requestManualReview($rules->evaluate($category, $evidence, $data), $request->boolean('request_manual_review'));
        if ($check['status'] === 'needs_correction') {
            throw \Illuminate\Validation\ValidationException::withMessages(['school_id_file' => 'The document and submitted details could not be matched. Check your details or upload a clearer ID.']);
        }
        $check['activation_policy'] = 'matched-id-and-email-v1';
        $check['identity_hash'] = \App\Services\Registration\RegistrationActivation::identity($category, $number, $data['first_name'], $data['last_name'], $data['middle_initial'] ?? '', $data['suffix'] ?? '');
        $path = $file->store('school-ids', 'private');
        if (!$path) return response()->json(['message' => 'The ID could not be stored. Please try again.'], 500);
        try {
            $user = DB::transaction(function () use ($data, $userType, $number, $department, $unit, $check, $path, $request) {
                $user = User::create(['user_code' => $number, 'email' => $data['email'], 'password' => $data['password'],
                    'user_type' => $userType, 'account_status' => 'pending']);
                $user->profile()->create([
                    'department_id' => $department?->department_id, 'registration_category' => $data['registration_category'], 'registration_unit' => $unit,
                    'first_name' => mb_strtoupper(trim($data['first_name'])), 'last_name' => mb_strtoupper(trim($data['last_name'])),
                    'middle_name' => empty($data['middle_initial']) ? null : mb_strtoupper(rtrim($data['middle_initial'], '.')).'.',
                    'suffix' => mb_strtoupper(\App\Services\Registration\InstitutionalIdRules::suffix($data['suffix'] ?? '')) ?: null,
                    'contact_number' => trim($data['contact_number']), 'school_id_document_path' => $path,
                    'school_id_check_status' => $check['status'], 'school_id_check_result' => $check,
                ]);
                $role = Role::where('role_code', $userType)->where('is_active', true)->firstOrFail();
                $user->roles()->syncWithoutDetaching([$role->role_id => ['assigned_at' => now()]]);
                \App\Support\ActivityAudit::record($request, $user, 'account_registered', 'users', (int) $user->user_id, null,
                    ['account_status'=>'pending', 'user_type'=>$userType, 'registration_category'=>$data['registration_category'], 'id_check_status'=>$check['status']]);
                return $user;
            });
        } catch (Throwable $exception) {
            Storage::disk('private')->delete($path);
            if ($exception instanceof \Illuminate\Database\QueryException && in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                return response()->json(['message'=>'This email or ID number is already registered. Sign in or recover your account.'], 422);
            }
            report($exception);
            return response()->json(['message'=>'Registration could not be completed. Please try again.'], 500);
        }
        // Mail failure never rolls back an already committed registration/document.
        $sent = false;
        try { app(\App\Services\Auth\EmailVerificationCodes::class)->send($user); $sent = true; }
        catch (Throwable $error) { report($error); }
        return response()->json([
            'message' => $sent ? 'Registration saved. Verify your email next. Matching IDs activate automatically; uncertain checks require staff review.' : 'Registration saved, but the verification email could not be sent. Use Send / resend code to try again.',
            'email_verification_sent' => $sent, 'id_check' => \App\Services\Registration\PublicIdCheck::summary($check), 'user' => $this->userPayload($user),
        ], 201);
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
        if (!$user && preg_match('/^(?:\d{7}|\d{9})$/D', $numericId)) {
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
            'schoolIdCheckStatus' => $profile?->school_id_check_status,
            'accountStatus' => $user->account_status,
            'emailVerified' => $user->hasVerifiedEmail(),
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
