<?php

namespace App\Services\Registration;

use App\Models\User;
use App\Support\ActivityAudit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** Complete email verification and activate only current, server-checked registrations. */
class RegistrationActivation
{
    public static function identity(string $category, string $number, string $first, string $last, ?string $initial, ?string $suffix = null): string
    {
        return hash('sha256', json_encode([$category, InstitutionalIdRules::digits($number),
            trim(InstitutionalIdRules::name($first)), trim(InstitutionalIdRules::name($last)),
            trim(InstitutionalIdRules::name($initial ?? '')), InstitutionalIdRules::suffix($suffix)], JSON_UNESCAPED_UNICODE));
    }

    public function complete(User $user): User
    {
        return DB::transaction(function () use ($user) {
            $current = User::whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            // Replayed links must never revive a subsequently suspended/rejected account.
            if ($current->hasVerifiedEmail()) return $current;
            $current->markEmailAsVerified();
            $profile = $current->profile()->lockForUpdate()->first();
            $check = $profile?->school_id_check_result ?? [];
            $role = $profile?->registration_category === 'employee' ? 'employee' : 'student';
            $eligible = $current->account_status === 'pending'
                && in_array($profile?->registration_category, ['college', 'employee'], true)
                && $current->user_type === $role
                && $profile->school_id_check_status === 'matched'
                && ($check['status'] ?? '') === 'matched'
                && ($check['policy_version'] ?? '') === InstitutionalIdRules::POLICY
                && ($check['activation_policy'] ?? '') === 'matched-id-and-email-v1'
                && !empty($check['checks']) && collect($check['checks'])->every(fn ($c) => ($c['result'] ?? '') === 'pass')
                && empty($check['manual_review_requested'])
                && hash_equals($check['identity_hash'] ?? '', self::identity($profile->registration_category, $current->user_code,
                    $profile->first_name, $profile->last_name, $profile->middle_name, $profile->suffix))
                && $profile->school_id_document_path && Storage::disk('private')->exists($profile->school_id_document_path)
                && $current->roles()->where('role_code', $role)->where('is_active', true)->exists()
                && !$current->roles()->whereNotIn('role_code', [$role])->exists();
            if ($eligible) {
                $current->forceFill(['account_status' => 'active'])->save();
                ActivityAudit::record(request(), $current, 'account_auto_approved', 'users', (int) $current->getKey(),
                    ['account_status' => 'pending'], ['account_status' => 'active', 'approval_method' => 'matched_id_and_verified_email', 'policy_version' => InstitutionalIdRules::POLICY]);
            }
            return $current;
        });
    }
}
