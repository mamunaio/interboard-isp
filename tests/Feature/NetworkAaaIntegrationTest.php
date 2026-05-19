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
use App\Modules\Network\Models\RadAcct;
use App\Modules\Network\Http\Livewire\OnlineUsersIndex;
use App\Modules\Network\Http\Livewire\NetworkSessionsIndex;
use Livewire\Livewire;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NetworkAaaIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Customer $customer;
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
            'uuid' => '00000000-0000-0000-0000-000000000002',
            'slug' => 'radius-tenant',
            'name' => 'Radius Tenant',
            'status' => 'active',
            'plan_id' => $plan->id,
        ]);

        app(TenantManager::class)->setTenant($this->tenant);

        $this->customer = Customer::create([
            'tenant_id' => $this->tenant->id,
            'customer_code' => 'CUST-RAD-001',
            'full_name' => 'Alice Radius',
            'email' => 'alice@radius.test',
            'phone' => '01700000002',
            'status' => \App\Modules\Customer\Enums\CustomerStatus::ACTIVE,
            'type' => \App\Modules\Customer\Enums\CustomerType::RESIDENTIAL,
        ]);

        $servicePlan = ServicePlan::create([
            'tenant_id' => $this->tenant->id,
            'plan_code' => 'SP-20M',
            'name' => '20 Mbps Gamer',
            'monthly_price' => '1000.0000',
            'billing_cycle' => 'monthly',
            'download_speed' => '20 Mbps',
            'upload_speed' => '10 Mbps',
            'is_active' => true,
        ]);

        $this->subscription = CustomerServiceSubscription::create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'service_plan_id' => $servicePlan->id,
            'subscription_code' => 'SUB-RAD-001',
            'service_type_snapshot' => ServiceType::BROADBAND,
            'status' => SubscriptionStatus::ACTIVE,
            'billing_mode' => BillingMode::PREPAID,
            'billing_cycle_type' => BillingCycleType::ANNIVERSARY,
            'next_billing_date' => now()->addMonth()->toDateString(),
            'activated_at' => now(),
        ]);
    }

    public function test_online_users_index_renders_active_sessions_only(): void
    {
        // Create one active session
        RadAcct::create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $this->subscription->id,
            'acctsessionid' => 'sess-12345',
            'acctuniqueid' => 'uniq-12345',
            'username' => 'SUB-RAD-001',
            'nasipaddress' => '192.168.1.1',
            'framedipaddress' => '10.0.0.50',
            'acctstarttime' => now()->subHours(2),
            'acctstoptime' => null, // Active
        ]);

        // Create one offline session
        RadAcct::create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $this->subscription->id,
            'acctsessionid' => 'sess-98765',
            'acctuniqueid' => 'uniq-98765',
            'username' => 'SUB-RAD-001',
            'nasipaddress' => '192.168.1.1',
            'framedipaddress' => '10.0.0.51',
            'acctstarttime' => now()->subDays(2),
            'acctstoptime' => now()->subDays(1), // Offline
            'acctsessiontime' => 86400,
        ]);

        Livewire::test(OnlineUsersIndex::class)
            ->assertSee('Alice Radius')
            ->assertSee('10.0.0.50')
            ->assertDontSee('10.0.0.51'); // Offline session should not appear here
    }

    public function test_network_sessions_index_filters_correctly(): void
    {
        RadAcct::create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $this->subscription->id,
            'acctsessionid' => 'sess-online',
            'acctuniqueid' => 'uniq-online',
            'username' => 'SUB-RAD-001',
            'nasipaddress' => '192.168.1.1',
            'framedipaddress' => '10.0.0.99',
            'acctstarttime' => now()->subHours(1),
            'acctstoptime' => null, 
        ]);

        RadAcct::create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $this->subscription->id,
            'acctsessionid' => 'sess-offline',
            'acctuniqueid' => 'uniq-offline',
            'username' => 'SUB-RAD-001',
            'nasipaddress' => '192.168.1.1',
            'framedipaddress' => '10.0.0.88',
            'acctstarttime' => now()->subDays(1),
            'acctstoptime' => now()->subHours(12),
        ]);

        Livewire::test(NetworkSessionsIndex::class)
            ->set('statusFilter', 'online')
            ->assertSee('10.0.0.99')
            ->assertDontSee('10.0.0.88')
            ->set('statusFilter', 'offline')
            ->assertSee('10.0.0.88')
            ->assertDontSee('10.0.0.99');
    }
}
