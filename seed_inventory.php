<?php

use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Tenant\Models\Tenant;
use Faker\Factory as Faker;

$faker = Faker::create();
$tenant = Tenant::first();

$categories = [
    'onu' => ['Huawei EPON ONU', 'ZTE GPON ONU', 'Netis EPON ONU', 'V-SOL GPON ONU'],
    'router' => ['TP-Link Archer C6', 'Mercusys AC12G', 'MikroTik hap Lite', 'Netgear Nighthawk'],
    'switch' => ['Cisco 24-Port Gigabit', 'TP-Link 8-Port Switch', 'MikroTik CRS326', 'Netgear ProSafe'],
    'cable' => ['Cat6 UTP Cable Roll', 'Fiber Drop Cable 1 Core (1KM)', 'Fiber 4 Core (2KM)'],
    'accessories' => ['RJ45 Connectors (Box)', 'Fiber Patch Cord', 'Media Converter', 'ONU Power Adapter']
];

foreach ($categories as $categoryKey => $items) {
    foreach ($items as $index => $itemName) {
        $sku = strtoupper(substr($categoryKey, 0, 3)) . '-' . date('Y') . '-' . str_pad((string)($index + 1), 3, '0', STR_PAD_LEFT);
        
        // Cable and Accessories usually don't track serial
        $trackSerial = !in_array($categoryKey, ['cable', 'accessories']);
        
        InventoryItem::create([
            'tenant_id' => $tenant ? $tenant->id : null,
            'name' => $itemName,
            'sku' => $sku . '-' . $faker->randomNumber(3, true),
            'category' => $categoryKey,
            'track_serial' => $trackSerial,
            'low_stock_threshold' => $faker->numberBetween(5, 20),
            'current_stock' => $faker->numberBetween(10, 150),
        ]);
    }
}

echo "Demo inventory items generated successfully.\n";
