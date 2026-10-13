<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleType extends Model
{
    public $timestamps = false;

    protected $table = 'vehicle_types';
    protected $primaryKey = 'vehicle_type_id';

    protected $fillable = ['type_code', 'type_name', 'requires_assigned_slot'];

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'vehicle_type_id', 'vehicle_type_id');
    }
}