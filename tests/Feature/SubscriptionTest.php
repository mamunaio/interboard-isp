<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Tenant\TenantManager;
use App\Modules\Tenant\Models\Plan;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Models\ServicePackage;
use App\Modules\Customer\Models\CustomerServiceSubscription;
use App\Modules\Customer\Models\SubscriptionScheduledChange;
use App\Modules\Customer\Enums\CustomerType;
use App\Modules\Customer\Enums\ServiceType;
use App\Modules\Customer\Enums\SubscriptionStatus;
use App\Modules\Customer\Enums\ScheduledChangeStatus;
use App\Modules\Customer\Services\CustomerService;
use App\Modules\Customer\Services\ContractPackageService;
use App\Modules\Customer\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected Plan $plan;
    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected CustomerService $customerService;
    protected ContractPackageService $contractPackageService;
    protected SubscriptionService $subscriptionService;
    protected Customer $customerA;
    protected Customer $customerB;
    protected ServicePackage $packageBroadbandA;
    protected ServicePackage $packageVoipA;
    protected ServicePackage $packageBroadbandB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plan = Plan::create([
            'name' => 'Premium Plan',
            'slug' => 'premium',
            'price_monthly' => 50.00,
            'price_yearly' => 500.00,
        ]);

        $this->tenantA = Tenant::create([
            'uuid' => '00000000-0000-0000-0000-000000000101',
            'slug' => 'tenant-101',
            'name' => 'Tenant 101',
            'status' => 'active',
            'plan_id' => $this->plan->id,
        ]);

        $this->tenantB = Tenant::create([
            'uuid' => '00000000-0000-0000-0000-000000000102',
            'slug' => 'tenant-102',
            'name' => 'Tenant 102',
            'status' => 'active',
            'plan_id' => $this->plan->id,
        ]);

        $this->customerService = app(CustomerService::class);
        $this->contractPackageService = app(ContractPackageService::class);
        $this->subscriptionService = app(SubscriptionService::class);

        $tenantManager = app(TenantManager::class);

        // Setup Tenant A Customer and Service Packages
        $tenantManager->setTenant($this->tenantA);
        $this->customerA = $this->customerService->createCustomer($this->tenantA->id, [
            'type' => CustomerType::RESIDENTIAL,
            'full_name' => 'John Doe Tenant A',
            'phone' => '01800000001',
        ]);

        $this->packageBroadbandA = $this->contractPackageService->createServicePackage($this->tenantA->id, [
            'package_code' => 'PKG-BB-A',
            'name' => 'Broadband 10M',
            'service_type' => ServiceType::BROADBAND,
            'download_speed_kbps' => 10240,
            'upload_speed_kbps' => 10240,
            'customer_type_compatibility' => [CustomerType::RESIDENTIAL->value],
        ]);

        $this->packageVoipA = $this->contractPackageService->createServicePackage($this->tenantA->id, [
            'package_code' => 'PKG-VOIP-A',
            'name' => 'VoIP Voice',
            'service_type' => ServiceType::VOIP,
            'download_speed_kbps' => 512,
            'upload_speed_kbps' => 512,
            'customer_type_compatibility' => [CustomerType::RESIDENTIAL->value],
        ]);

        // Setup Tenant B Customer and Service Packages
        $tenantManager->setTenant($this->tenantB);
        $this->customerB = $this->customerService->createCustomer($this->tenantB->id, [
            'type' => CustomerType::RESIDENTIAL,
            'full_name' => 'Jane Smith Tenant B',
            'phone' => '01800000002',
        ]);

        $this->packageBroadbandB = $this->contractPackageService->createServicePackage($this->tenantB->id, [
            'package_code' => 'PKG-BB-B',
            'name' => 'Broadband 20M',
            'service_type' => ServiceType::BROADBAND,
            'download_speed_kbps' => 20480,
            'upload_speed_kbps' => 20480,
            'customer_type_compatibility' => [CustomerType::RESIDENTIAL->value],
        ]);
    }

    /**
     * Test direct create-as-active is prohibited. Must create as pending only.
     */
    public function test_cannot_create_subscription_as_active_directly(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        try {
            $this->subscriptionService->createSubscription($this->tenantA->id, (int)$this->customerA->id, [
                'service_package_id' => $this->packageBroadbandA->id,
                'status' => SubscriptionStatus::ACTIVE, // prohibited!
            ]);
            $this->fail('Subscription created directly as active without throwing ValidationException.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
        }

        // Must succeed when creating as pending
        $sub = $this->subscriptionService->createSubscription($this->tenantA->id, (int)$this->customerA->id, [
            'service_package_id' => $this->packageBroadbandA->id,
            'status' => SubscriptionStatus::PENDING,
        ]);

        $this->assertEquals(SubscriptionStatus::PENDING, $sub->status);
    }

    /**
     * Test deterministic and collision-safe subscription sequence codes generation.
     */
    public function test_subscription_code_generation_uses_deterministic_sequences(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $sub1 = $this->subscriptionService->createSubscription($this->tenantA->id, (int)$this->customerA->id, [
            'service_package_id' => $this->packageBroadbandA->id,
        ]);

        $sub2 = $this->subscriptionService->createSubscription($this->tenantA->id, (int)$this->customerA->id, [
            'service_package_id' => $this->packageVoipA->id,
        ]);

        $this->assertNotEquals($sub1->subscription_code, $sub2->subscription_code);
        $this->assertMatchesRegularExpression('/^SUB-\d{4}-00001$/', $sub1->subscription_code);
        $this->assertMatchesRegularExpression('/^SUB-\d{4}-00002$/', $sub2->subscription_code);
    }

    /**
     * Test active same-service type concurrency constraints (only one active/suspended of same type).
     */
    public function test_customer_can_have_only_one_active_or_suspended_subscription_per_service_type(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        // 1. Create first Broadband subscription and activate it
        $sub1 = $this->subscriptionService->createSubscription($this->tenantA->id, (int)$this->customerA->id, [
            'service_package_id' => $this->packageBroadbandA->id,
        ]);
        $this->subscriptionService->transitionSubscriptionStatus($sub1, SubscriptionStatus::ACTIVE);

        // 2. Create second Broadband subscription as pending (succeeds)
        $sub2 = $this->subscriptionService->createSubscription($this->tenantA->id, (int)$this->customerA->id, [
            'service_package_id' => $this->packageBroadbandA->id,
        ]);

        // 3. Activating the second Broadband subscription should fail due to active concurrency limit
        try {
            $this->subscriptionService->transitionSubscriptionStatus($sub2, SubscriptionStatus::ACTIVE);
            $this->fail('Second broadband subscription was activated successfully.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
        }

        // 4. Activating a VOIP subscription for the same customer should succeed perfectly!
        $subVoip = $this->subscriptionService->createSubscription($this->tenantA->id, (int)$this->customerA->id, [
            'service_package_id' => $this->packageVoipA->id,
        ]);
        $this->subscriptionService->transitionSubscriptionStatus($subVoip, SubscriptionStatus::ACTIVE);
        $this->assertEquals(SubscriptionStatus::ACTIVE, $subVoip->status);
    }

    /**
     * Test reactivation transient timestamps rules (suspended_at and disconnected_at must be cleared on active).
     */
    public function test_reactivation_clears_transient_lifecycle_timestamps(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $sub = $this->subscriptionService->createSubscription($this->tenantA->id, (int)$this->customerA->id, [
            'service_package_id' => $this->packageBroadbandA->id,
        ]);

        // Transition to active
        $this->subscriptionService->transitionSubscriptionStatus($sub, SubscriptionStatus::ACTIVE);
        $this->assertNotNull($sub->fresh()->activated_at);
        $this->assertNull($sub->fresh()->suspended_at);

        // Transition to suspended
        $this->subscriptionService->transitionSubscriptionStatus($sub, SubscriptionStatus::SUSPENDED);
        $this->assertNotNull($sub->fresh()->suspended_at);

        // Reactivate back to active
        $this->subscriptionService->transitionSubscriptionStatus($sub, SubscriptionStatus::ACTIVE);
        $this->assertNull($sub->fresh()->suspended_at); // Cleared!
        $this->assertNull($sub->fresh()->disconnected_at); // Cleared!
    }

    /**
     * Test tenant matches validation and packages tenant leakage blocks.
     */
    public function test_subscription_creation_validates_customer_and_package_tenant_leakage(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        // Try creating subscription under Tenant A using a Tenant B customer
        try {
            $this->subscriptionService->createSubscription($this->tenantA->id, (int)$this->customerB->id, [
                'service_package_id' => $this->packageBroadbandA->id,
            ]);
            $this->fail('Subscription created using a different tenant customer.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('customer_id', $e->errors());
        }

        // Try creating subscription under Tenant A using a Tenant B service package
        try {
            $this->subscriptionService->createSubscription($this->tenantA->id, (int)$this->customerA->id, [
                'service_package_id' => $this->packageBroadbandB->id,
            ]);
            $this->fail('Subscription created using a different tenant package.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('service_package_id', $e->errors());
        }
    }

    /**
     * Test scheduling upgrades/downgrades relational updates and executions.
     */
    public function test_scheduling_changes_normalized_validations(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $sub = $this->subscriptionService->createSubscription($this->tenantA->id, (int)$this->customerA->id, [
            'service_package_id' => $this->packageBroadbandA->id,
        ]);

        // 1. Schedule a package upgrade (Broadband 10M -> VoIP package or another Broadband target package)
        $change = $this->subscriptionService->scheduleSubscriptionChange($sub, [
            'target_service_package_id' => $this->packageVoipA->id,
            'scheduled_for' => '2026-06-01',
            'payload' => [
                'reason' => 'Customer requested VoIP voice package change.',
            ]
        ]);

        $this->assertNotNull($change);
        $this->assertEquals(ScheduledChangeStatus::PENDING, $change->status);
        $this->assertEquals($this->packageVoipA->id, $change->target_service_package_id);

        // 2. Reject arbitrary junk keys in payload
        try {
            $this->subscriptionService->scheduleSubscriptionChange($sub, [
                'target_service_package_id' => $this->packageVoipA->id,
                'scheduled_for' => '2026-06-01',
                'payload' => [
                    'reason' => 'Customer requested upgrade.',
                    'junk_prohibited_key' => 'hack',
                ]
            ]);
            $this->fail('Scheduled change created with prohibited junk keys.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('payload', $e->errors());
        }

        // 3. Execute scheduled change and verify parent subscription package ID gets updated!
        $this->subscriptionService->executeScheduledChange($change);
        $this->assertEquals(ScheduledChangeStatus::EXECUTED, $change->fresh()->status);
        $this->assertNotNull($change->fresh()->executed_at);
        $this->assertEquals($this->packageVoipA->id, $sub->fresh()->service_package_id);
    }
}
