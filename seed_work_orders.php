<?php

use App\Modules\Support\Models\WorkOrder;
use App\Modules\Customer\Models\Customer;
use App\Models\User;
use Faker\Factory as Faker;
use App\Modules\Tenant\Models\Tenant;

$faker = Faker::create();
$tenant = Tenant::first();

$types = ['installation', 'maintenance', 'relocation', 'disconnection'];
$statuses = ['pending', 'scheduled', 'in_progress', 'on_hold', 'completed', 'cancelled'];
$priorities = ['low', 'medium', 'high', 'urgent'];

$customerIds = Customer::pluck('id')->toArray();
$userIds = User::pluck('id')->toArray();

if (empty($customerIds)) {
    echo "No customers found. Run customer seeder first.\n";
    exit;
}

if (empty($userIds)) {
    $userIds = [null]; // fallback if no users
}

for($i = 0; $i < 20; $i++) {
    $status = $faker->randomElement($statuses);
    
    WorkOrder::create([
        'tenant_id' => $tenant ? $tenant->id : null,
        'customer_id' => $faker->randomElement($customerIds),
        'assigned_to' => $faker->randomElement($userIds),
        'type' => $faker->randomElement($types),
        'status' => $status,
        'priority' => $faker->randomElement($priorities),
        'description' => $faker->sentence(10),
        'resolution_notes' => in_array($status, ['completed', 'cancelled']) ? $faker->sentence() : null,
        'scheduled_date' => $faker->dateTimeBetween('now', '+2 weeks')->format('Y-m-d H:i:s'),
        'completed_at' => $status === 'completed' ? $faker->dateTimeBetween('-1 week', 'now')->format('Y-m-d H:i:s') : null,
    ]);
}

echo "20 Demo work orders generated successfully.\n";
