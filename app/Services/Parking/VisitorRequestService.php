<?php

namespace App\Services\Parking;

use App\Models\User;
use App\Support\CampusTime;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Visitor parking requests: filing (by a guest from their account, or by
 * Parking Staff at the desk), Admin approval that issues exactly one Visitor
 * QR pass, and rejection. Callers run these inside a DB transaction; problems
 * are thrown (ValidationException / HTTP 409) so nothing is half-saved.
 */
class VisitorRequestService
{
    public const MAX_VISIT_DAYS = 7;

    /** Uses of an issued pass: one entry and one exit. */
    public const PASS_USES = 2;

    /** File a pending request for a visitor's registered vehicle and notify Admins. */
    public function create(int $visitorId, int $vehicleId, string $purpose, string $visiting, CarbonImmutable $from, CarbonImmutable $until, User $actor, string $source): int
    {
        if (CampusTime::date($from) <= CampusTime::date()) {
            throw ValidationException::withMessages(['valid_from' => 'Same-day requests are not allowed. Choose tomorrow or a later date (Manila time).']);
        }
        if ($until <= $from) {
            throw ValidationException::withMessages(['valid_until' => 'The visit must end after it starts.']);
        }
        if ($until <= CampusTime::now()) {
            throw ValidationException::withMessages(['valid_until' => 'The visit must end in the future.']);
        }
        if ($from->diffInDays($until) > self::MAX_VISIT_DAYS) {
            throw ValidationException::withMessages(['valid_until' => 'A single request can cover at most ' . self::MAX_VISIT_DAYS . ' days.']);
        }

        $visitor = DB::table('visitors')->where('visitor_id', $visitorId)->lockForUpdate()->first();
        if (!$visitor || $visitor->is_blacklisted) {
            throw ValidationException::withMessages(['visitor_id' => 'This visitor cannot request access.']);
        }

        $vehicle = DB::table('vehicles as v')->join('vehicle_types as vt', 'vt.vehicle_type_id', '=', 'v.vehicle_type_id')
            ->where('v.vehicle_id', $vehicleId)->whereNull('v.deleted_at')
            ->first(['v.vehicle_id', 'v.plate_number', 'v.owner_visitor_id', 'vt.parking_area_id']);
        if (!$vehicle || (int) $vehicle->owner_visitor_id !== $visitorId) {
            throw ValidationException::withMessages(['vehicle_id' => 'Choose one of the visitor\'s registered vehicles.']);
        }

        $overlap = DB::table('visitor_requests')
            ->where('vehicle_id', $vehicleId)
            ->whereIn('request_status', ['pending', 'approved'])
            ->where('requested_valid_from', '<', CampusTime::stored($until))
            ->where('requested_valid_until', '>', CampusTime::stored($from))
            ->exists();
        if ($overlap) {
            abort(409, "{$vehicle->plate_number} already has a pending or approved request for an overlapping time.");
        }

        $requestId = DB::table('visitor_requests')->insertGetId([
            'visitor_id' => $visitorId,
            'vehicle_id' => $vehicleId,
            'purpose' => Str::limit(trim($purpose) . ' · Visiting: ' . trim($visiting), 255, ''),
            'destination_area_id' => $vehicle->parking_area_id,
            'requested_valid_from' => CampusTime::stored($from),
            'requested_valid_until' => CampusTime::stored($until),
            'request_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ], 'request_id');

        $this->audit($actor, $requestId, 'visitor_request_created', null, ['request_status' => 'pending', 'plate_number' => $vehicle->plate_number, 'source' => $source]);

        $name = trim("{$visitor->first_name} {$visitor->last_name}");
        ParkingNotifier::notifyAdmins(
            'visitor_request_submitted',
            'Visitor parking request awaiting approval',
            "{$name} ({$vehicle->plate_number}) requested visitor parking for " . CampusTime::label($from) . ' – ' . CampusTime::label($until) . '.',
            'visitor_request', $requestId, ['request_id' => $requestId, 'plate_number' => $vehicle->plate_number]
        );

        return $requestId;
    }

    /** Approve a pending request (Admin only) and issue its Visitor QR pass. */
    public function approve(int $requestId, User $admin, ?string $remarks): object
    {
        $req = $this->lockPending($requestId);

        if ($req->requested_valid_until <= now()->toDateTimeString()) {
            abort(409, 'The requested visit time has already ended. A new request is needed.');
        }
        if (DB::table('visitors')->where('visitor_id', $req->visitor_id)->value('is_blacklisted')) {
            abort(409, 'The visitor is blacklisted.');
        }
        if (!$req->vehicle_id || !DB::table('vehicles')->where('vehicle_id', $req->vehicle_id)->whereNull('deleted_at')->exists()) {
            abort(409, 'The request has no registered vehicle.');
        }

        $code = (string) Str::uuid();
        $passId = DB::table('qr_passes')->insertGetId([
            'pass_code' => $code,
            'token_hash' => hash('sha256', $code),
            'pass_type' => 'visitor',
            'request_id' => $req->request_id,
            'visitor_id' => $req->visitor_id,
            'vehicle_id' => $req->vehicle_id,
            'valid_from' => $req->requested_valid_from,
            'valid_until' => $req->requested_valid_until,
            'max_uses' => self::PASS_USES,
            'use_count' => 0,
            'pass_status' => 'active',
            'issued_by' => $admin->user_id,
            'issued_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ], 'qr_pass_id');

        DB::table('visitor_requests')->where('request_id', $requestId)->update([
            'request_status' => 'approved',
            'reviewed_by' => $admin->user_id,
            'reviewed_at' => now(),
            'review_remarks' => $remarks,
            'updated_at' => now(),
        ]);

        $this->audit($admin, $requestId, 'visitor_request_approved', ['request_status' => 'pending'], ['request_status' => 'approved', 'qr_pass_id' => $passId]);
        $this->notifyGuest($req, 'visitor_request_approved', 'Visitor parking request approved',
            'Your visitor parking request for ' . $this->plate($req) . ' was approved. Your QR pass is ready in your PASS account; show it at the Main Entrance / Exit.'
                . ($remarks ? " Note: {$remarks}" : ''));

        return DB::table('qr_passes')->where('qr_pass_id', $passId)->first();
    }

    /** Reject a pending request (Admin only). No pass is issued. */
    public function reject(int $requestId, User $admin, string $remarks): void
    {
        $req = $this->lockPending($requestId);

        DB::table('visitor_requests')->where('request_id', $requestId)->update([
            'request_status' => 'rejected',
            'reviewed_by' => $admin->user_id,
            'reviewed_at' => now(),
            'review_remarks' => $remarks,
            'updated_at' => now(),
        ]);

        $this->audit($admin, $requestId, 'visitor_request_rejected', ['request_status' => 'pending'], ['request_status' => 'rejected', 'remarks' => $remarks]);
        $this->notifyGuest($req, 'visitor_request_rejected', 'Visitor parking request not approved',
            'Your visitor parking request for ' . $this->plate($req) . " was not approved. Reason: {$remarks}");
    }

    /** A guest cancels their own pending request. */
    public function cancel(int $requestId, User $guest): void
    {
        $req = $this->lockPending($requestId);

        DB::table('visitor_requests')->where('request_id', $requestId)->update(['request_status' => 'cancelled', 'updated_at' => now()]);
        $this->audit($guest, $requestId, 'visitor_request_cancelled', ['request_status' => 'pending'], ['request_status' => 'cancelled']);
    }

    /** Identity is declared by the requester; staff must inspect the physical license at the gate. */
    public static function driverSummary(object $request): ?array
    {
        if (empty($request->driver_name)) return null;
        $license = !empty($request->driver_license_encrypted)
            ? \Illuminate\Support\Facades\Crypt::decryptString($request->driver_license_encrypted) : '';
        return [
            'name' => $request->driver_name,
            'is_requester' => (bool) $request->driver_is_requester,
            'license_reference' => $license ? '•••• ' . substr($license, -4) : null,
            'license_expiry' => $request->driver_license_expiry,
            'passenger_count' => $request->passenger_count,
            'identity_verified' => false,
        ];
    }

    private function lockPending(int $requestId): object
    {
        $req = DB::table('visitor_requests')->where('request_id', $requestId)->lockForUpdate()->first();

        if (!$req) {
            abort(404, 'Visitor request not found.');
        }
        if ($req->request_status !== 'pending') {
            abort(409, "This request is already {$req->request_status}.");
        }

        return $req;
    }

    private function plate(object $req): string
    {
        return DB::table('vehicles')->where('vehicle_id', $req->vehicle_id)->value('plate_number') ?? 'your vehicle';
    }

    private function notifyGuest(object $req, string $type, string $title, string $body): void
    {
        $userId = DB::table('visitors')->where('visitor_id', $req->visitor_id)->value('user_id');

        if ($userId) {
            ParkingNotifier::notifyUser((int) $userId, $type, $title, $body, 'visitor_request', (int) $req->request_id, ['request_id' => (int) $req->request_id, 'screen' => 'guest_dashboard']);
        }
    }

    private function audit(User $actor, int $id, string $action, ?array $old, array $new): void
    {
        $request = request();

        DB::table('audit_logs')->insert([
            'user_id' => $actor->user_id,
            'actor_label' => $actor->user_code,
            'action' => $action,
            'auditable_type' => 'visitor_request',
            'auditable_id' => $id,
            'old_values' => $old ? json_encode($old) : null,
            'new_values' => json_encode($new),
            'ip_address' => $request?->ip(),
            'user_agent' => Str::limit((string) $request?->userAgent(), 252),
            'created_at' => now(),
        ]);
    }
}
