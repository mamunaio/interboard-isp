<?php

declare(strict_types=1);

namespace App\Modules\Customer\Policies;

use App\Core\Policies\ChecksRbac;
use App\Modules\Auth\Models\StaffUser;
use App\Modules\Customer\Models\CustomerContact;

class CustomerContactPolicy
{
    use ChecksRbac;

    /**
     * Determine whether the staff user can view contacts.
     */
    public function view(StaffUser $user, CustomerContact $contact): bool
    {
        if ($user->tenant_id !== $contact->tenant_id) {
            return false;
        }

        return $this->authorizeUser($user, 'customer.view');
    }

    /**
     * Determine whether the staff user can create contacts.
     */
    public function create(StaffUser $user): bool
    {
        return $this->authorizeUser($user, 'customer.edit');
    }

    /**
     * Determine whether the staff user can update contacts.
     */
    public function update(StaffUser $user, CustomerContact $contact): bool
    {
        if ($user->tenant_id !== $contact->tenant_id) {
            return false;
        }

        return $this->authorizeUser($user, 'customer.edit');
    }

    /**
     * Determine whether the staff user can delete contacts.
     */
    public function delete(StaffUser $user, CustomerContact $contact): bool
    {
        if ($user->tenant_id !== $contact->tenant_id) {
            return false;
        }

        return $this->authorizeUser($user, 'customer.edit');
    }
}
