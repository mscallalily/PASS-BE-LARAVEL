<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParkingSlotSimulation extends Model
{
    protected $fillable = [
        'vehicle_type',
        'slot_number',
        'is_occupied',
        'updated_by',
    ];

    protected $casts = [
        'is_occupied' => 'boolean',
    ];
}
