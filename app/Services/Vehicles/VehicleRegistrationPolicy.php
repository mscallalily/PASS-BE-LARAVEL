<?php

namespace App\Services\Vehicles;

use App\Models\User;
use App\Support\CampusTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Validate submission eligibility. Vehicle approval always requires a staff decision. */
class VehicleRegistrationPolicy
{
    public function validatePlate(string $plate): void
    {
        $key = \App\Support\PlateNumber::key($plate);
        if (!preg_match('/^(?=.*[A-Z])(?=.*[0-9])[A-Z0-9]{4,10}$/D', $key)) {
            throw ValidationException::withMessages(['plate_number' => 'Enter the plate as printed, using 4 to 10 letters and digits. Spaces or a hyphen may separate them.']);
        }
    }

    /** Rechecked under the owner lock before a registration is persisted. */
    public function driver(User $user, bool $lock = false): object
    {
        $owner = User::find($user->user_id);
        abort_unless($owner && $owner->account_status === 'active' && $owner->email_verified_at && $owner->roles()
            ->whereIn('role_code', ['student', 'faculty', 'employee'])->where('is_active', true)->exists(), 403, 'An active, email-verified regular account is required.');
        $query = DB::table('drivers')->where('user_id', $owner->user_id)->where('driver_status', 'active')->orderByDesc('driver_id');
        if ($lock) $query->lockForUpdate();
        $driver = $query->first();
        $query = DB::table('driver_licenses')->where('driver_id', $driver?->driver_id)->orderByDesc('license_id');
        if ($lock) $query->lockForUpdate();
        $license = $driver ? $query->first() : null;
        if (!$license || $license->verification_status !== 'verified' || $license->license_status !== 'valid' || !$license->expiry_date
            || substr($license->expiry_date, 0, 10) < CampusTime::date()) {
            throw ValidationException::withMessages(['license' => 'A verified, unexpired driver’s license is required. Complete Driver & License before submitting a vehicle.']);
        }
        return $driver;
    }

    public function authorization(int $vehicleId, int $areaId, int $userId, ?string $ip): void
    {
        $at = now();
        $id = DB::table('vehicle_authorizations')->insertGetId([
            'vehicle_id' => $vehicleId, 'parking_area_id' => $areaId, 'authorization_status' => 'pending',
            'valid_from' => CampusTime::date(), 'requested_at' => $at, 'reviewed_at' => null, 'reviewed_by' => null,
            'remarks' => 'Waiting for PASS staff to review the vehicle details and photo. Vehicle approval and RFID assignment remain separate.',
            'created_at' => $at, 'updated_at' => $at,
        ], 'authorization_id');
        DB::table('audit_logs')->insert([
            'user_id' => $userId, 'actor_label' => 'Vehicle owner', 'action' => 'vehicle_registration_submitted',
            'auditable_type' => 'vehicle_authorizations', 'auditable_id' => $id, 'ip_address' => $ip,
            'new_values' => json_encode(['authorization_status' => 'pending', 'vehicle_id' => $vehicleId, 'parking_area_id' => $areaId,
                'basis' => 'staff_review_required', 'plate_verified' => false, 'photo_verified' => false, 'ownership_verified' => false, 'authenticity_verified' => false]), 'created_at' => $at,
        ]);
    }
}
