<?php

declare(strict_types=1);

namespace App\Modules\Customer\Policies;

use App\Core\Policies\ChecksRbac;
use App\Modules\Auth\Models\StaffUser;
use App\Modules\Customer\Models\CustomerContract;

class CustomerContractPolicy
{
    use ChecksRbac;

    /**
     * Determine whether the staff user can view customer contracts.
     */
    public function view(StaffUser $user, CustomerContract $contract): bool
    {
        if ($user->tenant_id !== $contract->tenant_id) {
            return false;
        }

        return $this->authorizeUser($user, 'customer.view');
    }

    /**
     * Determine whether the staff user can create customer contracts.
     */
    public function create(StaffUser $user): bool
    {
        return $this->authorizeUser($user, 'customer.edit');
    }

    /**
     * Determine whether the staff user can update customer contracts.
     */
    public function update(StaffUser $user, CustomerContract $contract): bool
    {
        if ($user->tenant_id !== $contract->tenant_id) {
            return false;
        }

        return $this->authorizeUser($user, 'customer.edit');
    }

    /**
     * Determine whether the staff user can delete customer contracts.
     */
    public function delete(StaffUser $user, CustomerContract $contract): bool
    {
        if ($user->tenant_id !== $contract->tenant_id) {
            return false;
        }

        return $this->authorizeUser($user, 'customer.edit');
    }
}
