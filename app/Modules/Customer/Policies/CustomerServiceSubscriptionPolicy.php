<?php

declare(strict_types=1);

namespace App\Modules\Customer\Policies;

use App\Core\Policies\ChecksRbac;
use App\Modules\Auth\Models\StaffUser;
use App\Modules\Customer\Models\CustomerServiceSubscription;

class CustomerServiceSubscriptionPolicy
{
    use ChecksRbac;

    /**
     * Determine whether the staff user can view the customer service subscription.
     */
    public function view(StaffUser $user, CustomerServiceSubscription $subscription): bool
    {
        if ($user->tenant_id !== $subscription->tenant_id) {
            return false;
        }

        return $this->authorizeUser($user, 'customer.view');
    }

    /**
     * Determine whether the staff user can create customer service subscriptions.
     */
    public function create(StaffUser $user): bool
    {
        return $this->authorizeUser($user, 'customer.edit');
    }

    /**
     * Determine whether the staff user can update the customer service subscription.
     */
    public function update(StaffUser $user, CustomerServiceSubscription $subscription): bool
    {
        if ($user->tenant_id !== $subscription->tenant_id) {
            return false;
        }

        return $this->authorizeUser($user, 'customer.edit');
    }

    /**
     * Determine whether the staff user can delete the customer service subscription.
     */
    public function delete(StaffUser $user, CustomerServiceSubscription $subscription): bool
    {
        if ($user->tenant_id !== $subscription->tenant_id) {
            return false;
        }

        return $this->authorizeUser($user, 'customer.edit');
    }
}
