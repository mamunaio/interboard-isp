<?php

use App\Modules\Network\Models\OltDevice;
use App\Modules\Tenant\Models\Tenant;

$tenant = Tenant::first();

$olts = [
    [
        'name' => 'Huawei OLT - Mirpur',
        'ip_address' => '192.168.100.10',
        'port' => 23,
        'username' => 'root',
        'password' => 'admin123',
        'vendor' => 'Huawei',
        'status' => 'active',
    ],
    [
        'name' => 'ZTE OLT - Dhanmondi',
        'ip_address' => '192.168.101.10',
        'port' => 23,
        'username' => 'zte',
        'password' => 'zte',
        'vendor' => 'ZTE',
        'status' => 'active',
    ],
    [
        'name' => 'V-SOL OLT - Uttara',
        'ip_address' => '192.168.102.10',
        'port' => 22,
        'username' => 'admin',
        'password' => 'admin',
        'vendor' => 'V-SOL',
        'status' => 'inactive',
    ],
    [
        'name' => 'BDCOM OLT - Gulshan',
        'ip_address' => '192.168.103.10',
        'port' => 23,
        'username' => 'admin',
        'password' => 'admin',
        'vendor' => 'BDCOM',
        'status' => 'active',
    ],
];

foreach ($olts as $oltData) {
    OltDevice::create(array_merge($oltData, [
        'tenant_id' => $tenant ? $tenant->id : null,
    ]));
}

echo "Demo OLT Devices generated successfully.\n";
