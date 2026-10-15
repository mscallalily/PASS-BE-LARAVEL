<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, SoftDeletes;

    protected $table = 'users';
    protected $primaryKey = 'user_id';

    protected $fillable = [
        'user_code', 'email', 'password', 'user_type', 'account_status',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class, 'user_id', 'user_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id')
            ->withPivot('assigned_at', 'assigned_by');
    }

    /**
     * Active administrators and parking/security staff.
     */
    public function canManageParking(): bool
    {
        if ($this->account_status !== 'active') {
            return false;
        }

        if (in_array($this->user_type, ['security', 'admin'], true)) {
            return true;
        }

        return $this->roles()
            ->whereIn('role_code', ['admin', 'parking_admin', 'security'])
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Active administrators (they approve visitor parking requests; ordinary
     * Parking Staff cannot).
     */
    public function isAdmin(): bool
    {
        if ($this->account_status !== 'active') {
            return false;
        }

        if ($this->user_type === 'admin') {
            return true;
        }

        return $this->roles()
            ->whereIn('role_code', ['admin', 'parking_admin'])
            ->where('is_active', true)
            ->exists();
    }

    /** Guest (visitor) account with the guest role. */
    public function isGuest(): bool
    {
        return $this->roles()->where('role_code', 'guest')->exists();
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'owner_user_id', 'user_id');
    }
}