<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParkingArea extends Model
{
    protected $table = 'parking_areas';
    protected $primaryKey = 'parking_area_id';

    public $timestamps = false;

    protected $fillable = [
        'area_code',
        'area_name',
        'building',
        'floor_level',
        'description',
        'latitude',
        'longitude',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'is_active' => 'boolean',
        ];
    }

    public function zones(): HasMany
    {
        return $this->hasMany(
            ParkingZone::class,
            'parking_area_id',
            'parking_area_id'
        );
    }
}
