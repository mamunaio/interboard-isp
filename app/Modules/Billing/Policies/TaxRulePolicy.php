<?php

declare(strict_types=1);

namespace App\Modules\Billing\Policies;

use App\Core\Policies\ChecksRbac;
use App\Modules\Auth\Models\StaffUser;
use App\Modules\Billing\Models\TaxRule;

class TaxRulePolicy
{
    use ChecksRbac;

    /**
     * Determine whether the staff user can view tax rules.
     */
    public function view(StaffUser $user, TaxRule $taxRule): bool
    {
        if ($user->tenant_id !== $taxRule->tenant_id) {
            return false;
        }

        return $this->authorizeUser($user, 'billing.tax.view');
    }

    /**
     * Determine whether the staff user can create tax rules.
     */
    public function create(StaffUser $user): bool
    {
        return $this->authorizeUser($user, 'billing.tax.manage');
    }

    /**
     * Determine whether the staff user can update tax rules.
     */
    public function update(StaffUser $user, TaxRule $taxRule): bool
    {
        if ($user->tenant_id !== $taxRule->tenant_id) {
            return false;
        }

        return $this->authorizeUser($user, 'billing.tax.manage');
    }

    /**
     * Determine whether the staff user can delete tax rules.
     */
    public function delete(StaffUser $user, TaxRule $taxRule): bool
    {
        if ($user->tenant_id !== $taxRule->tenant_id) {
            return false;
        }

        return $this->authorizeUser($user, 'billing.tax.manage');
    }
}
