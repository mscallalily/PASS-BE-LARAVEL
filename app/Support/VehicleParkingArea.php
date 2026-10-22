<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** The two existing lots accept fixed categories. Configuration cannot swap them. */
final class VehicleParkingArea
{
    public static function code(string $typeCode): ?string
    {
        return match (strtolower($typeCode)) {
            'motorcycle' => 'MC',
            'sedan', 'suv', 'hatchback', 'pickup', 'van' => 'CAR',
            default => null,
        };
    }

    public static function forType(object $type): object
    {
        $code = self::code($type->type_code);
        $area = $code ? DB::table('parking_areas')->where('area_code', $code)->where('is_active', true)->first() : null;
        if (!$area || (int) $type->parking_area_id !== (int) $area->parking_area_id) {
            throw ValidationException::withMessages(['vehicle_type' => 'The parking lot for this vehicle type is unavailable. Contact PASS staff.']);
        }
        return $area;
    }

    public static function accepts(string $typeCode, object $area): bool
    {
        return self::code($typeCode) !== null && self::code($typeCode) === $area->area_code;
    }
}
