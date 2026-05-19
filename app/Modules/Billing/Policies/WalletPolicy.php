<?php

declare(strict_types=1);

namespace App\Modules\Billing\Policies;

use App\Modules\Auth\Models\StaffUser;
use App\Modules\Billing\Models\Wallet;
use Illuminate\Auth\Access\HandlesAuthorization;

class WalletPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the staff user can view wallets.
     */
    public function view(StaffUser $user, Wallet $wallet): bool
    {
        if ($user->tenant_id !== $wallet->tenant_id) {
            return false;
        }

        return $this->authorizeUser($user, 'billing.wallet.view');
    }

    /**
     * Determine whether the staff user can create wallets.
     */
    public function create(StaffUser $user): bool
    {
        return $this->authorizeUser($user, 'billing.wallet.manage');
    }

    /**
     * Determine whether the staff user can update wallets.
     */
    public function update(StaffUser $user, Wallet $wallet): bool
    {
        if ($user->tenant_id !== $wallet->tenant_id) {
            return false;
        }

        return $this->authorizeUser($user, 'billing.wallet.manage');
    }

    /**
     * Determine whether the staff user can delete wallets.
     */
    public function delete(StaffUser $user, Wallet $wallet): bool
    {
        if ($user->tenant_id !== $wallet->tenant_id) {
            return false;
        }

        return $this->authorizeUser($user, 'billing.wallet.manage');
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
