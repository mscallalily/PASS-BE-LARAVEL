<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Parking\ParkingAccessService;
use App\Services\Parking\ParkingNotifier;
use App\Support\CampusTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** A one-exit exception for a different driver, tied to one existing stay. */
class VehicleReleaseController extends Controller
{
    public function index(Request $request)
    {
        $staff = $request->user()->canManageParking();
        $sessions = DB::table('parking_transactions as t')->join('vehicles as v', 'v.vehicle_id', '=', 't.vehicle_id')
            ->where('t.transaction_status', 'active')->whereNotNull('v.owner_user_id');
        $rows = DB::table('vehicle_release_requests as r')->join('parking_transactions as t', 't.transaction_id', '=', 'r.transaction_id')
            ->join('vehicles as v', 'v.vehicle_id', '=', 't.vehicle_id');
        if (!$staff) {
            $sessions->where('v.owner_user_id', $request->user()->user_id);
            $rows->where('r.owner_user_id', $request->user()->user_id);
        }
        $data = $rows->select('r.*', 'v.plate_number', 't.transaction_status')->orderByDesc('r.release_id')->limit(100)->get()->map(function ($row) use ($staff) {
            $license = Crypt::decryptString($row->license_encrypted);
            unset($row->license_encrypted);
            $row->license_reference = $staff ? $license : '•••• '.substr($license, -4);
            if (in_array($row->status, ['pending', 'approved'], true)) {
                if ($row->transaction_status !== 'active') $row->status = 'session_ended';
                elseif ($row->expires_at <= now()->toDateTimeString()) $row->status = 'expired';
            }
            foreach (['expires_at', 'reviewed_at', 'released_at', 'created_at'] as $field) $row->$field = ParkingOperationsController::iso($row->$field);
            return $row;
        });
        return response()->json(['data' => $data, 'sessions' => $sessions->get(['t.transaction_id', 'v.plate_number']), 'can_manage' => $staff])->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'transaction_id' => 'required|integer', 'driver_name' => 'required|string|min:3|max:200',
            'driver_contact' => 'required|string|min:7|max:30', 'license_number' => ['required', 'string', 'min:6', 'max:30', 'regex:/^[A-Za-z0-9 -]+$/'],
            'license_expiry' => 'required|date_format:Y-m-d|after_or_equal:'.CampusTime::date(),
            'reason' => 'required|string|min:10|max:1000', 'acknowledged' => 'required|accepted',
            'exception_reason' => 'prohibited',
        ]);
        $id = DB::transaction(function () use ($request, $data) {
            $session = DB::table('parking_transactions')->where('transaction_id', $data['transaction_id'])->lockForUpdate()->first();
            abort_unless($session && $session->transaction_status === 'active', 409, 'Select a currently parked vehicle.');
            $owner = DB::table('vehicles')->where('vehicle_id', $session->vehicle_id)->value('owner_user_id');
            abort_unless($owner && DB::table('users')->where('user_id', $owner)->whereIn('user_type', ['student', 'faculty', 'employee'])->exists(), 422, 'Emergency release is for registered campus-user vehicles only.');
            $own = (int) $owner === (int) $request->user()->user_id;
            abort_unless($own, 403, 'Only the signed-in vehicle owner may authorize a substitute driver. Contact campus security if the owner is unavailable.');
            abort_if(DB::table('vehicle_release_requests')->where('transaction_id', $session->transaction_id)->whereIn('status', ['pending', 'approved'])->where('expires_at', '>', now())->exists(), 409, 'An unexpired release request already exists for this session.');
            $id = DB::table('vehicle_release_requests')->insertGetId([
                'transaction_id' => $session->transaction_id, 'owner_user_id' => $owner, 'requested_by' => $request->user()->user_id,
                'driver_name' => trim($data['driver_name']), 'driver_contact' => trim($data['driver_contact']),
                'license_encrypted' => Crypt::encryptString(strtoupper(trim($data['license_number']))), 'license_expiry' => $data['license_expiry'],
                'reason' => $data['reason'], 'exception_reason' => null,
                'status' => 'pending', 'expires_at' => now()->addHours(2), 'created_at' => now(), 'updated_at' => now(),
            ], 'release_id');
            $this->audit($request, $id, 'vehicle_release_requested');
            ParkingNotifier::notifyStaff('vehicle_release_requested', 'Emergency vehicle release request', 'Review substitute driver details before releasing the vehicle.', 'vehicle_release_requests', $id);
            return $id;
        });
        return response()->json(['message' => 'Release requested. Staff must inspect the actual driver and physical license. Valid for two hours and one exit only.', 'release_id' => $id], 201);
    }

    public function act(Request $request, int $id, ParkingAccessService $access)
    {
        $data = $request->validate(['action' => 'required|in:reject,cancel,release', 'remarks' => 'nullable|string|max:1000', 'identity_checked' => 'nullable|boolean', 'authorization_checked' => 'nullable|boolean']);
        return DB::transaction(function () use ($request, $id, $data, $access) {
            $reference = DB::table('vehicle_release_requests')->where('release_id', $id)->first();
            abort_unless($reference, 404);
            $staff = $request->user()->canManageParking();
            $own = (int) $reference->owner_user_id === (int) $request->user()->user_id;
            abort_unless($data['action'] === 'cancel' ? ($own || $staff) : $staff, 403);
            $session = DB::table('parking_transactions')->where('transaction_id', $reference->transaction_id)->lockForUpdate()->first();
            $row = DB::table('vehicle_release_requests')->where('release_id', $id)->lockForUpdate()->first();
            abort_unless(in_array($row->status, ['pending', 'approved'], true) && $session->transaction_status === 'active' && $row->expires_at > now()->toDateTimeString(), 409, 'This release request is no longer usable.');
            $action = $data['action'];
            $update = ['updated_at' => now()];
            if ($action === 'release') {
                abort_unless($row->status === 'pending' && !$row->exception_reason && (int) $row->requested_by === (int) $row->owner_user_id, 409, 'A current authorization submitted by the owner is required.');
                abort_unless(($data['identity_checked'] ?? false) && ($data['authorization_checked'] ?? false), 422, 'Confirm the physical driver/license/plate check and the owner authorization.');
                abort_unless($row->license_expiry >= CampusTime::date(), 422, 'Substitute driver license has expired.');
                $plate = DB::table('vehicles')->where('vehicle_id', $session->vehicle_id)->value('plate_number');
                $result = $access->exit(['plate_number' => $plate], $request->user(), ['emergency_release_id' => $id, 'source' => 'emergency_release']);
                abort_unless($result['status'] === 'completed', 409, $result['message'] ?? 'Exit could not be recorded.');
                $event = DB::table('parking_transactions')->where('transaction_id', $session->transaction_id)->value('exit_event_id');
                $update += ['status' => 'used', 'reviewed_by' => $request->user()->user_id, 'reviewed_at' => now(), 'review_remarks' => $data['remarks'] ?? null, 'released_by' => $request->user()->user_id, 'released_at' => now(), 'exit_event_id' => $event];
            } else {
                if ($action === 'reject') abort_unless(strlen(trim($data['remarks'] ?? '')) >= 5, 422, 'Enter a rejection reason.');
                $update += ['status' => $action === 'cancel' ? 'cancelled' : 'rejected', 'reviewed_by' => $request->user()->user_id, 'reviewed_at' => now(), 'review_remarks' => $data['remarks'] ?? null];
            }
            DB::table('vehicle_release_requests')->where('release_id', $id)->update($update);
            $this->audit($request, $id, 'vehicle_release_'.$action);
            ParkingNotifier::notifyUser($row->owner_user_id, 'vehicle_release_updated', 'Vehicle release update', 'Emergency vehicle release request #'.$id.' is '.$update['status'].'.', 'vehicle_release_requests', $id);
            return response()->json(['message' => $action === 'release' ? 'Vehicle exit recorded under the owner-authorized substitute driver. Slot released.' : 'Release request '.$update['status'].'.']);
        });
    }

    private function audit(Request $request, int $id, string $action): void
    {
        DB::table('audit_logs')->insert(['user_id' => $request->user()->user_id, 'actor_label' => $request->user()->user_code, 'action' => $action, 'auditable_type' => 'vehicle_release_requests', 'auditable_id' => $id, 'ip_address' => $request->ip(), 'user_agent' => Str::limit((string) $request->userAgent(), 252), 'created_at' => now()]);
    }
}
