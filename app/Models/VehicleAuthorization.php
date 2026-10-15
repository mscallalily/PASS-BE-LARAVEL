<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleAuthorization extends Model
{
    protected $table = 'vehicle_authorizations';
    protected $primaryKey = 'authorization_id';

   
protected $fillable = [
    'vehicle_id',
    'parking_area_id',
    'sticker_number',
    'authorization_status',
    'valid_from',
    'valid_until',
    'requested_at',
    'reviewed_by',
    'reviewed_at',
    'remarks',
];


    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id', 'vehicle_id');
    }
}