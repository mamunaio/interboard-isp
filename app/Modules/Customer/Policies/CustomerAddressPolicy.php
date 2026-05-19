<?php

declare(strict_types=1);

namespace App\Modules\Customer\Policies;

use App\Core\Policies\ChecksRbac;
use App\Modules\Auth\Models\StaffUser;
use App\Modules\Customer\Models\CustomerAddress;

class CustomerAddressPolicy
{
    use ChecksRbac;

    /**
     * Determine whether the staff user can view addresses.
     */
    public function view(StaffUser $user, CustomerAddress $address): bool
    {
        if ($user->tenant_id !== $address->tenant_id) {
            return false;
        }

        return $this->authorizeUser($user, 'customer.view');
    }

    /**
     * Determine whether the staff user can create addresses.
     */
    public function create(StaffUser $user): bool
    {
        return $this->authorizeUser($user, 'customer.edit');
    }

    /**
     * Determine whether the staff user can update addresses.
     */
    public function update(StaffUser $user, CustomerAddress $address): bool
    {
        if ($user->tenant_id !== $address->tenant_id) {
            return false;
        }

        return $this->authorizeUser($user, 'customer.edit');
    }

    /**
     * Determine whether the staff user can delete addresses.
     */
    public function delete(StaffUser $user, CustomerAddress $address): bool
    {
        if ($user->tenant_id !== $address->tenant_id) {
            return false;
        }

        return $this->authorizeUser($user, 'customer.edit');
    }
}
