<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Tenant\TenantManager;
use App\Core\Settings\SettingsRegistry;
use App\Modules\Tenant\Models\Plan;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Models\ServicePackage;
use App\Modules\Customer\Models\CustomerServiceSubscription;
use App\Modules\Customer\Enums\CustomerType;
use App\Modules\Customer\Enums\ServiceType;
use App\Modules\Customer\Enums\SubscriptionStatus;
use App\Modules\Customer\Services\CustomerService;
use App\Modules\Customer\Services\ContractPackageService;
use App\Modules\Customer\Services\SubscriptionService;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Models\SuspensionEvent;
use App\Modules\Billing\Enums\InvoiceStatus;
use App\Modules\Billing\Enums\InvoiceType;
use App\Modules\Billing\Services\SuspensionEvaluationService;
use App\Modules\Billing\Services\SuspensionActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Tests\TestCase;

class SuspensionReactivationTest extends TestCase
{
    use RefreshDatabase;

    protected Plan $plan;
    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected CustomerService $customerService;
    protected ContractPackageService $contractPackageService;
    protected SubscriptionService $subscriptionService;
    protected SuspensionEvaluationService $evaluationService;
    protected SuspensionActionService $actionService;
    protected SettingsRegistry $settingsRegistry;

    protected Customer $customerA;
    protected Customer $customerB;
    protected ServicePackage $packageBroadbandA;
    protected ServicePackage $packageBroadbandB;
    protected CustomerServiceSubscription $subscriptionA;
    protected CustomerServiceSubscription $subscriptionB;

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
        $this->evaluationService = app(SuspensionEvaluationService::class);
        $this->actionService = app(SuspensionActionService::class);
        $this->settingsRegistry = app(SettingsRegistry::class);

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

        $this->subscriptionA = $this->subscriptionService->createSubscription($this->tenantA->id, (int)$this->customerA->id, [
            'service_package_id' => $this->packageBroadbandA->id,
        ]);
        $this->subscriptionService->transitionSubscriptionStatus($this->subscriptionA, SubscriptionStatus::ACTIVE);

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

        $this->subscriptionB = $this->subscriptionService->createSubscription($this->tenantB->id, (int)$this->customerB->id, [
            'service_package_id' => $this->packageBroadbandB->id,
        ]);
        $this->subscriptionService->transitionSubscriptionStatus($this->subscriptionB, SubscriptionStatus::ACTIVE);
    }

    /**
     * 1. Test: active overdue unpaid => eligible
     */
    public function test_active_overdue_unpaid_is_eligible_for_suspension(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $dueDate = Carbon::parse('2026-05-10');
        $today = Carbon::parse('2026-05-15'); // 5 days after, grace days is 3

        $invoice = Invoice::create([
            'tenant_id' => $this->tenantA->id,
            'customer_id' => $this->customerA->id,
            'customer_service_subscription_id' => $this->subscriptionA->id,
            'invoice_number' => 'INV-TEST-001',
            'invoice_type' => InvoiceType::SUBSCRIPTION->value,
            'issue_date' => '2026-05-01',
            'due_date' => $dueDate->toDateString(),
            'currency' => 'BDT',
            'subtotal' => '100.0000',
            'grand_total' => '100.0000',
            'amount_paid' => '0.0000',
            'amount_due' => '100.0000',
            'status' => InvoiceStatus::ISSUED->value,
        ]);

        $evaluation = $this->evaluationService->evaluateSuspension($this->subscriptionA, $today);

        $this->assertTrue($evaluation->eligible);
        $this->assertContains($invoice->id, $evaluation->triggeringInvoiceIds);
    }

    /**
     * 2. Test: active no overdue => not eligible
     */
    public function test_active_no_overdue_is_not_eligible_for_suspension(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $dueDate = Carbon::parse('2026-05-13');
        $today = Carbon::parse('2026-05-15'); // 2 days after, grace days is 3

        Invoice::create([
            'tenant_id' => $this->tenantA->id,
            'customer_id' => $this->customerA->id,
            'customer_service_subscription_id' => $this->subscriptionA->id,
            'invoice_number' => 'INV-TEST-002',
            'invoice_type' => InvoiceType::SUBSCRIPTION->value,
            'issue_date' => '2026-05-01',
            'due_date' => $dueDate->toDateString(),
            'currency' => 'BDT',
            'subtotal' => '100.0000',
            'grand_total' => '100.0000',
            'amount_paid' => '0.0000',
            'amount_due' => '100.0000',
            'status' => InvoiceStatus::ISSUED->value,
        ]);

        $evaluation = $this->evaluationService->evaluateSuspension($this->subscriptionA, $today);

        $this->assertFalse($evaluation->eligible);
        $this->assertEmpty($evaluation->triggeringInvoiceIds);
    }

    /**
     * 3. Test: suspended fully paid => reactivation eligible
     */
    public function test_suspended_fully_paid_is_reactivation_eligible(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $this->subscriptionA->status = SubscriptionStatus::SUSPENDED;
        $this->subscriptionA->save();

        $dueDate = Carbon::parse('2026-05-10');
        $today = Carbon::parse('2026-05-15');

        Invoice::create([
            'tenant_id' => $this->tenantA->id,
            'customer_id' => $this->customerA->id,
            'customer_service_subscription_id' => $this->subscriptionA->id,
            'invoice_number' => 'INV-TEST-003',
            'invoice_type' => InvoiceType::SUBSCRIPTION->value,
            'issue_date' => '2026-05-01',
            'due_date' => $dueDate->toDateString(),
            'currency' => 'BDT',
            'subtotal' => '100.0000',
            'grand_total' => '100.0000',
            'amount_paid' => '100.0000',
            'amount_due' => '0.0000',
            'status' => InvoiceStatus::PAID->value,
        ]);

        $evaluation = $this->evaluationService->evaluateReactivation($this->subscriptionA, $today);

        $this->assertTrue($evaluation->eligible);
    }

    /**
     * 4. Test: suspended partial dues => blocked
     */
    public function test_suspended_partial_dues_blocks_reactivation(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $this->subscriptionA->status = SubscriptionStatus::SUSPENDED;
        $this->subscriptionA->save();

        $dueDate = Carbon::parse('2026-05-10');
        $today = Carbon::parse('2026-05-15');

        Invoice::create([
            'tenant_id' => $this->tenantA->id,
            'customer_id' => $this->customerA->id,
            'customer_service_subscription_id' => $this->subscriptionA->id,
            'invoice_number' => 'INV-TEST-004',
            'invoice_type' => InvoiceType::SUBSCRIPTION->value,
            'issue_date' => '2026-05-01',
            'due_date' => $dueDate->toDateString(),
            'currency' => 'BDT',
            'subtotal' => '100.0000',
            'grand_total' => '100.0000',
            'amount_paid' => '40.0000',
            'amount_due' => '60.0000',
            'status' => InvoiceStatus::PARTIALLY_PAID->value,
        ]);

        $evaluation = $this->evaluationService->evaluateReactivation($this->subscriptionA, $today);

        $this->assertFalse($evaluation->eligible);
        $this->assertNotEmpty($evaluation->triggeringInvoiceIds);
    }

    /**
     * 5. Test: suspend action writes event
     */
    public function test_suspend_action_transitions_status_and_writes_immutable_event(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $dueDate = Carbon::parse('2026-05-10');
        $today = Carbon::parse('2026-05-15');

        Invoice::create([
            'tenant_id' => $this->tenantA->id,
            'customer_id' => $this->customerA->id,
            'customer_service_subscription_id' => $this->subscriptionA->id,
            'invoice_number' => 'INV-TEST-005',
            'invoice_type' => InvoiceType::SUBSCRIPTION->value,
            'issue_date' => '2026-05-01',
            'due_date' => $dueDate->toDateString(),
            'currency' => 'BDT',
            'subtotal' => '100.0000',
            'grand_total' => '100.0000',
            'amount_paid' => '0.0000',
            'amount_due' => '100.0000',
            'status' => InvoiceStatus::ISSUED->value,
        ]);

        $sub = $this->actionService->suspend($this->tenantA->id, (int)$this->subscriptionA->id, [
            'today' => $today,
            'reason' => 'Non-payment suspension',
        ]);

        $this->assertEquals(SubscriptionStatus::SUSPENDED, $sub->status);

        $event = SuspensionEvent::latest('id')->first();
        $this->assertNotNull($event);
        $this->assertEquals('suspended', $event->action_type);
        $this->assertEquals('Non-payment suspension', $event->reason);
        $this->assertEquals($sub->id, $event->customer_service_subscription_id);

        $this->expectException(\RuntimeException::class);
        $event->update(['reason' => 'Hacked reason']);
    }

    /**
     * 6. Test: reactivate action writes event
     */
    public function test_reactivate_action_transitions_status_and_writes_immutable_event(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $this->subscriptionA->status = SubscriptionStatus::SUSPENDED;
        $this->subscriptionA->save();

        $today = Carbon::parse('2026-05-15');

        $sub = $this->actionService->reactivate($this->tenantA->id, (int)$this->subscriptionA->id, [
            'today' => $today,
            'reason' => 'Reconnection successful',
            'reconnection_fee_enabled' => false,
        ]);

        $this->assertEquals(SubscriptionStatus::ACTIVE, $sub->status);

        $event = SuspensionEvent::latest('id')->first();
        $this->assertNotNull($event);
        $this->assertEquals('reactivated', $event->action_type);
        $this->assertEquals('Reconnection successful', $event->reason);
    }

    /**
     * 7. Test: duplicate suspension blocked
     */
    public function test_duplicate_suspension_blocked(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $dueDate = Carbon::parse('2026-05-10');
        $today = Carbon::parse('2026-05-15');

        Invoice::create([
            'tenant_id' => $this->tenantA->id,
            'customer_id' => $this->customerA->id,
            'customer_service_subscription_id' => $this->subscriptionA->id,
            'invoice_number' => 'INV-TEST-006',
            'invoice_type' => InvoiceType::SUBSCRIPTION->value,
            'issue_date' => '2026-05-01',
            'due_date' => $dueDate->toDateString(),
            'currency' => 'BDT',
            'subtotal' => '100.0000',
            'grand_total' => '100.0000',
            'amount_paid' => '0.0000',
            'amount_due' => '100.0000',
            'status' => InvoiceStatus::ISSUED->value,
        ]);

        $this->actionService->suspend($this->tenantA->id, (int)$this->subscriptionA->id, [
            'today' => $today,
        ]);

        $this->expectException(ValidationException::class);
        $this->actionService->suspend($this->tenantA->id, (int)$this->subscriptionA->id, [
            'today' => $today,
        ]);
    }

    /**
     * 8. Test: reconnection fee invoice generated only once if enabled
     */
    public function test_reconnection_fee_invoice_generated_only_once_if_enabled(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        SuspensionEvent::create([
            'tenant_id' => $this->tenantA->id,
            'customer_service_subscription_id' => $this->subscriptionA->id,
            'action_type' => 'suspended',
            'reason' => 'Suspended for test',
            'created_at' => now()->subHour(),
        ]);

        $this->subscriptionA->status = SubscriptionStatus::SUSPENDED;
        $this->subscriptionA->save();

        $today = Carbon::parse('2026-05-15');

        $sub = $this->actionService->reactivate($this->tenantA->id, (int)$this->subscriptionA->id, [
            'today' => $today,
            'reconnection_fee_enabled' => true,
            'reconnection_fee_amount' => '150.0000',
            'issue_date' => '2026-05-15',
            'due_date' => '2026-05-15',
        ]);

        $reactivateEvent = SuspensionEvent::where('action_type', 'reactivated')->first();
        $this->assertNotNull($reactivateEvent);
        $this->assertNotNull($reactivateEvent->reconnection_invoice_id);

        $invoice = Invoice::withoutGlobalScopes()->find($reactivateEvent->reconnection_invoice_id);
        $this->assertNotNull($invoice);
        $this->assertEquals('150.0000', $invoice->grand_total);
        $this->assertEquals('150.0000', $invoice->amount_due);
        $this->assertEquals(InvoiceStatus::ISSUED, $invoice->status);

        $this->subscriptionA->status = SubscriptionStatus::SUSPENDED;
        $this->subscriptionA->save();

        $this->expectException(ValidationException::class);
        $this->actionService->reactivate($this->tenantA->id, (int)$this->subscriptionA->id, [
            'today' => $today,
            'reconnection_fee_enabled' => true,
            'reconnection_fee_amount' => '150.0000',
        ]);
    }

    /**
     * 9. Test: tenant isolation enforced
     */
    public function test_tenant_isolation_is_enforced(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        try {
            $this->actionService->suspend($this->tenantA->id, (int)$this->subscriptionB->id, [
                'today' => Carbon::now(),
            ]);
            $this->fail('Suspended other tenant subscription.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('subscription', $e->errors());
        }
    }
}
