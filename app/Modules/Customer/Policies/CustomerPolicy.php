<?php

declare(strict_types=1);

namespace App\Modules\Customer\Policies;

use App\Core\Policies\ChecksRbac;
use App\Modules\Auth\Models\StaffUser;
use App\Modules\Customer\Models\Customer;

class CustomerPolicy
{
    use ChecksRbac;

    /**
     * Determine whether the staff user can view any customers.
     */
    public function viewAny(StaffUser $user): bool
    {
        return $this->authorizeUser($user, 'customer.view');
    }

    /**
     * Determine whether the staff user can view a specific customer.
     */
    public function view(StaffUser $user, Customer $customer): bool
    {
        if ($user->tenant_id !== $customer->tenant_id) {
            return false;
        }

        return $this->authorizeUser($user, 'customer.view');
    }

    /**
     * Determine whether the staff user can create a customer.
     */
    public function create(StaffUser $user): bool
    {
        return $this->authorizeUser($user, 'customer.create');
    }

    /**
     * Determine whether the staff user can update the customer.
     */
    public function update(StaffUser $user, Customer $customer): bool
    {
        if ($user->tenant_id !== $customer->tenant_id) {
            return false;
        }

        return $this->authorizeUser($user, 'customer.edit');
    }

    /**
     * Determine whether the staff user can delete the customer.
     */
    public function delete(StaffUser $user, Customer $customer): bool
    {
        if ($user->tenant_id !== $customer->tenant_id) {
            return false;
        }

        return $this->authorizeUser($user, 'customer.delete');
    }
}
