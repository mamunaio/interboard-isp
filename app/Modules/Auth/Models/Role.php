<?php

declare(strict_types=1);

namespace App\Modules\Auth\Models;

use App\Core\Tenant\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use BelongsToTenant;

    protected $table = 'roles';

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
    ];

    /**
     * Get the permissions assigned to this role.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'role_permissions',
            'role_id',
            'permission_id'
        );
    }

    /**
     * Get the staff users assigned to this role.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            StaffUser::class,
            'staff_user_roles',
            'role_id',
            'staff_user_id'
        );
    }
}
