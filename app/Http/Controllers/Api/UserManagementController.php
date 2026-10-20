<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class UserManagementController extends Controller
{
    /**
     * Only active administrators may manage user accounts.
     */
    private function canManageUsers(User $user): bool
    {
        return $user->account_status === 'active'
            && $user->roles()
                ->whereIn('role_code', ['admin', 'parking_admin'])
                ->where('is_active', true)
                ->exists();
    }

    /**
     * List actual users from the database.
     */
    public function index(Request $request): JsonResponse
    {
        $actor = $request->user();

        if (! $actor || ! $this->canManageUsers($actor)) {
            return response()->json([
                'message' => 'You are not authorized to manage users.',
            ], 403);
        }

        $filters = $request->validate(['role' => ['nullable', 'in:admin,parking_staff,student,faculty,employee,guest'], 'page' => ['nullable', 'integer', 'min:1'], 'search' => ['nullable', 'string', 'max:100']]);
        $query = User::with([
            'profile.department',
            'roles',
        ]);

        if ($role = $filters['role'] ?? null) {
            $codes = match ($role) { 'admin' => ['admin', 'parking_admin'], 'parking_staff' => ['security', 'parking_staff'], default => [$role] };
            $type = $role === 'parking_staff' ? 'security' : $role;
            $query->where(fn ($q) => $q->where('user_type', $type)->orWhereHas('roles', fn ($r) => $r->whereIn('role_code', $codes)));
        }
        // Optional status filter.
        $status = $request->query('status');

        if (in_array($status, [
            'pending',
            'active',
            'suspended',
            'deactivated',
        ], true)) {
            $query->where('account_status', $status);
        }

        // Optional name, email, or ID search.
        $search = trim((string) $request->query('search', ''));

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('email', 'like', "%{$search}%")
                    ->orWhere('user_code', 'like', "%{$search}%")
                    ->orWhereHas('profile', function ($profileQuery) use ($search) {
                        $profileQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        $users = $query
            ->orderByDesc('created_at')
            ->paginate(15);

        $users->getCollection()->transform(function (User $user) {
            $profile = $user->profile;

            return [
                'id' => (string) $user->user_id,
                'name' => trim(
                    ($profile?->first_name ?? '') . ' ' .
                    ($profile?->last_name ?? '') . ' ' . ($profile?->suffix ?? '')
                ),
                'email' => $user->email,
                'idNum' => $user->user_code,
                'role' => $user->roles->first()?->role_code,
                'userType' => $user->user_type,
                'dept' => $profile?->registration_unit ?: $profile?->department?->department_name,
                'registrationCategory' => $profile?->registration_category,
                'schoolIdCheck' => $profile?->school_id_check_result,
                'schoolIdSubmitted' => (bool) $profile?->school_id_document_path,
                'contact' => $profile?->contact_number,
                'status' => $user->account_status,
                'registered' => $user->created_at,
            ];
        });

        return response()->json([
            'users' => $users,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user() && $this->canManageUsers($request->user()), 403);
        $request->merge(['full_name' => mb_strtoupper(trim((string) $request->input('full_name'))),
            'user_code' => strtoupper(trim((string) $request->input('user_code'))),
            'email' => strtolower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:200', 'regex:/^\S+(\s+\S+)+$/u'],
            'user_code' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9_-]+$/', 'unique:users,user_code'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'contact_number' => ['required', 'string', 'regex:/^(?:09\d{9}|\+639\d{9})$/'],
            'password' => ['bail', 'string', new \App\Rules\PasswordBytes, 'required', 'string', 'min:8', 'max:72', 'confirmed'],
        ]);
        try {
            $user = \Illuminate\Support\Facades\DB::transaction(function () use ($data, $request) {
                $parts = preg_split('/\s+/', $data['full_name']); $last = array_pop($parts);
                $department = \Illuminate\Support\Facades\DB::table('departments')->where('department_code', 'SEC')->value('department_id');
                if (!$department) {
                    \Illuminate\Support\Facades\DB::table('departments')->insertOrIgnore(['department_code' => 'SEC', 'department_name' => 'Security Office', 'is_active' => true]);
                    $department = \Illuminate\Support\Facades\DB::table('departments')->where('department_code', 'SEC')->value('department_id');
                }
                $role = \Illuminate\Support\Facades\DB::table('roles')->where('role_code', 'security')->where('is_active', true)->value('role_id');
                abort_unless($role, 503, 'Configure the Parking Staff role before creating staff.');
                $user = User::create(['user_code' => $data['user_code'], 'email' => $data['email'], 'password' => $data['password'], 'user_type' => 'security', 'account_status' => 'active']);
                $user->forceFill(['email_verified_at' => now()])->save();
                $user->profile()->create(['first_name' => implode(' ', $parts), 'last_name' => $last, 'contact_number' => $data['contact_number'], 'department_id' => $department]);
                $user->roles()->attach($role, ['assigned_at' => now()]);
                \App\Support\ActivityAudit::record($request, $request->user(), 'staff_created', 'users', $user->user_id, null, ['user_type' => 'security', 'account_status' => 'active']);
                return $user;
            }, 3);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $error) {
            throw \Illuminate\Validation\ValidationException::withMessages(['email' => 'This email or staff ID is already registered.']);
        }
        return response()->json(['message' => 'Parking Staff account created.', 'user' => ['id' => (string) $user->user_id, 'role' => 'security']], 201);
    }

    public function schoolId(Request $request, int $id)
    {
        if (!$request->user() || !$this->canManageUsers($request->user())) return response()->json(['message'=>'Unauthorized access.'], 403);
        $user = User::with('profile')->findOrFail($id);
        $path = $user->profile?->school_id_document_path;
        $disk = \Illuminate\Support\Facades\Storage::disk('private');
        if (!$path || !$disk->exists($path)) return response()->json(['message'=>'ID document not found.'], 404);
        return $disk->response($path, 'institutional-id.'.pathinfo($path, PATHINFO_EXTENSION), ['Cache-Control'=>'no-store, private', 'X-Content-Type-Options'=>'nosniff'], 'inline');
    }

    /**
     * Approve a pending account.
     */
    public function approve(Request $request, string $id): JsonResponse
    {
        return $this->decidePending($request, $id, 'approved');
    }

    public function reject(Request $request, string $id): JsonResponse
    {
        return $this->decidePending($request, $id, 'rejected');
    }

    private function decidePending(Request $request, string $id, string $decision): JsonResponse
    {
        $actor = $request->user();
        if (!$actor || !$this->canManageUsers($actor)) return response()->json(['message' => 'You are not authorized to manage users.'], 403);
        return \Illuminate\Support\Facades\DB::transaction(function () use ($actor, $request, $id, $decision) {
            $user = User::where('user_id', $id)->lockForUpdate()->firstOrFail();
            if ($user->account_status !== 'pending') return response()->json(['message' => 'Only pending accounts can be '.$decision.'.'], 409);
            $before = ['account_status' => $user->account_status];
            $user->account_status = $decision === 'approved' ? 'active' : 'deactivated';
            $user->save();
            \App\Support\ActivityAudit::record($request, $actor, 'account_'.$decision, 'users', (int) $user->user_id, $before, ['account_status' => $user->account_status]);
            return response()->json(['message' => $decision === 'approved' ? 'User account approved successfully.' : 'Account rejected and deactivated.', 'user' => ['id' => (string) $user->user_id, 'status' => $user->account_status]]);
        }, 3);
    }
}
