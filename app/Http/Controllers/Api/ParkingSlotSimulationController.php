<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParkingSlotSimulation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ParkingSlotSimulationController extends Controller
{
    // GET: Retrieve all simulated parking slots
    public function index(Request $request)
    {
        if (!$request->user()->canManageParking()) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 403);
        }

        $savedSlots = ParkingSlotSimulation::all()
            ->keyBy(fn ($slot) =>
                $slot->vehicle_type . '-' . $slot->slot_number
            );

        $slots = [];

        // 84 motorcycle slots, 55 provisional car slots
        $vehicleTypes = [
            'motorcycle' => 84,
            'car' => 55,
        ];

        foreach ($vehicleTypes as $type => $total) {
            for ($number = 1; $number <= $total; $number++) {
                $key = $type . '-' . $number;
                $saved = $savedSlots->get($key);

                $slots[] = [
                    'slot_code' => ($type === 'motorcycle' ? 'M-' : 'C-')
                        . str_pad($number, 2, '0', STR_PAD_LEFT),
                    'vehicle_type' => $type,
                    'slot_number' => $number,
                    'is_occupied' => $saved
                        ? (bool) $saved->is_occupied
                        : false,
                    'updated_at' => $saved?->updated_at,
                ];
            }
        }

        return response()->json([
            'mode' => 'simulation',
            'slots' => $slots,
            'summary' => [
                'total' => count($slots),
                'occupied' => collect($slots)
                    ->where('is_occupied', true)->count(),
                'available' => collect($slots)
                    ->where('is_occupied', false)->count(),
            ],
        ]);
    }

    // PATCH: Update one simulated parking slot
    public function update(Request $request)
    {
        if (!$request->user()->canManageParking()) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 403);
        }

        $validated = $request->validate([
            'vehicle_type' => [
                'required',
                Rule::in(['motorcycle', 'car']),
            ],
            'slot_number' => [
                'required',
                'integer',
                'min:1',
            ],
            'is_occupied' => [
                'required',
                'boolean',
            ],
        ]);

        $maximum = $validated['vehicle_type'] === 'motorcycle'
            ? 84
            : 55;

        if ($validated['slot_number'] > $maximum) {
            return response()->json([
                'message' => 'Invalid parking slot number.'
            ], 422);
        }

        $slot = ParkingSlotSimulation::updateOrCreate(
            [
                'vehicle_type' => $validated['vehicle_type'],
                'slot_number' => $validated['slot_number'],
            ],
            [
                'is_occupied' => $validated['is_occupied'],
                'updated_by' => $request->user()->user_id,
            ]
        );

        return response()->json([
            'message' => 'Parking slot updated successfully.',
            'slot' => $slot,
        ]);
    }
}
