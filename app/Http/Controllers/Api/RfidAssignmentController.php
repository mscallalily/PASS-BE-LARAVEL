<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\CampusTime;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RfidAssignmentController extends Controller
{
    private function authorizeAdmin(Request $request): void
    {
        $user = $request->user();
        abort_unless($user && $user->account_status === 'active' &&
            ($user->user_type === 'admin' || $user->roles()->whereIn('role_code', ['admin', 'parking_admin'])->where('is_active', true)->exists()), 403);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);
        $request->merge(['tag_uid' => trim((string) $request->input('tag_uid'))]);
        $data = $request->validate([
            'tag_uid' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9:_-]+$/'],
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,vehicle_id'],
            'valid_until' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:'.CampusTime::date()],
        ]);
        try {
            $id = DB::transaction(function () use ($data, $request) {
                $today = CampusTime::date();
                $vehicle = DB::table('vehicles')->where('vehicle_id', $data['vehicle_id'])->lockForUpdate()->first();
                if (!$vehicle || $vehicle->deleted_at || $vehicle->registration_status !== 'registered' || !$vehicle->owner_user_id) {
                    throw ValidationException::withMessages(['vehicle_id' => 'Select a registered regular-user vehicle.']);
                }
                $owner = DB::table('users')->where('user_id', $vehicle->owner_user_id)->whereNull('deleted_at')->lockForUpdate()->first();
                if (!$owner || $owner->account_status !== 'active' || !in_array($owner->user_type, ['student', 'faculty', 'employee'], true)) {
                    throw ValidationException::withMessages(['vehicle_id' => 'The vehicle owner must be an active student, faculty member or employee.']);
                }
                $authorized = DB::table('vehicle_authorizations')->where('vehicle_id', $vehicle->vehicle_id)->where('authorization_status', 'approved')
                    ->where(fn ($q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', $today))
                    ->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', $today))->exists();
                if (!$authorized) throw ValidationException::withMessages(['vehicle_id' => 'An approved, currently valid vehicle authorization is required.']);
                $driverId = DB::table('vehicle_driver')->where('vehicle_id', $vehicle->vehicle_id)->orderByDesc('is_primary')->value('driver_id');
                $driver = $driverId ? DB::table('drivers')->where('driver_id', $driverId)->first() : null;
                $license = $driver ? DB::table('driver_licenses')->where('driver_id', $driverId)->orderByDesc('license_id')->first() : null;
                if (!$driver || $driver->driver_status !== 'active' || !$license || $license->license_status !== 'valid'
                    || $license->verification_status !== 'verified' || !$license->expiry_date || substr($license->expiry_date, 0, 10) < $today) {
                    throw ValidationException::withMessages(['vehicle_id' => 'The linked driver needs a verified, valid, unexpired license.']);
                }
                if (DB::table('rfid_assignments')->where('vehicle_id', $vehicle->vehicle_id)->where('assignment_status', 'active')->exists()) {
                    throw ValidationException::withMessages(['vehicle_id' => 'This vehicle already has an active RFID assignment. Revoke it before assigning another tag.']);
                }
                $tag = DB::table('rfid_tags')->where('tag_uid', $data['tag_uid'])->lockForUpdate()->first();
                if ($tag && ($tag->tag_status !== 'available' || DB::table('rfid_assignments')->where('rfid_id', $tag->rfid_id)->where('assignment_status', 'active')->exists())) {
                    throw ValidationException::withMessages(['tag_uid' => 'This tag is already assigned or unavailable.']);
                }
                $tagId = $tag?->rfid_id ?? DB::table('rfid_tags')->insertGetId([
                    'tag_uid' => $data['tag_uid'], 'tag_type' => 'sticker', 'tag_status' => 'available', 'created_at' => now(), 'updated_at' => now(),
                ], 'rfid_id');
                $id = DB::table('rfid_assignments')->insertGetId([
                    'rfid_id' => $tagId, 'user_id' => $owner->user_id, 'vehicle_id' => $vehicle->vehicle_id,
                    'assigned_by' => $request->user()->user_id, 'assigned_at' => now(),
                    'valid_until' => $data['valid_until'] ?? null, 'assignment_status' => 'active', 'created_at' => now(), 'updated_at' => now(),
                ], 'assignment_id');
                DB::table('rfid_tags')->where('rfid_id', $tagId)->update(['tag_status' => 'assigned', 'updated_at' => now()]);
                $this->audit($request, $id, 'rfid_assigned');
                return $id;
            });
        } catch (QueryException $exception) {
            if (($exception->errorInfo[1] ?? null) === 1062) {
                throw ValidationException::withMessages(['tag_uid' => 'The tag was assigned by another request. Refresh and try again.']);
            }
            throw $exception;
        }
        return response()->json(['message' => 'RFID assigned successfully.', 'assignment_id' => $id], 201);
    }

    public function revoke(Request $request, int $id): JsonResponse
    {
        $this->authorizeAdmin($request);
        return DB::transaction(function () use ($request, $id) {
            $reference = DB::table('rfid_assignments')->where('assignment_id', $id)->first();
            abort_unless($reference, 404);
            if ($reference->vehicle_id) DB::table('vehicles')->where('vehicle_id', $reference->vehicle_id)->lockForUpdate()->first();
            $assignment = DB::table('rfid_assignments')->where('assignment_id', $id)->lockForUpdate()->first();
            abort_unless($assignment, 404);
            if ($assignment->assignment_status !== 'active') return response()->json(['message' => 'This assignment is no longer active.'], 409);
            if ($assignment->vehicle_id && DB::table('parking_transactions')->where('vehicle_id', $assignment->vehicle_id)->where('transaction_status', 'active')->exists()) {
                return response()->json(['message' => 'Record the vehicle exit before revoking its RFID.'], 409);
            }
            DB::table('rfid_assignments')->where('assignment_id', $id)->update(['assignment_status' => 'revoked', 'unassigned_at' => now(), 'updated_at' => now()]);
            DB::table('rfid_tags')->where('rfid_id', $assignment->rfid_id)->update(['tag_status' => 'deactivated', 'updated_at' => now()]);
            $this->audit($request, $id, 'rfid_revoked');
            return response()->json(['message' => 'RFID revoked. Future entry scans will be denied.']);
        });
    }

    private function audit(Request $request, int $id, string $action): void
    {
        DB::table('audit_logs')->insert([
            'user_id' => $request->user()->user_id, 'actor_label' => $request->user()->user_code,
            'action' => $action, 'auditable_type' => 'rfid_assignments', 'auditable_id' => $id,
            'ip_address' => $request->ip(), 'user_agent' => Str::limit((string) $request->userAgent(), 252), 'created_at' => now(),
        ]);
    }
}
