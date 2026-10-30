<?php

namespace App\Services\Vehicles;

use App\Models\VehicleAuthorization;
use App\Support\CampusTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Account, driver and record requirements a campus vehicle authorization must
 * meet before it can be approved, independent of who or what approves it.
 * Shared by suspension lifting and the automatic OR/CR approval so neither
 * path can approve under weaker rules.
 */
class VehicleApprovalEligibility
{
    /**
     * Reasons the authorization cannot return to approved after a suspension. Empty when eligible.
     *
     * @return string[]
     */
    public function liftBlockers(VehicleAuthorization $authorization): array
    {
        $today = CampusTime::date();
        $blockers = [];

        $vehicle = DB::table('vehicles')
            ->where('vehicle_id', $authorization->vehicle_id)
            ->first();

        if (!$vehicle || $vehicle->deleted_at !== null || $vehicle->registration_status !== 'registered') {
            return ['The vehicle is no longer registered.'];
        }

        if ($authorization->valid_until && $authorization->valid_until < $today) {
            $blockers[] = "The authorization validity ended on {$authorization->valid_until}.";
        }

        if ($vehicle->owner_user_id) {
            $ownerStatus = DB::table('users')
                ->where('user_id', $vehicle->owner_user_id)
                ->whereNull('deleted_at')
                ->value('account_status');

            if ($ownerStatus !== 'active') {
                $blockers[] = "The owner's account is " . ($ownerStatus ?? 'missing') . '.';
            }
        } elseif (DB::table('visitors')->where('visitor_id', $vehicle->owner_visitor_id)->value('is_blacklisted')) {
            $blockers[] = 'The visitor who owns this vehicle is blacklisted.';
        }

        $latestId = DB::table('vehicle_authorizations')->where('vehicle_id', $authorization->vehicle_id)->max('authorization_id');
        if ((int) $latestId !== (int) $authorization->authorization_id) {
            $blockers[] = 'A newer authorization exists. Review the current vehicle record.';
        }

        // When drivers are linked, at least one must be active, still
        // authorized for this vehicle, and hold a verified, valid, unexpired license
        // (the same rule the gate applies at entry).
        $hasLinkedDrivers = DB::table('vehicle_driver')
            ->where('vehicle_id', $authorization->vehicle_id)
            ->exists();

        if ($hasLinkedDrivers) {
            $hasEligibleDriver = DB::table('vehicle_driver as vd')
                ->join('drivers as d', 'vd.driver_id', '=', 'd.driver_id')
                ->join('driver_licenses as dl', 'dl.driver_id', '=', 'd.driver_id')
                ->where('vd.vehicle_id', $authorization->vehicle_id)
                ->where('d.driver_status', 'active')
                ->where(fn ($q) => $q->whereNull('vd.authorized_until')->orWhere('vd.authorized_until', '>=', $today))
                ->where('dl.license_status', 'valid')
                ->where('dl.verification_status', 'verified')
                ->where(fn ($q) => $q->whereNull('dl.expiry_date')->orWhere('dl.expiry_date', '>=', $today))
                ->exists();

            if (!$hasEligibleDriver) {
                $blockers[] = 'No linked driver has an active record and a verified, valid, unexpired license.';
            }
        }

        return $blockers;
    }

    /**
     * Requirements for approving a pending registration: everything above plus
     * a linked driver whose LATEST license is verified and unexpired (the rule
     * previously applied by staff approval). A vehicle photo is no longer
     * required: it was stored for reference only and never verified the
     * plate; the plate comes from the OR and CR, which must agree.
     *
     * @return string[]
     */
    public function approvalBlockers(VehicleAuthorization $authorization): array
    {
        $blockers = $this->liftBlockers($authorization);
        $vehicle = DB::table('vehicles')->where('vehicle_id', $authorization->vehicle_id)->first();
        if (!$vehicle) return $blockers;

        $today = CampusTime::date();
        $eligibleDriver = DB::table('vehicle_driver as vd')
            ->join('drivers as d', 'vd.driver_id', '=', 'd.driver_id')
            ->join('driver_licenses as dl', 'd.driver_id', '=', 'dl.driver_id')
            ->where('vd.vehicle_id', $vehicle->vehicle_id)->where('d.driver_status', 'active')
            ->whereRaw('dl.license_id = (SELECT MAX(latest.license_id) FROM driver_licenses latest WHERE latest.driver_id = d.driver_id)')
            ->where('dl.verification_status', 'verified')->where('dl.license_status', 'valid')
            ->where('dl.expiry_date', '>=', $today)
            ->where(fn ($q) => $q->whereNull('vd.authorized_from')->orWhere('vd.authorized_from', '<=', $today))
            ->where(fn ($q) => $q->whereNull('vd.authorized_until')->orWhere('vd.authorized_until', '>=', $today))
            ->exists();
        if (!$eligibleDriver) $blockers[] = 'The latest license of an eligible linked driver must be verified and unexpired.';

        return array_values(array_unique($blockers));
    }
}
