<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    public $timestamps = false;

    protected $table = 'departments';
    protected $primaryKey = 'department_id';

    protected $fillable = ['department_code', 'department_name', 'is_active'];

    public function profiles(): HasMany
    {
        return $this->hasMany(UserProfile::class, 'department_id', 'department_id');
    }
}