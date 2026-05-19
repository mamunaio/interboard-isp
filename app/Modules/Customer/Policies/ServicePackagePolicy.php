<?php

declare(strict_types=1);

namespace App\Modules\Customer\Policies;

use App\Core\Policies\ChecksRbac;
use App\Modules\Auth\Models\StaffUser;
use App\Modules\Customer\Models\ServicePackage;

class ServicePackagePolicy
{
    use ChecksRbac;

    /**
     * Determine whether the staff user can view service packages.
     */
    public function view(StaffUser $user, ServicePackage $package): bool
    {
        if ($user->tenant_id !== $package->tenant_id) {
            return false;
        }

        return $this->authorizeUser($user, 'customer.view');
    }

    /**
     * Determine whether the staff user can create service packages.
     */
    public function create(StaffUser $user): bool
    {
        return $this->authorizeUser($user, 'customer.edit');
    }

    /**
     * Determine whether the staff user can update service packages.
     */
    public function update(StaffUser $user, ServicePackage $package): bool
    {
        if ($user->tenant_id !== $package->tenant_id) {
            return false;
        }

        return $this->authorizeUser($user, 'customer.edit');
    }

    /**
     * Determine whether the staff user can delete service packages.
     */
    public function delete(StaffUser $user, ServicePackage $package): bool
    {
        if ($user->tenant_id !== $package->tenant_id) {
            return false;
        }

        return $this->authorizeUser($user, 'customer.edit');
    }
}
