<?php

namespace App\Services\Parking;

use App\Models\User;
use App\Support\CampusTime;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Gate entry and exit, automatic slot assignment, and staff-confirmed slot
 * observations.
 *
 * A vehicle is identified by RFID tag (campus users), Visitor QR pass
 * (visitors), or plate number (a recorded Parking Staff override). Entry is
 * granted only if the vehicle currently satisfies its authorization
 * requirements and, for campus users, the driver's license is verified; a
 * free slot in the area for its vehicle type is then assigned. Calendar
 * checks use the campus date (App\Support\CampusTime).
 *
 * Slot assignment is serialized per parking area by locking the area row,
 * and the database's unique (slot_id, active_slot_flag) index guarantees no
 * slot is held by two active transactions even if that lock were bypassed.
 *
 * An assignment is not proof of physical location: there is no slot-level
 * sensor, so staff confirm or report the actual slot.
 */
class ParkingAccessService
{
    public const WRONG_SLOT = 'WRONG_SLOT';

    public const WRONG_AREA = 'WRONG_AREA';

    /** Staff get a "nearly full" alert at or above this occupancy. */
    private const NEARLY_FULL_PERCENT = 90;

    /** A refused scan repeated within this window is the same gate event. */
    private const REPEAT_SCAN_SECONDS = 10;

    /** @var array<int, callable> Notifications sent only after the transaction commits. */
    private array $afterCommit = [];

    // ------------------------------------------------------------------ entry

    /**
     * @param  array  $credential  One of rfid_tag_uid, qr_pass_code, plate_number
     * @param  array  $context  gate_id, reader_id, at (CarbonImmutable; seeding/back-fill only)
     */
    public function enter(array $credential, ?User $actor, array $context = []): array
    {
        $at = $context['at'] ?? CarbonImmutable::now();

        // Identify outside the transaction: under REPEATABLE READ the first
        // plain read fixes the snapshot, so the area lock must come first.
        $id = $this->identify($credential);

        $reason = $id['error']
            ?? $this->credentialProblem($id)
            ?? (!$id['vehicle']->area_id ? "No parking area accepts vehicle type {$id['vehicle']->type_name}." : null);

        if ($reason) {
            $this->afterCommit = [];
            $result = DB::transaction(fn () => $this->deny($id, $reason, 'in', $actor, $context, $at));
            $this->flushAfterCommit();

            return $result;
        }

        // The unique (slot_id, active_slot_flag) index is the final guard; if it
        // ever fires, retry so the vehicle gets another slot instead of an error.
        for ($attempt = 1; ; $attempt++) {
            $this->afterCommit = [];

            try {
                $result = DB::transaction(fn () => $this->enterLocked($id, $actor, $context, $at));
                break;
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt >= 3) {
                    throw $e;
                }
            }
        }

        $this->flushAfterCommit();

        return $result;
    }

    private function enterLocked(array $id, ?User $actor, array $context, CarbonImmutable $at): array
    {
        $vehicle = $id['vehicle'];
        $areaId = $vehicle->area_id;

        // First statement of the transaction: serializes entries per area,
        // and every read after it sees the latest committed slot usage.
        $area = DB::table('parking_areas')->where('parking_area_id', $areaId)->lockForUpdate()->first();

        $active = DB::table('parking_transactions')
            ->where('vehicle_id', $vehicle->vehicle_id)
            ->where('transaction_status', 'active')
            ->lockForUpdate()
            ->first();

        if ($active) {
            // Repeated gate scans must not create a second stay.
            return [
                'status' => 'already_inside',
                'message' => "{$vehicle->plate_number} is already inside (slot " . ($this->slotCode($active->slot_id) ?? 'none') . ').',
                'transaction_id' => $active->transaction_id,
            ] + $this->slotInfo($active->slot_id);
        }

        if ($reason = $this->entryDenialReason($id, $area, $at)) {
            return $this->deny($id, $reason, 'in', $actor, $context, $at);
        }

        $slot = $this->findFreeSlot($areaId, $vehicle->vehicle_type_id);

        if (!$slot) {
            $denied = $this->deny($id, "{$area->area_name} is full.", 'in', $actor, $context, $at);
            $this->afterCommit[] = fn () => ParkingNotifier::notifyStaff(
                'parking_area_full',
                "{$area->area_name} is full",
                "{$area->area_name} is full. {$vehicle->plate_number} was turned away at " . CampusTime::label($at) . '.',
                'parking_area', $areaId, ['parking_area_id' => $areaId], "area:{$areaId}:full", 30
            );

            return $denied;
        }

        $eventId = $this->recordEvent($id, 'in', true, null, $actor, $context, $at, $areaId);

        $transactionId = DB::table('parking_transactions')->insertGetId([
            'ticket_number' => 'PT-' . $at->format('Ymd') . '-' . strtoupper(Str::random(6)),
            'vehicle_id' => $vehicle->vehicle_id,
            'driver_id' => $this->entryDriverId($id),
            'user_id' => $vehicle->owner_user_id,
            'visitor_id' => $vehicle->owner_user_id ? null : $vehicle->owner_visitor_id,
            'parking_area_id' => $areaId,
            'slot_id' => $slot->slot_id,
            'access_method' => $id['method'],
            'entry_event_id' => $eventId,
            'entry_datetime' => $at,
            'transaction_status' => 'active',
            'recorded_by' => $actor?->user_id,
            'created_at' => $at,
            'updated_at' => $at,
        ], 'transaction_id');

        if ($id['qr_pass']) {
            $this->useQrPass($id['qr_pass']->qr_pass_id);
        } elseif ($vehicle->owner_visitor_id && ($pass = $this->visitorPassFor($vehicle, $at, requireUsable: true))) {
            // Plate-number override for a visitor still spends the pass that admitted them.
            $this->useQrPass($pass->qr_pass_id);
        }

        $this->afterCommit[] = fn () => $this->checkCapacity($areaId);

        $override = $id['method'] === 'manual';

        return [
            'status' => 'granted',
            'message' => "Entry granted. {$vehicle->plate_number} is assigned to slot {$slot->slot_code} ({$area->area_name})."
                . ($override ? ' Recorded as a Parking Staff override (plate number).' : ''),
            'transaction_id' => $transactionId,
            'plate_number' => $vehicle->plate_number,
            'staff_override' => $override,
        ] + $this->slotInfo($slot->slot_id);
    }

    // ------------------------------------------------------------------ exit

    public function exit(array $credential, ?User $actor, array $context = []): array
    {
        $at = $context['at'] ?? CarbonImmutable::now();

        $this->afterCommit = [];

        $result = DB::transaction(function () use ($credential, $actor, $context, $at) {
            $id = $this->identify($credential);

            if (!$id['vehicle']) {
                return ['status' => 'not_found', 'message' => $id['error'] ?? 'Vehicle not identified.'];
            }

            // Staff can still record the exit by plate number (override).
            if ($problem = $this->credentialProblem($id)) {
                return $this->deny($id, $problem . ' Record the exit by plate number instead.', 'out', $actor, $context, $at);
            }

            $vehicle = $id['vehicle'];
            $transaction = DB::table('parking_transactions')
                ->where('vehicle_id', $vehicle->vehicle_id)
                ->where('transaction_status', 'active')
                ->lockForUpdate()
                ->first();

            if (!$transaction) {
                // A repeated exit scan must not close anything twice.
                return ['status' => 'not_inside', 'message' => "{$vehicle->plate_number} has no active parking session."];
            }

            $eventId = $this->recordEvent($id, 'out', true, null, $actor, $context, $at, $transaction->parking_area_id);

            DB::table('parking_transactions')->where('transaction_id', $transaction->transaction_id)->update([
                'exit_event_id' => $eventId,
                'exit_datetime' => $at,
                'transaction_status' => 'completed',
                'updated_at' => $at,
            ]);

            if ($id['qr_pass']) {
                $this->useQrPass($id['qr_pass']->qr_pass_id);
            } elseif ($vehicle->owner_visitor_id) {
                $pass = $this->visitorPassFor($vehicle, $at, requireUsable: false);
                if ($pass) {
                    $this->useQrPass($pass->qr_pass_id);
                }
            }

            $minutes = (int) DB::table('parking_transactions')->where('transaction_id', $transaction->transaction_id)->value('duration_minutes');
            $slot = $this->slotCode($transaction->slot_id);

            return [
                'status' => 'completed',
                'message' => "Exit recorded for {$vehicle->plate_number}. Slot " . ($slot ?? '—') . ' is now available. Duration: ' . self::formatDuration($minutes) . '.',
                'transaction_id' => $transaction->transaction_id,
                'plate_number' => $vehicle->plate_number,
                'duration_minutes' => $minutes,
                'staff_override' => $id['method'] === 'manual',
            ] + $this->slotInfo($transaction->slot_id);
        });

        $this->flushAfterCommit();

        return $result;
    }

    // ------------------------------------------------- staff slot observations

    /**
     * Staff report where an active vehicle is actually parked. The same slot
     * confirms the assignment; a different slot opens (or updates) a
     * wrong-slot incident in parking_violations and alerts staff.
     */
    public function reportObservedSlot(int $transactionId, int $observedSlotId, User $actor): array
    {
        $this->afterCommit = [];

        $result = DB::transaction(function () use ($transactionId, $observedSlotId, $actor) {
            $txn = DB::table('parking_transactions as t')
                ->join('vehicles as v', 'v.vehicle_id', '=', 't.vehicle_id')
                ->where('t.transaction_id', $transactionId)
                ->lockForUpdate()
                ->first(['t.*', 'v.plate_number']);

            if (!$txn) {
                return ['status' => 'not_found', 'message' => 'Parking transaction not found.'];
            }

            if ($txn->transaction_status !== 'active') {
                return ['status' => 'not_active', 'message' => "{$txn->plate_number} is no longer parked (session {$txn->transaction_status})."];
            }

            $observed = $this->slotWithArea($observedSlotId);

            if (!$observed) {
                return ['status' => 'not_found', 'message' => 'Parking slot not found.'];
            }

            if ((int) $txn->slot_id === (int) $observed->slot_id) {
                DB::table('parking_transactions')->where('transaction_id', $transactionId)->update([
                    'slot_confirmed_at' => now(),
                    'slot_confirmed_by' => $actor->user_id,
                    'updated_at' => now(),
                ]);

                return ['status' => 'confirmed', 'message' => "Confirmed: {$txn->plate_number} is parked in its assigned slot {$observed->slot_code}."];
            }

            $assigned = $txn->slot_id ? $this->slotWithArea($txn->slot_id) : null;
            $type = $assigned && (int) $assigned->parking_area_id !== (int) $observed->parking_area_id ? self::WRONG_AREA : self::WRONG_SLOT;
            $typeId = DB::table('violation_types')->where('violation_code', $type)->value('violation_type_id');

            if (!$typeId) {
                return ['status' => 'error', 'message' => "Violation type {$type} is missing. Run ViolationTypeSeeder."];
            }

            $now = CarbonImmutable::now();
            $occupant = DB::table('parking_transactions as t')
                ->join('vehicles as v', 'v.vehicle_id', '=', 't.vehicle_id')
                ->where('t.slot_id', $observed->slot_id)
                ->where('t.transaction_status', 'active')
                ->value('v.plate_number');

            $description = $type === self::WRONG_AREA
                ? "Vehicle {$txn->plate_number} was assigned to {$assigned->slot_code} in the {$assigned->area_name} but was detected at {$observed->slot_code} in the {$observed->area_name}."
                : "Vehicle {$txn->plate_number} was assigned to " . ($assigned->slot_code ?? 'no slot') . " but was detected at {$observed->slot_code}.";

            if ($occupant) {
                $description .= " Slot {$observed->slot_code} is assigned to {$occupant}.";
            }

            // One unresolved incident per parking session.
            $existing = DB::table('parking_violations')
                ->where('parking_transaction_id', $transactionId)
                ->whereIn('violation_type_id', DB::table('violation_types')->whereIn('violation_code', [self::WRONG_SLOT, self::WRONG_AREA])->pluck('violation_type_id'))
                ->whereIn('violation_status', ['open', 'under_review'])
                ->lockForUpdate()
                ->first();

            if ($existing && (int) $existing->slot_id === (int) $observed->slot_id) {
                return [
                    'status' => 'duplicate',
                    'message' => "An unresolved incident already exists for {$txn->plate_number} at {$observed->slot_code}. No duplicate alert was sent.",
                    'violation_id' => $existing->violation_id,
                ];
            }

            if ($existing) {
                DB::table('parking_violations')->where('violation_id', $existing->violation_id)->update([
                    'violation_type_id' => $typeId,
                    'slot_id' => $observed->slot_id,
                    'parking_area_id' => $observed->parking_area_id,
                    'occurred_at' => $now,
                    'reported_by' => $actor->user_id,
                    'description' => $description,
                    'updated_at' => $now,
                ]);

                return [
                    'status' => 'updated',
                    'message' => "The open incident for {$txn->plate_number} was updated with the new location {$observed->slot_code}.",
                    'violation_id' => $existing->violation_id,
                ];
            }

            $violationId = DB::table('parking_violations')->insertGetId([
                'violation_type_id' => $typeId,
                'parking_transaction_id' => $transactionId,
                'vehicle_id' => $txn->vehicle_id,
                'user_id' => $txn->user_id,
                'visitor_id' => $txn->visitor_id,
                'slot_id' => $observed->slot_id,
                'expected_slot_id' => $txn->slot_id,
                'parking_area_id' => $observed->parking_area_id,
                'occurred_at' => $now,
                'reported_by' => $actor->user_id,
                'reported_at' => $now,
                'description' => $description,
                'violation_status' => 'open',
                'created_at' => $now,
                'updated_at' => $now,
            ], 'violation_id');

            $title = $type === self::WRONG_AREA ? 'Wrong Parking Area' : 'Parking Slot Violation';
            $when = CampusTime::label($now);
            $this->afterCommit[] = fn () => ParkingNotifier::notifyStaff(
                strtolower($type),
                $title,
                "{$title}: {$description} Reported {$when}. Please verify the vehicle's location.",
                'parking_violation', $violationId,
                ['violation_id' => $violationId, 'plate_number' => $txn->plate_number, 'assigned_slot' => $assigned->slot_code ?? null, 'observed_slot' => $observed->slot_code]
            );

            return [
                'status' => 'incident',
                'message' => "{$title} recorded: {$description} Parking Staff have been notified.",
                'violation_id' => $violationId,
            ];
        });

        $this->flushAfterCommit();

        return $result;
    }

    // ---------------------------------------------------------------- helpers

    /**
     * Resolve the credential to a vehicle, owner and access method.
     */
    public function identify(array $credential): array
    {
        $id = ['vehicle' => null, 'method' => 'manual', 'error' => null, 'rfid_id' => null, 'assignment' => null, 'qr_pass' => null, 'credential' => null];

        if (!empty($credential['rfid_tag_uid'])) {
            $id['method'] = 'rfid';
            $id['credential'] = $credential['rfid_tag_uid'];
            $tag = DB::table('rfid_tags')->where('tag_uid', $credential['rfid_tag_uid'])->first();

            if (!$tag) {
                return ['error' => 'Unknown RFID tag.'] + $id;
            }

            $id['rfid_id'] = $tag->rfid_id;

            if ($tag->tag_status !== 'assigned') {
                return ['error' => "RFID tag is {$tag->tag_status}."] + $id;
            }

            $assignment = DB::table('rfid_assignments')
                ->where('rfid_id', $tag->rfid_id)
                ->where('assignment_status', 'active')
                ->orderByDesc('assignment_id')
                ->first();

            if (!$assignment) {
                return ['error' => 'RFID tag is not assigned to anyone.'] + $id;
            }

            $id['assignment'] = $assignment;

            if ($assignment->valid_until && $assignment->valid_until < CampusTime::date()) {
                return ['error' => 'RFID assignment has expired.'] + $id;
            }

            if (!$assignment->vehicle_id) {
                return ['error' => 'RFID tag is not linked to a vehicle.'] + $id;
            }

            $id['vehicle'] = $this->vehicle(['v.vehicle_id' => $assignment->vehicle_id]);
        } elseif (!empty($credential['qr_pass_code'])) {
            $id['method'] = 'qr';
            $id['credential'] = $credential['qr_pass_code'];
            $pass = DB::table('qr_passes')->where('pass_code', $credential['qr_pass_code'])->first();

            if (!$pass) {
                return ['error' => 'Unknown QR pass.'] + $id;
            }

            $id['qr_pass'] = $pass;

            if (!$pass->vehicle_id) {
                return ['error' => 'QR pass is not linked to a vehicle.'] + $id;
            }

            $id['vehicle'] = $this->vehicle(['v.vehicle_id' => $pass->vehicle_id]);
        } else {
            $plate = strtoupper(trim((string) ($credential['plate_number'] ?? '')));
            $id['credential'] = $plate;
            $id['vehicle'] = $plate !== '' ? $this->vehicle(['v.plate_number' => $plate]) : null;

            if (!$id['vehicle']) {
                return ['error' => "Vehicle {$plate} is not registered."] + $id;
            }
        }

        if (!$id['vehicle']) {
            return ['error' => 'Vehicle not found.'] + $id;
        }

        return $id;
    }

    /**
     * Why entry must be refused, or null when the vehicle may enter.
     */
    private function entryDenialReason(array $id, object $area, CarbonImmutable $at): ?string
    {
        $vehicle = $id['vehicle'];
        $today = CampusTime::date($at);

        if ($vehicle->deleted_at !== null || $vehicle->registration_status !== 'registered') {
            return 'Vehicle registration is archived.';
        }

        if ($vehicle->owner_user_id) {
            $owner = DB::table('users')->where('user_id', $vehicle->owner_user_id)->first(['account_status', 'deleted_at']);

            if (!$owner || $owner->deleted_at !== null || $owner->account_status !== 'active') {
                return "Owner account is " . ($owner->account_status ?? 'missing') . '.';
            }

            if ($id['assignment'] && (int) $id['assignment']->user_id !== (int) $vehicle->owner_user_id
                && !DB::table('vehicle_driver as vd')->join('drivers as d', 'd.driver_id', '=', 'vd.driver_id')
                    ->where('vd.vehicle_id', $vehicle->vehicle_id)->where('d.user_id', $id['assignment']->user_id)->exists()) {
                return 'RFID holder is not linked to this vehicle.';
            }

            $authorization = DB::table('vehicle_authorizations')
                ->where('vehicle_id', $vehicle->vehicle_id)
                ->orderByRaw("authorization_status = 'approved' desc")
                ->orderByDesc('authorization_id')
                ->first();

            if (!$authorization) {
                return 'Vehicle has no authorization.';
            }

            $authorizationProblem = match (true) {
                $authorization->authorization_status === 'pending' => 'Vehicle authorization is pending review.',
                $authorization->authorization_status === 'suspended' => 'Vehicle authorization is suspended.',
                $authorization->authorization_status === 'rejected' => 'Vehicle authorization was rejected.',
                $authorization->authorization_status === 'revoked' => 'Vehicle authorization was revoked.',
                $authorization->authorization_status !== 'approved' => "Vehicle authorization is {$authorization->authorization_status}.",
                $authorization->valid_from && $authorization->valid_from > $today => "Vehicle authorization is not valid until {$authorization->valid_from}.",
                $authorization->valid_until && $authorization->valid_until < $today => "Vehicle authorization expired on {$authorization->valid_until}.",
                $authorization->parking_area_id && (int) $authorization->parking_area_id !== (int) $area->parking_area_id => "Vehicle is not authorized for the {$area->area_name}.",
                default => null,
            };

            return $authorizationProblem ?? $this->licenseProblem($id, $today);
        }

        $visitor = DB::table('visitors')->where('visitor_id', $vehicle->owner_visitor_id)->first();

        if (!$visitor) {
            return 'Visitor record not found.';
        }

        if ($visitor->is_blacklisted) {
            return 'Visitor is blacklisted.';
        }

        // Re-read under lock so one pass cannot be used by two simultaneous scans.
        $pass = $id['qr_pass']
            ? DB::table('qr_passes')->where('qr_pass_id', $id['qr_pass']->qr_pass_id)->lockForUpdate()->first()
            : $this->visitorPassFor($vehicle, $at, requireUsable: false);

        if (!$pass) {
            return 'No visitor QR pass for this vehicle.';
        }

        // Only passes issued from an approved visitor request admit a vehicle.
        return VisitorPassPolicy::entryProblem($pass, $vehicle, $at);
    }

    /**
     * The credential must match who owns the vehicle: campus users (students,
     * faculty, employees) use RFID, visitors use their own Visitor QR pass.
     * A plate number is a Parking Staff override and is accepted for both.
     */
    private function credentialProblem(array $id): ?string
    {
        $vehicle = $id['vehicle'];

        if (!$vehicle || $id['method'] === 'manual') {
            return null;
        }

        if ($id['method'] === 'rfid' && !$vehicle->owner_user_id) {
            return 'Visitors must use their Visitor QR pass; RFID is for campus users only.';
        }

        if ($id['method'] === 'qr') {
            $pass = $id['qr_pass'];

            if ($vehicle->owner_user_id || ($pass->pass_type ?? 'visitor') !== 'visitor') {
                return 'Campus users must use their RFID tag; Visitor QR passes are for visitors only.';
            }

            if ($pass->visitor_id && (int) $pass->visitor_id !== (int) $vehicle->owner_visitor_id) {
                return 'This Visitor QR pass belongs to a different visitor.';
            }
        }

        return null;
    }

    /**
     * The driver of this stay: the RFID holder's driver record when a tag
     * was used, otherwise the vehicle's primary (or owner's) driver.
     */
    private function entryDriverId(array $id): ?int
    {
        if ($id['assignment']) {
            $holder = DB::table('drivers')->where('user_id', $id['assignment']->user_id)->value('driver_id');

            if ($holder) {
                return (int) $holder;
            }
        }

        return $id['vehicle'] ? $this->driverId($id['vehicle']) : null;
    }

    /**
     * Campus-user entry requires the driver to hold a license that has been
     * verified (automatically or by staff), is valid, and is not expired.
     */
    private function licenseProblem(array $id, string $today): ?string
    {
        $driverId = $this->entryDriverId($id);
        $driver = $driverId ? DB::table('drivers')->where('driver_id', $driverId)->first(['driver_id', 'driver_status']) : null;

        if (!$driver) {
            return "No driver's license is on file for this vehicle's driver.";
        }

        if ($driver->driver_status !== 'active') {
            return 'The driver record is inactive.';
        }

        // The current license (license_status valid), otherwise the latest one.
        $license = DB::table('driver_licenses')
            ->where('driver_id', $driver->driver_id)
            ->orderByRaw("license_status = 'valid' desc")
            ->orderByDesc('license_id')
            ->first(['license_status', 'verification_status', 'expiry_date']);

        if (!$license) {
            return "No driver's license is on file for this vehicle's driver.";
        }

        return match (true) {
            $license->license_status !== 'valid' => "Driver's license is {$license->license_status}.",
            $license->verification_status === 'pending_review' => "Driver's license is waiting for staff verification.",
            $license->verification_status === 'needs_correction' => "Driver's license needs correction before it can be verified.",
            $license->verification_status !== 'verified' => "Driver's license is still being checked.",
            $license->expiry_date && $license->expiry_date < $today => "Driver's license expired on {$license->expiry_date}.",
            default => null,
        };
    }

    private function visitorPassFor(object $vehicle, CarbonImmutable $at, bool $requireUsable): ?object
    {
        return DB::table('qr_passes')
            ->where('visitor_id', $vehicle->owner_visitor_id)
            ->where(fn ($q) => $q->whereNull('vehicle_id')->orWhere('vehicle_id', $vehicle->vehicle_id))
            ->when($requireUsable, fn ($q) => $q->where('pass_status', 'active'))
            // Prefer passes issued from an approved request over legacy ones.
            ->orderByRaw('request_id IS NULL')
            ->orderByRaw("pass_status = 'active' desc")
            ->orderByDesc('valid_until')
            ->first();
    }

    private function useQrPass(int $passId): void
    {
        DB::table('qr_passes')->where('qr_pass_id', $passId)->update([
            'use_count' => DB::raw('LEAST(use_count + 1, 255)'),
            'pass_status' => DB::raw("IF(use_count + 1 >= max_uses AND pass_status = 'active', 'used', pass_status)"),
            'updated_at' => now(),
        ]);
    }

    private function findFreeSlot(int $areaId, int $vehicleTypeId): ?object
    {
        return DB::table('parking_slots as s')
            ->join('parking_zones as z', 'z.zone_id', '=', 's.zone_id')
            ->where('z.parking_area_id', $areaId)
            ->where('z.is_active', true)
            ->where('s.slot_status', 'available')
            ->where(fn ($q) => $q->whereNull('z.vehicle_type_id')->orWhere('z.vehicle_type_id', $vehicleTypeId))
            ->where(fn ($q) => $q->whereNull('s.vehicle_type_id')->orWhere('s.vehicle_type_id', $vehicleTypeId))
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('parking_transactions as t')
                ->whereColumn('t.slot_id', 's.slot_id')->where('t.transaction_status', 'active'))
            // The accessible (PWD) slot is given out only when nothing else is
            // free. Otherwise prefer bays that are on the DYCI layout map; slots
            // whose position is not printed on the drawing come after them.
            ->orderBy('s.is_pwd_slot')
            ->orderByRaw('s.pos_x IS NULL')
            ->orderBy('z.zone_code')
            ->orderBy('s.slot_code')
            ->first(['s.slot_id', 's.slot_code']);
    }

    private function deny(array $id, string $reason, string $direction, ?User $actor, array $context, CarbonImmutable $at): array
    {
        $vehicle = $id['vehicle'];
        $label = $vehicle->plate_number ?? ($id['credential'] ? "credential {$id['credential']}" : 'Unknown vehicle');
        $verb = $direction === 'out' ? 'Exit' : 'Entry';
        $gateId = $context['gate_id'] ?? $this->defaultGate($vehicle->area_id ?? null);

        // The same refused scan repeated at the gate within a few seconds is one
        // gate event: reuse it instead of recording a duplicate and re-alerting.
        $repeat = DB::table('access_events')
            ->where('auth_result', 'denied')
            ->where('direction', $direction)
            ->where('gate_id', $gateId)
            ->where('denial_reason', $reason)
            ->when($vehicle, fn ($q) => $q->where('vehicle_id', $vehicle->vehicle_id),
                fn ($q) => $q->whereNull('vehicle_id')->where('raw_payload->credential', $id['credential']))
            ->whereBetween('event_datetime', [$at->subSeconds(self::REPEAT_SCAN_SECONDS), $at])
            ->orderByDesc('event_id')
            ->value('event_id');

        if ($repeat) {
            return [
                'status' => 'denied',
                'message' => "{$verb} denied for {$label}: {$reason}",
                'reason' => $reason,
                'access_event_id' => $repeat,
                'plate_number' => $vehicle->plate_number ?? null,
                'repeat_scan' => true,
            ];
        }

        $eventId = $this->recordEvent($id, $direction, false, $reason, $actor, ['gate_id' => $gateId] + $context, $at, $vehicle->area_id ?? null);

        $this->afterCommit[] = fn () => ParkingNotifier::notifyStaff(
            'parking_access_denied',
            "{$verb} denied",
            "{$verb} denied for {$label} at " . CampusTime::label($at) . ": {$reason}",
            'access_event', $eventId,
            ['access_event_id' => $eventId, 'plate_number' => $vehicle->plate_number ?? null, 'reason' => $reason],
            'vehicle:' . ($vehicle->vehicle_id ?? $id['credential']), 15
        );

        return [
            'status' => 'denied',
            'message' => "{$verb} denied for {$label}: {$reason}",
            'reason' => $reason,
            'access_event_id' => $eventId,
            'plate_number' => $vehicle->plate_number ?? null,
        ];
    }

    private function recordEvent(array $id, string $direction, bool $granted, ?string $reason, ?User $actor, array $context, CarbonImmutable $at, ?int $areaId): int
    {
        $vehicle = $id['vehicle'];

        return DB::table('access_events')->insertGetId([
            'event_datetime' => $at,
            'direction' => $direction,
            'access_method' => $id['method'],
            'gate_id' => $context['gate_id'] ?? $this->defaultGate($areaId),
            'reader_id' => $context['reader_id'] ?? null,
            'rfid_id' => $id['rfid_id'],
            'rfid_assignment_id' => $id['assignment']->assignment_id ?? null,
            'qr_pass_id' => $id['qr_pass']->qr_pass_id ?? null,
            'user_id' => $vehicle->owner_user_id ?? null,
            'visitor_id' => ($vehicle && !$vehicle->owner_user_id) ? $vehicle->owner_visitor_id : null,
            'vehicle_id' => $vehicle->vehicle_id ?? null,
            'auth_result' => $granted ? 'granted' : 'denied',
            'denial_reason' => $reason,
            'raw_payload' => json_encode(array_filter([
                'credential' => $id['credential'],
                'method' => $id['method'],
                // Plate-number entry/exit is a Parking Staff override of the RFID / QR rule.
                'staff_override' => $id['method'] === 'manual' ? true : null,
                // Where the scan came from: staff gate screen, RFID reader, or the development simulator.
                'source' => $context['source'] ?? null,
            ])),
            'processed_by_user_id' => $actor?->user_id,
            'created_at' => $at,
            'updated_at' => $at,
        ], 'event_id');
    }

    /**
     * The shared Main Entrance/Exit (parking_area_id NULL) when one exists,
     * otherwise a gate of the vehicle's area.
     */
    private function defaultGate(?int $areaId): int
    {
        $gate = DB::table('access_gates')
            ->whereIn('gate_type', ['both', 'entry', 'exit'])
            ->orderByRaw('parking_area_id IS NULL desc')
            ->when($areaId, fn ($q) => $q->orderByRaw('parking_area_id = ? desc', [$areaId]))
            ->orderBy('gate_id')
            ->value('gate_id');

        if (!$gate) {
            throw new \RuntimeException('No access gate is configured. Run ParkingAreaSeeder.');
        }

        return $gate;
    }

    private function vehicle(array $where): ?object
    {
        return DB::table('vehicles as v')
            ->join('vehicle_types as vt', 'vt.vehicle_type_id', '=', 'v.vehicle_type_id')
            ->where($where)
            ->whereNull('v.deleted_at')
            ->first(['v.*', 'vt.type_name', 'vt.parking_area_id as area_id']);
    }

    private function driverId(object $vehicle): ?int
    {
        if (!$vehicle->owner_user_id) {
            return null;
        }

        return DB::table('vehicle_driver')->where('vehicle_id', $vehicle->vehicle_id)->orderByDesc('is_primary')->value('driver_id')
            ?? DB::table('drivers')->where('user_id', $vehicle->owner_user_id)->value('driver_id');
    }

    private function slotWithArea(int $slotId): ?object
    {
        return DB::table('parking_slots as s')
            ->join('parking_zones as z', 'z.zone_id', '=', 's.zone_id')
            ->join('parking_areas as a', 'a.parking_area_id', '=', 'z.parking_area_id')
            ->where('s.slot_id', $slotId)
            ->first(['s.slot_id', 's.slot_code', 's.slot_status', 'z.parking_area_id', 'a.area_name']);
    }

    private function slotCode(?int $slotId): ?string
    {
        return $slotId ? DB::table('parking_slots')->where('slot_id', $slotId)->value('slot_code') : null;
    }

    private function slotInfo(?int $slotId): array
    {
        $slot = $slotId ? $this->slotWithArea($slotId) : null;

        return ['slot_id' => $slot->slot_id ?? null, 'slot_code' => $slot->slot_code ?? null, 'parking_area' => $slot->area_name ?? null];
    }

    private function checkCapacity(int $areaId): void
    {
        $stats = ParkingStats::area($areaId);

        if ($stats && $stats['occupancy_percent'] >= self::NEARLY_FULL_PERCENT) {
            ParkingNotifier::notifyStaff(
                $stats['available'] === 0 ? 'parking_area_full' : 'parking_area_nearly_full',
                "{$stats['area_name']} is " . ($stats['available'] === 0 ? 'full' : 'nearly full'),
                "{$stats['area_name']}: {$stats['occupied']} of {$stats['capacity']} slots occupied ({$stats['occupancy_percent']}%), {$stats['available']} available.",
                'parking_area', $areaId, ['parking_area_id' => $areaId], "area:{$areaId}:capacity", 60
            );
        }
    }

    private function flushAfterCommit(): void
    {
        foreach ($this->afterCommit as $callback) {
            $callback();
        }

        $this->afterCommit = [];
    }

    public static function formatDuration(?int $minutes): string
    {
        if ($minutes === null) {
            return '—';
        }

        return intdiv($minutes, 60) > 0 ? intdiv($minutes, 60) . 'h ' . ($minutes % 60) . 'm' : "{$minutes}m";
    }
}
