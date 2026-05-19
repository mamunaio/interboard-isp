<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Tenant\TenantManager;
use App\Models\User;
use App\Modules\Tenant\Models\Plan;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Auth\Models\StaffUser;
use App\Modules\Auth\Models\TrustedDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected Plan $plan;
    protected Tenant $tenant;
    protected StaffUser $staffUser;
    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plan = Plan::create([
            'name' => 'Starter',
            'slug' => 'starter',
            'price_monthly' => 50.00,
            'price_yearly' => 500.00,
        ]);

        $this->tenant = Tenant::create([
            'uuid' => '00000000-0000-0000-0000-000000000001',
            'slug' => 'alpha',
            'name' => 'Alpha ISP',
            'status' => 'active',
            'plan_id' => $this->plan->id,
        ]);

        $this->tenantManager = app(TenantManager::class);
        $this->tenantManager->setTenant($this->tenant);

        $this->staffUser = StaffUser::create([
            'name' => 'Tenant Admin',
            'email' => 'admin@alpha.com',
            'password' => Hash::make('secret123'),
            'status' => 'active',
        ]);

        $this->tenantManager->setBypassed(true);
        $this->superAdmin = User::create([
            'name' => 'Global Admin',
            'email' => 'super@interboard.net',
            'password' => Hash::make('supersecret'),
        ]);
        $this->tenantManager->setBypassed(false);
    }

    /**
     * Test successful staff login on resolved tenant context.
     */
    public function test_staff_login_succeeds_with_valid_credentials(): void
    {
        $response = $this->postJson('http://alpha.interboard.net/api/auth/login', [
            'email' => 'admin@alpha.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'token'
                ]
            ]);

        $this->assertDatabaseHas('trusted_devices', [
            'user_id' => $this->staffUser->id,
            'user_type' => StaffUser::class,
        ]);
    }

    /**
     * Test staff login rate limiting/throttling.
     */
    public function test_staff_login_throttling_triggers_after_repeated_failures(): void
    {
        $this->tenantManager->setTenant($this->tenant);

        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('http://alpha.interboard.net/api/auth/login', [
                'email' => 'admin@alpha.com',
                'password' => 'wrongpassword',
            ]);
        }

        $response->assertStatus(429);
    }

    /**
     * Test superadmin login strictly on the central domain.
     */
    public function test_superadmin_login_restricted_to_central_domain(): void
    {
        // 1. Attempt on tenant subdomain should be blocked
        $response1 = $this->postJson('http://alpha.interboard.net/api/auth/superadmin/login', [
            'email' => 'super@interboard.net',
            'password' => 'supersecret',
        ]);

        $response1->assertStatus(403);

        // 2. Attempt on central domain should pass
        $response2 = $this->postJson('http://interboard.net/api/auth/superadmin/login', [
            'email' => 'super@interboard.net',
            'password' => 'supersecret',
        ]);

        $response2->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['token']
            ]);
    }
}
