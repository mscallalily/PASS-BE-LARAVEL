<?php

namespace App\Services\LicenseVerification;

use App\Models\DriverLicense;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Review history (audit_logs) and owner notifications (notifications) for
 * driver's license submissions.
 */
class LicenseActivity
{
    public const SYSTEM_ACTOR = 'system:license-check';

    /** Fields recorded in the review history. The license image is never copied here. */
    public const TRACKED = [
        'license_number',
        'expiry_date',
        'original_file_name',
        'verification_status',
        'verification_source',
        'verification_reason',
        'reviewed_by',
    ];

    public static function audit(string $action, DriverLicense $license, array $old, ?User $actor = null, ?Request $request = null): void
    {
        DB::table('audit_logs')->insert([
            'user_id' => $actor?->user_id,
            'actor_label' => $actor?->user_code ?? self::SYSTEM_ACTOR,
            'action' => $action,
            'auditable_type' => DriverLicense::class,
            'auditable_id' => $license->license_id,
            'old_values' => $old ? json_encode($old) : null,
            'new_values' => json_encode(self::snapshot($license)),
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? Str::limit((string) $request->userAgent(), 252) : null,
            'created_at' => now(),
        ]);
    }

    public static function snapshot(DriverLicense $license): array
    {
        $values = $license->only(self::TRACKED);

        if (isset($values['expiry_date']) && $values['expiry_date'] instanceof \DateTimeInterface) {
            $values['expiry_date'] = $values['expiry_date']->format('Y-m-d');
        }

        return $values;
    }

    /**
     * Tell the license owner about a status change they need to know about.
     */
    public static function notifyOwner(DriverLicense $license, string $status, ?string $reason = null, bool $automatic = false): void
    {
        $userId = DB::table('drivers')->where('driver_id', $license->driver_id)->value('user_id');

        if (!$userId) {
            return;
        }

        [$title, $body] = match ($status) {
            'needs_correction' => [
                "Driver's license needs correction",
                ($automatic
                    ? 'Your driver\'s license was automatically returned because it did not pass the document check. '
                    : 'PASS staff returned your driver\'s license submission. ')
                . 'Please correct the following and resubmit: ' . ($reason ?: 'see the Driver & License page for details.'),
            ],
            'pending_review' => [
                "Driver's license submitted for review",
                'Your driver\'s license passed the preliminary document check and is waiting for review by PASS staff.',
            ],
            'verified' => [
                "Driver's license verified",
                'PASS staff verified your driver\'s license. This does not by itself authorize a vehicle or assign an RFID.',
            ],
            default => [null, null],
        };

        if (!$title) {
            return;
        }

        DB::table('notifications')->insert([
            'user_id' => $userId,
            'notification_type' => "license_{$status}",
            'title' => $title,
            'body' => $body,
            'payload' => json_encode(['screen' => 'driver_license', 'license_id' => $license->license_id]),
            'related_type' => DriverLicense::class,
            'related_id' => $license->license_id,
            'channel' => 'in_app',
            'is_read' => false,
            'sent_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
