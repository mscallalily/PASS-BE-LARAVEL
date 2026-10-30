<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistrationSession extends Model
{
    protected $primaryKey = 'session_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
    protected $hidden = ['owner_hash', 'capture_hash', 'code_hash', 'draft', 'license_result', 'document_path', 'document_hash'];

    protected function casts(): array
    {
        return [
            'draft' => 'encrypted:array', 'license_result' => 'encrypted:array',
            'code_sent_at' => 'datetime', 'code_expires_at' => 'datetime',
            'email_verified_at' => 'datetime', 'capture_expires_at' => 'datetime',
            'license_started_at' => 'datetime', 'consumed_at' => 'datetime', 'expires_at' => 'datetime',
        ];
    }
}
