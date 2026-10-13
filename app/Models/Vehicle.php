<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use SoftDeletes;

    protected $table = 'vehicles';
    protected $primaryKey = 'vehicle_id';

    protected $fillable = [
        'owner_user_id', 'owner_visitor_id', 'vehicle_type_id', 'plate_number',
        'make', 'model', 'color', 'year_model', 'or_number', 'cr_number',
        'chassis_number', 'registration_status',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id', 'user_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class, 'vehicle_type_id', 'vehicle_type_id');
    }

    public function authorizations(): HasMany
    {
        return $this->hasMany(VehicleAuthorization::class, 'vehicle_id', 'vehicle_id');
    }
}