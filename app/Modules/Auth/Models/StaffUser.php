<?php

declare(strict_types=1);

namespace App\Modules\Auth\Models;

use App\Core\Tenant\Traits\BelongsToTenant;
use App\Core\Audit\Traits\LogsActivity;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class StaffUser extends Authenticatable
{
    use HasApiTokens, BelongsToTenant, LogsActivity;

    protected $table = 'staff_users';

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'phone',
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'status',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $casts = [
        'password' => 'hashed',
        'two_factor_confirmed_at' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    /**
     * Get the roles assigned to this staff user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'staff_user_roles',
            'staff_user_id',
            'role_id'
        );
    }

    /**
     * Check if user has a specific role by slug.
     */
    public function hasRole(string $slug): bool
    {
        return $this->roles->contains('slug', $slug);
    }
}
