<?php

namespace App\Services\Parking;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * The single rule for whether a Visitor QR pass admits a vehicle. Used by
 * the gate (ParkingAccessService), the staff verification screen, and the
 * development gate simulator, so they always agree.
 *
 * A pass is usable for entry only if it was issued from an approved visitor
 * request. Passes created before that rule (request_id NULL, "legacy") are
 * kept unchanged but never admit a vehicle; they can still be used to exit.
 */
class VisitorPassPolicy
{
    public const LEGACY_REASON = 'This pass was issued before visitor approval was required. Approve a visitor request to issue a new pass.';

    /** Why the pass cannot be used to enter now, or null when it can. */
    public static function entryProblem(object $pass, ?object $vehicle, CarbonImmutable $at): ?string
    {
        if (($pass->pass_type ?? 'visitor') !== 'visitor') {
            return 'This QR pass is not a visitor pass.';
        }

        if (!$pass->request_id) {
            return self::LEGACY_REASON;
        }

        $requestStatus = DB::table('visitor_requests')->where('request_id', $pass->request_id)->value('request_status');

        if ($requestStatus !== 'approved') {
            return 'The visitor request for this pass is ' . ($requestStatus ?? 'missing') . '.';
        }

        $now = $at->setTimezone(config('app.timezone'))->toDateTimeString();

        return match (true) {
            $pass->pass_status === 'revoked' => 'Visitor QR pass was revoked.',
            $pass->pass_status === 'used' || $pass->use_count >= $pass->max_uses => 'Visitor QR pass has already been used.',
            $pass->pass_status === 'expired' || $pass->valid_until < $now => 'Visitor QR pass has expired.',
            $pass->valid_from > $now => 'Visitor QR pass is not valid yet.',
            $vehicle && $pass->vehicle_id && (int) $pass->vehicle_id !== (int) $vehicle->vehicle_id => 'Visitor QR pass is for a different vehicle.',
            default => null,
        };
    }

    /**
     * Status to display: active, scheduled (not valid yet), used, expired,
     * revoked, or legacy (no approved request).
     */
    public static function displayStatus(object $pass, CarbonImmutable $at): string
    {
        $now = $at->setTimezone(config('app.timezone'))->toDateTimeString();

        return match (true) {
            $pass->pass_status === 'revoked' => 'revoked',
            $pass->pass_status === 'used' || $pass->use_count >= $pass->max_uses => 'used',
            $pass->pass_status === 'expired' || $pass->valid_until < $now => 'expired',
            !$pass->request_id => 'legacy',
            $pass->valid_from > $now => 'scheduled',
            default => 'active',
        };
    }
}
