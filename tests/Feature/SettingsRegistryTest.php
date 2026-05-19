<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Tenant\TenantManager;
use App\Core\Settings\SettingsRegistry;
use App\Modules\Tenant\Models\Plan;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Tenant\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SettingsRegistryTest extends TestCase
{
    use RefreshDatabase;

    protected Plan $plan;
    protected Tenant $tenant;
    protected SettingsRegistry $settings;
    protected TenantManager $tenantManager;

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
            'slug' => 'gamma',
            'name' => 'Gamma ISP',
            'status' => 'active',
            'plan_id' => $this->plan->id,
        ]);

        $this->tenantManager = app(TenantManager::class);
        $this->settings = app(SettingsRegistry::class);

        // Ensure clear cache before each test run
        Cache::clear();
    }

    /**
     * Test setting and getting basic types and dynamic casting.
     */
    public function test_settings_registry_performs_correct_type_casting(): void
    {
        $this->tenantManager->setTenant($this->tenant);

        // String setting
        $this->settings->set('company_name', 'My Tech Corp');
        $this->assertEquals('My Tech Corp', $this->settings->get('company_name'));

        // Boolean setting
        $this->settings->set('enable_autologin', true, 'boolean');
        $this->assertTrue($this->settings->get('enable_autologin'));

        // Integer setting
        $this->settings->set('session_timeout', 1800, 'integer');
        $this->assertEquals(1800, $this->settings->get('session_timeout'));

        // Array/JSON setting
        $config = ['host' => '127.0.0.1', 'port' => 6379];
        $this->settings->set('redis_config', $config, 'array');
        $this->assertEquals($config, $this->settings->get('redis_config'));
    }

    /**
     * Test tenant setting override over global fallback.
     */
    public function test_tenant_settings_correctly_override_global_defaults(): void
    {
        // 1. Set global platform setting
        $this->settings->set('primary_dns', '8.8.8.8', 'string', false, true);

        // 2. Query globally without tenant
        $this->tenantManager->setTenant(null);
        $this->assertEquals('8.8.8.8', $this->settings->get('primary_dns'));

        // 3. Query under tenant, should fall back to global
        $this->tenantManager->setTenant($this->tenant);
        $this->assertEquals('8.8.8.8', $this->settings->get('primary_dns'));

        // 4. Override primary_dns specifically for tenant
        $this->settings->set('primary_dns', '1.1.1.1', 'string', false, false);

        // 5. Verify tenant gets override, while global stays intact
        $this->assertEquals('1.1.1.1', $this->settings->get('primary_dns'));

        $this->tenantManager->setTenant(null);
        $this->assertEquals('8.8.8.8', $this->settings->get('primary_dns'));
    }

    /**
     * Test secure secrets encryption and decryption.
     */
    public function test_encrypted_settings_are_stored_securely(): void
    {
        $this->tenantManager->setTenant($this->tenant);

        // Save a secure secret key
        $this->settings->set('mikrotik_secret', 'my-super-secret-key-123', 'string', true);

        // Check that the raw database value is indeed encrypted
        $this->tenantManager->setBypassed(true);
        $rawRecord = Setting::where('tenant_id', $this->tenant->id)->where('key', 'mikrotik_secret')->first();
        $this->assertNotEquals('my-super-secret-key-123', $rawRecord->value);
        $this->assertEquals('my-super-secret-key-123', Crypt::decryptString($rawRecord->value));
        $this->tenantManager->setBypassed(false);

        // Check that the settings registry decrypts it automatically
        $this->assertEquals('my-super-secret-key-123', $this->settings->get('mikrotik_secret'));
    }
}
