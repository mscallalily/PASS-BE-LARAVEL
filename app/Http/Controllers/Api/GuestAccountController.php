<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Throwable;
use App\Services\Parking\VisitorPassPolicy;
use App\Services\Parking\VisitorRequestService;
use App\Support\CampusTime;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Guest (visitor) accounts: self-registration, profile, vehicles, visitor
 * parking requests, issued QR passes and visit history. A guest account is a
 * users row with the guest role, linked 1:1 to a visitors row
 * (visitors.user_id). A guest only ever sees their own records; requests are
 * approved by an Administrator (VisitorAccessController).
 */
class GuestAccountController extends Controller
{
    /** Philippine mobile number: 09XXXXXXXXX or +639XXXXXXXXX (spaces and dashes allowed). */
    private const MOBILE = '/^(\+63|0)9\d{9}$/';

    public function __construct(private VisitorRequestService $requests)
    {
    }

    // ----------------------------------------------------------- registration

    public function register(Request $request): JsonResponse
    {
        $request->merge(['contact_number' => preg_replace('/[\s-]/', '', (string) $request->input('contact_number'))]);

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:200', 'regex:/^\S+(\s+\S+)+$/u'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
            'contact_number' => ['required', 'string', 'regex:' . self::MOBILE],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'agree' => ['accepted'],
        ], [
            'full_name.regex' => 'Enter your first and last name.',
            'email.unique' => 'An account with this email already exists. Sign in instead.',
            'contact_number.regex' => 'Enter a mobile number like 09171234567.',
            'agree.accepted' => 'You must agree to the Terms and Conditions and Privacy Notice.',
        ]);

        $roleId = DB::table('roles')->where('role_code', 'guest')->where('is_active', true)->value('role_id');
        if (!$roleId) {
            return response()->json(['message' => 'Guest registration is not available right now. Please contact the PASS office.'], 503);
        }

        [$first, $last] = self::splitName($data['full_name']);
        $email = strtolower(trim($data['email']));

        try {
            $userId = DB::transaction(function () use ($data, $first, $last, $email, $roleId) {
                $userId = DB::table('users')->insertGetId([
                    'user_code' => self::newGuestCode(),
                    'email' => $email,
                    'password' => Hash::make($data['password']),
                    'user_type' => 'other',
                    'account_status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ], 'user_id');

                DB::table('user_profiles')->insert([
                    'user_id' => $userId, 'first_name' => $first, 'last_name' => $last,
                    'contact_number' => $data['contact_number'], 'program_or_position' => 'Guest / Visitor',
                    'created_at' => now(), 'updated_at' => now(),
                ]);

                DB::table('user_roles')->insert(['user_id' => $userId, 'role_id' => $roleId, 'assigned_at' => now()]);

                DB::table('visitors')->insert([
                    'user_id' => $userId, 'first_name' => $first, 'last_name' => $last,
                    'contact_number' => $data['contact_number'], 'email' => $email, 'is_blacklisted' => false,
                    'created_at' => now(), 'updated_at' => now(),
                ]);

                return $userId;
            });
        } catch (UniqueConstraintViolationException) {
            // Two submissions at once: the database keeps only one account.
            throw ValidationException::withMessages(['email' => 'An account with this email already exists. Sign in instead.']);
        }

        $sent = false;
        try {
            app(\App\Services\Auth\EmailVerificationCodes::class)->send(User::findOrFail($userId));
            $sent = true;
        } catch (Throwable $exception) {
            report($exception);
        }

        return response()->json([
            'message' => $sent
                ? 'Guest account created. Check your inbox and spam folder to verify your email before signing in.'
                : 'Guest account created, but the verification email could not be sent. Please resend it before signing in.',
            'email_verification_sent' => $sent,
        ], 201);
    }

    // ----------------------------------------------------------- profile

    public function profile(Request $request): JsonResponse
    {
        [$user, $visitor] = $this->guest($request);

        return response()->json($this->profilePayload($user, $visitor));
    }

    public function updateProfile(Request $request): JsonResponse
    {
        [$user, $visitor] = $this->guest($request);
        $request->merge(['contact_number' => preg_replace('/[\s-]/', '', (string) $request->input('contact_number'))]);

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:200', 'regex:/^\S+(\s+\S+)+$/u'],
            'contact_number' => ['required', 'string', 'regex:' . self::MOBILE],
            'affiliation' => ['nullable', 'string', 'max:150'],
        ], ['full_name.regex' => 'Enter your first and last name.', 'contact_number.regex' => 'Enter a mobile number like 09171234567.']);

        [$first, $last] = self::splitName($data['full_name']);

        DB::transaction(function () use ($user, $visitor, $data, $first, $last) {
            DB::table('user_profiles')->where('user_id', $user->user_id)->update(['first_name' => $first, 'last_name' => $last, 'contact_number' => $data['contact_number'], 'updated_at' => now()]);
            DB::table('visitors')->where('visitor_id', $visitor->visitor_id)->update([
                'first_name' => $first, 'last_name' => $last, 'contact_number' => $data['contact_number'], 'affiliation' => $data['affiliation'] ?? null, 'updated_at' => now(),
            ]);
        });

        return response()->json(['message' => 'Profile updated.', 'profile' => $this->profilePayload($user, DB::table('visitors')->where('visitor_id', $visitor->visitor_id)->first())]);
    }

    // ----------------------------------------------------------- vehicles

    public function vehicles(Request $request): JsonResponse
    {
        [, $visitor] = $this->guest($request);

        $rows = DB::table('vehicles as v')
            ->join('vehicle_types as vt', 'vt.vehicle_type_id', '=', 'v.vehicle_type_id')
            ->leftJoin('parking_areas as a', 'a.parking_area_id', '=', 'vt.parking_area_id')
            ->where('v.owner_visitor_id', $visitor->visitor_id)
            ->whereNull('v.deleted_at')
            ->orderBy('v.plate_number')
            ->get(['v.vehicle_id', 'v.plate_number', 'v.make', 'v.model', 'v.color', 'vt.type_name', 'a.area_name', 'v.created_at'])
            ->map(fn ($v) => (array) $v + [
                'created_at' => ParkingOperationsController::iso($v->created_at),
                'inside' => DB::table('parking_transactions')->where('vehicle_id', $v->vehicle_id)->where('transaction_status', 'active')->exists(),
            ]);

        $types = DB::table('vehicle_types as vt')->join('parking_areas as a', 'a.parking_area_id', '=', 'vt.parking_area_id')
            ->where('a.is_active', true)->orderBy('vt.type_name')->get(['vt.vehicle_type_id', 'vt.type_name', 'a.area_name']);

        return response()->json(['data' => $rows, 'vehicle_types' => $types]);
    }

    public function storeVehicle(Request $request): JsonResponse
    {
        [, $visitor] = $this->guest($request);

        $data = $request->validate([
            'plate_number' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9 -]+$/'],
            'vehicle_type_id' => ['required', 'integer', Rule::exists('vehicle_types', 'vehicle_type_id')->whereNotNull('parking_area_id')],
            'make' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:50'],
        ], ['plate_number.regex' => 'Use letters, numbers, spaces and dashes only.', 'vehicle_type_id.exists' => 'Choose a vehicle type.']);

        $plate = strtoupper(preg_replace('/\s+/', ' ', trim($data['plate_number'])));

        $vehicleId = DB::transaction(function () use ($visitor, $plate, $data) {
            if (DB::table('vehicles')->where('plate_number', $plate)->where('owner_visitor_id', $visitor->visitor_id)->whereNull('deleted_at')->exists()) {
                throw ValidationException::withMessages(['plate_number' => "{$plate} is already one of your vehicles."]);
            }

            return VisitorAccessController::registerVisitorVehicle($visitor->visitor_id, $plate, (int) $data['vehicle_type_id'], $data);
        });

        return response()->json(['message' => "Vehicle {$plate} registered.", 'vehicle_id' => $vehicleId], 201);
    }

    // ----------------------------------------------------------- requests and passes

    public function requests(Request $request): JsonResponse
    {
        [, $visitor] = $this->guest($request);
        $now = CarbonImmutable::now();

        $rows = DB::table('visitor_requests as r')
            ->leftJoin('vehicles as v', 'v.vehicle_id', '=', 'r.vehicle_id')
            ->leftJoin('vehicle_types as vt', 'vt.vehicle_type_id', '=', 'v.vehicle_type_id')
            ->leftJoin('parking_areas as a', 'a.parking_area_id', '=', 'r.destination_area_id')
            ->where('r.visitor_id', $visitor->visitor_id)
            ->orderByDesc('r.request_id')
            ->get(['r.driver_license_photo_path', 'r.vehicle_photo_path', 'r.documents_reviewed_at', 'r.driver_name', 'r.driver_is_requester', 'r.driver_license_encrypted', 'r.driver_license_expiry', 'r.passenger_count', 'r.request_id', 'r.request_status', 'r.purpose', 'r.requested_valid_from', 'r.requested_valid_until', 'r.reviewed_at', 'r.review_remarks', 'r.created_at', 'v.plate_number', 'vt.type_name', 'a.area_name'])
            ->map(function ($r) use ($now) {
                $pass = DB::table('qr_passes')->where('request_id', $r->request_id)->orderByDesc('qr_pass_id')->first();

                return [
                    'request_id' => $r->request_id,
                    'request_status' => $r->request_status,
                    'purpose' => $r->purpose,
                    'documents' => \App\Services\Parking\VisitorRequestService::documentSummary($r),
                    'driver' => \App\Services\Parking\VisitorRequestService::driverSummary($r),
                    'valid_from' => ParkingOperationsController::iso($r->requested_valid_from),
                    'valid_until' => ParkingOperationsController::iso($r->requested_valid_until),
                    'window_ended' => $r->requested_valid_until < $now->toDateTimeString(),
                    'created_at' => ParkingOperationsController::iso($r->created_at),
                    'reviewed_at' => ParkingOperationsController::iso($r->reviewed_at),
                    'review_remarks' => $r->review_remarks,
                    'plate_number' => $r->plate_number,
                    'vehicle_type' => $r->type_name,
                    'area_name' => $r->area_name,
                    'pass' => $pass ? VisitorAccessController::passSummary($pass, $now) : null,
                ];
            });

        return response()->json(['data' => $rows]);
    }

    public function storeRequest(Request $request): JsonResponse
    {
        [$user, $visitor] = $this->guest($request);

        $data = $request->validate([
            'vehicle_id' => ['required', 'integer'],
            'visit_date' => ['required', 'date_format:Y-m-d'],
            'arrival_time' => ['required', 'date_format:H:i'],
            'departure_time' => ['required', 'date_format:H:i'],
            'purpose' => ['required', 'string', 'max:150'],
            'visiting' => ['required', 'string', 'max:90'],
            'driver_license_photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'vehicle_photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'driver_is_requester' => ['required', 'boolean'],
            'driver_name' => ['required_if:driver_is_requester,false', 'nullable', 'string', 'max:200', 'regex:/^\S+(\s+\S+)+$/u'],
            'driver_license_number' => ['required', 'string', 'min:6', 'max:30', 'regex:/^[A-Za-z0-9 -]+$/'],
            'driver_license_expiry' => ['required', 'date_format:Y-m-d', 'after_or_equal:visit_date'],
            'passenger_count' => ['required', 'integer', 'min:0', 'max:99'],
        ], [], ['visiting' => 'person or office to visit']);

        // Visit date and times are campus (Asia/Manila) local times.
        $from = CarbonImmutable::createFromFormat('!Y-m-d H:i', "{$data['visit_date']} {$data['arrival_time']}", CampusTime::zone());
        $until = CarbonImmutable::createFromFormat('!Y-m-d H:i', "{$data['visit_date']} {$data['departure_time']}", CampusTime::zone());

        if ($data['visit_date'] <= CampusTime::date()) {
            throw ValidationException::withMessages(['visit_date' => 'Same-day requests are not allowed. Choose tomorrow or a later date (Manila time).']);
        }
        if ($from <= CampusTime::now()) {
            throw ValidationException::withMessages(['arrival_time' => 'Choose a future arrival time in Manila time. Past or current-minute arrivals cannot be submitted online.']);
        }
        if ($until <= $from) {
            throw ValidationException::withMessages(['departure_time' => 'Departure must be after arrival.']);
        }

        $requestId = $this->requests->withDocuments($request, function ($paths) use ($visitor, $user, $data, $from, $until, $request) {
            $id = $this->requests->create(
                $visitor->visitor_id, (int) $data['vehicle_id'], $data['purpose'], $data['visiting'], $from, $until, $user, 'guest_account'
            );
            DB::table('visitor_requests')->where('request_id', $id)->update([
                ...$paths,
                'driver_is_requester' => $request->boolean('driver_is_requester'),
                'driver_name' => $request->boolean('driver_is_requester')
                    ? trim($visitor->first_name . ' ' . $visitor->last_name) : trim($data['driver_name']),
                'driver_license_encrypted' => \Illuminate\Support\Facades\Crypt::encryptString(strtoupper(trim($data['driver_license_number']))),
                'driver_license_expiry' => $data['driver_license_expiry'],
                'passenger_count' => (int) $data['passenger_count'],
            ]);
            return $id;
        });

        return response()->json(['message' => 'Your visitor parking request was submitted and is pending Admin approval.', 'request_id' => $requestId], 201);
    }

    public function cancelRequest(Request $request, int $id): JsonResponse
    {
        [$user, $visitor] = $this->guest($request);

        if (!DB::table('visitor_requests')->where('request_id', $id)->where('visitor_id', $visitor->visitor_id)->exists()) {
            return response()->json(['message' => 'Visitor request not found.'], 404);
        }

        DB::transaction(fn () => $this->requests->cancel($id, $user));

        return response()->json(['message' => 'Request cancelled.']);
    }

    /** The guest's Visitor QR passes, newest first. */
    public function passes(Request $request): JsonResponse
    {
        [, $visitor] = $this->guest($request);
        $now = CarbonImmutable::now();

        $rows = DB::table('qr_passes')->where('visitor_id', $visitor->visitor_id)->where('pass_type', 'visitor')
            ->orderByDesc('qr_pass_id')->get()
            ->map(fn ($p) => VisitorAccessController::passSummary($p, $now) + [
                'entry_problem' => VisitorPassPolicy::entryProblem($p, null, $now),
            ]);

        return response()->json(['data' => $rows]);
    }

    /** Parking visits (transactions) of the guest's vehicles. */
    public function visits(Request $request): JsonResponse
    {
        [, $visitor] = $this->guest($request);

        $rows = DB::table('parking_transactions as t')
            ->join('vehicles as v', 'v.vehicle_id', '=', 't.vehicle_id')
            ->leftJoin('parking_slots as s', 's.slot_id', '=', 't.slot_id')
            ->leftJoin('parking_areas as a', 'a.parking_area_id', '=', 't.parking_area_id')
            ->where('t.visitor_id', $visitor->visitor_id)
            ->orderByDesc('t.entry_datetime')
            ->limit(100)
            ->get(['t.transaction_id', 't.ticket_number', 't.transaction_status', 't.entry_datetime', 't.exit_datetime', 't.duration_minutes', 'v.plate_number', 's.slot_code', 'a.area_name'])
            ->map(fn ($t) => [
                'transaction_id' => $t->transaction_id,
                'ticket_number' => $t->ticket_number,
                'status' => $t->transaction_status,
                'plate_number' => $t->plate_number,
                'slot_code' => $t->slot_code,
                'area_name' => $t->area_name,
                'entry_datetime' => ParkingOperationsController::iso($t->entry_datetime),
                'exit_datetime' => ParkingOperationsController::iso($t->exit_datetime),
                'duration_minutes' => $t->duration_minutes,
            ]);

        return response()->json(['data' => $rows]);
    }

    // ----------------------------------------------------------- helpers

    /**
     * The signed-in guest and their visitor profile; 403 for any other account.
     *
     * @return array{0: \App\Models\User, 1: object}
     */
    private function guest(Request $request): array
    {
        $user = $request->user();
        if ($user->isGuest() && !$user->hasVerifiedEmail()) {
            abort(403, 'Please verify your email address before using your guest account.');
        }
        $visitor = $user->isGuest() && $user->account_status === 'active'
            ? DB::table('visitors')->where('user_id', $user->user_id)->first()
            : null;

        if (!$visitor) {
            abort(403, 'This page is only for guest (visitor) accounts.');
        }

        return [$user, $visitor];
    }

    private function profilePayload(object $user, object $visitor): array
    {
        return [
            'user_code' => $user->user_code,
            'email' => $user->email,
            'full_name' => trim("{$visitor->first_name} {$visitor->last_name}"),
            'contact_number' => $visitor->contact_number,
            'affiliation' => $visitor->affiliation,
            'is_blacklisted' => (bool) $visitor->is_blacklisted,
            'member_since' => ParkingOperationsController::iso($user->created_at instanceof \DateTimeInterface ? $user->created_at->format('Y-m-d H:i:s') : $user->created_at),
        ];
    }

    /** @return array{0: string, 1: string} First name(s) and last name. */
    public static function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/u', trim($fullName));
        $last = array_pop($parts);

        return [Str::limit(implode(' ', $parts), 100, ''), Str::limit($last, 100, '')];
    }

    private static function newGuestCode(): string
    {
        do {
            $code = 'GST-' . strtoupper(Str::random(8));
        } while (DB::table('users')->where('user_code', $code)->exists());

        return $code;
    }
}
