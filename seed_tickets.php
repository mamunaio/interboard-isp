<?php

use App\Modules\Support\Models\Ticket;
use App\Modules\Customer\Models\Customer;
use App\Models\User;
use Faker\Factory as Faker;
use App\Modules\Tenant\Models\Tenant;

$faker = Faker::create();
$tenant = Tenant::first();

$categories = ['technical', 'billing', 'sales', 'general'];
$statuses = ['open', 'in_progress', 'waiting', 'closed'];
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
    
    Ticket::create([
        'tenant_id' => $tenant ? $tenant->id : null,
        'customer_id' => $faker->randomElement($customerIds),
        'assigned_to_id' => $faker->randomElement($userIds),
        'work_order_id' => null,
        'title' => $faker->sentence(6),
        'category' => $faker->randomElement($categories),
        'status' => $status,
        'priority' => $faker->randomElement($priorities),
        'created_at' => $faker->dateTimeBetween('-1 month', 'now'),
        'updated_at' => $faker->dateTimeBetween('-1 week', 'now'),
    ]);
}

echo "20 Demo tickets generated successfully.\n";
