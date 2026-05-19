<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Tenant\TenantManager;
use App\Modules\Tenant\Models\Plan;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Models\CustomerContract;
use App\Modules\Customer\Models\ServicePackage;
use App\Modules\Customer\Enums\CustomerType;
use App\Modules\Customer\Enums\ContractStatus;
use App\Modules\Customer\Enums\ServiceType;
use App\Modules\Customer\Services\CustomerService;
use App\Modules\Customer\Services\ContractPackageService;
use App\Modules\Auth\Models\StaffUser;
use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ContractPackageTest extends TestCase
{
    use RefreshDatabase;

    protected Plan $plan;
    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected CustomerService $customerService;
    protected ContractPackageService $contractPackageService;
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
        $this->contractPackageService = app(ContractPackageService::class);

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
     * Test contract creation and deterministic incrementing sequences.
     */
    public function test_contract_creation_uses_deterministic_incrementing_sequence(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $contract1 = $this->contractPackageService->createContract($this->tenantA->id, (int)$this->customerA->id, [
            'title' => 'Contract 1',
            'start_date' => '2026-05-01',
        ]);

        $contract2 = $this->contractPackageService->createContract($this->tenantA->id, (int)$this->customerA->id, [
            'title' => 'Contract 2',
            'start_date' => '2026-05-01',
        ]);

        $this->assertNotEquals($contract1->contract_number, $contract2->contract_number);
        $this->assertMatchesRegularExpression('/^CON-\d{4}-00001$/', $contract1->contract_number);
        $this->assertMatchesRegularExpression('/^CON-\d{4}-00002$/', $contract2->contract_number);
    }

    /**
     * Test contract terms_snapshot schema validation blocks arbitrary keys and validates required fields.
     */
    public function test_contract_terms_snapshot_schema_validation(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        // 1. Valid snapshot schema succeeds
        $contract = $this->contractPackageService->createContract($this->tenantA->id, (int)$this->customerA->id, [
            'title' => 'Valid Terms Contract',
            'start_date' => '2026-05-01',
            'terms_snapshot' => [
                'sla_level' => 'premium',
                'billing_cycle' => 'monthly',
                'minimum_commitment_months' => 12,
            ]
        ]);

        $this->assertEquals('premium', $contract->terms_snapshot['sla_level']);

        // 2. Missing required fields fails
        try {
            $this->contractPackageService->createContract($this->tenantA->id, (int)$this->customerA->id, [
                'title' => 'Invalid Terms Contract',
                'start_date' => '2026-05-01',
                'terms_snapshot' => [
                    'sla_level' => 'premium',
                ]
            ]);
            $this->fail('Validation exception was not thrown for missing snapshot fields.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('terms_snapshot', $e->errors());
        }

        // 3. Prohibited junk fields fails
        try {
            $this->contractPackageService->createContract($this->tenantA->id, (int)$this->customerA->id, [
                'title' => 'Junk Terms Contract',
                'start_date' => '2026-05-01',
                'terms_snapshot' => [
                    'sla_level' => 'premium',
                    'billing_cycle' => 'monthly',
                    'minimum_commitment_months' => 12,
                    'junk_data_field' => 'hack',
                ]
            ]);
            $this->fail('Validation exception was not thrown for arbitrary terms snapshot keys.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('terms_snapshot', $e->errors());
        }
    }

    /**
     * Test activation rule consistency (signed_at cannot be null when active).
     */
    public function test_contract_activation_requires_signed_at_date(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        // 1. Attempt creating active contract with null signed_at should fail
        try {
            $this->contractPackageService->createContract($this->tenantA->id, (int)$this->customerA->id, [
                'title' => 'Direct Active Contract',
                'start_date' => '2026-05-01',
                'status' => ContractStatus::ACTIVE,
                'signed_at' => null,
            ]);
            $this->fail('Activation succeeded without signed_at date.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('signed_at', $e->errors());
        }

        // 2. Create as draft, then transition to active with signed_at should succeed
        $contract = $this->contractPackageService->createContract($this->tenantA->id, (int)$this->customerA->id, [
            'title' => 'Draft Contract',
            'start_date' => '2026-05-01',
            'status' => ContractStatus::DRAFT,
        ]);

        $this->contractPackageService->transitionContractStatus($contract, ContractStatus::ACTIVE, [
            'signed_at' => now(),
        ]);

        $this->assertEquals(ContractStatus::ACTIVE, $contract->fresh()->status);
        $this->assertNotNull($contract->fresh()->signed_at);
    }

    /**
     * Test contract lifecycle state transition rules.
     */
    public function test_contract_lifecycle_transitions_rules(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $contract = $this->contractPackageService->createContract($this->tenantA->id, (int)$this->customerA->id, [
            'title' => 'Lifecycle Contract',
            'start_date' => '2026-05-01',
            'status' => ContractStatus::DRAFT,
        ]);

        // 1. DRAFT -> TERMINATED is permitted
        $this->contractPackageService->transitionContractStatus($contract, ContractStatus::TERMINATED);
        $this->assertEquals(ContractStatus::TERMINATED, $contract->fresh()->status);

        // 2. TERMINATED -> ACTIVE is NOT permitted (terminal state)
        try {
            $this->contractPackageService->transitionContractStatus($contract, ContractStatus::ACTIVE, [
                'signed_at' => now(),
            ]);
            $this->fail('Transition from terminated to active succeeded.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
        }
    }

    /**
     * Test Service Package configuration validations (download/upload speed, burst logical consistency, compatibility).
     */
    public function test_service_package_configuration_validations(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        // 1. Speeds must be > 0
        try {
            $this->contractPackageService->createServicePackage($this->tenantA->id, [
                'package_code' => 'PKG-0',
                'name' => 'Speed 0 Package',
                'service_type' => ServiceType::BROADBAND,
                'download_speed_kbps' => 0, // speed <= 0
                'upload_speed_kbps' => 1024,
            ]);
            $this->fail('Package with 0 download speed was created.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('download_speed_kbps', $e->errors());
        }

        // 2. Burst consistency checks: burst speed must be strictly greater than standard speeds
        try {
            $this->contractPackageService->createServicePackage($this->tenantA->id, [
                'package_code' => 'PKG-BURST-ERR',
                'name' => 'Invalid Burst Package',
                'service_type' => ServiceType::BROADBAND,
                'download_speed_kbps' => 5000,
                'upload_speed_kbps' => 5000,
                'burst_download_kbps' => 4000, // Should be > 5000
                'burst_upload_kbps' => 6000,
                'burst_threshold_kb' => 1000,
                'burst_time_seconds' => 10,
            ]);
            $this->fail('Package with invalid burst download speed was created.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('burst_download_kbps', $e->errors());
        }

        // 3. Customer Type Compatibility validation checks
        try {
            $this->contractPackageService->createServicePackage($this->tenantA->id, [
                'package_code' => 'PKG-COMPAT-ERR',
                'name' => 'Invalid Compat Package',
                'service_type' => ServiceType::BROADBAND,
                'download_speed_kbps' => 5000,
                'upload_speed_kbps' => 5000,
                'customer_type_compatibility' => ['invalid_customer_type'], // Non-existent enum value!
            ]);
            $this->fail('Package with invalid customer type compatibility was created.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('customer_type_compatibility', $e->errors());
        }
    }

    /**
     * Test unique package_code per tenant isolation.
     */
    public function test_service_package_code_must_be_unique_per_tenant_only(): void
    {
        $tenantManager = app(TenantManager::class);

        // 1. Create package code PKG-100 under Tenant A
        $tenantManager->setTenant($this->tenantA);
        $this->contractPackageService->createServicePackage($this->tenantA->id, [
            'package_code' => 'PKG-100',
            'name' => 'Standard Package A',
            'service_type' => ServiceType::BROADBAND,
            'download_speed_kbps' => 10000,
            'upload_speed_kbps' => 10000,
            'customer_type_compatibility' => [CustomerType::RESIDENTIAL->value],
        ]);

        // 2. Duplicate under Tenant A must fail
        try {
            $this->contractPackageService->createServicePackage($this->tenantA->id, [
                'package_code' => 'PKG-100',
                'name' => 'Standard Package A2',
                'service_type' => ServiceType::BROADBAND,
                'download_speed_kbps' => 10000,
                'upload_speed_kbps' => 10000,
                'customer_type_compatibility' => [CustomerType::RESIDENTIAL->value],
            ]);
            $this->fail('Duplicate package code was created under same tenant.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('package_code', $e->errors());
        }

        // 3. Same package code PKG-100 under Tenant B must succeed perfectly! (Tenant isolation check)
        $tenantManager->setTenant($this->tenantB);
        $pkgB = $this->contractPackageService->createServicePackage($this->tenantB->id, [
            'package_code' => 'PKG-100',
            'name' => 'Standard Package B',
            'service_type' => ServiceType::BROADBAND,
            'download_speed_kbps' => 10000,
            'upload_speed_kbps' => 10000,
            'customer_type_compatibility' => [CustomerType::RESIDENTIAL->value],
        ]);

        $this->assertNotNull($pkgB);
        $this->assertEquals('PKG-100', $pkgB->package_code);
    }
}
