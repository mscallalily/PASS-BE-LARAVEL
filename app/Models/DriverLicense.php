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
        'original_file_name',
        'license_status',
        'verification_status',
        'verification_source',
        'verification_reason',
        'ocr_result',
        'verification_run_id',
        'submitted_at',
        'checked_at',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiry_date' => 'date',
            'ocr_result' => 'array',
            'submitted_at' => 'datetime',
            'checked_at' => 'datetime',
            'reviewed_at' => 'datetime',
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