<?php

declare(strict_types=1);

namespace App\Modules\Auth\Policies;

use App\Core\Policies\ChecksRbac;
use App\Modules\Auth\Models\StaffUser;

class StaffUserPolicy
{
    use ChecksRbac;

    /**
     * Determine whether the user can view any staff users.
     */
    public function viewAny(StaffUser $user): bool
    {
        return $this->authorizeUser($user, 'customer.staff.view');
    }

    /**
     * Determine whether the user can create a staff user.
     */
    public function create(StaffUser $user): bool
    {
        return $this->authorizeUser($user, 'customer.staff.create');
    }

    /**
     * Determine whether the user can update the staff user.
     */
    public function update(StaffUser $user, StaffUser $model): bool
    {
        // Enforce tenant boundary
        if ($user->tenant_id !== $model->tenant_id) {
            return false;
        }

        return $this->authorizeUser($user, 'customer.staff.edit');
    }

    /**
     * Determine whether the user can delete the staff user.
     */
    public function delete(StaffUser $user, StaffUser $model): bool
    {
        if ($user->tenant_id !== $model->tenant_id) {
            return false;
        }

        return $this->authorizeUser($user, 'customer.staff.delete');
    }
}
