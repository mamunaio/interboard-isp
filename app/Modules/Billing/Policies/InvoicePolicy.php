<?php

declare(strict_types=1);

namespace App\Modules\Billing\Policies;

use App\Core\Policies\ChecksRbac;
use App\Modules\Auth\Models\StaffUser;
use App\Modules\Billing\Models\Invoice;

class InvoicePolicy
{
    use ChecksRbac;

    /**
     * Determine whether the staff user can view invoices.
     */
    public function view(StaffUser $user, Invoice $invoice): bool
    {
        if ($user->tenant_id !== $invoice->tenant_id) {
            return false;
        }

        return $this->authorizeUser($user, 'customer.view');
    }

    /**
     * Determine whether the staff user can create invoices.
     */
    public function create(StaffUser $user): bool
    {
        return $this->authorizeUser($user, 'customer.edit');
    }

    /**
     * Determine whether the staff user can update invoices.
     */
    public function update(StaffUser $user, Invoice $invoice): bool
    {
        if ($user->tenant_id !== $invoice->tenant_id) {
            return false;
        }

        return $this->authorizeUser($user, 'customer.edit');
    }

    /**
     * Determine whether the staff user can delete/void invoices.
     */
    public function delete(StaffUser $user, Invoice $invoice): bool
    {
        if ($user->tenant_id !== $invoice->tenant_id) {
            return false;
        }

        return $this->authorizeUser($user, 'customer.edit');
    }
}
