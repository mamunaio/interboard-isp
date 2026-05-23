<?php

use App\Modules\Customer\Models\ServicePlan;
use App\Modules\Tenant\Models\Tenant;

$tenant = Tenant::first();

$plans = [
    [
        'plan_code' => 'PLN-BASIC-10M',
        'name' => 'Fiber Basic 10Mbps',
        'monthly_price' => 500.00,
        'billing_cycle' => 'monthly',
        'download_speed' => '10 Mbps',
        'upload_speed' => '10 Mbps',
        'is_active' => true,
    ],
    [
        'plan_code' => 'PLN-STD-20M',
        'name' => 'Fiber Standard 20Mbps',
        'monthly_price' => 800.00,
        'billing_cycle' => 'monthly',
        'download_speed' => '20 Mbps',
        'upload_speed' => '20 Mbps',
        'is_active' => true,
    ],
    [
        'plan_code' => 'PLN-PRO-30M',
        'name' => 'Fiber Pro 30Mbps',
        'monthly_price' => 1000.00,
        'billing_cycle' => 'monthly',
        'download_speed' => '30 Mbps',
        'upload_speed' => '30 Mbps',
        'is_active' => true,
    ],
    [
        'plan_code' => 'PLN-ULT-50M',
        'name' => 'Fiber Ultra 50Mbps',
        'monthly_price' => 1500.00,
        'billing_cycle' => 'monthly',
        'download_speed' => '50 Mbps',
        'upload_speed' => '50 Mbps',
        'is_active' => true,
    ],
    [
        'plan_code' => 'PLN-GMR-100M',
        'name' => 'Fiber Gamer 100Mbps',
        'monthly_price' => 2500.00,
        'billing_cycle' => 'monthly',
        'download_speed' => '100 Mbps',
        'upload_speed' => '100 Mbps',
        'is_active' => true,
    ],
    [
        'plan_code' => 'PLN-CORP-50M',
        'name' => 'Corporate Dedicated 50Mbps',
        'monthly_price' => 5000.00,
        'billing_cycle' => 'monthly',
        'download_speed' => '50 Mbps',
        'upload_speed' => '50 Mbps',
        'is_active' => true,
    ],
    [
        'plan_code' => 'PLN-CORP-100M',
        'name' => 'Corporate Dedicated 100Mbps',
        'monthly_price' => 9000.00,
        'billing_cycle' => 'monthly',
        'download_speed' => '100 Mbps',
        'upload_speed' => '100 Mbps',
        'is_active' => true,
    ],
];

foreach ($plans as $planData) {
    ServicePlan::firstOrCreate(
        [
            'tenant_id' => $tenant ? $tenant->id : null,
            'plan_code' => $planData['plan_code']
        ],
        $planData
    );
}

echo "Demo service plans generated successfully.\n";
