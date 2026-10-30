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
        $inventory = app(\App\Services\Rfid\RfidInventory::class);
        $rows = $query->orderByDesc('vehicle_id')->get()->map(function (Vehicle $vehicle) use ($selectionIds, $insideOwnerIds, $inventory) {
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
                // issued | assigned (collect from Admin) | pending_assignment (no sticker available yet) | null
                'rfidStatus' => $inventory->vehicleStatus($vehicle, $auth?->authorization_status),
                'status' => $auth?->authorization_status ?? 'pending',
                'registrationStatus' => $vehicle->registration_status,
                'authorizationStatus' => $auth?->authorization_status,
                'validity' => $auth?->valid_until,
                'rejectionReason' => $auth?->authorization_status === 'rejected' ? $auth->remarks : null,
                'canResubmit' => $vehicle->registration_status === 'registered' && in_array($auth?->authorization_status, ['pending', 'rejected'], true)
                    && $auth?->document_check_status !== 'processing',
                'hasOr' => (bool) $vehicle->or_document_path, 'hasCr' => (bool) $vehicle->cr_document_path,
                'documentCheck' => $this->documentCheckPayload($auth),
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
        // A retry of a registration that was already saved (e.g. the response
        // was lost) returns that registration instead of failing or duplicating it.
        if (is_string($request->input('plate_number')) && ($existing = $this->recentOwnRegistration($user, $request))) {
            return response()->json($this->checkResponse($existing) + ['already_registered' => true]);
        }
        // The details are read from the OR/CR and confirmed by the owner; the
        // vehicle photo is optional (it never verified anything).
        $data = $request->validate([
            'plate_number' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9]+(?:[ -]?[A-Z0-9]+)*$/', 'unique:vehicles,plate_number'],
            'vehicle_type' => ['required', 'string', 'exists:vehicle_types,type_name'],
            'year_model' => ['nullable', 'integer', 'min:1900', 'max:'.(CampusTime::now()->year + 1)],
            'make' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'color' => ['required', 'string', 'max:50'],
            'vehicle_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ] + self::DOCUMENT_RULES, self::DOCUMENT_MESSAGES + self::DETAIL_MESSAGES);

        $this->registration->validatePlate($data['plate_number']);
        if (DB::table('vehicles')->whereRaw("REPLACE(REPLACE(UPPER(plate_number), '-', ''), ' ', '') = ?", [\App\Support\PlateNumber::key($data['plate_number'])])->exists()) throw ValidationException::withMessages(['plate_number' => 'This plate number is already registered.']);

        $this->registration->driver($user);
        VehicleParkingArea::forType(DB::table('vehicle_types')->where('type_name', $data['vehicle_type'])->first());
        $photoPath = $request->hasFile('vehicle_photo') ? $request->file('vehicle_photo')->store('vehicle-photos', 'private') : null;
        if ($request->hasFile('vehicle_photo') && !$photoPath) abort(500, 'Unable to store vehicle photo.');
        [$orPath, $crPath] = $this->storeDocuments($request, array_filter([$photoPath]));
        // Nothing is saved unless the OR and CR confirm the details.
        $check = $this->checkDocuments($orPath, $crPath, $data);
        if ($check['decision'] !== 'passed') {
            Storage::disk('private')->delete(array_filter([$photoPath, $orPath, $crPath]));
            return $this->notRegistered($check, 'Not registered');
        }
        $runId = (string) \Illuminate\Support\Str::uuid();
        $authorizationId = null;
        try {
            $vehicle = DB::transaction(function () use ($user, $data, $photoPath, $orPath, $crPath, $runId, &$authorizationId, $request) {
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
                DB::table('vehicles')->where('vehicle_id', $vehicle->vehicle_id)->update(['vehicle_photo_path' => $photoPath, 'or_document_path' => $orPath, 'cr_document_path' => $crPath, 'rfid_requested_at' => now()]);
                $authorizationId = $this->registration->authorization($vehicle->vehicle_id, $area->parking_area_id, $user->user_id, $request->ip(), $runId);
                DB::table('vehicle_driver')->insert([
                    'vehicle_id' => $vehicle->vehicle_id, 'driver_id' => $driver->driver_id,
                    'relationship' => 'owner', 'is_primary' => true,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                return $vehicle;
            });
        } catch (Throwable $exception) {
            Storage::disk('private')->delete(array_filter([$photoPath, $orPath, $crPath]));
            if ($exception instanceof QueryException && ($exception->errorInfo[1] ?? null) === 1062) {
                throw ValidationException::withMessages(['plate_number' => 'This plate number is already registered.']);
            }
            throw $exception;
        }

        $this->dispatchCheck($authorizationId, $runId, $check);

        return response()->json($this->checkResponse($vehicle->vehicle_id), 201);
    }

    /**
     * The owner's own registration saved in the last few minutes with the
     * same plate and details: a repeated submission of the same request (for
     * example after a lost response). Anything different is a new request and
     * gets the normal "already registered" validation error.
     */
    private function recentOwnRegistration(User $user, Request $request): ?int
    {
        $key = \App\Support\PlateNumber::key(mb_strtoupper(trim((string) $request->input('plate_number'))));
        if ($key === '') return null;
        $vehicle = DB::table('vehicles as v')->join('vehicle_types as vt', 'vt.vehicle_type_id', '=', 'v.vehicle_type_id')
            ->where('v.owner_user_id', $user->user_id)->whereNull('v.deleted_at')
            ->whereRaw("REPLACE(REPLACE(UPPER(v.plate_number), '-', ''), ' ', '') = ?", [$key])
            ->where('v.created_at', '>=', now()->subMinutes(10))->first(['v.vehicle_id', 'v.make', 'v.model', 'v.color', 'vt.type_name']);
        if (!$vehicle) return null;
        foreach (['make' => $vehicle->make, 'model' => $vehicle->model, 'color' => $vehicle->color, 'vehicle_type' => $vehicle->type_name] as $field => $saved) {
            if (mb_strtoupper(trim((string) $request->input($field))) !== mb_strtoupper(trim((string) $saved))) return null;
        }
        $status = DB::table('vehicle_authorizations')->where('vehicle_id', $vehicle->vehicle_id)->orderByDesc('authorization_id')->value('authorization_status');
        return in_array($status, ['pending', 'approved'], true) ? (int) $vehicle->vehicle_id : null;
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
            'vehicle_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ] + self::DOCUMENT_RULES, self::DOCUMENT_MESSAGES + self::DETAIL_MESSAGES);
        $this->registration->validatePlate($data['plate_number']);
        if (DB::table('vehicles')->where('vehicle_id', '<>', $id)->whereRaw(
            "REPLACE(REPLACE(UPPER(plate_number), '-', ''), ' ', '') = ?", [\App\Support\PlateNumber::key($data['plate_number'])]
        )->exists()) throw ValidationException::withMessages(['plate_number' => 'This plate number is already registered to another vehicle.']);
        $this->registration->driver($user);
        VehicleParkingArea::forType(DB::table('vehicle_types')->where('type_name', $data['vehicle_type'])->first());
        // Optional: without a new photo the one on file (if any) is kept.
        $path = $request->hasFile('vehicle_photo') ? $request->file('vehicle_photo')->store('vehicle-photos', 'private') : null;
        if ($request->hasFile('vehicle_photo') && !$path) abort(500, 'Unable to store vehicle photo.');
        [$orPath, $crPath] = $this->storeDocuments($request, array_filter([$path]));
        $check = $this->checkDocuments($orPath, $crPath, $data);
        if ($check['decision'] !== 'passed') {
            Storage::disk('private')->delete(array_filter([$path, $orPath, $crPath]));
            return $this->notRegistered($check, 'Not updated: your saved registration was not changed');
        }
        $runId = (string) \Illuminate\Support\Str::uuid();
        $authorizationId = null;
        try {
            DB::transaction(function () use ($request, $id, $user, $vehicle, $authorization, $data, $path, $orPath, $crPath, $runId, &$authorizationId) {
                DB::table('users')->where('user_id', $user->user_id)->lockForUpdate()->first();
                $driver = $this->registration->driver($user, true);
                [$current, $latest] = $this->correctionTarget($user, $id, true);
                $fields = ['plate_number', 'vehicle_type_id', 'year_model', 'make', 'model', 'color', 'vehicle_photo_path', 'or_document_path', 'cr_document_path', 'registration_status'];
                abort_unless($current->only($fields) === $vehicle->only($fields)
                    && (int) $latest->authorization_id === (int) $authorization->authorization_id
                    && $latest->authorization_status === $authorization->authorization_status,
                    409, 'This submission changed while the registration was being submitted. Refresh before resubmitting.');
                $type = DB::table('vehicle_types')->where('type_name', $data['vehicle_type'])->first();
                $area = VehicleParkingArea::forType($type);
                $old = $current->only($fields);
                $new = ['plate_number' => $data['plate_number'], 'vehicle_type_id' => $type->vehicle_type_id,
                    'year_model' => $data['year_model'] ?? null, 'make' => $data['make'], 'model' => $data['model'],
                    'color' => $data['color'], 'vehicle_photo_path' => $path ?? $current->vehicle_photo_path, 'or_document_path' => $orPath, 'cr_document_path' => $crPath, 'updated_at' => now()];
                DB::table('vehicles')->where('vehicle_id', $id)->update($new);
                DB::table('vehicle_driver')->updateOrInsert(['vehicle_id' => $id, 'driver_id' => $driver->driver_id],
                    ['relationship' => 'owner', 'is_primary' => true, 'updated_at' => now()]);
                $authorizationId = $this->registration->authorization($id, $area->parking_area_id, $user->user_id, $request->ip(), $runId);
                DB::table('audit_logs')->insert([
                    'user_id' => $user->user_id, 'actor_label' => $user->user_code, 'action' => 'vehicle_resubmitted',
                    'auditable_type' => 'vehicles', 'auditable_id' => $id, 'ip_address' => $request->ip(),
                    'old_values' => json_encode($old + ['authorization_id' => $authorization->authorization_id, 'authorization_status' => $authorization->authorization_status]),
                    'new_values' => json_encode(array_diff_key($new, ['updated_at' => true]) + ['authorization_status' => 'pending', 'basis' => 'automatic_document_check']),
                    'created_at' => now(),
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk('private')->delete(array_filter([$path, $orPath, $crPath]));
            if ($exception instanceof QueryException && ($exception->errorInfo[1] ?? null) === 1062) {
                throw ValidationException::withMessages(['plate_number' => 'This plate number is already registered to another vehicle.']);
            }
            throw $exception;
        }
        $this->dispatchCheck($authorizationId, $runId, $check);
        return response()->json($this->checkResponse($id) + ['corrected' => true]);
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
        abort_if($authorization->document_check_status === 'processing', 409, 'The automatic document check is still running. Wait for it to finish before correcting the registration.');
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
        $runId = null;
        $authorizationId = null;
        try {
            DB::transaction(function () use ($id, $user, $path, $request, $vehicle, &$runId, &$authorizationId) {
                DB::table('users')->where('user_id', $user->user_id)->lockForUpdate()->first();
                $this->registration->driver($user, true);
                $current = Vehicle::where('owner_user_id', $user->user_id)->lockForUpdate()->findOrFail($id);
                abort_unless($current->registration_status === 'registered' && $current->plate_number === $vehicle->plate_number && $current->vehicle_type_id === $vehicle->vehicle_type_id, 409, 'The vehicle changed. Refresh before submitting again.');
                $authorization = DB::table('vehicle_authorizations')->where('vehicle_id', $id)->orderByDesc('authorization_id')->lockForUpdate()->first();
                abort_unless($authorization && in_array($authorization->authorization_status, ['pending', 'rejected'], true), 409, 'The authorization changed. Refresh before submitting again.');
                abort_if($authorization->document_check_status === 'processing', 409, 'The automatic document check is still running. Wait for it to finish before replacing the photo.');
                $area = VehicleParkingArea::forType(DB::table('vehicle_types')->where('vehicle_type_id', $current->vehicle_type_id)->first());
                DB::table('vehicles')->where('vehicle_id', $id)->update(['vehicle_photo_path' => $path, 'updated_at' => now()]);
                // Append the new decision; retain previous photos and decisions in protected history.
                // The stored OR/CR are checked again; without them the owner must upload them first.
                $runId = $current->or_document_path && $current->cr_document_path ? (string) \Illuminate\Support\Str::uuid() : null;
                $authorizationId = $this->registration->authorization($id, $area->parking_area_id, $user->user_id, $request->ip(), $runId);
            });
        } catch (Throwable $exception) { Storage::disk('private')->delete($path); throw $exception; }
        if ($runId) $this->dispatchCheck($authorizationId, $runId);
        return response()->json($this->checkResponse($id));
    }

    /** Owner retry when the reading service was unavailable; nothing else changes. */
    public function retryDocumentCheck(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        abort_unless($this->regular($user), 403);
        $runId = (string) \Illuminate\Support\Str::uuid();
        $authorizationId = DB::transaction(function () use ($user, $id, $runId) {
            DB::table('users')->where('user_id', $user->user_id)->lockForUpdate()->first();
            $vehicle = Vehicle::where('owner_user_id', $user->user_id)->lockForUpdate()->findOrFail($id);
            $authorization = \App\Models\VehicleAuthorization::where('vehicle_id', $id)->orderByDesc('authorization_id')->lockForUpdate()->first();
            abort_unless($authorization && $authorization->authorization_status === 'pending' && $authorization->document_check_status === 'unavailable', 409,
                'Only a check that could not finish can be retried. Correct or re-upload the documents for other results.');
            abort_unless($vehicle->or_document_path && $vehicle->cr_document_path, 409, 'Upload the OR and CR first.');
            $authorization->forceFill(['document_check_status' => 'processing', 'document_check_run_id' => $runId, 'document_check_result' => null, 'document_checked_at' => null,
                'remarks' => 'The automatic OR/CR document check is in progress. Vehicle approval and RFID assignment remain separate.'])->save();
            return $authorization->authorization_id;
        });
        $this->dispatchCheck($authorizationId, $runId);
        return response()->json($this->checkResponse($id));
    }

    /** Stream an OR or CR image to its owner or to parking staff/administrators. */
    public function document(Request $request, int $id, string $document)
    {
        abort_unless(in_array($document, ['or', 'cr'], true), 404);
        $kind = $document;
        $vehicle = Vehicle::findOrFail($id);
        $user = $request->user();
        abort_unless($user->canManageParking() || ($this->regular($user) && (int) $vehicle->owner_user_id === (int) $user->user_id), 403);
        $path = $kind === 'or' ? $vehicle->or_document_path : $vehicle->cr_document_path;
        abort_unless($path && Storage::disk('private')->exists($path), 404, 'No document on file.');
        return response()->file(Storage::disk('private')->path($path), ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    private const DOCUMENT_RULES = [
        'or_document' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'mimetypes:image/jpeg,image/png', 'max:5120'],
        'cr_document' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'mimetypes:image/jpeg,image/png', 'max:5120'],
    ];

    /** The details come from the OR/CR review; name what is missing in those terms. */
    private const DETAIL_MESSAGES = [
        'plate_number.required' => 'Enter the plate number shown on the OR/CR.',
        'plate_number.unique' => 'This plate number is already registered.',
        'vehicle_type.required' => 'Choose the vehicle type shown on the CR.',
        'make.required' => 'Enter the make shown on the CR.',
        'model.required' => 'Enter the model (series) shown on the CR.',
        'color.required' => 'Enter the color shown on the OR/CR.',
    ];

    private const DOCUMENT_MESSAGES = [
        'or_document.required' => 'Upload a photo of the Official Receipt (OR).',
        'cr_document.required' => 'Upload a photo of the Certificate of Registration (CR).',
        'or_document.*' => 'The OR must be a JPG or PNG photo of 5 MB or less.',
        'cr_document.*' => 'The CR must be a JPG or PNG photo of 5 MB or less.',
    ];

    /** @return array{0: string, 1: string} */
    private function storeDocuments(Request $request, array $cleanup): array
    {
        $or = $request->file('or_document')->store('vehicle-documents', 'private');
        $cr = $or ? $request->file('cr_document')->store('vehicle-documents', 'private') : false;
        if (!$or || !$cr) {
            Storage::disk('private')->delete(array_filter(array_merge($cleanup, [$or ?: null])));
            abort(500, 'Unable to store the vehicle documents.');
        }
        return [$or, $cr];
    }

    private function dispatchCheck(int $authorizationId, string $runId, ?array $result = null): void
    {
        try {
            \App\Jobs\ProcessVehicleDocumentCheck::dispatch($authorizationId, $runId, $result);
        } catch (Throwable $e) {
            report($e);
            // A queue failure must leave a retryable, unapproved state.
            (new \App\Jobs\ProcessVehicleDocumentCheck($authorizationId, $runId))->failed($e);
        }
    }

    /** The OR/CR check on the uploaded documents, before anything is saved. */
    private function checkDocuments(string $orPath, string $crPath, array $data): array
    {
        try {
            return app(\App\Services\Vehicles\VehicleDocumentCheck::class)->run(['or' => $orPath, 'cr' => $crPath], [
                'plate' => $data['plate_number'], 'make' => $data['make'], 'model' => $data['model'], 'color' => $data['color'],
                'year' => $data['year_model'] ?? null,
                'type_code' => DB::table('vehicle_types')->where('type_name', $data['vehicle_type'])->value('type_code'),
            ]);
        } catch (Throwable $e) {
            report($e);
            return ['decision' => 'unavailable', 'reasons' => ['The OR/CR could not be read right now. Try again in a moment.'], 'extracted' => []];
        }
    }

    /** 422: the documents did not confirm the details; no vehicle, authorization or file was kept. */
    private function notRegistered(array $check, string $prefix): JsonResponse
    {
        $reasons = array_values($check['reasons'] ?? []) ?: ['The OR/CR did not confirm the vehicle details.'];
        $message = $prefix.match ($check['decision']) {
            'retake_required' => ': clearer OR/CR photos are needed. ',
            'unavailable' => ': the OR/CR could not be read right now. ',
            default => ': the details do not match your OR/CR. ',
        }.implode(' ', $reasons);
        return response()->json([
            'message' => $message, 'saved' => false,
            'errors' => ['documents' => [$message]],
            'document_check' => ['status' => $check['decision'], 'reasons' => $reasons, 'extracted' => $check['extracted'] ?? null],
        ], 422);
    }

    private function checkResponse(int $vehicleId): array
    {
        $auth = \App\Models\VehicleAuthorization::where('vehicle_id', $vehicleId)->orderByDesc('authorization_id')->first();
        $check = $this->documentCheckPayload($auth);
        $message = match ($check['status'] ?? null) {
            'passed' => 'Vehicle approved automatically after the OR/CR check. RFID assignment remains separate.',
            'processing' => 'Vehicle submitted. The automatic OR/CR check is in progress.',
            'retake_required' => 'Vehicle submitted but not approved: clearer OR/CR photos are needed.',
            'unavailable' => 'Vehicle submitted but not approved: the document check could not finish. Retry later.',
            default => 'Vehicle submitted but not approved: correct the details below.',
        };
        return [
            'message' => $message, 'vehicle_id' => $vehicleId,
            'authorization_status' => $auth?->authorization_status, 'document_check' => $check,
            // The vehicle photo is stored for reference only; neither it nor client-sent flags verify anything.
            'approval_basis' => 'automatic_document_check', 'plate_verified' => false, 'photo_verified' => false,
            'ownership_verified' => false, 'authenticity_verified' => false,
        ];
    }

    private function documentCheckPayload(?object $auth): ?array
    {
        if (!$auth || !$auth->document_check_status) return null;
        $result = $auth->document_check_result ? json_decode($auth->document_check_result, true) : [];
        return [
            'status' => $auth->document_check_status,
            'reasons' => $result['blocking_reasons'] ?? $result['reasons'] ?? ($auth->document_check_status === 'needs_correction' && $auth->remarks ? [$auth->remarks] : []),
            'extracted' => $result['extracted'] ?? null,
            'checkedAt' => $auth->document_checked_at ? CampusTime::apiTimestamp($auth->document_checked_at) : null,
            'approvalSource' => $auth->approval_source,
        ];
    }
}
