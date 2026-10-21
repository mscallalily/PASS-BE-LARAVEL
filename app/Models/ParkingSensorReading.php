<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParkingSensorReading extends Model
{
    protected $table = 'parking_sensor_readings';
    protected $primaryKey = 'reading_id';

    protected $fillable = [
        'sensor_id',
        'occupancy_status',
        'detected_at',
        'received_at',
        'source',
        'event_reference',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'detected_at' => 'datetime',
            'received_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function sensor(): BelongsTo
    {
        return $this->belongsTo(
            ParkingSensor::class,
            'sensor_id',
            'sensor_id'
        );
    }
}
