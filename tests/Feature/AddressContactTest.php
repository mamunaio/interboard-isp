<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Tenant\TenantManager;
use App\Modules\Tenant\Models\Plan;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Models\CustomerAddress;
use App\Modules\Customer\Models\CustomerContact;
use App\Modules\Customer\Enums\CustomerType;
use App\Modules\Customer\Enums\AddressType;
use App\Modules\Customer\Enums\ContactType;
use App\Modules\Customer\Services\CustomerService;
use App\Modules\Customer\Services\AddressContactService;
use App\Modules\Auth\Models\StaffUser;
use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AddressContactTest extends TestCase
{
    use RefreshDatabase;

    protected Plan $plan;
    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected CustomerService $customerService;
    protected AddressContactService $addressContactService;
    protected Customer $customerA;
    protected Customer $customerB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plan = Plan::create([
            'name' => 'Starter Plan',
            'slug' => 'starter',
            'price_monthly' => 20.00,
            'price_yearly' => 200.00,
        ]);

        $this->tenantA = Tenant::create([
            'uuid' => '00000000-0000-0000-0000-000000000001',
            'slug' => 'tenant-a',
            'name' => 'Tenant A',
            'status' => 'active',
            'plan_id' => $this->plan->id,
        ]);

        $this->tenantB = Tenant::create([
            'uuid' => '00000000-0000-0000-0000-000000000002',
            'slug' => 'tenant-b',
            'name' => 'Tenant B',
            'status' => 'active',
            'plan_id' => $this->plan->id,
        ]);

        $this->customerService = app(CustomerService::class);
        $this->addressContactService = app(AddressContactService::class);

        $tenantManager = app(TenantManager::class);

        // Setup Tenant A Customer
        $tenantManager->setTenant($this->tenantA);
        $this->customerA = $this->customerService->createCustomer($this->tenantA->id, [
            'type' => CustomerType::RESIDENTIAL,
            'full_name' => 'Customer Tenant A',
            'phone' => '01700000001',
        ]);

        // Setup Tenant B Customer
        $tenantManager->setTenant($this->tenantB);
        $this->customerB = $this->customerService->createCustomer($this->tenantB->id, [
            'type' => CustomerType::BUSINESS,
            'full_name' => 'Customer Tenant B',
            'phone' => '01700000002',
        ]);
    }

    /**
     * Test adding address and contact under owned customer succeeds and populates tenant context.
     */
    public function test_adding_address_and_contact_succeeds_for_owned_customer(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $address = $this->addressContactService->addAddress($this->tenantA->id, (int)$this->customerA->id, [
            'type' => AddressType::BOTH,
            'label' => 'HQ',
            'address_line_1' => '123 Main St',
            'city' => 'Dhaka',
            'country' => 'Bangladesh',
            'latitude' => 23.8103,
            'longitude' => 90.4125,
        ]);

        $this->assertEquals($this->tenantA->id, $address->tenant_id);
        $this->assertEquals($this->customerA->id, $address->customer_id);

        $contact = $this->addressContactService->addContact($this->tenantA->id, (int)$this->customerA->id, [
            'full_name' => 'John Doe',
            'phone' => '01700000003',
            'type' => ContactType::TECHNICAL,
        ]);

        $this->assertEquals($this->tenantA->id, $contact->tenant_id);
        $this->assertEquals($this->customerA->id, $contact->customer_id);
    }

    /**
     * Test cross-tenant customer ownership blocks creation strictly.
     */
    public function test_cross_tenant_attachment_fails_strictly(): void
    {
        $tenantManager = app(TenantManager::class);

        // Under Tenant A active session, attempt to link Address to Tenant B's Customer
        $tenantManager->setTenant($this->tenantA);

        $this->expectException(ValidationException::class);
        $this->addressContactService->addAddress($this->tenantA->id, (int)$this->customerB->id, [
            'type' => AddressType::BILLING,
            'label' => 'HQ',
            'address_line_1' => '123 Cross St',
            'city' => 'Dhaka',
            'country' => 'Bangladesh',
        ]);
    }

    /**
     * Test coordinates validation limits.
     */
    public function test_coordinate_validations_enforce_boundaries(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        // 1. Invalid Latitude
        try {
            $this->addressContactService->addAddress($this->tenantA->id, (int)$this->customerA->id, [
                'type' => AddressType::BILLING,
                'label' => 'HQ',
                'address_line_1' => '123 Main St',
                'city' => 'Dhaka',
                'country' => 'Bangladesh',
                'latitude' => 95.0, // Limit is 90
            ]);
            $this->fail('Validation exception was not thrown for invalid latitude.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('latitude', $e->errors());
        }

        // 2. Invalid Longitude
        try {
            $this->addressContactService->addAddress($this->tenantA->id, (int)$this->customerA->id, [
                'type' => AddressType::BILLING,
                'label' => 'HQ',
                'address_line_1' => '123 Main St',
                'city' => 'Dhaka',
                'country' => 'Bangladesh',
                'longitude' => -185.0, // Limit is -180
            ]);
            $this->fail('Validation exception was not thrown for invalid longitude.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('longitude', $e->errors());
        }
    }

    /**
     * Test address primary unsetting rules (billing and installation separate).
     */
    public function test_address_default_unsetting_works_perfectly(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        // 1. Create Address 1 as default billing
        $address1 = $this->addressContactService->addAddress($this->tenantA->id, (int)$this->customerA->id, [
            'type' => AddressType::BILLING,
            'label' => 'Address 1',
            'address_line_1' => '123 St',
            'city' => 'Dhaka',
            'country' => 'Bangladesh',
            'is_default_billing' => true,
        ]);

        $this->assertTrue((bool)$address1->is_default_billing);

        // 2. Create Address 2 as default billing (should unset Address 1)
        $address2 = $this->addressContactService->addAddress($this->tenantA->id, (int)$this->customerA->id, [
            'type' => AddressType::BILLING,
            'label' => 'Address 2',
            'address_line_1' => '456 St',
            'city' => 'Dhaka',
            'country' => 'Bangladesh',
            'is_default_billing' => true,
        ]);

        $this->assertTrue((bool)$address2->is_default_billing);
        $this->assertFalse((bool)$address1->fresh()->is_default_billing);
    }

    /**
     * Test contact primary flag uniqueness.
     */
    public function test_contact_primary_unsetting_works_perfectly(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        // 1. Add primary contact 1
        $contact1 = $this->addressContactService->addContact($this->tenantA->id, (int)$this->customerA->id, [
            'full_name' => 'Contact 1',
            'phone' => '01700000004',
            'type' => ContactType::GENERAL,
            'is_primary' => true,
        ]);

        $this->assertTrue((bool)$contact1->is_primary);

        // 2. Add primary contact 2 (should unset Contact 1)
        $contact2 = $this->addressContactService->addContact($this->tenantA->id, (int)$this->customerA->id, [
            'full_name' => 'Contact 2',
            'phone' => '01700000005',
            'type' => ContactType::GENERAL,
            'is_primary' => true,
        ]);

        $this->assertTrue((bool)$contact2->is_primary);
        $this->assertFalse((bool)$contact1->fresh()->is_primary);
    }

    /**
     * Test Policy gates authorization rules.
     */
    public function test_address_and_contact_policies_secure_access(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $address = $this->addressContactService->addAddress($this->tenantA->id, (int)$this->customerA->id, [
            'type' => AddressType::BILLING,
            'label' => 'Branch Office',
            'address_line_1' => 'HQ Street',
            'city' => 'Dhaka',
            'country' => 'Bangladesh',
        ]);

        // Create Viewer Role
        $viewPermission = Permission::create([
            'module' => 'customer',
            'action' => 'view',
            'slug' => 'customer.view',
        ]);

        $role = Role::create([
            'name' => 'Viewer',
            'slug' => 'viewer',
        ]);
        $role->permissions()->attach($viewPermission);

        $staffUser = StaffUser::create([
            'name' => 'Staff Policy Tester',
            'email' => 'tester@tenant-a.com',
            'password' => 'secret123',
            'status' => 'active',
        ]);
        $staffUser->roles()->attach($role);

        $this->actingAs($staffUser, 'sanctum');

        $this->assertTrue(Gate::allows('view', $address));
        $this->assertFalse(Gate::allows('update', $address));
    }
}
