<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Tenant\TenantManager;
use App\Modules\Tenant\Models\Plan;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Models\ServicePlan;
use App\Modules\Customer\Models\CustomerServiceSubscription;
use App\Modules\Customer\Enums\SubscriptionStatus;
use App\Modules\Customer\Enums\ServiceType;
use App\Modules\Billing\Enums\BillingMode;
use App\Modules\Billing\Enums\BillingCycleType;
use App\Modules\Billing\Services\SuspensionActionService;
use App\Modules\Provisioning\Models\ServiceProvisioningEvent;
use App\Modules\Provisioning\Enums\ProvisioningActionType;
use App\Modules\Provisioning\Enums\ProvisioningStatus;
use App\Modules\Provisioning\Jobs\ProcessProvisioningJob;
use App\Modules\Provisioning\Http\Livewire\ProvisioningIndex;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProvisioningIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Customer $customer;
    protected ServicePlan $servicePlan;
    protected CustomerServiceSubscription $subscription;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::create([
            'name' => 'Ultra Plan',
            'slug' => 'ultra',
            'price_monthly' => 200.00,
            'price_yearly' => 2000.00,
        ]);

        $this->tenant = Tenant::create([
            'uuid' => '00000000-0000-0000-0000-000000000001',
            'slug' => 'test-tenant',
            'name' => 'Test Tenant',
            'status' => 'active',
            'plan_id' => $plan->id,
        ]);

        app(TenantManager::class)->setTenant($this->tenant);

        $this->customer = Customer::create([
            'tenant_id' => $this->tenant->id,
            'customer_code' => 'CUST-001',
            'full_name' => 'John Provision Doe',
            'email' => 'john.prov@example.com',
            'phone' => '01700000001',
            'status' => \App\Modules\Customer\Enums\CustomerStatus::ACTIVE,
            'type' => \App\Modules\Customer\Enums\CustomerType::RESIDENTIAL,
        ]);

        $this->servicePlan = ServicePlan::create([
            'tenant_id' => $this->tenant->id,
            'plan_code' => 'SP-10M',
            'name' => '10 Mbps Standard',
            'monthly_price' => '500.0000',
            'billing_cycle' => 'monthly',
            'download_speed' => '10 Mbps',
            'upload_speed' => '5 Mbps',
            'is_active' => true,
        ]);

        $this->subscription = CustomerServiceSubscription::create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'service_plan_id' => $this->servicePlan->id,
            'subscription_code' => 'SUB-001',
            'service_type_snapshot' => ServiceType::BROADBAND,
            'status' => SubscriptionStatus::ACTIVE,
            'billing_mode' => BillingMode::PREPAID,
            'billing_cycle_type' => BillingCycleType::ANNIVERSARY,
            'next_billing_date' => now()->addMonth()->toDateString(),
            'activated_at' => now(),
        ]);
    }

    public function test_suspension_service_dispatches_provisioning_job(): void
    {
        Queue::fake();

        // Mock the evaluation service to always return eligible
        $mockEvaluation = \Mockery::mock(\App\Modules\Billing\Services\SuspensionEvaluationService::class);
        $mockEvaluation->shouldReceive('evaluateSuspension')
            ->andReturn(new \App\Modules\Billing\Services\SuspensionEvaluationResult(true, [], []));
        
        $this->app->instance(\App\Modules\Billing\Services\SuspensionEvaluationService::class, $mockEvaluation);

        $suspensionService = app(SuspensionActionService::class);
        $suspensionService->suspend($this->tenant->id, $this->subscription->id, [
            'reason' => 'Test Overdue',
        ]);

        $this->assertDatabaseHas('service_provisioning_events', [
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $this->subscription->id,
            'action_type' => ProvisioningActionType::SUSPEND->value,
            'status' => ProvisioningStatus::PENDING->value,
        ]);

        Queue::assertPushed(ProcessProvisioningJob::class);
    }

    public function test_provisioning_index_livewire_renders_events(): void
    {
        Queue::fake();

        $event = ServiceProvisioningEvent::create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'subscription_id' => $this->subscription->id,
            'action_type' => ProvisioningActionType::PROVISION,
            'status' => ProvisioningStatus::FAILED,
            'provider' => 'mikrotik',
            'request_payload_json' => ['username' => 'SUB-001'],
            'error_message' => 'Router unreachable',
        ]);

        Livewire::test(ProvisioningIndex::class)
            ->assertSee('John Provision Doe')
            ->assertSee('SUB-001')
            ->assertSee('failed')
            ->assertSee('provision')
            ->call('viewDetails', $event->id)
            ->assertSet('isModalOpen', true)
            ->assertSee('Router unreachable')
            ->call('retryAction', $event->id);

        Queue::assertPushed(ProcessProvisioningJob::class);

        $this->assertEquals(ProvisioningStatus::PENDING, $event->fresh()->status);
    }
}
