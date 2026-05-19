<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Tenant\TenantManager;
use App\Modules\Tenant\Models\Plan;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Models\ServicePackage;
use App\Modules\Customer\Models\CustomerServiceSubscription;
use App\Modules\Customer\Enums\CustomerType;
use App\Modules\Customer\Enums\ServiceType;
use App\Modules\Customer\Enums\SubscriptionStatus;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Enums\InvoiceStatus;
use App\Modules\Billing\Enums\BillingMode;
use App\Modules\Billing\Enums\BillingCycleType;
use App\Modules\Billing\Services\BillingGenerationService;
use App\Modules\Customer\Services\CustomerService;
use App\Modules\Customer\Services\ContractPackageService;
use App\Modules\Customer\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Carbon\Carbon;

class BillingSchedulerTest extends TestCase
{
    use RefreshDatabase;

    protected Plan $plan;
    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected CustomerService $customerService;
    protected ContractPackageService $contractPackageService;
    protected SubscriptionService $subscriptionService;
    protected BillingGenerationService $billingGenerationService;
    protected Customer $customerA;
    protected ServicePackage $packageBroadband;

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
            'uuid' => '00000000-0000-0000-0000-000000000201',
            'slug' => 'tenant-201',
            'name' => 'Tenant 201',
            'status' => 'active',
            'plan_id' => $this->plan->id,
        ]);

        $this->tenantB = Tenant::create([
            'uuid' => '00000000-0000-0000-0000-000000000202',
            'slug' => 'tenant-202',
            'name' => 'Tenant 202',
            'status' => 'active',
            'plan_id' => $this->plan->id,
        ]);

        $this->customerService = app(CustomerService::class);
        $this->contractPackageService = app(ContractPackageService::class);
        $this->subscriptionService = app(SubscriptionService::class);
        $this->billingGenerationService = app(BillingGenerationService::class);

        $tenantManager = app(TenantManager::class);

        // Setup Tenant A Customer and Service Package
        $tenantManager->setTenant($this->tenantA);

        $this->customerA = $this->customerService->createCustomer($this->tenantA->id, [
            'type' => CustomerType::RESIDENTIAL,
            'full_name' => 'Alice Miller',
            'phone' => '01700000001',
        ]);

        $this->packageBroadband = $this->contractPackageService->createServicePackage($this->tenantA->id, [
            'package_code' => 'PKG-BB-TEST',
            'name' => 'Ultra Broadband 50M',
            'service_type' => ServiceType::BROADBAND,
            'price_monthly' => '1500.0000',
            'download_speed_kbps' => 51200,
            'upload_speed_kbps' => 51200,
            'customer_type_compatibility' => [CustomerType::RESIDENTIAL->value],
        ]);
    }

    /**
     * Test Prepaid Anniversary billing period calculation and next billing date advancement.
     */
    public function test_prepaid_anniversary_generation(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        // Create subscription as pending
        $sub = $this->subscriptionService->createSubscription($this->tenantA->id, (int)$this->customerA->id, [
            'service_package_id' => $this->packageBroadband->id,
        ]);

        // Setup scheduler details and force transition to active
        $sub->update([
            'status' => SubscriptionStatus::ACTIVE,
            'billing_mode' => BillingMode::PREPAID,
            'billing_cycle_type' => BillingCycleType::ANNIVERSARY,
            'next_billing_date' => '2026-06-15',
            'activated_at' => now(),
        ]);

        $invoice = $this->billingGenerationService->generateInvoiceForSubscription(
            $this->tenantA->id,
            (int)$sub->id,
            '2026-06-15'
        );

        $this->assertNotNull($invoice);
        $this->assertEquals(InvoiceStatus::ISSUED, $invoice->status);
        $this->assertEquals('1500.0000', (string)$invoice->grand_total);
        $this->assertEquals('1500.0000', (string)$invoice->amount_due);
        $this->assertEquals('0.0000', (string)$invoice->amount_paid);
        $this->assertEquals('2026-06-15', $invoice->billing_period_start->toDateString());
        $this->assertEquals('2026-07-14', $invoice->billing_period_end->toDateString());

        // Check subscription dates updated
        $sub = $sub->fresh();
        $this->assertEquals('2026-07-15', $sub->next_billing_date->toDateString());
        $this->assertNotNull($sub->last_billed_at);
    }

    /**
     * Test Prepaid Calendar billing period calculation.
     */
    public function test_prepaid_calendar_generation(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $sub = $this->subscriptionService->createSubscription($this->tenantA->id, (int)$this->customerA->id, [
            'service_package_id' => $this->packageBroadband->id,
        ]);

        $sub->update([
            'status' => SubscriptionStatus::ACTIVE,
            'billing_mode' => BillingMode::PREPAID,
            'billing_cycle_type' => BillingCycleType::CALENDAR,
            'next_billing_date' => '2026-06-01', // First day of calendar month
            'activated_at' => now(),
        ]);

        $invoice = $this->billingGenerationService->generateInvoiceForSubscription(
            $this->tenantA->id,
            (int)$sub->id,
            '2026-06-01'
        );

        $this->assertEquals('2026-06-01', $invoice->billing_period_start->toDateString());
        $this->assertEquals('2026-06-30', $invoice->billing_period_end->toDateString());
        $this->assertEquals('2026-07-01', $sub->fresh()->next_billing_date->toDateString());
    }

    /**
     * Test Postpaid Anniversary billing period calculation.
     */
    public function test_postpaid_anniversary_generation(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $sub = $this->subscriptionService->createSubscription($this->tenantA->id, (int)$this->customerA->id, [
            'service_package_id' => $this->packageBroadband->id,
        ]);

        $sub->update([
            'status' => SubscriptionStatus::ACTIVE,
            'billing_mode' => BillingMode::POSTPAID,
            'billing_cycle_type' => BillingCycleType::ANNIVERSARY,
            'next_billing_date' => '2026-07-15',
            'activated_at' => now(),
        ]);

        $invoice = $this->billingGenerationService->generateInvoiceForSubscription(
            $this->tenantA->id,
            (int)$sub->id,
            '2026-07-15'
        );

        $this->assertEquals('2026-06-15', $invoice->billing_period_start->toDateString());
        $this->assertEquals('2026-07-14', $invoice->billing_period_end->toDateString());
        $this->assertEquals('2026-08-15', $sub->fresh()->next_billing_date->toDateString());
    }

    /**
     * Test Postpaid Calendar billing period calculation.
     */
    public function test_postpaid_calendar_generation(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $sub = $this->subscriptionService->createSubscription($this->tenantA->id, (int)$this->customerA->id, [
            'service_package_id' => $this->packageBroadband->id,
        ]);

        $sub->update([
            'status' => SubscriptionStatus::ACTIVE,
            'billing_mode' => BillingMode::POSTPAID,
            'billing_cycle_type' => BillingCycleType::CALENDAR,
            'next_billing_date' => '2026-07-01', // First day of calendar month
            'activated_at' => now(),
        ]);

        $invoice = $this->billingGenerationService->generateInvoiceForSubscription(
            $this->tenantA->id,
            (int)$sub->id,
            '2026-07-01'
        );

        $this->assertEquals('2026-06-01', $invoice->billing_period_start->toDateString());
        $this->assertEquals('2026-06-30', $invoice->billing_period_end->toDateString());
        $this->assertEquals('2026-08-01', $sub->fresh()->next_billing_date->toDateString());
    }

    /**
     * Test that Calendar cycles strictly enforce first day of the month.
     */
    public function test_strict_calendar_mode_invariant(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $sub = $this->subscriptionService->createSubscription($this->tenantA->id, (int)$this->customerA->id, [
            'service_package_id' => $this->packageBroadband->id,
        ]);

        $sub->update([
            'status' => SubscriptionStatus::ACTIVE,
            'billing_mode' => BillingMode::PREPAID,
            'billing_cycle_type' => BillingCycleType::CALENDAR,
            'next_billing_date' => '2026-06-15', // Malformed! Calendar must be 1st of month.
            'activated_at' => now(),
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('For calendar billing cycles, next_billing_date must align to the 1st of the month.');

        $this->billingGenerationService->generateInvoiceForSubscription(
            $this->tenantA->id,
            (int)$sub->id,
            '2026-06-15'
        );
    }

    /**
     * Test that non-active subscription statuses are strictly rejected.
     */
    public function test_inactive_status_rejected(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $sub = $this->subscriptionService->createSubscription($this->tenantA->id, (int)$this->customerA->id, [
            'service_package_id' => $this->packageBroadband->id,
        ]);

        // Keep it pending
        $sub->update([
            'status' => SubscriptionStatus::PENDING,
            'billing_mode' => BillingMode::PREPAID,
            'billing_cycle_type' => BillingCycleType::ANNIVERSARY,
            'next_billing_date' => '2026-06-15',
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Only active subscriptions are eligible for billing.');

        $this->billingGenerationService->generateInvoiceForSubscription(
            $this->tenantA->id,
            (int)$sub->id,
            '2026-06-15'
        );
    }

    /**
     * Test unique period database constraints block duplicate invoice creation.
     */
    public function test_strict_database_duplicate_prevention(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $sub = $this->subscriptionService->createSubscription($this->tenantA->id, (int)$this->customerA->id, [
            'service_package_id' => $this->packageBroadband->id,
        ]);

        $sub->update([
            'status' => SubscriptionStatus::ACTIVE,
            'billing_mode' => BillingMode::PREPAID,
            'billing_cycle_type' => BillingCycleType::ANNIVERSARY,
            'next_billing_date' => '2026-06-15',
            'activated_at' => now(),
        ]);

        // First generation (succeeds)
        $this->billingGenerationService->generateInvoiceForSubscription(
            $this->tenantA->id,
            (int)$sub->id,
            '2026-06-15'
        );

        // Try second generation for the exact same subscription and billing period
        // We artificially reset subscription date back to trigger calculation for same period
        $sub->refresh();
        $sub->update(['next_billing_date' => '2026-06-15']);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('An invoice already exists for subscription ID');

        $this->billingGenerationService->generateInvoiceForSubscription(
            $this->tenantA->id,
            (int)$sub->id,
            '2026-06-15'
        );
    }

    /**
     * Test chunked and tenant-scoped manually runnable console command.
     */
    public function test_console_command_executions(): void
    {
        $tenantManager = app(TenantManager::class);

        // Setup Tenant A Active Subscription
        $tenantManager->setTenant($this->tenantA);
        $subA = $this->subscriptionService->createSubscription($this->tenantA->id, (int)$this->customerA->id, [
            'service_package_id' => $this->packageBroadband->id,
        ]);
        $subA->update([
            'status' => SubscriptionStatus::ACTIVE,
            'billing_mode' => BillingMode::PREPAID,
            'billing_cycle_type' => BillingCycleType::ANNIVERSARY,
            'next_billing_date' => '2026-06-15',
            'activated_at' => now(),
        ]);

        // Setup Tenant B Customer, Service Package and Active Subscription
        $tenantManager->setTenant($this->tenantB);
        
        $customerB = $this->customerService->createCustomer($this->tenantB->id, [
            'type' => CustomerType::RESIDENTIAL,
            'full_name' => 'Bob Davis',
            'phone' => '01700000002',
        ]);

        $packageB = $this->contractPackageService->createServicePackage($this->tenantB->id, [
            'package_code' => 'PKG-BB-TEST-B',
            'name' => 'Standard Broadband 20M',
            'service_type' => ServiceType::BROADBAND,
            'price_monthly' => '1000.0000',
            'download_speed_kbps' => 20480,
            'upload_speed_kbps' => 20480,
            'customer_type_compatibility' => [CustomerType::RESIDENTIAL->value],
        ]);

        $subB = $this->subscriptionService->createSubscription($this->tenantB->id, (int)$customerB->id, [
            'service_package_id' => $packageB->id,
        ]);
        
        $subB->update([
            'status' => SubscriptionStatus::ACTIVE,
            'billing_mode' => BillingMode::POSTPAID,
            'billing_cycle_type' => BillingCycleType::ANNIVERSARY,
            'next_billing_date' => '2026-06-15',
            'activated_at' => now(),
        ]);

        // Switch tenant context to B before command run to test auto context restore
        $tenantManager->setTenant($this->tenantB);

        // Run manually runnable billing generate console command
        $this->artisan('billing:generate', ['--date' => '2026-06-15'])
            ->expectsOutputToContain('Starting subscription invoice generation for run date: 2026-06-15...')
            ->expectsOutputToContain('Successfully generated invoice INV-')
            ->expectsOutputToContain('Invoice generation complete. Total processed: 2. Successes: 2. Failures: 0.')
            ->assertExitCode(0);

        // Verify Tenant A subscription updated
        $this->assertEquals('2026-07-15', $subA->fresh()->next_billing_date->toDateString());
        // Verify Tenant B subscription updated
        $this->assertEquals('2026-07-15', $subB->fresh()->next_billing_date->toDateString());

        // Verify active tenant context was perfectly restored back to Tenant B (prevent leaks!)
        $this->assertEquals($this->tenantB->id, $tenantManager->getTenantId());
    }
}
