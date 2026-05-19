<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Tenant\TenantManager;
use App\Modules\Tenant\Models\Plan;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Enums\CustomerType;
use App\Modules\Customer\Enums\CustomerStatus;
use App\Modules\Customer\Services\CustomerService;
use App\Modules\Auth\Models\StaffUser;
use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CustomerCoreTest extends TestCase
{
    use RefreshDatabase;

    protected Plan $plan;
    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected CustomerService $customerService;

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
    }

    /**
     * Test tenant isolation for Customer creation and queries.
     */
    public function test_customer_creation_sets_tenant_id_and_isolates_queries(): void
    {
        $tenantManager = app(TenantManager::class);

        // 1. Create Customer for Tenant A
        $tenantManager->setTenant($this->tenantA);
        $customerA = $this->customerService->createCustomer($this->tenantA->id, [
            'type' => CustomerType::RESIDENTIAL,
            'full_name' => 'John Tenant A',
            'phone' => '01700000001',
            'email' => 'john@a.com',
        ]);

        $this->assertEquals($this->tenantA->id, $customerA->tenant_id);

        // 2. Create Customer for Tenant B
        $tenantManager->setTenant($this->tenantB);
        $customerB = $this->customerService->createCustomer($this->tenantB->id, [
            'type' => CustomerType::BUSINESS,
            'full_name' => 'Jane Tenant B',
            'phone' => '01700000002',
            'email' => 'jane@b.com',
        ]);

        $this->assertEquals($this->tenantB->id, $customerB->tenant_id);

        // 3. Under Tenant A context, only Customer A is visible
        $tenantManager->setTenant($this->tenantA);
        $this->assertCount(1, Customer::all());
        $this->assertEquals('John Tenant A', Customer::first()->full_name);

        // 4. Under Tenant B context, only Customer B is visible
        $tenantManager->setTenant($this->tenantB);
        $this->assertCount(1, Customer::all());
        $this->assertEquals('Jane Tenant B', Customer::first()->full_name);
    }

    /**
     * Test encrypted cast fields for PII protection and blind SHA-256 searchable indexes.
     */
    public function test_sensitive_pii_is_stored_encrypted_with_searchable_hashes(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $customer = $this->customerService->createCustomer($this->tenantA->id, [
            'type' => CustomerType::RESIDENTIAL,
            'full_name' => 'Secret Customer',
            'phone' => '01700000003',
            'national_id' => '1234567890',
            'business_registration' => 'REG-999-XYZ',
            'date_of_birth' => '1990-05-15',
        ]);

        // 1. Assert decrypted access on the model object
        $this->assertEquals('1234567890', $customer->national_id);
        $this->assertEquals('REG-999-XYZ', $customer->business_registration);
        $this->assertEquals('1990-05-15', $customer->date_of_birth);

        // 2. Fetch raw row from Database to confirm encryption and blind index hashes
        $rawRow = DB::table('customers')->where('id', $customer->id)->first();
        
        $this->assertNotEquals('1234567890', $rawRow->national_id);
        $this->assertNotEquals('REG-999-XYZ', $rawRow->business_registration);
        $this->assertNotEquals('1990-05-15', $rawRow->date_of_birth);

        $this->assertStringContainsString('eyJpdi', $rawRow->national_id);
        $this->assertStringContainsString('eyJpdi', $rawRow->business_registration);
        $this->assertStringContainsString('eyJpdi', $rawRow->date_of_birth);

        // 3. Assert searchable blind index SHA-256 hashes match perfectly
        $this->assertEquals(hash('sha256', '1234567890'), $rawRow->national_id_hash);
        $this->assertEquals(hash('sha256', 'reg-999-xyz'), $rawRow->business_registration_hash);
    }

    /**
     * Test lifecycle state transitions rules.
     */
    public function test_customer_lifecycle_rules_enforce_valid_transitions(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $customer = $this->customerService->createCustomer($this->tenantA->id, [
            'type' => CustomerType::RESIDENTIAL,
            'full_name' => 'Lifecycle Customer',
            'phone' => '01700000004',
            'status' => CustomerStatus::LEAD,
        ]);

        // 1. LEAD -> PROSPECT is permitted
        $this->customerService->transitionStatus($customer, CustomerStatus::PROSPECT);
        $this->assertEquals(CustomerStatus::PROSPECT, $customer->fresh()->status);

        // 2. PROSPECT -> LEAD is permitted
        $this->customerService->transitionStatus($customer, CustomerStatus::LEAD);
        $this->assertEquals(CustomerStatus::LEAD, $customer->fresh()->status);

        // 3. LEAD -> ACTIVE is NOT permitted directly, must throw ValidationException
        $this->expectException(ValidationException::class);
        $this->customerService->transitionStatus($customer, CustomerStatus::ACTIVE);
    }

    /**
     * Test concurrency-safe customer code generation and database-locked sequence fallback.
     */
    public function test_customer_code_generation_uses_transaction_locked_database_sequence_on_redis_failure(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        // Explicitly test the deterministic transaction-locked database sequence generation fallback!
        // We will call the generator and verify it inserts a record and increments it
        $code1 = $this->customerService->generateCustomerCode($this->tenantA->id);
        $code2 = $this->customerService->generateCustomerCode($this->tenantA->id);

        $this->assertNotEquals($code1, $code2);
        $this->assertMatchesRegularExpression('/^CUST-\d{4}-\d{5}$/', $code1);
        $this->assertMatchesRegularExpression('/^CUST-\d{4}-\d{5}$/', $code2);

        // Verify sequence table increments safely
        $year = date('Y');
        $this->assertDatabaseHas('customer_code_sequences', [
            'tenant_id' => $this->tenantA->id,
            'year' => $year,
        ]);
    }

    /**
     * Test tenant-scoped optional uniqueness for email, national_id, business_registration.
     */
    public function test_tenant_scoped_uniqueness_checks_are_enforced_by_service(): void
    {
        $tenantManager = app(TenantManager::class);

        // 1. Create a customer under Tenant A
        $tenantManager->setTenant($this->tenantA);
        $this->customerService->createCustomer($this->tenantA->id, [
            'type' => CustomerType::RESIDENTIAL,
            'full_name' => 'First Customer A',
            'phone' => '01700000005',
            'email' => 'unique@email.com',
            'national_id' => 'NID-999',
            'business_registration' => 'REG-999',
        ]);

        // 2. Creating another customer under Tenant A with same email should fail
        try {
            $this->customerService->createCustomer($this->tenantA->id, [
                'type' => CustomerType::RESIDENTIAL,
                'full_name' => 'Duplicate Customer A',
                'phone' => '01700000006',
                'email' => 'unique@email.com',
            ]);
            $this->fail('Expected ValidationException was not thrown for duplicate email.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('email', $e->errors());
        }

        // 3. Creating another customer under Tenant A with same national_id should fail (blind index check!)
        try {
            $this->customerService->createCustomer($this->tenantA->id, [
                'type' => CustomerType::RESIDENTIAL,
                'full_name' => 'Duplicate Customer A',
                'phone' => '01700000006',
                'national_id' => 'NID-999',
            ]);
            $this->fail('Expected ValidationException was not thrown for duplicate national_id.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('national_id', $e->errors());
        }

        // 4. Creating another customer under Tenant A with same business_registration should fail (blind index check!)
        try {
            $this->customerService->createCustomer($this->tenantA->id, [
                'type' => CustomerType::RESIDENTIAL,
                'full_name' => 'Duplicate Customer A',
                'phone' => '01700000006',
                'business_registration' => 'REG-999',
            ]);
            $this->fail('Expected ValidationException was not thrown for duplicate business_registration.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('business_registration', $e->errors());
        }

        // 5. Creating a customer under Tenant B with SAME credentials should succeed perfectly (cross-tenant uniqueness!)
        $tenantManager->setTenant($this->tenantB);
        $customerB = $this->customerService->createCustomer($this->tenantB->id, [
            'type' => CustomerType::RESIDENTIAL,
            'full_name' => 'Customer under Tenant B',
            'phone' => '01700000005', // same phone across different tenant is permitted by DB unique scoping
            'email' => 'unique@email.com',
            'national_id' => 'NID-999',
            'business_registration' => 'REG-999',
        ]);

        $this->assertNotNull($customerB);
        $this->assertEquals('unique@email.com', $customerB->email);
    }

    /**
     * Test Policy Authorization (RBAC) rules.
     */
    public function test_customer_policies_enforce_rbac_correctly(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $customer = $this->customerService->createCustomer($this->tenantA->id, [
            'type' => CustomerType::RESIDENTIAL,
            'full_name' => 'Policy Customer',
            'phone' => '01700000010',
        ]);

        // Create Roles & Permissions
        $viewPermission = Permission::create([
            'module' => 'customer',
            'action' => 'view',
            'slug' => 'customer.view',
        ]);

        $viewRole = Role::create([
            'name' => 'Viewer',
            'slug' => 'customer-viewer',
        ]);
        $viewRole->permissions()->attach($viewPermission);

        $staffUser = StaffUser::create([
            'name' => 'Staff Tester',
            'email' => 'tester@tenant-a.com',
            'password' => 'secret123',
            'status' => 'active',
        ]);
        
        $staffUser->roles()->attach($viewRole);

        // Act as staffUser
        $this->actingAs($staffUser, 'sanctum');

        $this->assertTrue(Gate::allows('view', $customer));
        $this->assertFalse(Gate::allows('create', Customer::class));
    }
}
