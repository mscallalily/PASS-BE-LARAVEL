<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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

            $user = DB::transaction(function () use (
                $data,
                $hasVehicle,
                $department,
                $type,
                $firstName,
                $lastName,
                $schoolIdPath,
                $licensePath
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

$vehicle->authorizations()->create([
    'authorization_status' => 'pending',
    'active_flag' => true,
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

DB::table('driver_licenses')->insert([
    'driver_id' => $driverId,
    'license_number' => $data['license_number'],
    'license_type' => $data['license_type'],
    'restriction_codes' => null,
    'issue_date' => null,
    'expiry_date' => $data['license_expiry'],
    'issuing_agency' => null,
    'license_image_path' => $licensePath,
    'license_status' => 'valid',
    'current_flag' => true,
    'created_at' => now(),
    'updated_at' => now(),
]);


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

public function forgotPassword(Request $request): JsonResponse
{
    $data = $request->validate([
        'email' => ['required', 'email', 'max:255'],
    ]);

    $status = Password::sendResetLink([
        'email' => $data['email'],
    ]);

    // Avoid revealing whether an email exists in the database.
    if ($status === Password::RESET_LINK_SENT ||
        $status === Password::INVALID_USER) {
        return response()->json([
            'message' =>
                'If an account exists for that email, a password reset link will be sent.',
        ]);
    }

    if ($status === Password::RESET_THROTTLED) {
        return response()->json([
            'message' =>
                'Please wait before requesting another reset link.',
        ], 429);
    }

    report(new \RuntimeException(
        'Password reset link could not be sent: ' . $status
    ));

    return response()->json([
        'message' =>
            'Unable to process your request right now. Please try again later.',
    ], 500);
}

public function resetPassword(Request $request): JsonResponse
{
    $data = $request->validate([
        'token' => ['required', 'string'],
        'email' => ['required', 'email', 'max:255'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
    ]);

    $status = Password::reset(
        $data,
        function (User $user, string $password): void {
            // The User model's "hashed" cast hashes the new password.
            $user->forceFill([
                'password' => $password,
                'remember_token' => Str::random(60),
            ])->save();

            // Revoke existing API sessions after a password change.
            $user->tokens()->delete();
        }
    );

    if ($status === Password::PASSWORD_RESET) {
        return response()->json([
            'message' => 'Your password has been reset successfully.',
        ]);
    }

    return response()->json([
        'message' => match ($status) {
            Password::INVALID_USER =>
                'Unable to reset the password for this account.',
            Password::INVALID_TOKEN =>
                'This reset link is invalid or has expired. Please request a new one.',
            Password::RESET_THROTTLED =>
                'Please wait before trying again.',
            default =>
                'Unable to reset your password. Please request a new link.',
        },
    ], 422);
}

}