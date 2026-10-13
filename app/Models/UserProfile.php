<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    protected $table = 'user_profiles';
    protected $primaryKey = 'profile_id';

    protected $fillable = [
    'user_id', 'department_id', 'first_name', 'middle_name', 'last_name', 'suffix',
    'sex', 'birth_date', 'contact_number', 'address_line', 'city', 'province',
    'program_or_position', 'year_level', 'photo_path',
    'school_id_document_path',
    'emergency_contact_name', 'emergency_contact_number',
];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }
}