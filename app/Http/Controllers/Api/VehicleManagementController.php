<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VehicleManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $actor = $request->user();

        if (! $actor || $actor->account_status !== 'active') {
            return response()->json([
                'message' => 'Your account is not authorized to view vehicles.',
            ], 403);
        }

        $isAdmin = $actor->roles()
            ->whereIn('role_code', ['admin', 'parking_admin'])
            ->where('is_active', true)
            ->exists();

        $query = Vehicle::with([
            'owner.profile',
            'type',
            'authorizations' => function ($query) {
                $query->orderByDesc('requested_at');
            },
        ]);

        // Administrators can view all vehicles.
        // Other active users can view only their own vehicles.
        if (! $isAdmin) {
            $query->where('owner_user_id', $actor->user_id);
        }

        // Optional search by plate number.
        $search = trim((string) $request->query('search', ''));

        if ($search !== '') {
            $query->where('plate_number', 'like', "%{$search}%");
        }

        $vehicles = $query
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Vehicle $vehicle) {
                $profile = $vehicle->owner?->profile;
                $authorization = $vehicle->authorizations->first();

                $ownerName = trim(
                    ($profile?->first_name ?? '') . ' ' .
                    ($profile?->last_name ?? '')
                );

                if ($ownerName === '' && $vehicle->owner_visitor_id) {
                    $visitor = DB::table('visitors')
                        ->where('visitor_id', $vehicle->owner_visitor_id)
                        ->first();

                    $ownerName = $visitor
                        ? trim(
                            $visitor->first_name . ' ' .
                            $visitor->last_name
                        )
                        : 'Visitor';
                }

                $rfid = DB::table('rfid_assignments as ra')
                    ->join('rfid_tags as rt', 'rt.rfid_id', '=', 'ra.rfid_id')
                    ->where('ra.vehicle_id', $vehicle->vehicle_id)
                    ->where('ra.assignment_status', 'active')
                    ->orderByDesc('ra.assigned_at')
                    ->value('rt.tag_uid');

                $authorizationStatus = $authorization?->authorization_status;

                $status = match ($authorizationStatus) {
                    'approved' => 'active',
                    'pending' => 'pending',
                    'rejected' => 'rejected',
                    'revoked' => 'suspended',
                    'expired' => 'expired',
                    default => $vehicle->registration_status,
                };

                return [
                    'id' => (string) $vehicle->vehicle_id,
                    'plate' => $vehicle->plate_number,
                    'owner' => $ownerName !== '' ? $ownerName : 'Unassigned',
                    'make' => $vehicle->make ?? '',
                    'model' => $vehicle->model ?? '',
                    'year' => $vehicle->year_model,
                    'type' => $vehicle->type?->type_name ?? 'Unknown',
                    'color' => $vehicle->color ?? '',
                    'rfid' => $rfid,
                    'status' => $status,
                    'registrationStatus' => $vehicle->registration_status,
                    'authorizationStatus' => $authorizationStatus,
                    'validity' => $authorization?->valid_until,
                ];
            });

        return response()->json([
            'vehicles' => $vehicles,
            'total' => $vehicles->count(),
        ]);
    }
}
