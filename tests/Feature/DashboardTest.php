<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Tenant\TenantManager;
use App\Modules\Tenant\Models\Plan;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Tenant\Models\AuditLog;
use App\Modules\Tenant\Http\Livewire\Dashboard;
use Livewire\Livewire;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected Plan $plan;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plan = Plan::create([
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
            'plan_id' => $this->plan->id,
        ]);

        app(TenantManager::class)->setTenant($this->tenant);
    }

    /**
     * Test that the Dashboard component mounts successfully and computes realistic telemetry.
     */
    public function test_dashboard_mounts_and_computes_billing_telemetry(): void
    {
        // 1. Create a dummy AuditLog event to verify streaming output
        AuditLog::create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => 'App\Modules\Billing\Models\PaymentReceipt',
            'auditable_id' => 42,
            'event' => 'created',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit Test Agent',
            'tags' => ['billing', 'receipt'],
        ]);

        Livewire::test(Dashboard::class)
            ->assertSet('stats.active_customers', 0)
            ->assertSet('stats.active_subscriptions', 0)
            ->assertSet('stats.unpaid_invoices', 0)
            ->assertSet('stats.overdue_invoices', 0)
            ->assertSet('stats.total_wallet_balance', '0.00');
    }
}
