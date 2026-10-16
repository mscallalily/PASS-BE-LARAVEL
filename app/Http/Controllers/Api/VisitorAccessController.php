<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Parking\ParkingNotifier;
use App\Services\Parking\VisitorPassPolicy;
use App\Services\Parking\VisitorRequestService;
use App\Support\CampusTime;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Visitor registration, access requests, approval, and Visitor QR passes
 * (tables: visitors, vehicles, visitor_requests, qr_passes).
 *
 * A QR pass is issued only when a pending request is approved, and is linked
 * to that request, its visitor and its vehicle. Entry and exit with the pass
 * go through ParkingAccessService; VisitorPassPolicy decides validity.
 */
class VisitorAccessController extends Controller
{
    public function __construct(private VisitorRequestService $requests)
    {
    }

    private function forbidden(Request $request): ?JsonResponse
    {
        return $request->user()->canManageParking()
            ? null
            : response()->json(['message' => 'Only Parking Staff and administrators can manage visitor access.'], 403);
    }

    // ----------------------------------------------------------- reference data

    /** Existing visitors (for repeat visits) and vehicle types visitors may use. */
    public function options(Request $request): JsonResponse
    {
        if ($denied = $this->forbidden($request)) {
            return $denied;
        }

        $visitors = DB::table('visitors as vis')
            ->orderBy('vis.last_name')->orderBy('vis.first_name')
            ->get(['vis.visitor_id', 'vis.first_name', 'vis.last_name', 'vis.contact_number', 'vis.email', 'vis.affiliation', 'vis.is_blacklisted'])
            ->map(fn ($v) => (array) $v + [
                'plates' => DB::table('vehicles')->where('owner_visitor_id', $v->visitor_id)->whereNull('deleted_at')->pluck('plate_number'),
            ]);

        $types = DB::table('vehicle_types as vt')
            ->join('parking_areas as a', 'a.parking_area_id', '=', 'vt.parking_area_id')
            ->where('a.is_active', true)
            ->orderBy('vt.type_name')
            ->get(['vt.vehicle_type_id', 'vt.type_name', 'a.area_name']);

        return response()->json(['visitors' => $visitors, 'vehicle_types' => $types, 'max_visit_days' => VisitorRequestService::MAX_VISIT_DAYS, 'timezone' => CampusTime::zone()]);
    }

    // ----------------------------------------------------------- requests

    public function requests(Request $request): JsonResponse
    {
        if ($denied = $this->forbidden($request)) {
            return $denied;
        }

        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'approved', 'rejected', 'cancelled', 'expired'])],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $now = CarbonImmutable::now();

        $rows = DB::table('visitor_requests as r')
            ->join('visitors as vis', 'vis.visitor_id', '=', 'r.visitor_id')
            ->leftJoin('vehicles as v', 'v.vehicle_id', '=', 'r.vehicle_id')
            ->leftJoin('vehicle_types as vt', 'vt.vehicle_type_id', '=', 'v.vehicle_type_id')
            ->leftJoin('parking_areas as a', 'a.parking_area_id', '=', 'r.destination_area_id')
            ->leftJoin('users as rev', 'rev.user_id', '=', 'r.reviewed_by')
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('r.request_status', $s))
            ->when(trim((string) ($filters['search'] ?? '')) !== '', function ($q) use ($filters) {
                $like = '%' . trim($filters['search']) . '%';
                $q->where(fn ($w) => $w->where('v.plate_number', 'like', $like)->orWhere('r.purpose', 'like', $like)
                    ->orWhereRaw("CONCAT_WS(' ', vis.first_name, vis.last_name) like ?", [$like]));
            })
            ->orderByRaw("r.request_status = 'pending' desc")
            ->orderByDesc('r.request_id')
            ->limit(300)
            ->get([
                'r.driver_license_photo_path', 'r.vehicle_photo_path', 'r.documents_reviewed_at', 'r.driver_name', 'r.driver_is_requester', 'r.driver_license_encrypted', 'r.driver_license_expiry', 'r.passenger_count', 'r.request_id', 'r.request_status', 'r.purpose', 'r.requested_valid_from', 'r.requested_valid_until', 'r.reviewed_at', 'r.review_remarks', 'r.created_at',
                'vis.visitor_id', 'vis.user_id as visitor_user_id', DB::raw("CONCAT_WS(' ', vis.first_name, vis.last_name) as visitor_name"), 'vis.contact_number', 'vis.affiliation', 'vis.is_blacklisted',
                'v.vehicle_id', 'v.plate_number', 'vt.type_name as vehicle_type', 'a.area_name', 'rev.user_code as reviewed_by',
            ])
            ->map(function ($r) use ($now) {
                $pass = DB::table('qr_passes')->where('request_id', $r->request_id)->orderByDesc('qr_pass_id')->first();

                return [
                    'request_id' => $r->request_id,
                    'request_status' => $r->request_status,
                    'purpose' => $r->purpose,
                    'documents' => \App\Services\Parking\VisitorRequestService::documentSummary($r),
                    'driver' => VisitorRequestService::driverSummary($r),
                    'valid_from' => ParkingOperationsController::iso($r->requested_valid_from),
                    'valid_until' => ParkingOperationsController::iso($r->requested_valid_until),
                    'window_ended' => $r->requested_valid_until < $now->toDateTimeString(),
                    'created_at' => ParkingOperationsController::iso($r->created_at),
                    'reviewed_at' => ParkingOperationsController::iso($r->reviewed_at),
                    'reviewed_by' => $r->reviewed_by,
                    'review_remarks' => $r->review_remarks,
                    'visitor' => ['visitor_id' => $r->visitor_id, 'has_account' => (bool) $r->visitor_user_id, 'name' => $r->visitor_name, 'contact_number' => $r->contact_number, 'affiliation' => $r->affiliation, 'is_blacklisted' => (bool) $r->is_blacklisted],
                    'vehicle' => ['vehicle_id' => $r->vehicle_id, 'plate_number' => $r->plate_number, 'vehicle_type' => $r->vehicle_type, 'area_name' => $r->area_name],
                    'pass' => $pass ? self::passSummary($pass, $now) : null,
                ];
            });

        return response()->json(['data' => $rows]);
    }

    /** Register (or reuse) a visitor and their vehicle, and file a pending access request. */
    public function storeRequest(Request $request): JsonResponse
    {
        if ($denied = $this->forbidden($request)) {
            return $denied;
        }

        $data = $request->validate([
            'visitor_id' => ['nullable', 'integer', 'exists:visitors,visitor_id'],
            'first_name' => ['required_without:visitor_id', 'nullable', 'string', 'max:100'],
            'last_name' => ['required_without:visitor_id', 'nullable', 'string', 'max:100'],
            'contact_number' => ['required_without:visitor_id', 'nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'id_type' => ['nullable', Rule::in(['drivers_license', 'national_id', 'company_id', 'school_id', 'passport', 'other'])],
            'id_number' => ['nullable', 'string', 'max:100'],
            'affiliation' => ['nullable', 'string', 'max:150'],
            'plate_number' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9 -]+$/'],
            'vehicle_type_id' => ['required', 'integer', Rule::exists('vehicle_types', 'vehicle_type_id')->whereNotNull('parking_area_id')],
            'make' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:50'],
            'purpose' => ['required', 'string', 'max:150'],
            'visiting' => ['required', 'string', 'max:90'],
            'driver_name' => ['required', 'string', 'max:200', 'regex:/^\S+(\s+\S+)+$/u'],
            'driver_license_number' => ['required', 'string', 'min:6', 'max:30', 'regex:/^[A-Za-z0-9 -]+$/'],
            'driver_license_expiry' => ['required', 'date_format:Y-m-d'],
            'passenger_count' => ['required', 'integer', 'min:0', 'max:99'],
            'driver_license_photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'vehicle_photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            // Campus (Asia/Manila) local times from a datetime-local input.
            'valid_from' => ['required', 'date_format:Y-m-d\TH:i'],
            'valid_until' => ['required', 'date_format:Y-m-d\TH:i'],
        ], [
            'plate_number.regex' => 'Use letters, numbers, spaces and dashes only.',
            'vehicle_type_id.exists' => 'Choose a vehicle type that has a parking area.',
        ]);

        $from = CarbonImmutable::createFromFormat('Y-m-d\TH:i', $data['valid_from'], CampusTime::zone());
        $until = CarbonImmutable::createFromFormat('Y-m-d\TH:i', $data['valid_until'], CampusTime::zone());
        $plate = strtoupper(preg_replace('/\s+/', ' ', trim($data['plate_number'])));
        $actor = $request->user();
        if ($data['driver_license_expiry'] < CampusTime::date($until)) {
            throw ValidationException::withMessages(['driver_license_expiry' => 'The license must be valid through the end of the visit.']);
        }

        return $this->requests->withDocuments($request, function ($paths) use ($data, $plate, $from, $until, $actor) {
            if (!empty($data['visitor_id'])) {
                $visitorId = (int) $data['visitor_id'];
            } else {
                $visitorId = DB::table('visitors')->insertGetId([
                    'first_name' => trim($data['first_name']),
                    'last_name' => trim($data['last_name']),
                    'contact_number' => $data['contact_number'],
                    'email' => $data['email'] ?? null,
                    'id_type' => $data['id_type'] ?? null,
                    'id_number' => $data['id_number'] ?? null,
                    'affiliation' => $data['affiliation'] ?? null,
                    'is_blacklisted' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], 'visitor_id');
            }

            if (DB::table('visitors')->where('visitor_id', $visitorId)->value('is_blacklisted')) {
                $this->fail('visitor_id', 'This visitor is blacklisted and cannot request access.');
            }

            $vehicleId = self::registerVisitorVehicle($visitorId, $plate, (int) $data['vehicle_type_id'], $data);
            $requestId = $this->requests->create($visitorId, $vehicleId, $data['purpose'], $data['visiting'], $from, $until, $actor, 'staff_desk');
            DB::table('visitor_requests')->where('request_id', $requestId)->update([
                ...$paths, 'driver_is_requester' => false, 'driver_name' => trim($data['driver_name']),
                'driver_license_encrypted' => \Illuminate\Support\Facades\Crypt::encryptString(strtoupper(trim($data['driver_license_number']))),
                'driver_license_expiry' => $data['driver_license_expiry'], 'passenger_count' => (int) $data['passenger_count'],
            ]);

            return response()->json(['message' => "Visitor request #{$requestId} for {$plate} is waiting for Admin approval.", 'request_id' => $requestId], 201);
        });
    }

    /**
     * Reuse the visitor's vehicle with this plate or register it. A plate
     * belongs to one owner: a campus user's or another visitor's plate is refused.
     */
    public static function registerVisitorVehicle(int $visitorId, string $plate, int $vehicleTypeId, array $details = []): int
    {
        $vehicle = DB::table('vehicles')->where('plate_number', $plate)->lockForUpdate()->first();

        if ($vehicle && ((int) $vehicle->owner_visitor_id !== $visitorId || $vehicle->deleted_at !== null)) {
            throw ValidationException::withMessages(['plate_number' => $vehicle->owner_user_id
                ? "Plate {$plate} is registered to a campus user, who must use their RFID tag."
                : "Plate {$plate} is registered to another visitor."]);
        }

        if ($vehicle) {
            return (int) $vehicle->vehicle_id;
        }

        return (int) DB::table('vehicles')->insertGetId([
            'owner_visitor_id' => $visitorId,
            'vehicle_type_id' => $vehicleTypeId,
            'plate_number' => $plate,
            'make' => $details['make'] ?? null,
            'model' => $details['model'] ?? null,
            'color' => $details['color'] ?? null,
            'registration_status' => 'registered',
            'created_at' => now(),
            'updated_at' => now(),
        ], 'vehicle_id');
    }

    /** Only Admins approve or reject visitor requests (not ordinary Parking Staff). */
    private function notAdmin(Request $request): ?JsonResponse
    {
        return $request->user()->isAdmin()
            ? null
            : response()->json(['message' => 'Only an Administrator can approve or reject visitor parking requests.'], 403);
    }

    /** Approve a pending request (Admin only) and issue its Visitor QR pass. */
    public function approve(Request $request, int $id): JsonResponse
    {
        if ($denied = $this->notAdmin($request)) {
            return $denied;
        }

        $data = $request->validate(['remarks' => ['nullable', 'string', 'max:255']]);

        $pass = DB::transaction(fn () => $this->requests->approve($id, $request->user(), $data['remarks'] ?? null, $request->boolean('documents_reviewed')));

        return response()->json(['message' => 'Request approved. Visitor QR pass issued and the guest was notified.', 'pass' => self::passSummary($pass, CarbonImmutable::now())]);
    }

    /** Reject a pending request (Admin only) with a reason. No pass is issued. */
    public function reject(Request $request, int $id): JsonResponse
    {
        if ($denied = $this->notAdmin($request)) {
            return $denied;
        }

        $data = $request->validate(['remarks' => ['required', 'string', 'max:255']], ['remarks.required' => 'Give the reason for rejecting the request.']);

        DB::transaction(fn () => $this->requests->reject($id, $request->user(), $data['remarks']));

        return response()->json(['message' => 'Request rejected. No QR pass was issued.']);
    }

    // ----------------------------------------------------------- passes

    public function passes(Request $request): JsonResponse
    {
        if ($denied = $this->forbidden($request)) {
            return $denied;
        }

        $search = trim((string) $request->query('search', ''));
        $now = CarbonImmutable::now();

        $rows = DB::table('qr_passes as q')
            ->where('q.pass_type', 'visitor')
            ->leftJoin('visitors as vis', 'vis.visitor_id', '=', 'q.visitor_id')
            ->leftJoin('vehicles as v', 'v.vehicle_id', '=', 'q.vehicle_id')
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('v.plate_number', 'like', "%{$search}%")->orWhere('q.pass_code', 'like', "%{$search}%")
                ->orWhereRaw("CONCAT_WS(' ', vis.first_name, vis.last_name) like ?", ["%{$search}%"])))
            ->orderByDesc('q.qr_pass_id')
            ->limit(300)
            ->get(['q.*'])
            ->map(fn ($p) => self::passSummary($p, $now));

        $status = $request->query('status');

        return response()->json(['data' => $status ? $rows->where('status', $status)->values() : $rows]);
    }

    /** Look up a scanned or typed pass code for the staff verification screen. */
    public function lookup(Request $request): JsonResponse
    {
        if ($denied = $this->forbidden($request)) {
            return $denied;
        }

        $code = trim((string) $request->validate(['code' => ['required', 'string', 'max:64']])['code']);
        $pass = DB::table('qr_passes')->where('pass_code', $code)->first();

        if (!$pass) {
            return response()->json(['message' => 'No QR pass matches this code. It is not a PASS visitor pass.'], 404);
        }

        $now = CarbonImmutable::now();
        $vehicle = $pass->vehicle_id ? DB::table('vehicles')->where('vehicle_id', $pass->vehicle_id)->first() : null;
        $inside = $vehicle ? DB::table('parking_transactions as t')->leftJoin('parking_slots as s', 's.slot_id', '=', 't.slot_id')
            ->where('t.vehicle_id', $vehicle->vehicle_id)->where('t.transaction_status', 'active')->first(['t.transaction_id', 't.entry_datetime', 's.slot_code']) : null;

        // Campus-user vehicles may not use QR at all (same rule as the gate).
        $entryProblem = $vehicle && $vehicle->owner_user_id
            ? 'Campus users must use their RFID tag; Visitor QR passes are for visitors only.'
            : VisitorPassPolicy::entryProblem($pass, $vehicle, $now);

        return response()->json(self::passSummary($pass, $now) + [
            'entry_allowed' => $entryProblem === null && !$inside,
            'entry_problem' => $inside ? 'The vehicle is already inside.' : $entryProblem,
            'inside' => $inside ? ['transaction_id' => $inside->transaction_id, 'slot_code' => $inside->slot_code, 'entry_datetime' => ParkingOperationsController::iso($inside->entry_datetime)] : null,
        ]);
    }

    public function revoke(Request $request, int $id): JsonResponse
    {
        if ($denied = $this->forbidden($request)) {
            return $denied;
        }

        $data = $request->validate(['reason' => ['required', 'string', 'max:255']], ['reason.required' => 'Give the reason for revoking the pass.']);

        return DB::transaction(function () use ($id, $data, $request) {
            $pass = DB::table('qr_passes')->where('qr_pass_id', $id)->lockForUpdate()->first();

            if (!$pass) {
                return response()->json(['message' => 'QR pass not found.'], 404);
            }
            if ($pass->pass_status !== 'active') {
                return response()->json(['message' => "This pass is already {$pass->pass_status}."], 409);
            }

            DB::table('qr_passes')->where('qr_pass_id', $id)->update([
                'pass_status' => 'revoked',
                'revoked_at' => now(),
                'revoke_reason' => $data['reason'],
                'updated_at' => now(),
            ]);

            $this->audit($request, 'qr_pass', $id, 'qr_pass_revoked', ['pass_status' => 'active'], ['pass_status' => 'revoked', 'reason' => $data['reason']]);

            // A guest with an account sees the revocation in their notifications.
            $guestUserId = $pass->visitor_id ? DB::table('visitors')->where('visitor_id', $pass->visitor_id)->value('user_id') : null;
            if ($guestUserId) {
                $plate = DB::table('vehicles')->where('vehicle_id', $pass->vehicle_id)->value('plate_number');
                ParkingNotifier::notifyUser((int) $guestUserId, 'visitor_pass_revoked', 'Visitor QR pass revoked',
                    "Your visitor QR pass for {$plate} was revoked and can no longer be used to enter. Reason: {$data['reason']}",
                    'qr_pass', $id, ['qr_pass_id' => $id, 'screen' => 'guest_dashboard']);
            }

            return response()->json(['message' => 'QR pass revoked. It can no longer be used to enter.']);
        });
    }

    // ----------------------------------------------------------- helpers

    public static function passSummary(object $pass, CarbonImmutable $now): array
    {
        $visitor = $pass->visitor_id ? DB::table('visitors')->where('visitor_id', $pass->visitor_id)->first(['first_name', 'last_name']) : null;
        $vehicle = $pass->vehicle_id ? DB::table('vehicles as v')->leftJoin('vehicle_types as vt', 'vt.vehicle_type_id', '=', 'v.vehicle_type_id')
            ->where('v.vehicle_id', $pass->vehicle_id)->first(['v.plate_number', 'vt.type_name']) : null;
        $visitRequest = $pass->request_id ? DB::table('visitor_requests')->where('request_id', $pass->request_id)->first() : null;
        $requestStatus = $visitRequest?->request_status;

        return [
            'qr_pass_id' => $pass->qr_pass_id,
            'pass_code' => $pass->pass_code,
            'status' => VisitorPassPolicy::displayStatus($pass, $now),
            'stored_status' => $pass->pass_status,
            'request_id' => $pass->request_id,
            'request_status' => $requestStatus,
            'driver' => $visitRequest ? VisitorRequestService::driverSummary($visitRequest) : null,
            'documents' => $visitRequest ? VisitorRequestService::documentSummary($visitRequest) : null,
            'legacy' => !$pass->request_id,
            'visitor_name' => $visitor ? trim("{$visitor->first_name} {$visitor->last_name}") : null,
            'plate_number' => $vehicle->plate_number ?? null,
            'vehicle_type' => $vehicle->type_name ?? null,
            'valid_from' => ParkingOperationsController::iso($pass->valid_from),
            'valid_until' => ParkingOperationsController::iso($pass->valid_until),
            'use_count' => (int) $pass->use_count,
            'max_uses' => (int) $pass->max_uses,
            'issued_at' => ParkingOperationsController::iso($pass->issued_at),
            'revoked_at' => ParkingOperationsController::iso($pass->revoked_at),
            'revoke_reason' => $pass->revoke_reason,
        ];
    }

    /** Fail inside a transaction: throwing rolls back anything already written. */
    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }

    private function invalid(string $field, string $message): JsonResponse
    {
        return response()->json(['message' => $message, 'errors' => [$field => [$message]]], 422);
    }

    private function audit(Request $request, string $type, int $id, string $action, ?array $old, array $new): void
    {
        DB::table('audit_logs')->insert([
            'user_id' => $request->user()->user_id,
            'actor_label' => $request->user()->user_code,
            'action' => $action,
            'auditable_type' => $type,
            'auditable_id' => $id,
            'old_values' => $old ? json_encode($old) : null,
            'new_values' => json_encode($new),
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 252),
            'created_at' => now(),
        ]);
    }
}
