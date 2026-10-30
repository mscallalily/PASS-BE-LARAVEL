<?php

namespace App\Services\Rfid;

use App\Models\User;
use App\Models\VehicleAuthorization;
use App\Services\Vehicles\VehicleApprovalEligibility;
use App\Support\CampusTime;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Admin RFID inventory: physical stickers registered from the existing USB
 * reader (rfid_tags.inventory_registered_at), assigned automatically to new,
 * eligible campus registrations (vehicles.rfid_requested_at) and issued by
 * Admin. Available → Assigned (database) → Issued (physical hand-over).
 *
 * Existing tags, assignments and vehicles without these markers are never
 * touched. Nothing here bypasses the gate rules in ParkingAccessService.
 */
class RfidInventory
{
    /** One 96-bit EPC as typed by the USB reader (same frame as tools/rfid ScanState::frame). */
    public const READER_UID = '/^[A-F0-9]{24}$/D';
    /** A real UID typed by hand when the reader cannot be used. */
    public const MANUAL_UID = '/^[A-F0-9]{8,50}$/D';

    public function __construct(private VehicleApprovalEligibility $eligibility) {}

    public static function normalize(string $uid): string
    {
        return strtoupper(preg_replace('/[\s:-]+/', '', trim($uid)));
    }

    /** @return array{rfid_id:int, assigned_plate:?string} */
    public function register(string $rawUid, string $source, User $admin): array
    {
        $uid = self::normalize($rawUid);
        if (!preg_match($source === 'reader' ? self::READER_UID : self::MANUAL_UID, $uid)) {
            throw ValidationException::withMessages(['tag_uid' => $source === 'reader'
                ? 'Incomplete scan. Present one sticker so the reader sends its full 24-character EPC, then Enter.'
                : 'Enter the sticker UID exactly as read: 8 to 50 hexadecimal characters (0-9, A-F).']);
        }
        try {
            $id = DB::transaction(function () use ($uid, $source, $admin) {
                $existing = DB::table('rfid_tags')->where('tag_uid', $uid)->lockForUpdate()->first();
                if ($existing) throw ValidationException::withMessages(['tag_uid' => $this->duplicateMessage($existing)]);
                $id = DB::table('rfid_tags')->insertGetId([
                    'tag_uid' => $uid, 'tag_type' => 'sticker', 'tag_status' => 'available', 'acquired_at' => CampusTime::date(),
                    'inventory_registered_at' => now(), 'inventory_registered_by' => $admin->user_id, 'inventory_source' => $source,
                    'created_at' => now(), 'updated_at' => now(),
                ], 'rfid_id');
                $this->audit($admin, 'rfid_inventory_registered', 'rfid_tags', $id, ['tag_uid' => $uid, 'source' => $source]);
                return $id;
            }, 3);
        } catch (QueryException $e) {
            if (($e->errorInfo[1] ?? null) !== 1062) throw $e;
            throw ValidationException::withMessages(['tag_uid' => 'This UID was registered by another request. Refresh the inventory.']);
        }
        // A new sticker goes to the earliest registration still waiting for one.
        $assigned = $this->assignPending(1);
        return ['rfid_id' => $id, 'assigned_plate' => $assigned[0] ?? null];
    }

    private function duplicateMessage(object $tag): string
    {
        $plate = DB::table('rfid_assignments as a')->join('vehicles as v', 'v.vehicle_id', '=', 'a.vehicle_id')
            ->where('a.rfid_id', $tag->rfid_id)->where('a.assignment_status', 'active')->value('v.plate_number');
        return match (true) {
            (bool) $plate => "This UID is already registered and assigned to {$plate}. It was not changed.",
            (bool) $tag->inventory_registered_at => 'This UID is already in the RFID inventory. It was not changed.',
            default => "This UID already exists as an RFID tag ({$tag->tag_status}). It was not added to the inventory or changed.",
        };
    }

    /**
     * Assign one available inventory sticker to a newly registered campus vehicle
     * that is approved and eligible. Returns the assignment ID, or null when the
     * vehicle does not qualify or no sticker is available (Pending RFID Assignment).
     */
    public function assignToVehicle(int $vehicleId): ?int
    {
        $reference = DB::table('vehicles')->where('vehicle_id', $vehicleId)->first();
        if (!$reference?->rfid_requested_at || !$reference->owner_user_id) return null;
        try {
            return DB::transaction(function () use ($reference, $vehicleId) {
                // Same lock order as RfidAssignmentController::store: owner, vehicle, tag.
                $owner = DB::table('users')->where('user_id', $reference->owner_user_id)->whereNull('deleted_at')->lockForUpdate()->first();
                $vehicle = DB::table('vehicles')->where('vehicle_id', $vehicleId)->lockForUpdate()->first();
                if (!$owner || !$vehicle || $this->blocker($vehicle, $owner)) return null;
                $tag = DB::table('rfid_tags')->whereNotNull('inventory_registered_at')->where('tag_status', 'available')
                    ->orderBy('inventory_registered_at')->orderBy('rfid_id')->limit(1)->lock('for update skip locked')->first();
                if (!$tag || DB::table('rfid_assignments')->where('rfid_id', $tag->rfid_id)->where('assignment_status', 'active')->exists()) return null;
                $id = DB::table('rfid_assignments')->insertGetId([
                    'rfid_id' => $tag->rfid_id, 'user_id' => $owner->user_id, 'vehicle_id' => $vehicle->vehicle_id,
                    'assigned_by' => null, 'assigned_at' => now(), 'assignment_source' => 'inventory', 'assignment_status' => 'active',
                    'remarks' => 'Assigned automatically from the RFID inventory after vehicle approval. Awaiting physical issuance by Admin.',
                    'created_at' => now(), 'updated_at' => now(),
                ], 'assignment_id');
                DB::table('rfid_tags')->where('rfid_id', $tag->rfid_id)->update(['tag_status' => 'assigned', 'updated_at' => now()]);
                DB::table('user_vehicle_selections')->insertOrIgnore(['user_id' => $owner->user_id, 'vehicle_id' => $vehicle->vehicle_id, 'updated_at' => now()]);
                DB::table('audit_logs')->insert([
                    'user_id' => null, 'actor_label' => 'system:rfid-inventory', 'action' => 'rfid_auto_assigned',
                    'auditable_type' => 'rfid_assignments', 'auditable_id' => $id,
                    'new_values' => json_encode(['tag_uid' => $tag->tag_uid, 'vehicle_id' => $vehicle->vehicle_id, 'user_id' => $owner->user_id]), 'created_at' => now(),
                ]);
                DB::table('notifications')->insert([
                    'user_id' => $owner->user_id, 'notification_type' => 'rfid_assigned', 'title' => 'RFID sticker assigned',
                    'body' => "An RFID sticker was assigned to {$vehicle->plate_number}. Collect it from the PASS Admin office; it works at the gate after Admin confirms the hand-over.",
                    'payload' => json_encode(['screen' => 'vehicles', 'vehicle_id' => $vehicle->vehicle_id]),
                    'related_type' => 'rfid_assignments', 'related_id' => $id,
                    'channel' => 'in_app', 'is_read' => false, 'sent_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                ]);
                return $id;
            }, 3);
        } catch (QueryException $e) {
            // The unique active-assignment index stopped a concurrent assignment of the same sticker.
            if (($e->errorInfo[1] ?? null) === 1062) return null;
            throw $e;
        }
    }

    /**
     * Why this vehicle cannot receive an inventory sticker now; null when it can.
     * Requires the new-registration marker, an active campus owner, the current
     * approved authorization and the existing approval rules (driver, license).
     */
    public function blocker(object $vehicle, object $owner): ?string
    {
        $today = CampusTime::date();
        if (!$vehicle->rfid_requested_at || $vehicle->deleted_at || $vehicle->registration_status !== 'registered' || !$vehicle->owner_user_id) return 'Not a new campus registration.';
        if ($owner->account_status !== 'active' || !in_array($owner->user_type, ['student', 'faculty', 'employee'], true)) return 'The owner is not an active student, faculty member or employee.';
        $authorization = VehicleAuthorization::where('vehicle_id', $vehicle->vehicle_id)->orderByDesc('authorization_id')->first();
        if (!$authorization || $authorization->authorization_status !== 'approved'
            || ($authorization->valid_from && $authorization->valid_from > $today) || ($authorization->valid_until && $authorization->valid_until < $today)) return 'The vehicle authorization is not approved and current.';
        if ($this->eligibility->approvalBlockers($authorization)) return 'The vehicle no longer meets the approval requirements.';
        $latest = DB::table('rfid_assignments')->where('vehicle_id', $vehicle->vehicle_id)->orderByDesc('assignment_id')->first();
        // Once only: a revoked sticker is not replaced automatically; a lost one marked for replacement is.
        if (($latest && $latest->assignment_status !== 'replaced')
            || DB::table('rfid_assignments')->where('vehicle_id', $vehicle->vehicle_id)->where('assignment_status', 'active')->exists()) return 'The vehicle already had an RFID assignment.';
        return null;
    }

    /** Fill waiting registrations in registration order. @return string[] plates that received a sticker */
    public function assignPending(int $limit = 50): array
    {
        $plates = [];
        foreach ($this->pendingVehicles($limit * 2) as $vehicle) {
            if (count($plates) >= $limit) break;
            if (!DB::table('rfid_tags')->whereNotNull('inventory_registered_at')->where('tag_status', 'available')->exists()) break;
            if ($this->assignToVehicle($vehicle->vehicle_id)) $plates[] = $vehicle->plate_number;
        }
        return $plates;
    }

    /** New, approved campus registrations without a current sticker (Pending RFID Assignment). */
    public function pendingVehicles(int $limit = 200): \Illuminate\Support\Collection
    {
        return DB::table('vehicles as v')
            ->join('users as u', 'u.user_id', '=', 'v.owner_user_id')
            ->leftJoin('user_profiles as p', 'p.user_id', '=', 'u.user_id')
            ->whereNotNull('v.rfid_requested_at')->whereNull('v.deleted_at')->where('v.registration_status', 'registered')
            ->whereNull('u.deleted_at')->where('u.account_status', 'active')->whereIn('u.user_type', ['student', 'faculty', 'employee'])
            ->whereRaw("(SELECT a.authorization_status FROM vehicle_authorizations a WHERE a.vehicle_id = v.vehicle_id ORDER BY a.authorization_id DESC LIMIT 1) = 'approved'")
            ->whereRaw("COALESCE((SELECT r.assignment_status FROM rfid_assignments r WHERE r.vehicle_id = v.vehicle_id ORDER BY r.assignment_id DESC LIMIT 1), 'replaced') = 'replaced'")
            ->orderBy('v.rfid_requested_at')->orderBy('v.vehicle_id')->limit($limit)
            ->get(['v.vehicle_id', 'v.plate_number', 'v.rfid_requested_at', 'u.user_code', 'u.user_type', DB::raw("TRIM(CONCAT(COALESCE(p.first_name, ''), ' ', COALESCE(p.last_name, ''))) as owner_name")]);
    }

    /** Admin confirms the physical sticker was handed to the owner. */
    public function issue(int $rfidId, User $admin): object
    {
        return DB::transaction(function () use ($rfidId, $admin) {
            $tag = DB::table('rfid_tags')->where('rfid_id', $rfidId)->whereNotNull('inventory_registered_at')->lockForUpdate()->first();
            abort_unless($tag, 404, 'Inventory sticker not found.');
            $assignment = DB::table('rfid_assignments')->where('rfid_id', $rfidId)->where('assignment_status', 'active')->lockForUpdate()->first();
            abort_unless($tag->tag_status === 'assigned' && $assignment, 409, 'Only an assigned sticker can be issued.');
            abort_if((bool) $assignment->issued_at, 409, 'This sticker was already issued.');
            DB::table('rfid_assignments')->where('assignment_id', $assignment->assignment_id)->update(['issued_at' => now(), 'issued_by' => $admin->user_id, 'updated_at' => now()]);
            $this->audit($admin, 'rfid_issued', 'rfid_assignments', $assignment->assignment_id, ['tag_uid' => $tag->tag_uid, 'vehicle_id' => $assignment->vehicle_id]);
            DB::table('notifications')->insert([
                'user_id' => $assignment->user_id, 'notification_type' => 'rfid_issued', 'title' => 'RFID sticker issued',
                'body' => 'Your RFID sticker hand-over was confirmed. The usual license, authorization and selected-vehicle rules still apply at the gate.',
                'payload' => json_encode(['screen' => 'vehicles', 'vehicle_id' => $assignment->vehicle_id]),
                'related_type' => 'rfid_assignments', 'related_id' => $assignment->assignment_id,
                'channel' => 'in_app', 'is_read' => false, 'sent_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            return DB::table('rfid_assignments')->where('assignment_id', $assignment->assignment_id)->first();
        }, 3);
    }

    /**
     * Mark a sticker lost or damaged. Its active assignment ends (history kept);
     * with $replace the vehicle waits for, or immediately receives, another sticker.
     *
     * @return array{replacement_plate:?string, pending:bool}
     */
    public function retire(int $rfidId, string $status, bool $replace, User $admin): array
    {
        $vehicleId = DB::transaction(function () use ($rfidId, $status, $replace, $admin) {
            $reference = DB::table('rfid_assignments')->where('rfid_id', $rfidId)->where('assignment_status', 'active')->first();
            if ($reference) DB::table('users')->where('user_id', $reference->user_id)->lockForUpdate()->first();
            if ($reference?->vehicle_id) DB::table('vehicles')->where('vehicle_id', $reference->vehicle_id)->lockForUpdate()->first();
            $tag = DB::table('rfid_tags')->where('rfid_id', $rfidId)->whereNotNull('inventory_registered_at')->lockForUpdate()->first();
            abort_unless($tag, 404, 'Inventory sticker not found.');
            abort_unless(in_array($tag->tag_status, ['available', 'assigned'], true), 409, "This sticker is already {$tag->tag_status}.");
            $assignment = DB::table('rfid_assignments')->where('rfid_id', $rfidId)->where('assignment_status', 'active')->lockForUpdate()->first();
            if ($assignment?->vehicle_id && DB::table('parking_transactions')->where('vehicle_id', $assignment->vehicle_id)->where('transaction_status', 'active')->lockForUpdate()->first()) {
                abort(409, 'Record the vehicle exit before retiring its sticker.');
            }
            if ($assignment) {
                DB::table('rfid_assignments')->where('assignment_id', $assignment->assignment_id)->update([
                    'assignment_status' => $replace ? 'replaced' : 'revoked', 'unassigned_at' => now(), 'updated_at' => now(),
                    'remarks' => mb_strimwidth("Sticker marked {$status}".($replace ? '; replacement requested.' : '.'), 0, 255),
                ]);
            }
            DB::table('rfid_tags')->where('rfid_id', $rfidId)->update(['tag_status' => $status, 'updated_at' => now()]);
            $this->audit($admin, "rfid_inventory_{$status}", 'rfid_tags', $rfidId, ['tag_uid' => $tag->tag_uid, 'assignment_id' => $assignment?->assignment_id, 'replace' => $replace]);
            return $replace ? $assignment?->vehicle_id : null;
        }, 3);
        if (!$vehicleId) return ['replacement_plate' => null, 'pending' => false];
        $replacement = $this->assignToVehicle($vehicleId);
        return ['replacement_plate' => $replacement ? DB::table('vehicles')->where('vehicle_id', $vehicleId)->value('plate_number') : null, 'pending' => !$replacement];
    }

    /** Owner-facing RFID status for a vehicle: issued, assigned, pending_assignment or null. */
    public function vehicleStatus(object $vehicle, ?string $authorizationStatus): ?string
    {
        $latest = DB::table('rfid_assignments')->where('vehicle_id', $vehicle->vehicle_id)->orderByDesc('assignment_id')->first();
        if ($latest?->assignment_status === 'active') return $latest->issued_at || $latest->assignment_source !== 'inventory' ? 'issued' : 'assigned';
        if ($vehicle->rfid_requested_at && $authorizationStatus === 'approved' && (!$latest || $latest->assignment_status === 'replaced')) return 'pending_assignment';
        return null;
    }

    private function audit(User $admin, string $action, string $type, int $id, array $values): void
    {
        DB::table('audit_logs')->insert([
            'user_id' => $admin->user_id, 'actor_label' => $admin->user_code, 'action' => $action,
            'auditable_type' => $type, 'auditable_id' => $id, 'new_values' => json_encode($values),
            'ip_address' => request()?->ip(), 'created_at' => now(),
        ]);
    }
}
