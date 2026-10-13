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

        $query = User::with([
            'profile.department',
            'roles',
        ]);

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
                    ($profile?->last_name ?? '')
                ),
                'email' => $user->email,
                'idNum' => $user->user_code,
                'role' => $user->roles->first()?->role_code,
                'userType' => $user->user_type,
                'dept' => $profile?->department?->department_name,
                'contact' => $profile?->contact_number,
                'status' => $user->account_status,
                'registered' => $user->created_at,
            ];
        });

        return response()->json([
            'users' => $users,
        ]);
    }

    /**
     * Approve a pending account.
     */
    public function approve(Request $request, string $id): JsonResponse
    {
        $actor = $request->user();

        if (! $actor || ! $this->canManageUsers($actor)) {
            return response()->json([
                'message' => 'You are not authorized to approve accounts.',
            ], 403);
        }

        $user = User::where('user_id', $id)->firstOrFail();

        if ($user->account_status !== 'pending') {
            return response()->json([
                'message' => 'Only pending accounts can be approved.',
            ], 409);
        }

        $user->account_status = 'active';
        $user->save();

        return response()->json([
            'message' => 'User account approved successfully.',
            'user' => [
                'id' => (string) $user->user_id,
                'status' => $user->account_status,
            ],
        ]);
    }

    /**
     * Reject a pending account using the existing deactivated status.
     */
    public function reject(Request $request, string $id): JsonResponse
    {
        $actor = $request->user();

        if (! $actor || ! $this->canManageUsers($actor)) {
            return response()->json([
                'message' => 'You are not authorized to reject accounts.',
            ], 403);
        }

        $user = User::where('user_id', $id)->firstOrFail();

        if ($user->account_status !== 'pending') {
            return response()->json([
                'message' => 'Only pending accounts can be rejected.',
            ], 409);
        }

        // The database enum has no "rejected" value.
        $user->account_status = 'deactivated';
        $user->save();

        return response()->json([
            'message' => 'Account rejected and deactivated.',
            'user' => [
                'id' => (string) $user->user_id,
                'status' => $user->account_status,
            ],
        ]);
    }
}
