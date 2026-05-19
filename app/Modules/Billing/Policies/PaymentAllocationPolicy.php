<?php

declare(strict_types=1);

namespace App\Modules\Billing\Policies;

use App\Modules\Auth\Models\StaffUser;
use App\Modules\Billing\Models\PaymentAllocation;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentAllocationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the staff user can view payment allocations.
     */
    public function view(StaffUser $user, PaymentAllocation $allocation): bool
    {
        if ($user->tenant_id !== $allocation->tenant_id) {
            return false;
        }

        return $this->authorizeUser($user, 'billing.allocation.view');
    }

    /**
     * Determine whether the staff user can create payment allocations.
     */
    public function create(StaffUser $user): bool
    {
        return $this->authorizeUser($user, 'billing.allocation.manage');
    }

    /**
     * Helper to verify permission slug on staff actor.
     */
    protected function authorizeUser(StaffUser $user, string $permissionSlug): bool
    {
        return $user->roles()
            ->whereHas('permissions', function ($query) use ($permissionSlug) {
                $query->where('slug', $permissionSlug);
            })->exists();
    }
}
