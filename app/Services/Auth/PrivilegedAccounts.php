<?php

namespace App\Services\Auth;

use App\Models\Role;
use App\Models\User;
use App\Notifications\PrivilegedAccountInvitation;
use App\Support\ActivityAudit;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class PrivilegedAccounts
{
    private const TABLE = 'privileged_account_invitations';

    public function state(User $user): ?array
    {
        $record = DB::table(self::TABLE)->where('user_id', $user->getKey())->first();
        if (!$record) return null; // Existing administrators keep their current login flow.
        return [
            'required' => !$record->completed_at,
            'step' => $record->completed_at ? 'complete' : ($record->email_activated_at ? 'change_password' : 'verify_email'),
            'expired' => !$record->completed_at && now()->gte(Carbon::parse($record->expires_at)),
            'expiresAt' => Carbon::parse($record->expires_at)->toIso8601String(),
            'codeExpiresAt' => $record->code_expires_at ? Carbon::parse($record->code_expires_at)->toIso8601String() : null,
            'resendAvailableAt' => $record->sent_at ? Carbon::parse($record->sent_at)->addMinute()->toIso8601String() : null,
            'delivery' => $record->delivery_failed_at ? 'failed' : ($record->delivered_at ? 'accepted' : 'pending'),
        ];
    }

    /** Called by AccountAccess before its /me and /logout exceptions. */
    public function guard(Request $request): ?JsonResponse
    {
        $user = $request->user();
        if (!$user) return null;
        $state = $this->state($user);
        $token = $user->currentAccessToken();
        $restricted = $token && !$token->can('*') && $token->can('account:setup');
        if (!$restricted && !($state['required'] ?? false)) return null;
        if ($request->is('api/me', 'api/logout', 'api/account/setup', 'api/account/setup/*')) return null;
        return response()->json(['message' => 'Complete email activation and choose your password before using PASS.', 'code' => 'account_setup_required'], 403);
    }

    /** Password has already been checked by AuthController. No dashboard token is issued. */
    public function setupLogin(Request $request, User $user): ?JsonResponse
    {
        return DB::transaction(function () use ($request, $user) {
            $current = User::whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            $record = DB::table(self::TABLE)->where('user_id', $current->getKey())->first();
            if (!$record) return null;
            if (!Hash::check((string) $request->input('password'), $current->password)) {
                return response()->json(['message' => 'Invalid email/ID number or password.'], 401);
            }
            if ($record->completed_at) return null;
            if ($current->account_status !== 'active') {
                return response()->json(['message' => 'Your account is not active. Contact the PASS office.', 'code' => 'account_not_active'], 403);
            }
            if (!$record->delivered_at || $record->delivery_failed_at || now()->gte(Carbon::parse($record->expires_at))) {
                return response()->json(['message' => 'This invitation is unavailable or has expired. Ask your administrator to resend it.', 'code' => 'invitation_expired'], 403);
            }
            $current->forceFill(['last_login_at' => now()])->save();
            $token = $current->createToken('account-setup', ['account:setup', 'setup-generation:'.$record->generation], now()->addMinutes(30));
            ActivityAudit::record($request, $current, 'account_setup_login', 'users', (int) $current->getKey());
            $current->loadMissing('profile', 'roles');
            return response()->json(['token' => $token->plainTextToken, 'user' => [
                'id' => (string) $current->getKey(),
                'name' => trim(($current->profile?->first_name ?? '').' '.($current->profile?->last_name ?? '')),
                'email' => $current->email, 'idNum' => $current->user_code,
                'role' => $current->roles->first()?->role_code ?? $current->user_type,
                'accountStatus' => $current->account_status, 'emailVerified' => $current->hasVerifiedEmail(),
                'accountSetup' => $this->state($current),
            ]])->header('Cache-Control', 'no-store, private');
        }, 3);
    }

    public function assertAdmin(User $actor): void
    {
        abort_unless($actor->isAdmin() && !($this->state($actor)['required'] ?? false), 403, 'Only a fully activated administrator can invite accounts.');
    }

    public function create(Request $request, array $data): array
    {
        $actor = $request->user();
        $this->assertAdmin($actor);
        $password = Str::password(24, symbols: false); // Avoid Markdown characters changing how the email renders.
        $code = (string) random_int(100000, 999999);
        $generation = (string) Str::uuid();
        $user = DB::transaction(function () use ($request, $data, $actor, $password, $code, $generation) {
            $this->assertAdmin(User::whereKey($actor->getKey())->lockForUpdate()->firstOrFail());
            $roleCode = $data['role'] === 'admin' ? 'admin' : 'security';
            $role = Role::where('role_code', $roleCode)->where('is_active', true)->first();
            if (!$role) throw ValidationException::withMessages(['role' => 'The selected role is not configured. Ask the PASS administrator.']);
            $user = User::create([
                'user_code' => Str::upper(trim($data['user_code'])), 'email' => Str::lower(trim($data['email'])),
                'password' => $password, 'user_type' => $roleCode, 'account_status' => 'active',
            ]);
            $user->profile()->create([
                'first_name' => Str::upper(trim($data['first_name'])), 'last_name' => Str::upper(trim($data['last_name'])),
                'contact_number' => $data['contact_number'] ?? null,
                'program_or_position' => $roleCode === 'admin' ? 'ADMINISTRATOR' : 'PARKING STAFF',
            ]);
            $user->roles()->attach($role->getKey(), ['assigned_at' => now(), 'assigned_by' => $actor->getKey()]);
            DB::table(self::TABLE)->insert([
                'user_id' => $user->getKey(), 'created_by' => $actor->getKey(), 'generation' => $generation, 'delivery_id' => $generation,
                'temporary_password_hash' => $user->password, 'code_hash' => Hash::make($code),
                'expires_at' => now()->addDay(), 'code_expires_at' => now()->addMinutes(10),
                'sent_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            ActivityAudit::record($request, $actor, 'privileged_account_invited', 'users', (int) $user->getKey(), null,
                ['role' => $data['role'], 'setup_required' => true]);
            return $user;
        }, 3);
        return [$user, $this->deliver($user, $generation, $password, $code)];
    }

    public function resendInvitation(Request $request, int $id): array
    {
        $this->assertAdmin($request->user());
        $password = Str::password(24, symbols: false);
        $code = (string) random_int(100000, 999999);
        $generation = (string) Str::uuid();
        $user = DB::transaction(function () use ($request, $id, $password, $code, $generation) {
            $actor = User::whereKey($request->user()->getKey())->lockForUpdate()->firstOrFail();
            $this->assertAdmin($actor);
            $user = User::whereKey($id)->lockForUpdate()->firstOrFail();
            $record = DB::table(self::TABLE)->where('user_id', $id)->lockForUpdate()->first();
            abort_unless($record && !$record->completed_at, 409, 'Only an incomplete invitation can be resent.');
            abort_unless($user->account_status === 'active', 409, 'This account is not active.');
            $this->cooldown($record);
            $user->forceFill(['password' => $password, 'email_verified_at' => null, 'remember_token' => Str::random(60)])->save();
            $user->tokens()->delete();
            DB::table(self::TABLE)->where('user_id', $id)->update([
                'generation' => $generation, 'delivery_id' => $generation, 'temporary_password_hash' => $user->password,
                'code_hash' => Hash::make($code), 'attempts' => 0, 'expires_at' => now()->addDay(),
                'code_expires_at' => now()->addMinutes(10), 'sent_at' => now(), 'delivered_at' => null,
                'delivery_failed_at' => null, 'email_activated_at' => null, 'updated_at' => now(),
            ]);
            ActivityAudit::record($request, $actor, 'privileged_invitation_resent', 'users', $id);
            return $user;
        }, 3);
        return [$user, $this->deliver($user, $generation, $password, $code)];
    }

    public function resendCode(Request $request): bool
    {
        $code = (string) random_int(100000, 999999);
        $deliveryId = (string) Str::uuid();
        $user = DB::transaction(function () use ($request, $code, $deliveryId) {
            [$user, $record] = $this->lockedSetup($request);
            abort_if($record->email_activated_at, 409, 'Email is already activated. Choose your password next.');
            $this->cooldown($record);
            DB::table(self::TABLE)->where('user_id', $user->getKey())->update([
                'code_hash' => Hash::make($code), 'attempts' => 0, 'code_expires_at' => now()->addMinutes(10),
                'delivery_id' => $deliveryId,
                'sent_at' => now(), 'delivered_at' => null, 'delivery_failed_at' => null, 'updated_at' => now(),
            ]);
            return $user;
        }, 3);
        return $this->deliver($user, $deliveryId, null, $code);
    }

    public function verify(Request $request, string $code): bool
    {
        // Return failure instead of throwing in the transaction: failed attempts must commit.
        return DB::transaction(function () use ($request, $code) {
            [$user, $record] = $this->lockedSetup($request);
            if ($record->email_activated_at) return false;
            if (!$record->delivered_at || $record->delivery_failed_at || !$record->code_hash || $record->attempts >= 5 || now()->gte(Carbon::parse($record->code_expires_at))) return false;
            if (!Hash::check($code, $record->code_hash)) {
                DB::table(self::TABLE)->where('user_id', $user->getKey())->increment('attempts');
                return false;
            }
            $user->forceFill(['email_verified_at' => now()])->save();
            DB::table(self::TABLE)->where('user_id', $user->getKey())->update([
                'email_activated_at' => now(), 'code_hash' => null, 'code_expires_at' => null, 'updated_at' => now(),
            ]);
            ActivityAudit::record($request, $user, 'privileged_email_activated', 'users', (int) $user->getKey());
            return true;
        }, 3);
    }

    public function complete(Request $request, string $password): string
    {
        return DB::transaction(function () use ($request, $password) {
            [$user, $record] = $this->lockedSetup($request);
            abort_unless($record->email_activated_at && $user->hasVerifiedEmail(), 403, 'Verify your activation code first.');
            if (Hash::check($password, $record->temporary_password_hash) || Hash::check($password, $user->password)) {
                throw ValidationException::withMessages(['password' => 'Choose a new password that differs from the temporary or current password.']);
            }
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            $user->tokens()->delete();
            DB::table(self::TABLE)->where('user_id', $user->getKey())->update([
                'completed_at' => now(), 'temporary_password_hash' => '', 'code_hash' => null, 'updated_at' => now(),
            ]);
            // Reset links requested during onboarding must not work after completion.
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            ActivityAudit::record($request, $user, 'privileged_account_setup_completed', 'users', (int) $user->getKey());
            return $user->createToken('web')->plainTextToken;
        }, 3);
    }

    private function lockedSetup(Request $request): array
    {
        $user = User::whereKey($request->user()->getKey())->lockForUpdate()->firstOrFail();
        $record = DB::table(self::TABLE)->where('user_id', $user->getKey())->lockForUpdate()->first();
        abort_unless($record && !$record->completed_at && $user->account_status === 'active', 409, 'Account setup is not available.');
        abort_if(now()->gte(Carbon::parse($record->expires_at)), 403, 'Your invitation has expired. Ask your administrator to resend it.');
        // A fresh Eloquent instance has no token; the authenticated request owns that token.
        $token = $request->user()->currentAccessToken();
        abort_unless($token && $token->can('account:setup') && $token->can('setup-generation:'.$record->generation), 403, 'Sign in again with your invitation credentials.');
        return [$user, $record];
    }

    private function cooldown(object $record): void
    {
        if ($record->sent_at && Carbon::parse($record->sent_at)->addMinute()->isFuture()) {
            throw ValidationException::withMessages(['email' => 'Wait 60 seconds before requesting another email.']);
        }
    }

    private function deliver(User $user, string $deliveryId, ?string $password, string $code): bool
    {
        try {
            if (!app()->environment('testing') && in_array(config('mail.default'), ['array', 'log', 'failover'], true)) {
                throw new RuntimeException('A delivering mail transport is required.');
            }
            $origin = rtrim((string) config('app.frontend_url', env('FRONTEND_URL', '')), '/');
            $parts = parse_url($origin);
            if (!$parts || empty($parts['host']) || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])
                || !in_array($parts['scheme'] ?? '', app()->environment('local', 'testing') ? ['http', 'https'] : ['https'], true)
                || !empty($parts['path'])) {
                throw new RuntimeException('Configure FRONTEND_URL to the trusted frontend origin.');
            }
            Notification::sendNow($user, new PrivilegedAccountInvitation($password, $code, $origin.'/login'));
            $updated = DB::table(self::TABLE)->where('user_id', $user->getKey())->where('delivery_id', $deliveryId)
                ->update(['delivered_at' => now(), 'delivery_failed_at' => null, 'updated_at' => now()]);
            return $updated === 1;
        } catch (Throwable $exception) {
            // SMTP exceptions can contain protocol details. Log only the exception class, never the email body/secrets.
            logger()->warning('PASS invitation delivery failed.', ['user_id' => $user->getKey(), 'exception_type' => get_class($exception)]);
            DB::table(self::TABLE)->where('user_id', $user->getKey())->where('delivery_id', $deliveryId)
                ->update(['delivery_failed_at' => now(), 'updated_at' => now()]);
            return false;
        }
    }
}
