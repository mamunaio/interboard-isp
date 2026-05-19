<?php

declare(strict_types=1);

namespace App\Modules\Customer\Policies;

use App\Core\Policies\ChecksRbac;
use App\Modules\Auth\Models\StaffUser;
use App\Modules\Customer\Models\CustomerDocument;

class CustomerDocumentPolicy
{
    use ChecksRbac;

    /**
     * Determine whether the staff user can view customer documents.
     */
    public function view(StaffUser $user, CustomerDocument $document): bool
    {
        if ($user->tenant_id !== $document->tenant_id) {
            return false;
        }

        return $this->authorizeUser($user, 'customer.view');
    }

    /**
     * Determine whether the staff user can upload customer documents.
     */
    public function create(StaffUser $user): bool
    {
        return $this->authorizeUser($user, 'customer.edit');
    }

    /**
     * Determine whether the staff user can update or verify customer documents.
     */
    public function update(StaffUser $user, CustomerDocument $document): bool
    {
        if ($user->tenant_id !== $document->tenant_id) {
            return false;
        }

        return $this->authorizeUser($user, 'customer.edit');
    }

    /**
     * Determine whether the staff user can delete customer documents.
     */
    public function delete(StaffUser $user, CustomerDocument $document): bool
    {
        if ($user->tenant_id !== $document->tenant_id) {
            return false;
        }

        return $this->authorizeUser($user, 'customer.edit');
    }

    /**
     * Determine whether the staff user can download customer documents.
     */
    public function download(StaffUser $user, CustomerDocument $document): bool
    {
        if ($user->tenant_id !== $document->tenant_id) {
            return false;
        }

        return $this->authorizeUser($user, 'customer.view');
    }
}
