<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParkingSensor extends Model
{
    protected $table = 'parking_sensors';
    protected $primaryKey = 'sensor_id';

    protected $fillable = [
        'slot_id',
        'sensor_code',
        'sensor_type',
        'hardware_model',
        'sensor_status',
        'last_seen_at',
        'installed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'installed_at' => 'datetime',
        ];
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(ParkingSlot::class, 'slot_id', 'slot_id');
    }

    public function readings(): HasMany
    {
        return $this->hasMany(
            ParkingSensorReading::class,
            'sensor_id',
            'sensor_id'
        );
    }
}
