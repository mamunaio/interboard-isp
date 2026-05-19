<?php

declare(strict_types=1);

namespace App\Modules\Customer\Services;

use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Models\CustomerAddress;
use App\Modules\Customer\Models\CustomerContact;
use Illuminate\Validation\ValidationException;

class AddressContactService
{
    /**
     * Add a new Address to a Customer cleanly.
     */
    public function addAddress(int $tenantId, int $customerId, array $data): CustomerAddress
    {
        // 1. Verify tenant-scoped Customer ownership strictly
        $customer = $this->resolveAndValidateCustomer($tenantId, $customerId);

        // 2. Validate coordinates range
        $this->validateCoordinates($data);

        // 3. Populate matching tenant identity
        $data['tenant_id'] = $customer->tenant_id;
        $data['customer_id'] = $customer->id;

        // 4. Force default values for billing/installation flag if missing
        $data['is_default_billing'] = (bool)($data['is_default_billing'] ?? false);
        $data['is_default_installation'] = (bool)($data['is_default_installation'] ?? false);

        // 5. Enforce single-primary rules
        if ($data['is_default_billing']) {
            $this->unsetOtherDefaultBillings($customer->id);
        }
        if ($data['is_default_installation']) {
            $this->unsetOtherDefaultInstallations($customer->id);
        }

        return CustomerAddress::create($data);
    }

    /**
     * Update an existing Address cleanly.
     */
    public function updateAddress(CustomerAddress $address, array $data): CustomerAddress
    {
        // 1. Validate coordinates range
        $this->validateCoordinates($data);

        // 2. Handle default billing flag toggle
        if (isset($data['is_default_billing']) && (bool)$data['is_default_billing']) {
            $this->unsetOtherDefaultBillings((int)$address->customer_id, (int)$address->id);
            $data['is_default_billing'] = true;
        }

        // 3. Handle default installation flag toggle
        if (isset($data['is_default_installation']) && (bool)$data['is_default_installation']) {
            $this->unsetOtherDefaultInstallations((int)$address->customer_id, (int)$address->id);
            $data['is_default_installation'] = true;
        }

        $address->update($data);

        return $address;
    }

    /**
     * Add a new Contact to a Customer cleanly.
     */
    public function addContact(int $tenantId, int $customerId, array $data): CustomerContact
    {
        // 1. Verify tenant-scoped Customer ownership strictly
        $customer = $this->resolveAndValidateCustomer($tenantId, $customerId);

        // 2. Populate matching tenant identity
        $data['tenant_id'] = $customer->tenant_id;
        $data['customer_id'] = $customer->id;

        // 3. Force default values for primary flag if missing
        $data['is_primary'] = (bool)($data['is_primary'] ?? false);

        // 4. Enforce single-primary rule
        if ($data['is_primary']) {
            $this->unsetOtherPrimaryContacts($customer->id);
        }

        return CustomerContact::create($data);
    }

    /**
     * Update an existing Contact cleanly.
     */
    public function updateContact(CustomerContact $contact, array $data): CustomerContact
    {
        // 1. Handle primary flag toggle
        if (isset($data['is_primary']) && (bool)$data['is_primary']) {
            $this->unsetOtherPrimaryContacts((int)$contact->customer_id, (int)$contact->id);
            $data['is_primary'] = true;
        }

        $contact->update($data);

        return $contact;
    }

    /**
     * Retrieve and validate that the customer belongs to the active tenant strictly.
     */
    protected function resolveAndValidateCustomer(int $tenantId, int $customerId): Customer
    {
        $customer = Customer::withoutGlobalScopes()
            ->where('id', $customerId)
            ->first();

        if (!$customer || (int)$customer->tenant_id !== $tenantId) {
            throw ValidationException::withMessages([
                'customer_id' => ['The selected customer does not belong to the active tenant context.']
            ]);
        }

        return $customer;
    }

    /**
     * Validate coordinate boundaries.
     * Latitude: -90 to 90
     * Longitude: -180 to 180
     */
    protected function validateCoordinates(array $data): void
    {
        if (isset($data['latitude']) && $data['latitude'] !== null) {
            $lat = (float)$data['latitude'];
            if ($lat < -90.0 || $lat > 90.0) {
                throw ValidationException::withMessages([
                    'latitude' => ['Latitude must be between -90 and 90 degrees.']
                ]);
            }
        }

        if (isset($data['longitude']) && $data['longitude'] !== null) {
            $lon = (float)$data['longitude'];
            if ($lon < -180.0 || $lon > 180.0) {
                throw ValidationException::withMessages([
                    'longitude' => ['Longitude must be between -180 and 180 degrees.']
                ]);
            }
        }
    }

    /**
     * Unset other default billing addresses for the given customer.
     */
    protected function unsetOtherDefaultBillings(int $customerId, ?int $excludeAddressId = null): void
    {
        $query = CustomerAddress::withoutGlobalScopes()
            ->where('customer_id', $customerId);

        if ($excludeAddressId !== null) {
            $query->where('id', '!=', $excludeAddressId);
        }

        $query->update(['is_default_billing' => false]);
    }

    /**
     * Unset other default installation addresses for the given customer.
     */
    protected function unsetOtherDefaultInstallations(int $customerId, ?int $excludeAddressId = null): void
    {
        $query = CustomerAddress::withoutGlobalScopes()
            ->where('customer_id', $customerId);

        if ($excludeAddressId !== null) {
            $query->where('id', '!=', $excludeAddressId);
        }

        $query->update(['is_default_installation' => false]);
    }

    /**
     * Unset other primary contacts for the given customer.
     */
    protected function unsetOtherPrimaryContacts(int $customerId, ?int $excludeContactId = null): void
    {
        $query = CustomerContact::withoutGlobalScopes()
            ->where('customer_id', $customerId);

        if ($excludeContactId !== null) {
            $query->where('id', '!=', $excludeContactId);
        }

        $query->update(['is_primary' => false]);
    }
}
