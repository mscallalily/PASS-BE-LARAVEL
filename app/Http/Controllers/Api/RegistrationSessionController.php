<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\DriverLicense;
use App\Models\RegistrationSession;
use App\Models\Role;
use App\Models\User;
use App\Notifications\RegistrationEmailCode;
use App\Services\LicenseVerification\LicenseActivity;
use App\Services\LicenseVerification\LicenseValidationRules;
use App\Services\Registration\InstitutionalIdRules;
use App\Services\Registration\InstitutionalIdReceipt;
use App\Services\Registration\InstitutionalIdAutofill;
use App\Services\Registration\RegistrationLicenseCheck;
use App\Support\ActivityAudit;
use App\Support\CampusTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

/** A bounded, private registration draft shared only with its owner's photo-capture link. */
class RegistrationSessionController extends Controller
{
    public function start(Request $request, InstitutionalIdReceipt $receipts, InstitutionalIdAutofill $autofill)
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        $category = $request->input('registration_category');
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', 'regex:/^[^@\s]+@dyci\.edu\.ph$/iD', 'unique:users,email'],
            'registration_category' => ['required', Rule::in(InstitutionalIdRules::CATEGORIES)],
            'school_id_file' => ['required', 'file', 'mimes:jpg,jpeg,png', 'mimetypes:image/jpeg,image/png', 'max:5120'],
            'id_check_receipt' => ['required', 'string', 'max:50000'],
            'first_name' => ['prohibited'], 'last_name' => ['prohibited'], 'middle_name' => ['prohibited'],
            'middle_initial' => ['prohibited'], 'suffix' => ['prohibited'], 'user_code' => ['prohibited'], 'birth_date' => ['prohibited'],
            'department' => ['required', 'string', 'max:150'],
            'year_level' => [Rule::requiredIf($category === 'shs'), Rule::prohibitedIf($category !== 'shs'), 'nullable', Rule::in(['11', '12'])],
            'section' => [Rule::requiredIf($category === 'shs'), Rule::prohibitedIf($category !== 'shs'), 'nullable', 'string', 'max:60', 'not_regex:/[<>\x00-\x1F\x7F]/'],
            'contact_number' => ['required', 'string', 'max:30'],
            'user_type' => ['prohibited'], 'role' => ['prohibited'], 'password' => ['prohibited'],
        ], ['email.regex' => 'Use your institutional email ending exactly in @dyci.edu.ph.']);
        $file = $request->file('school_id_file');
        $evidence = $receipts->read($data['id_check_receipt'], $file, $category);
        $school = $autofill->evaluate($category, $evidence);
        if ($school['status'] !== 'passed') throw ValidationException::withMessages(['school_id_file' => 'Pass the automatic institutional ID check with a clear photo before continuing.']);
        unset($data['school_id_file'], $data['id_check_receipt']);
        $data = array_replace($data, $school['fields'], ['identity_source' => 'institutional_id']);
        // A submitted supplement cannot override a field confidently printed on the ID.
        foreach ($school['profile_fields'] as $field => $value) {
            if (isset($data[$field]) && trim((string) $data[$field]) !== (string) $value) throw ValidationException::withMessages([$field => 'This value was read from your ID and cannot be edited. Retake the ID photo if it was read incorrectly.']);
            $data[$field] = $value;
        }
        $data['user_code'] = InstitutionalIdRules::format($category, $data['user_code']);
        $this->assertIdentifiersAvailable($data);
        $department = Department::where('department_name', trim($data['department']))->where('is_active', true)->first();
        if ($category === 'college' && (!$department || in_array(strtoupper($department->department_code), ['SHS', 'JHS', 'BED'], true)
            || in_array(strtoupper($department->department_name), ['SECURITY OFFICE', 'HR DEPARTMENT', 'IT DEPARTMENT', 'REGISTRAR OFFICE'], true))) {
            throw ValidationException::withMessages(['department' => 'Choose an active college department.']);
        }
        if ($category === 'shs') {
            $data['department'] = 'Senior High School';
            $department = Department::where('department_code', 'SHS')->where('is_active', true)->first();
        }
        $data['department_id'] = $department?->department_id;
        foreach (['first_name', 'last_name', 'middle_name', 'suffix', 'section'] as $field) if (isset($data[$field])) $data[$field] = mb_strtoupper(trim($data[$field]));
        $key = bin2hex(random_bytes(32));
        $path = $file->store('registration-ids', 'private');
        abort_unless($path, 503, 'The ID photo could not be stored. Try again.');
        $data['school_id'] = ['path' => $path, 'sha256' => hash_file('sha256', Storage::disk('private')->path($path)),
            'status' => 'matched', 'policy_version' => InstitutionalIdAutofill::POLICY, 'evidence' => $school['evidence']];
        try {
            $session = RegistrationSession::create([
                'session_id' => (string) Str::uuid(), 'email' => $data['email'], 'owner_hash' => hash('sha256', $key),
                'draft' => $data, 'expires_at' => now()->addHour(),
            ]);
        } catch (Throwable $error) { Storage::disk('private')->delete($path); throw $error; }
        $sent = $this->sendCode($session);
        return response()->json(['session_id' => $session->session_id, 'owner_key' => $key, 'email_sent' => $sent,
            'message' => $sent ? 'A verification code was sent to your DYCI email.' : 'The draft was saved, but email delivery is unavailable. Use Send code to retry.',
            'data' => $this->payload($session->fresh())], 201);
    }

    public function status(Request $request, string $id)
    {
        return response()->json(['data' => $this->payload($this->owner($request, $id))]);
    }

    public function resend(Request $request, string $id)
    {
        $session = $this->owner($request, $id);
        abort_if($session->consumed_at, 409, 'Registration is already complete. Sign in.');
        if ($session->email_verified_at) return response()->json(['message' => 'Email is already verified.', 'data' => $this->payload($session)]);
        $sent = $this->sendCode($session);
        return response()->json(['message' => $sent ? 'A new verification code was sent.' : 'Email delivery is unavailable. Try again shortly.', 'email_sent' => $sent], $sent ? 200 : 503);
    }

    public function verify(Request $request, string $id)
    {
        $request->validate(['code' => ['required', 'string', 'regex:/^[0-9]{6}$/D']]);
        $valid = DB::transaction(function () use ($request, $id) {
            $session = $this->owner($request, $id, true);
            if ($session->email_verified_at) return true;
            if (!$session->code_hash || $session->code_attempts >= 5 || !$session->code_expires_at || now()->gte($session->code_expires_at)) return false;
            if (!Hash::check($request->input('code'), $session->code_hash)) {
                $session->increment('code_attempts');
                return false; // Return before throwing so failed attempts are committed.
            }
            $session->forceFill(['email_verified_at' => now(), 'code_hash' => null, 'code_expires_at' => null])->save();
            return true;
        });
        if (!$valid) throw ValidationException::withMessages(['code' => 'The code is incorrect, expired, or has reached its attempt limit. Request a new code.']);
        return response()->json(['message' => 'Institutional email verified. Continue with your driver\'s license.', 'data' => $this->payload($this->owner($request, $id))]);
    }

    public function handoff(Request $request, string $id)
    {
        $key = bin2hex(random_bytes(32));
        $session = DB::transaction(function () use ($request, $id, $key) {
            $session = $this->owner($request, $id, true); $this->requireVerified($session);
            abort_if($session->license_started_at && $session->license_started_at->gt(now()->subMinutes(2)), 409, 'Wait for the current license check to finish.');
            $session->forceFill(['capture_hash' => hash('sha256', $key), 'capture_expires_at' => now()->addMinutes(10)->min($session->expires_at), 'license_run_id' => (string) Str::uuid(), 'license_started_at' => null, 'license_result' => null])->save();
            return $session;
        });
        return response()->json(['session_id' => $id, 'capture_key' => $key, 'expires_at' => $session->capture_expires_at->toIso8601String(),
            'capture_path' => '/api/registration/capture-page']);
    }

    public function revokeHandoff(Request $request, string $id)
    {
        DB::transaction(function () use ($request, $id) {
            $session = $this->owner($request, $id, true);
            // Invalidate any in-flight upload from the old capture link as well.
            $values = ['capture_hash' => null, 'capture_expires_at' => null];
            if ($session->license_started_at) $values += ['license_run_id' => (string) Str::uuid(), 'license_started_at' => null, 'license_result' => null];
            $session->forceFill($values)->save();
        });
        return response()->json(['message' => 'The phone capture link was cancelled.']);
    }

    public function captureStatus(Request $request, string $id)
    {
        $session = $this->capture($request, $id); $this->requireVerified($session);
        return response()->json(['data' => $this->payload($session)]);
    }

    public function upload(Request $request, string $id, RegistrationLicenseCheck $checker)
    {
        return $this->checkPhoto($request, $id, $checker, false);
    }

    public function captureUpload(Request $request, string $id, RegistrationLicenseCheck $checker)
    {
        return $this->checkPhoto($request, $id, $checker, true);
    }

    private function checkPhoto(Request $request, string $id, RegistrationLicenseCheck $checker, bool $capture)
    {
        // Authenticate and verify the email before accepting or reading any upload.
        $session = $capture ? $this->capture($request, $id) : $this->owner($request, $id);
        $this->requireVerified($session);
        $request->validate(['license_file' => ['required', 'file', 'mimes:jpg,jpeg,png', 'mimetypes:image/jpeg,image/png', 'max:5120']]);
        $file = $request->file('license_file'); $size = @getimagesize($file->getRealPath());
        if (!$size || $size[0] * $size[1] > 20000000 || max($size[0], $size[1]) > 12000) throw ValidationException::withMessages(['license_file' => 'Use a JPG/PNG image of at most 20 megapixels and 5 MB.']);
        $run = (string) Str::uuid(); $path = $file->store('registration-licenses', 'private');
        abort_unless($path, 503, 'The photo could not be stored. Try again.');
        try {
            $session = DB::transaction(function () use ($request, $id, $capture, $run) {
                $s = $capture ? $this->capture($request, $id, true) : $this->owner($request, $id, true);
                $this->requireVerified($s);
                abort_if($s->license_started_at && $s->license_started_at->gt(now()->subMinutes(2)), 409, 'A license check is already running. Wait for its result.');
                $s->forceFill(['license_run_id' => $run, 'license_started_at' => now(), 'license_result' => null])->save();
                return $s;
            });
            try { $result = $checker->read(Storage::disk('private')->path($path), (string) $file->getMimeType(), $session->draft); }
            catch (Throwable $error) {
                report($error);
                $result = ['status' => 'unavailable', 'message' => 'The license reader could not complete. Try again shortly.', 'fields' => [], 'checks' => [], 'reasons' => [], 'policy_version' => RegistrationLicenseCheck::POLICY];
            }
            $oldPath = null;
            $session = DB::transaction(function () use ($request, $id, $capture, $run, $path, $result, &$oldPath) {
                $s = $capture ? $this->capture($request, $id, true) : $this->owner($request, $id, true);
                $this->requireVerified($s);
                abort_unless($s->license_run_id === $run, 409, 'This check was replaced or cancelled. Use the latest result.');
                $oldPath = $s->document_path;
                $s->forceFill(['document_path' => $path, 'document_hash' => hash_file('sha256', Storage::disk('private')->path($path)), 'license_result' => $result, 'license_started_at' => null])->save();
                if ($result['status'] === 'passed') $s->forceFill(['capture_hash' => null, 'capture_expires_at' => null])->save();
                return $s;
            });
        } catch (Throwable $error) { Storage::disk('private')->delete($path); throw $error; }
        if ($oldPath && $oldPath !== $path) Storage::disk('private')->delete($oldPath);
        return response()->json(['data' => $this->payload($session)]);
    }

    public function complete(Request $request)
    {
        $data = $request->validate([
            'session_id' => ['required', 'uuid'],
            'password' => ['bail', 'required', 'string', new \App\Rules\PasswordBytes, 'min:8', 'confirmed'],
            'agree' => ['required', 'accepted'],
            'email' => ['prohibited'], 'user_type' => ['prohibited'], 'role' => ['prohibited'],
            'license_number' => ['prohibited'], 'expiry_date' => ['prohibited'], 'license_expiry' => ['prohibited'],
            'first_name' => ['prohibited'], 'last_name' => ['prohibited'], 'birth_date' => ['prohibited'],
            'school_id_file' => ['prohibited'], 'license_file' => ['prohibited'],
        ]);
        try {
            [$user, $already] = DB::transaction(function () use ($request, $data) {
                $session = $this->owner($request, $data['session_id'], true);
                if ($session->consumed_at) return [User::findOrFail($session->user_id), true];
                $this->requireVerified($session);
                $draft = $session->draft; $check = $session->license_result;
                $this->requireSchoolId($session);
                abort_unless(!$session->license_started_at && ($check['status'] ?? '') === 'passed'
                    && ($check['policy_version'] ?? '') === RegistrationLicenseCheck::POLICY, 422, 'Pass the automatic license check before creating an account.');
                $fields = $check['fields'];
                abort_unless(($fields['expiry_date'] ?? '') >= CampusTime::date(), 422, 'Your license has expired. Upload a current license.');
                abort_unless($session->document_path && Storage::disk('private')->exists($session->document_path)
                    && hash_equals($session->document_hash ?? '', hash_file('sha256', Storage::disk('private')->path($session->document_path))), 422, 'The checked license photo is unavailable. Retake the photo.');
                $this->assertIdentifiersAvailable($draft);
                $number = LicenseValidationRules::formatLicenseNumber($fields['license_number']);
                if (DB::table('driver_licenses')->whereRaw("REPLACE(REPLACE(UPPER(license_number), '-', ''), ' ', '') = ?", [LicenseValidationRules::normalizeLicenseNumber($number)])->exists()) {
                    throw ValidationException::withMessages(['license_file' => 'This license number is already registered. Sign in or recover the original account.']);
                }
                $middleName = $fields['middle_name'] ?? $draft['middle_name'] ?? null;
                $roleCode = $draft['registration_category'] === 'employee' ? 'employee' : 'student';
                $role = Role::where('role_code', $roleCode)->where('is_active', true)->firstOrFail();
                $user = User::create(['user_code' => $draft['user_code'], 'email' => $session->email, 'password' => $data['password'], 'user_type' => $roleCode, 'account_status' => 'active']);
                $user->forceFill(['email_verified_at' => $session->email_verified_at])->save();
                $user->roles()->attach($role->role_id, ['assigned_at' => now()]);
                $user->profile()->create([
                    'department_id' => $draft['department_id'], 'registration_category' => $draft['registration_category'], 'registration_unit' => $draft['department'],
                    'first_name' => $draft['first_name'], 'last_name' => $draft['last_name'], 'middle_name' => $middleName, 'suffix' => $draft['suffix'] ?? null,
                    'school_id_document_path' => $draft['school_id']['path'], 'school_id_check_status' => 'matched', 'school_id_check_result' => $draft['school_id']['evidence'],
                    'birth_date' => $fields['birth_date'], 'contact_number' => $draft['contact_number'], 'year_level' => $draft['year_level'] ?? null, 'section' => $draft['section'] ?? null,
                ]);
                $driver = DB::table('drivers')->insertGetId(['user_id' => $user->user_id, 'first_name' => $draft['first_name'], 'last_name' => $draft['last_name'],
                    'middle_name' => $middleName, 'contact_number' => $draft['contact_number'], 'driver_status' => 'active', 'created_at' => now(), 'updated_at' => now()], 'driver_id');
                $license = DriverLicense::create(['driver_id' => $driver, 'license_number' => $number, 'license_type' => null, 'expiry_date' => $fields['expiry_date'],
                    'license_image_path' => $session->document_path, 'original_file_name' => 'registration-license.'.pathinfo($session->document_path, PATHINFO_EXTENSION), 'license_status' => 'valid', 'verification_status' => 'verified',
                    'verification_source' => 'automatic', 'verification_run_id' => $session->license_run_id, 'ocr_result' => $check['evidence'], 'submitted_at' => now(), 'checked_at' => now()]);
                LicenseActivity::audit('license_auto_verified', $license, [], $user, $request);
                ActivityAudit::record($request, $user, 'account_auto_approved', 'users', (int) $user->user_id, null,
                    ['account_status' => 'active', 'approval_method' => 'institutional_id_verified_email_and_license_ocr', 'policy_version' => RegistrationLicenseCheck::POLICY, 'registration_category' => $draft['registration_category']]);
                // The persistent license now owns the private image. Draft cleanup must never delete it.
                $session->forceFill(['consumed_at' => now(), 'user_id' => $user->user_id, 'draft' => [], 'license_result' => null, 'document_path' => null, 'document_hash' => null,
                    'capture_hash' => null, 'capture_expires_at' => null, 'code_hash' => null])->save();
                return [$user, false];
            });
        } catch (\Illuminate\Database\QueryException $error) {
            if (!in_array((string) $error->getCode(), ['23000', '23505'], true)) throw $error;
            throw ValidationException::withMessages(['registration' => 'An email, institutional ID or license number is already registered. Sign in or recover the existing account.']);
        }
        return response()->json(['message' => $already ? 'Registration is already complete. Sign in.' : 'Your email and license checks passed. Your account is active; you can now sign in.',
            'user' => ['id' => (string) $user->user_id, 'email' => $user->email, 'accountStatus' => $user->account_status, 'emailVerified' => $user->hasVerifiedEmail()]], $already ? 200 : 201);
    }

    public function cancel(Request $request, string $id)
    {
        $path = DB::transaction(function () use ($request, $id) {
            $s = $this->owner($request, $id, true);
            abort_if($s->consumed_at, 409, 'Registration is already complete.');
            $paths = array_filter([$s->document_path, $s->draft['school_id']['path'] ?? null]);
            $s->forceFill(['expires_at' => now(), 'capture_hash' => null, 'code_hash' => null, 'draft' => [], 'document_path' => null, 'license_result' => null])->save();
            return $paths;
        });
        if ($path) Storage::disk('private')->delete($path);
        return response()->json(['message' => 'Registration draft cancelled.']);
    }

    public function capturePage()
    {
        $nonce = base64_encode(random_bytes(24));
        return response()->view('registration-capture', ['nonce' => $nonce])->withHeaders([
            'Content-Security-Policy' => "default-src 'none'; script-src 'nonce-{$nonce}'; style-src 'nonce-{$nonce}'; img-src blob:; connect-src 'self'; base-uri 'none'; form-action 'none'; frame-ancestors 'none'",
            'Permissions-Policy' => 'geolocation=(), microphone=()', 'X-Frame-Options' => 'DENY',
        ]);
    }

    private function owner(Request $request, string $id, bool $lock = false): RegistrationSession
    {
        $s = RegistrationSession::whereKey($id)->when($lock, fn ($q) => $q->lockForUpdate())->first();
        $key = $request->header('X-Registration-Key', '');
        abort_unless($s && strlen($key) === 64 && hash_equals($s->owner_hash, hash('sha256', $key)), 404, 'Registration session not found.');
        abort_if(now()->gte($s->expires_at), 410, 'This registration has expired. Start again.');
        return $s;
    }

    private function capture(Request $request, string $id, bool $lock = false): RegistrationSession
    {
        $s = RegistrationSession::whereKey($id)->when($lock, fn ($q) => $q->lockForUpdate())->first();
        $key = $request->header('X-Capture-Key', '');
        abort_unless($s && !$s->consumed_at && strlen($key) === 64 && $s->capture_hash && hash_equals($s->capture_hash, hash('sha256', $key)), 404, 'The photo link is invalid, replaced or already used. Return to your original device.');
        abort_if(now()->gte($s->expires_at) || !$s->capture_expires_at || now()->gte($s->capture_expires_at), 410, 'The photo link expired. Generate a new link on your original device.');
        return $s;
    }

    private function requireVerified(RegistrationSession $s): void
    {
        abort_unless($s->email_verified_at && str_ends_with($s->email, '@dyci.edu.ph'), 403, 'Verify your institutional email before photographing a license.');
        abort_if($s->consumed_at, 409, 'Registration is already complete. Sign in.');
        $this->requireSchoolId($s);
    }

    private function requireSchoolId(RegistrationSession $s): void
    {
        $school = $s->draft['school_id'] ?? [];
        abort_unless(($school['status'] ?? '') === 'matched' && ($school['policy_version'] ?? '') === InstitutionalIdAutofill::POLICY
            && !empty($school['path']) && Storage::disk('private')->exists($school['path'])
            && hash_equals($school['sha256'] ?? '', hash_file('sha256', Storage::disk('private')->path($school['path']))),
            422, 'Upload and read your institutional ID first. Start a new registration if this draft was created before the ID upload update.');
    }

    private function payload(RegistrationSession $s): array
    {
        $result = $s->license_result;
        if ($result) unset($result['evidence'], $result['policy_version']);
        return ['email_verified' => $s->email_verified_at !== null, 'expires_at' => $s->expires_at->toIso8601String(),
            'institutional_fields' => array_intersect_key($s->draft ?? [], array_flip(['first_name', 'last_name', 'middle_initial', 'suffix', 'user_code', 'department', 'year_level', 'section'])),
            'completed' => $s->consumed_at !== null, 'license_processing' => $s->license_started_at !== null && $s->license_started_at->gt(now()->subMinutes(2)), 'license' => $result];
    }

    private function assertIdentifiersAvailable(array $data): void
    {
        if (DB::table('users')->where('email', $data['email'])->exists()) throw ValidationException::withMessages(['email' => 'This email is already registered. Sign in or recover your account.']);
        if (DB::table('users')->whereRaw("REPLACE(REPLACE(UPPER(user_code), '-', ''), ' ', '') = ?", [InstitutionalIdRules::digits($data['user_code'])])->exists()) {
            throw ValidationException::withMessages(['user_code' => 'This institutional ID is already registered. Sign in or recover your account.']);
        }
    }

    private function sendCode(RegistrationSession $s): bool
    {
        if (!app()->environment('testing') && in_array(config('mail.default'), ['log', 'array', 'failover'], true)) return false;
        $code = (string) random_int(100000, 999999); $hash = Hash::make($code);
        DB::transaction(function () use ($s, $hash) {
            $row = RegistrationSession::whereKey($s->session_id)->lockForUpdate()->firstOrFail();
            $latest = RegistrationSession::where('email', $row->email)->whereNotNull('code_sent_at')->orderByDesc('code_sent_at')->first();
            abort_if($latest && $latest->code_sent_at->gt(now()->subMinute()), 429, 'Wait 60 seconds before requesting another code.');
            $row->forceFill(['code_hash' => $hash, 'code_attempts' => 0, 'code_sent_at' => now(), 'code_expires_at' => now()->addMinutes(10)])->save();
        });
        try { Notification::route('mail', $s->email)->notify(new RegistrationEmailCode($code)); return true; }
        catch (Throwable $error) {
            RegistrationSession::whereKey($s->session_id)->where('code_hash', $hash)->update(['code_hash' => null]);
            report($error); return false;
        }
    }
}
