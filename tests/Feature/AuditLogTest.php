<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Tenant\TenantManager;
use App\Core\Audit\Jobs\ProcessAuditLogJob;
use App\Modules\Tenant\Models\Plan;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Tenant\Models\AuditLog;
use App\Modules\Auth\Models\StaffUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Hash;

use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected Plan $plan;
    protected Tenant $tenant;
    protected TenantManager $tenantManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plan = Plan::create([
            'name' => 'Scale',
            'slug' => 'scale',
            'price_monthly' => 150.00,
            'price_yearly' => 1500.00,
        ]);

        $this->tenant = Tenant::create([
            'uuid' => '00000000-0000-0000-0000-000000000001',
            'slug' => 'omega',
            'name' => 'Omega ISP',
            'status' => 'active',
            'plan_id' => $this->plan->id,
        ]);

        $this->tenantManager = app(TenantManager::class);
        $this->tenantManager->setTenant($this->tenant);
    }

    /**
     * Test that creating a model dispatches the ProcessAuditLogJob.
     */
    public function test_creating_a_model_dispatches_audit_log_job(): void
    {
        Queue::fake();

        // StaffUser uses the LogsActivity trait
        $staff = StaffUser::create([
            'name' => 'Audit Target User',
            'email' => 'target@omega.com',
            'password' => Hash::make('secret123'),
            'status' => 'active',
        ]);

        Queue::assertPushed(ProcessAuditLogJob::class, function ($job) use ($staff) {
            $payload = $job->payload;

            $this->assertEquals('created', $payload['event']);
            $this->assertEquals(StaffUser::class, $payload['auditable_type']);
            $this->assertEquals($staff->id, $payload['auditable_id']);
            $this->assertEquals($this->tenant->id, $payload['tenant_id']);
            
            // Password must be redacted/omitted
            $this->assertArrayNotHasKey('password', $payload['new_values']);
            $this->assertEquals('Audit Target User', $payload['new_values']['name']);

            return true;
        });
    }

    /**
     * Test that executing the job writes the record into the database.
     */
    public function test_audit_log_job_execution_persists_log_to_db(): void
    {
        $payload = [
            'tenant_id' => $this->tenant->id,
            'user_type' => null,
            'user_id' => null,
            'event' => 'created',
            'auditable_type' => StaffUser::class,
            'auditable_id' => 99,
            'old_values' => null,
            'new_values' => ['name' => 'Queue User'],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ];

        // Directly invoke the job handler
        $job = new ProcessAuditLogJob($payload);
        $job->handle();

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $this->tenant->id,
            'event' => 'created',
            'auditable_type' => StaffUser::class,
            'auditable_id' => 99,
            'ip_address' => '127.0.0.1',
        ]);
    }
}
