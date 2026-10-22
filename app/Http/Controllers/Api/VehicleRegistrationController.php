<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\CampusTime;
use App\Support\VehicleParkingArea;
use App\Services\Vehicles\VehicleRegistrationPolicy;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Throwable;

class VehicleRegistrationController extends Controller
{
    public function __construct(private VehicleRegistrationPolicy $registration) {}

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
                'rejectionReason' => $auth?->authorization_status === 'rejected' ? $auth->remarks : null,
                'canResubmit' => $vehicle->registration_status === 'registered' && in_array($auth?->authorization_status, ['pending', 'rejected'], true),
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
        foreach (['plate_number', 'make', 'model', 'color'] as $field) {
            if (is_string($request->input($field))) $request->merge([$field => mb_strtoupper(trim($request->input($field)))]);
        }
        $data = $request->validate([
            'plate_number' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9]+(?:[ -]?[A-Z0-9]+)*$/', 'unique:vehicles,plate_number'],
            'vehicle_type' => ['required', 'string', 'exists:vehicle_types,type_name'],
            'year_model' => ['nullable', 'integer', 'min:1900', 'max:'.(CampusTime::now()->year + 1)],
            'make' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'color' => ['required', 'string', 'max:50'],
            'vehicle_photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        $this->registration->validatePlate($data['plate_number']);
        if (DB::table('vehicles')->whereRaw("REPLACE(REPLACE(UPPER(plate_number), '-', ''), ' ', '') = ?", [\App\Support\PlateNumber::key($data['plate_number'])])->exists()) throw ValidationException::withMessages(['plate_number' => 'This plate number is already registered.']);

        $this->registration->driver($user);
        VehicleParkingArea::forType(DB::table('vehicle_types')->where('type_name', $data['vehicle_type'])->first());
        $photoPath = $request->file('vehicle_photo')->store('vehicle-photos', 'private');
        if (!$photoPath) abort(500, 'Unable to store vehicle photo.');
        try {
            $vehicle = DB::transaction(function () use ($user, $data, $photoPath, $request) {
                DB::table('users')->where('user_id', $user->user_id)->lockForUpdate()->first();
                $driver = $this->registration->driver($user, true);
                $type = DB::table('vehicle_types')->where('type_name', $data['vehicle_type'])->first();
                $area = VehicleParkingArea::forType($type);
                $vehicle = Vehicle::create([
                    'owner_user_id' => $user->user_id,
                    'vehicle_type_id' => $type->vehicle_type_id,
                    'plate_number' => $data['plate_number'],
                    'year_model' => $data['year_model'] ?? null,
                    'make' => $data['make'], 'model' => $data['model'], 'color' => $data['color'],
                    'registration_status' => 'registered',
                ]);
                DB::table('vehicles')->where('vehicle_id', $vehicle->vehicle_id)->update(['vehicle_photo_path' => $photoPath]);
                $this->registration->authorization($vehicle->vehicle_id, $area->parking_area_id, $user->user_id, $request->ip());
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
            'message' => 'Vehicle submitted. Pending review by PASS staff; RFID assignment is available only after approval.',
            'authorization_status' => 'pending', 'approval_basis' => 'staff_review_required',
            'plate_verified' => false, 'photo_verified' => false, 'ownership_verified' => false, 'authenticity_verified' => false,
            'vehicle_id' => $vehicle->vehicle_id,
        ], 201);
    }

    /** Correct an existing submission; keep its identity, previous decision and photos. */
    public function resubmit(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        abort_unless($this->regular($user), 403);
        [$vehicle, $authorization] = $this->correctionTarget($user, $id);
        foreach (['plate_number', 'make', 'model', 'color'] as $field) {
            if (is_string($request->input($field))) $request->merge([$field => mb_strtoupper(trim($request->input($field)))]);
        }
        $data = $request->validate([
            'plate_number' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9]+(?:[ -]?[A-Z0-9]+)*$/', Rule::unique('vehicles', 'plate_number')->ignore($id, 'vehicle_id')],
            'vehicle_type' => ['required', 'string', 'exists:vehicle_types,type_name'],
            'year_model' => ['nullable', 'integer', 'min:1900', 'max:'.(CampusTime::now()->year + 1)],
            'make' => ['required', 'string', 'max:100'], 'model' => ['required', 'string', 'max:100'],
            'color' => ['required', 'string', 'max:50'],
            'vehicle_photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);
        $this->registration->validatePlate($data['plate_number']);
        if (DB::table('vehicles')->where('vehicle_id', '<>', $id)->whereRaw(
            "REPLACE(REPLACE(UPPER(plate_number), '-', ''), ' ', '') = ?", [\App\Support\PlateNumber::key($data['plate_number'])]
        )->exists()) throw ValidationException::withMessages(['plate_number' => 'This plate number is already registered to another vehicle.']);
        $this->registration->driver($user);
        VehicleParkingArea::forType(DB::table('vehicle_types')->where('type_name', $data['vehicle_type'])->first());
        $path = $request->file('vehicle_photo')->store('vehicle-photos', 'private');
        if (!$path) abort(500, 'Unable to store vehicle photo.');
        try {
            DB::transaction(function () use ($request, $id, $user, $vehicle, $authorization, $data, $path) {
                DB::table('users')->where('user_id', $user->user_id)->lockForUpdate()->first();
                $driver = $this->registration->driver($user, true);
                [$current, $latest] = $this->correctionTarget($user, $id, true);
                $fields = ['plate_number', 'vehicle_type_id', 'year_model', 'make', 'model', 'color', 'vehicle_photo_path', 'registration_status'];
                abort_unless($current->only($fields) === $vehicle->only($fields)
                    && (int) $latest->authorization_id === (int) $authorization->authorization_id
                    && $latest->authorization_status === $authorization->authorization_status,
                    409, 'This submission changed while the registration was being submitted. Refresh before resubmitting.');
                $type = DB::table('vehicle_types')->where('type_name', $data['vehicle_type'])->first();
                $area = VehicleParkingArea::forType($type);
                $old = $current->only($fields);
                $new = ['plate_number' => $data['plate_number'], 'vehicle_type_id' => $type->vehicle_type_id,
                    'year_model' => $data['year_model'] ?? null, 'make' => $data['make'], 'model' => $data['model'],
                    'color' => $data['color'], 'vehicle_photo_path' => $path, 'updated_at' => now()];
                DB::table('vehicles')->where('vehicle_id', $id)->update($new);
                DB::table('vehicle_driver')->updateOrInsert(['vehicle_id' => $id, 'driver_id' => $driver->driver_id],
                    ['relationship' => 'owner', 'is_primary' => true, 'updated_at' => now()]);
                $this->registration->authorization($id, $area->parking_area_id, $user->user_id, $request->ip());
                DB::table('audit_logs')->insert([
                    'user_id' => $user->user_id, 'actor_label' => $user->user_code, 'action' => 'vehicle_resubmitted',
                    'auditable_type' => 'vehicles', 'auditable_id' => $id, 'ip_address' => $request->ip(),
                    'old_values' => json_encode($old + ['authorization_id' => $authorization->authorization_id, 'authorization_status' => $authorization->authorization_status]),
                    'new_values' => json_encode(array_diff_key($new, ['updated_at' => true]) + ['authorization_status' => 'pending', 'basis' => 'staff_review_required']),
                    'created_at' => now(),
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk('private')->delete($path);
            if ($exception instanceof QueryException && ($exception->errorInfo[1] ?? null) === 1062) {
                throw ValidationException::withMessages(['plate_number' => 'This plate number is already registered to another vehicle.']);
            }
            throw $exception;
        }
        return response()->json(['message' => 'Corrected vehicle submitted. Pending review by PASS staff. Your previous submission remains in history.',
            'vehicle_id' => $id, 'authorization_status' => 'pending', 'approval_basis' => 'staff_review_required',
            'plate_verified' => false, 'photo_verified' => false, 'ownership_verified' => false, 'authenticity_verified' => false]);
    }

    private function correctionTarget(User $user, int $id, bool $lock = false): array
    {
        $query = Vehicle::where('owner_user_id', $user->user_id);
        // Staff review locks authorization before vehicle; use the same order.
        $authorization = $lock ? DB::table('vehicle_authorizations')->where('vehicle_id', $id)
            ->orderByDesc('authorization_id')->lockForUpdate()->first() : null;
        if ($lock) $query->lockForUpdate();
        $vehicle = $query->findOrFail($id);
        if (!$lock) $authorization = DB::table('vehicle_authorizations')->where('vehicle_id', $id)->orderByDesc('authorization_id')->first();
        abort_unless($vehicle->registration_status === 'registered' && $authorization
            && in_array($authorization->authorization_status, ['pending', 'rejected'], true),
            409, 'Only pending or rejected registrations can be corrected. Approved, suspended or revoked vehicles require PASS staff assistance.');
        abort_if(DB::table('parking_transactions')->where('vehicle_id', $id)->where('transaction_status', 'active')->exists(),
            409, 'Record this vehicle’s exit before correcting its registration.');
        abort_if(DB::table('rfid_assignments')->where('vehicle_id', $id)->where('assignment_status', 'active')->whereNull('unassigned_at')->exists(),
            409, 'Ask PASS staff to release this vehicle’s existing RFID assignment before correcting its registration.');
        return [$vehicle, $authorization];
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
        $latest = $vehicle->authorizations()->orderByDesc('authorization_id')->first();
        abort_unless($latest && in_array($latest->authorization_status, ['pending', 'rejected'], true), 409, 'Only pending or rejected submissions can have their photo replaced.');
        $this->registration->driver($user);
        $path = $request->file('vehicle_photo')->store('vehicle-photos', 'private');
        if (!$path) abort(500, 'Unable to store vehicle photo.');
        try {
            DB::transaction(function () use ($id, $user, $path, $request, $vehicle) {
                DB::table('users')->where('user_id', $user->user_id)->lockForUpdate()->first();
                $this->registration->driver($user, true);
                $current = Vehicle::where('owner_user_id', $user->user_id)->lockForUpdate()->findOrFail($id);
                abort_unless($current->registration_status === 'registered' && $current->plate_number === $vehicle->plate_number && $current->vehicle_type_id === $vehicle->vehicle_type_id, 409, 'The vehicle changed. Refresh before submitting again.');
                $authorization = DB::table('vehicle_authorizations')->where('vehicle_id', $id)->orderByDesc('authorization_id')->lockForUpdate()->first();
                abort_unless($authorization && in_array($authorization->authorization_status, ['pending', 'rejected'], true), 409, 'The authorization changed. Refresh before submitting again.');
                $area = VehicleParkingArea::forType(DB::table('vehicle_types')->where('vehicle_type_id', $current->vehicle_type_id)->first());
                DB::table('vehicles')->where('vehicle_id', $id)->update(['vehicle_photo_path' => $path, 'updated_at' => now()]);
                // Append the new decision; retain previous photos and staff decisions in protected history.
                $this->registration->authorization($id, $area->parking_area_id, $user->user_id, $request->ip());
            });
        } catch (Throwable $exception) { Storage::disk('private')->delete($path); throw $exception; }
        return response()->json(['message' => 'Vehicle submitted. Pending review by PASS staff; RFID assignment is available only after approval.', 'authorization_status' => 'pending', 'approval_basis' => 'staff_review_required', 'plate_verified' => false, 'photo_verified' => false]);
    }
}
