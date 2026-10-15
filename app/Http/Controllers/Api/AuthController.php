<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessLicenseVerification;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Throwable;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        // Normalize the plate number before checking uniqueness.
        $request->merge([
            'plate_number' => strtoupper(
                trim((string) $request->input('plate_number'))
            ),
        ]);

        $hasVehicle = $request->boolean('has_vehicle');

        $data = $request->validate([
            'full_name' => [
                'required',
                'string',
                'max:200',
                'regex:/\S+\s+\S+/',
            ],

            'user_code' => [
                'required',
                'string',
                'max:50',
                'unique:users,user_code',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],

            'user_type' => [
                'required',
                Rule::in([
                    'student',
                    'faculty',
                    'employee',
                    'other',
                ]),
            ],

            'department' => [
                'required',
                'exists:departments,department_name',
            ],

            'contact_number' => [
                'required',
                'string',
                'max:30',
            ],

            'has_vehicle' => [
                'required',
                'boolean',
            ],

            'school_id_file' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:5120',
            ],

            // Vehicle information is required only when has_vehicle is true.
            'vehicle_type' => [
                Rule::requiredIf($hasVehicle),
                'nullable',
                'exists:vehicle_types,type_name',
            ],

            'plate_number' => [
                Rule::requiredIf($hasVehicle),
                'nullable',
                'string',
                'max:20',
                'unique:vehicles,plate_number',
            ],

            'make' => [
                Rule::requiredIf($hasVehicle),
                'nullable',
                'string',
                'max:100',
            ],

            'model' => [
                Rule::requiredIf($hasVehicle),
                'nullable',
                'string',
                'max:100',
            ],

            'color' => [
                Rule::requiredIf($hasVehicle),
                'nullable',
                'string',
                'max:50',
            ],

            'license_number' => [
                Rule::requiredIf($hasVehicle),
                'nullable',
                'string',
                'max:100',
                'unique:driver_licenses,license_number',
            ],

            'license_type' => [
                Rule::requiredIf($hasVehicle),
                'nullable',
                Rule::in([
                    'student_permit',
                    'non_professional',
                    'professional',
                    'international',
                ]),
            ],

            'license_expiry' => [
                Rule::requiredIf($hasVehicle),
                'nullable',
                'date',
                'after_or_equal:today',
            ],

            'license_file' => [
                Rule::requiredIf($hasVehicle),
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:5120',
            ],
        ]);

        // Find the department before storing files.
        $department = Department::where(
            'department_name',
            $data['department']
        )->firstOrFail();

        $type = null;

        if ($hasVehicle) {
            $type = VehicleType::where(
                'type_name',
                $data['vehicle_type']
            )->firstOrFail();
        }

        // Split the name into first and last name.
        $nameParts = preg_split(
    '/\s+/',
    trim($data['full_name'])
);

$lastName = array_pop($nameParts);
$firstName = implode(' ', $nameParts);

        try {
            // Store documents on a private disk, not in public storage.
            $schoolIdPath = $request->file('school_id_file')
                ->store('school-ids', 'private');

            if (! $schoolIdPath) {
                throw new \RuntimeException(
                    'Unable to store the school ID document.'
                );
            }

            $storedFiles[] = $schoolIdPath;

            $licensePath = null;

            if ($hasVehicle) {
                $licensePath = $request->file('license_file')
                    ->store('driver-licenses', 'private');

                if (! $licensePath) {
                    throw new \RuntimeException(
                        'Unable to store the driver license document.'
                    );
                }

                $storedFiles[] = $licensePath;
            }

            // Set when a license is saved, so its automatic check runs after commit.
            $licenseId = null;
            $licenseRunId = (string) Str::uuid();

            $user = DB::transaction(function () use (
                $data,
                $hasVehicle,
                $department,
                $type,
                $firstName,
                $lastName,
                $schoolIdPath,
                $licensePath,
                $licenseRunId,
                $request,
                &$licenseId
            ) {
                // Create the account as pending. Do not auto-approve it.
                $user = User::create([
                    'user_code' => $data['user_code'],
                    'email' => $data['email'],
                    'password' => $data['password'],
                    'user_type' => $data['user_type'],
                    'account_status' => 'pending',
                ]);

                $profile = $user->profile()->create([
                    'department_id' => $department->department_id,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'contact_number' => $data['contact_number'],
                ]);

                // Requires school_id_document_path to exist in user_profiles.
                $profile->forceFill([
                    'school_id_document_path' => $schoolIdPath,
                ])->save();

                // Regular users receive only their matching regular-user role.
                // "other" receives no role until an administrator assigns one.
                if (in_array(
                    $data['user_type'],
                    ['student', 'faculty', 'employee'],
                    true
                )) {
                    $role = Role::where(
                        'role_code',
                        $data['user_type']
                    )->first();

                    if ($role) {
                        $user->roles()->syncWithoutDetaching([
                            $role->role_id,
                        ]);
                    }
                }

                if ($hasVehicle) {
                    

$vehicle = Vehicle::create([
    'owner_user_id' => $user->user_id,
    'vehicle_type_id' => $type->vehicle_type_id,
    'plate_number' => $data['plate_number'],
    'make' => $data['make'] ?? null,
    'model' => $data['model'] ?? null,
    'color' => $data['color'] ?? null,
    'registration_status' => 'registered',
]);

// active_flag is a generated column, so it is not set here.
$vehicle->authorizations()->create([
    'authorization_status' => 'pending',
]);



                    // Create the driver record using the same registrant.
                    $driverId = DB::table('drivers')->insertGetId([
                        'user_id' => $user->user_id,
                        'first_name' => $firstName,
                        'middle_name' => null,
                        'last_name' => $lastName,
                        'contact_number' => $data['contact_number'],
                        'driver_status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ], 'driver_id');

                    // Save the driver's license details and private file path.
                    
$licenseId = DB::table('driver_licenses')->insertGetId([
    'driver_id' => $driverId,
    'license_number' => $data['license_number'],
    'license_type' => $data['license_type'],
    'restriction_codes' => null,
    'issue_date' => null,
    'expiry_date' => $data['license_expiry'],
    'issuing_agency' => null,
    'license_image_path' => $licensePath,
    'original_file_name' => Str::limit($request->file('license_file')->getClientOriginalName(), 250, ''),
    'license_status' => 'valid',
    'verification_status' => 'processing',
    'verification_source' => 'automatic',
    'verification_run_id' => $licenseRunId,
    'submitted_at' => now(),
    'created_at' => now(),
    'updated_at' => now(),
], 'license_id');


                    // Link the vehicle to the registered driver.
                    DB::table('vehicle_driver')->insert([
                        'vehicle_id' => $vehicle->vehicle_id,
                        'driver_id' => $driverId,
                        'relationship' => 'owner',
                        'is_primary' => true,
                        'authorized_from' => null,
                        'authorized_until' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                return $user;
            });

            // The registration is saved; a failure in the document check must not undo it.
            if ($licenseId) {
                try {
                    ProcessLicenseVerification::dispatch($licenseId, $licenseRunId);
                } catch (Throwable $exception) {
                    report($exception);
                }
            }

            return response()->json([
                'message' =>
                    'Registration submitted. Your account is pending approval.',
                'user' => $this->userPayload($user),
            ], 201);
        } catch (Throwable $exception) {
            // If the database transaction fails, remove uploaded files too.
            foreach ($storedFiles as $path) {
                Storage::disk('private')->delete($path);
            }

            report($exception);

            return response()->json([
                'message' =>
                    'Registration could not be completed. Please try again.',
            ], 500);
        }
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'identifier' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['identifier'])
            ->orWhere('user_code', $data['identifier'])
            ->first();

        if (! $user || ! Hash::check(
            $data['password'],
            $user->password
        )) {
            $this->logAttempt(
                $request,
                $user,
                $data['identifier'],
                false,
                'invalid_credentials'
            );

            return response()->json([
                'message' => 'Invalid email/ID number or password.',
            ], 401);
        }

        if ($user->account_status !== 'active') {
            $this->logAttempt(
                $request,
                $user,
                $data['identifier'],
                false,
                'account_' . $user->account_status
            );

            $messages = [
                'pending' =>
                    'Your account is still pending approval by the PASS administrator.',
                'suspended' =>
                    'Your account is suspended. Please contact the PASS office.',
                'deactivated' =>
                    'Your account is deactivated. Please contact the PASS office.',
            ];

            return response()->json([
                'message' => $messages[$user->account_status]
                    ?? 'Account not active.',
            ], 403);
        }

        $this->logAttempt(
            $request,
            $user,
            $data['identifier'],
            true
        );

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        return response()->json([
            'token' => $user->createToken('web')->plainTextToken,
            'user' => $this->userPayload($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->userPayload($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out.',
        ]);
    }

    /**
     * Keep the response compatible with AuthContext.tsx.
     */
    private function userPayload(User $user): array
    {
        $user->loadMissing('profile.department', 'roles');

        $profile = $user->profile;

        return [
            'id' => (string) $user->user_id,
            'name' => trim(
                ($profile->first_name ?? '') . ' ' .
                ($profile->last_name ?? '')
            ),
            'email' => $user->email,
            'role' => $user->roles->first()?->role_code,
            'idNum' => $user->user_code,
            'dept' => $profile?->department?->department_name,
        ];
    }

    private function logAttempt(
        Request $request,
        ?User $user,
        string $identifier,
        bool $success,
        ?string $reason = null
    ): void {
        DB::table('login_attempts')->insert([
            'user_id' => $user?->user_id,
            'identifier' => $identifier,
            'ip_address' => $request->ip(),
            'user_agent' => substr(
                (string) $request->userAgent(),
                0,
                255
            ),
            'was_successful' => $success,
            'failure_reason' => $reason,
            'attempted_at' => now(),
        ]);
    }
}