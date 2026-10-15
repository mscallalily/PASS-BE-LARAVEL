<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParkingSlot extends Model
{
    protected $table = 'parking_slots';
    protected $primaryKey = 'slot_id';

    protected $fillable = [
        'zone_id',
        'slot_code',
        'vehicle_type_id',
        'slot_status',
        'is_pwd_slot',
        'reserved_for_user_id',
        'pos_x',
        'pos_y',
        'slot_width',
        'slot_height',
        'rotation_deg',
    ];

    protected function casts(): array
    {
        return [
            'is_pwd_slot' => 'boolean',
        ];
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(ParkingZone::class, 'zone_id', 'zone_id');
    }

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(
            VehicleType::class,
            'vehicle_type_id',
            'vehicle_type_id'
        );
    }

    public function sensors(): HasMany
    {
        return $this->hasMany(ParkingSensor::class, 'slot_id', 'slot_id');
    }
}
