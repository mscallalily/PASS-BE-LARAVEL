<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverLicense extends Model
{
    protected $table = 'driver_licenses';

    protected $primaryKey = 'license_id';

    protected $fillable = [
        'driver_id',
        'license_number',
        'license_type',
        'restriction_codes',
        'issue_date',
        'expiry_date',
        'issuing_agency',
        'license_image_path',
        'license_status',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(
            Driver::class,
            'driver_id',
            'driver_id'
        );
    }
}