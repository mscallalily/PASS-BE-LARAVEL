<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\CampusTime;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Throwable;

class VehicleRegistrationController extends Controller
{
    private function regular(User $user): bool
    {
        return $user->account_status === 'active' && $user->roles()
            ->whereIn('role_code', ['student', 'faculty', 'employee'])->where('is_active', true)->exists();
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $staff = $user->canManageParking();
        abort_unless($staff || $this->regular($user), 403);

        $query = Vehicle::with(['type', 'owner.profile', 'authorizations' => fn ($q) => $q->orderByDesc('authorization_id')]);
        if (!$staff) $query->where('owner_user_id', $user->user_id);
        $selectionIds = DB::table('user_vehicle_selections')->pluck('vehicle_id', 'user_id');
        $insideOwnerIds = DB::table('parking_transactions as t')->join('vehicles as v', 'v.vehicle_id', '=', 't.vehicle_id')->where('t.transaction_status', 'active')->whereNotNull('v.owner_user_id')->distinct()->pluck('v.owner_user_id')->all();
        $rows = $query->orderByDesc('vehicle_id')->get()->map(function (Vehicle $vehicle) use ($selectionIds, $insideOwnerIds) {
            $auth = $vehicle->authorizations->first();
            $profile = $vehicle->owner?->profile;
            return [
                'id' => (string) $vehicle->vehicle_id,
                'selectedForUse' => (int) ($selectionIds[$vehicle->owner_user_id] ?? 0) === (int) $vehicle->vehicle_id,
                'switchLocked' => in_array((int) $vehicle->owner_user_id, array_map('intval', $insideOwnerIds), true),
                'hasPhoto' => (bool) $vehicle->vehicle_photo_path,
                'plate' => $vehicle->plate_number,
                'owner' => trim(($profile?->first_name ?? '').' '.($profile?->last_name ?? '')),
                'make' => $vehicle->make ?? '', 'model' => $vehicle->model ?? '',
                'year' => $vehicle->year_model, 'type' => $vehicle->type?->type_name ?? '',
                'color' => $vehicle->color ?? '',
                'rfid' => DB::table('rfid_assignments as a')->join('rfid_tags as t', 'a.rfid_id', '=', 't.rfid_id')
                    ->where('a.vehicle_id', $vehicle->vehicle_id)->where('a.assignment_status', 'active')
                    ->whereNull('a.unassigned_at')->where('t.tag_status', 'assigned')
                    ->where(fn ($q) => $q->whereNull('a.valid_until')->orWhere('a.valid_until', '>=', CampusTime::date()))
                    ->orderByDesc('a.assignment_id')->value('t.tag_uid'),
                'status' => $auth?->authorization_status ?? 'pending',
                'registrationStatus' => $vehicle->registration_status,
                'authorizationStatus' => $auth?->authorization_status,
                'validity' => $auth?->valid_until,
            ];
        });
        return response()->json(['vehicles' => $rows]);
    }

    public function selectForUse(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        abort_unless($this->regular($user), 403);
        return DB::transaction(function () use ($user, $id, $request) {
            // Same owner lock as gate entry: a selection cannot change mid-scan.
            DB::table('users')->where('user_id', $user->user_id)->lockForUpdate()->first();
            $vehicle = DB::table('vehicles')->where('vehicle_id', $id)->where('owner_user_id', $user->user_id)->whereNull('deleted_at')->first();
            abort_unless($vehicle, 404);
            $inside = DB::table('parking_transactions as t')->join('vehicles as v', 'v.vehicle_id', '=', 't.vehicle_id')
                ->where('v.owner_user_id', $user->user_id)->where('t.transaction_status', 'active')->lockForUpdate()->first(['t.transaction_id']);
            abort_if($inside, 409, 'Record your parked vehicle’s exit before switching vehicles.');
            $today = CampusTime::date();
            $authorized = DB::table('vehicle_authorizations')->where('vehicle_id', $id)->orderByDesc('authorization_id')->first();
            abort_unless($vehicle->registration_status === 'registered' && $authorized && $authorized->authorization_status === 'approved'
                && (!$authorized->valid_from || $authorized->valid_from <= $today) && (!$authorized->valid_until || $authorized->valid_until >= $today), 422, 'Select a currently authorized vehicle.');
            $rfid = DB::table('rfid_assignments as a')->join('rfid_tags as t', 't.rfid_id', '=', 'a.rfid_id')
                ->where('a.vehicle_id', $id)->where('a.assignment_status', 'active')->whereNull('a.unassigned_at')->where('t.tag_status', 'assigned')
                ->where(fn ($q) => $q->whereNull('a.valid_until')->orWhere('a.valid_until', '>=', $today))->exists();
            abort_unless($rfid, 422, 'This vehicle needs an active assigned RFID first.');
            DB::table('user_vehicle_selections')->updateOrInsert(['user_id' => $user->user_id], ['vehicle_id' => $id, 'updated_at' => now()]);
            DB::table('audit_logs')->insert(['user_id' => $user->user_id, 'actor_label' => $user->user_code, 'action' => 'vehicle_selected_for_use', 'auditable_type' => 'vehicles', 'auditable_id' => $id, 'ip_address' => $request->ip(), 'created_at' => now()]);
            return response()->json(['message' => $vehicle->plate_number.' is selected for use. Your other vehicles are inactive for entry.']);
        });
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($this->regular($user), 403);
        $request->merge(['plate_number' => strtoupper(trim((string) $request->input('plate_number')))]);
        $data = $request->validate([
            'plate_number' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9]+(?:[ -]?[A-Z0-9]+)*$/', 'unique:vehicles,plate_number'],
            'vehicle_type' => ['required', 'string', 'exists:vehicle_types,type_name'],
            'year_model' => ['nullable', 'integer', 'min:1900', 'max:'.(CampusTime::now()->year + 1)],
            'make' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'color' => ['required', 'string', 'max:50'],
            'vehicle_photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        $photoPath = $request->file('vehicle_photo')->store('vehicle-photos', 'private');
        if (!$photoPath) abort(500, 'Unable to store vehicle photo.');
        try {
            $vehicle = DB::transaction(function () use ($user, $data, $photoPath) {
                // License submission permits registration; staff verification and
                // vehicle authorization remain separate mandatory access steps.
                $driver = DB::table('drivers')->where('user_id', $user->user_id)
                    ->where('driver_status', 'active')->lockForUpdate()->first();
                $license = $driver ? DB::table('driver_licenses')->where('driver_id', $driver->driver_id)
                    ->orderByDesc('license_id')->lockForUpdate()->first() : null;
                if (!$license || !in_array($license->verification_status, ['processing', 'pending_review', 'verified'], true)
                    || $license->license_status !== 'valid' || !$license->expiry_date
                    || substr($license->expiry_date, 0, 10) < CampusTime::date()) {
                    throw ValidationException::withMessages(['license' => 'Submit a current driver license before registering a vehicle.']);
                }
                $type = DB::table('vehicle_types')->where('type_name', $data['vehicle_type'])->first();
                $vehicle = Vehicle::create([
                    'owner_user_id' => $user->user_id,
                    'vehicle_type_id' => $type->vehicle_type_id,
                    'plate_number' => $data['plate_number'],
                    'year_model' => $data['year_model'] ?? null,
                    'make' => $data['make'], 'model' => $data['model'], 'color' => $data['color'],
                    'registration_status' => 'registered',
                ]);
                DB::table('vehicles')->where('vehicle_id', $vehicle->vehicle_id)->update(['vehicle_photo_path' => $photoPath]);
                $vehicle->authorizations()->create(['authorization_status' => 'pending', 'requested_at' => now()]);
                DB::table('vehicle_driver')->insert([
                    'vehicle_id' => $vehicle->vehicle_id, 'driver_id' => $driver->driver_id,
                    'relationship' => 'owner', 'is_primary' => true,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                return $vehicle;
            });
        } catch (Throwable $exception) {
            Storage::disk('private')->delete($photoPath);
            if ($exception instanceof QueryException && ($exception->errorInfo[1] ?? null) === 1062) {
                throw ValidationException::withMessages(['plate_number' => 'This plate number is already registered.']);
            }
            throw $exception;
        }

        return response()->json([
            'message' => 'Vehicle registered. Authorization is pending; no RFID has been assigned.',
            'vehicle_id' => $vehicle->vehicle_id,
        ], 201);
    }

    public function photo(Request $request, int $id)
    {
        $vehicle = Vehicle::findOrFail($id);
        $user = $request->user();
        abort_unless($user->canManageParking() || ($this->regular($user) && (int) $vehicle->owner_user_id === (int) $user->user_id), 403);
        $path = $vehicle->vehicle_photo_path;
        abort_unless($path && Storage::disk('private')->exists($path), 404, 'No vehicle photo on file.');
        return response()->file(Storage::disk('private')->path($path), [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function uploadPhoto(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        abort_unless($this->regular($user), 403);
        $request->validate(['vehicle_photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120']]);
        $vehicle = Vehicle::where('owner_user_id', $user->user_id)->findOrFail($id);
        $path = $request->file('vehicle_photo')->store('vehicle-photos', 'private');
        if (!$path) abort(500, 'Unable to store vehicle photo.');
        try {
            $oldPath = DB::transaction(function () use ($id, $user, $path) {
                $authorization = DB::table('vehicle_authorizations')->where('vehicle_id', $id)
                    ->orderByDesc('authorization_id')->lockForUpdate()->first();
                if (!$authorization || !in_array($authorization->authorization_status, ['pending', 'rejected'], true)) {
                    throw ValidationException::withMessages(['vehicle_photo' => 'Only pending or rejected submissions can have their photo replaced.']);
                }
                $vehicle = Vehicle::where('owner_user_id', $user->user_id)->lockForUpdate()->findOrFail($id);
                $old = $vehicle->vehicle_photo_path;
                DB::table('vehicles')->where('vehicle_id', $id)->update(['vehicle_photo_path' => $path, 'updated_at' => now()]);
                DB::table('vehicle_authorizations')->where('authorization_id', $authorization->authorization_id)->update([
                    'authorization_status' => 'pending', 'remarks' => null, 'reviewed_by' => null,
                    'reviewed_at' => null, 'requested_at' => now(), 'updated_at' => now(),
                ]);
                return $old;
            });
        } catch (Throwable $exception) {
            Storage::disk('private')->delete($path);
            throw $exception;
        }
        if ($oldPath) Storage::disk('private')->delete($oldPath);
        return response()->json(['message' => 'Vehicle photo saved. Authorization is pending review.']);
    }

}
