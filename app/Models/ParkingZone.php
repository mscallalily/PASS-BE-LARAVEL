<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParkingZone extends Model
{
    protected $table = 'parking_zones';
    protected $primaryKey = 'zone_id';

    protected $fillable = [
        'parking_area_id',
        'zone_code',
        'zone_name',
        'vehicle_type_id',
        'declared_capacity',
        'layout_width',
        'layout_height',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'declared_capacity' => 'integer',
            'layout_width' => 'integer',
            'layout_height' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(
            ParkingArea::class,
            'parking_area_id',
            'parking_area_id'
        );
    }

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(
            VehicleType::class,
            'vehicle_type_id',
            'vehicle_type_id'
        );
    }

    public function slots(): HasMany
    {
        return $this->hasMany(
            ParkingSlot::class,
            'zone_id',
            'zone_id'
        );
    }
}
