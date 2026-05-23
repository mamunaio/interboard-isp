<?php

use App\Modules\Billing\Models\Wallet;
use App\Modules\Customer\Models\Customer;
use App\Modules\Tenant\Models\Tenant;
use Faker\Factory as Faker;

$faker = Faker::create();
$tenant = Tenant::first();

$customers = Customer::all();

if ($customers->isEmpty()) {
    echo "No customers found. Run customer seeder first.\n";
    exit;
}

foreach ($customers as $customer) {
    // Check if wallet already exists
    $wallet = Wallet::where('owner_type', Customer::class)
        ->where('owner_id', $customer->id)
        ->first();
        
    if (!$wallet) {
        $balance = $faker->boolean(70) ? $faker->numberBetween(-500, 2000) : 0;
        
        Wallet::create([
            'tenant_id' => $tenant ? $tenant->id : null,
            'owner_type' => Customer::class,
            'owner_id' => $customer->id,
            'currency' => 'BDT',
            'current_balance' => $balance,
            'active' => true,
        ]);
    }
}

echo "Demo wallets generated for all customers successfully.\n";
