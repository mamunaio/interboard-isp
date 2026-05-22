<?php

declare(strict_types=1);

namespace App\Modules\Network\Services;

use App\Modules\Network\Models\OltDevice;
use App\Modules\Network\Models\OnuDevice;
use Exception;

class OltProvisioningService
{
    /**
     * Get simulated unconfigured/autofind ONUs for a given OLT device.
     */
    public function discoverOnus(OltDevice $olt): array
    {
        // Return a list of realistic unconfigured ONUs found on PON ports
        return [
            [
                'serial_number' => 'ZTEG' . strtoupper(bin2hex(random_bytes(4))),
                'gpon_port' => '0/1/1',
                'vendor' => 'ZTE',
                'model' => 'F660',
                'distance_meters' => rand(150, 950),
            ],
            [
                'serial_number' => 'HWTC' . strtoupper(bin2hex(random_bytes(4))),
                'gpon_port' => '0/1/2',
                'vendor' => 'Huawei',
                'model' => 'HG8546M',
                'distance_meters' => rand(300, 1800),
            ],
        ];
    }

    /**
     * Simulate provisioning an ONU on the OLT.
     */
    public function provisionOnu(OnuDevice $onu): array
    {
        $olt = $onu->oltDevice;
        if (!$olt) {
            throw new Exception("ONU is not linked to any OLT device.");
        }

        // Generate simulated CLI session commands
        $commands = [];
        $onuId = rand(1, 128);
        $onu->update(['onu_id' => $onuId, 'status' => 'active']);

        if (strtolower($olt->vendor) === 'zte') {
            $commands = [
                "telnet {$olt->ip_address} {$olt->port}",
                "Username: {$olt->username}",
                "Password: *****",
                "ZXAN# configure terminal",
                "ZXAN(config)# interface gpon-olt_{$onu->gpon_port}",
                "ZXAN(config-if)# ont {$onuId} name SUB-{$onu->serial_number} type ZTE-F660 sn-cfg {$onu->serial_number}",
                "ZXAN(config-if)# ont {$onuId} line-profile {$onu->line_profile} service-profile {$onu->service_profile}",
                "ZXAN(config-if)# exit",
                "ZXAN(config)# interface gpon-onu_{$onu->gpon_port}:{$onuId}",
                "ZXAN(config-if)# name SUB-{$onu->serial_number}",
                "ZXAN(config-if)# tcont 1 profile 100M",
                "ZXAN(config-if)# gemport 1 tcont 1",
                "ZXAN(config-if)# exit",
                "ZXAN(config)# write memory"
            ];
        } else {
            // Huawei OLT syntax
            $commands = [
                "telnet {$olt->ip_address} {$olt->port}",
                "Username: {$olt->username}",
                "Password: *****",
                "OLT> enable",
                "OLT# config",
                "OLT(config)# interface gpon {$onu->gpon_port}",
                "OLT(config-if-gpon-{$onu->gpon_port})# ont add {$onuId} sn-auth {$onu->serial_number} omci-type1 line-profile-name {$onu->line_profile} srv-profile-name {$onu->service_profile}",
                "OLT(config-if-gpon-{$onu->gpon_port})# exit",
                "OLT(config)# service-port gemport 1 multi-service user-vlan 100 tag-in-user-vlan tag",
                "OLT(config)# save"
            ];
        }

        return [
            'status' => 'success',
            'onu_id' => $onuId,
            'log' => implode("\n", $commands),
            'timestamp' => now()->toDateTimeString(),
        ];
    }

    /**
     * Simulate suspending an ONU (disabling the ONT port).
     */
    public function suspendOnu(OnuDevice $onu): array
    {
        $olt = $onu->oltDevice;
        $onuId = $onu->onu_id ?? 1;

        if (strtolower($olt->vendor) === 'zte') {
            $log = "ZXAN(config)# interface gpon-olt_{$onu->gpon_port}\nZXAN(config-if)# ont {$onuId} block\nZXAN(config-if)# exit\nZXAN(config)# write memory";
        } else {
            $log = "OLT(config)# interface gpon {$onu->gpon_port}\nOLT(config-if-gpon-{$onu->gpon_port})# ont deactivate {$onuId}\nOLT(config-if-gpon-{$onu->gpon_port})# exit\nOLT(config)# save";
        }

        $onu->update(['status' => 'suspended']);

        return [
            'status' => 'suspended',
            'log' => $log,
        ];
    }

    /**
     * Simulate reactivating a suspended ONU.
     */
    public function reactivateOnu(OnuDevice $onu): array
    {
        $olt = $onu->oltDevice;
        $onuId = $onu->onu_id ?? 1;

        if (strtolower($olt->vendor) === 'zte') {
            $log = "ZXAN(config)# interface gpon-olt_{$onu->gpon_port}\nZXAN(config-if)# ont {$onuId} unblock\nZXAN(config-if)# exit\nZXAN(config)# write memory";
        } else {
            $log = "OLT(config)# interface gpon {$onu->gpon_port}\nOLT(config-if-gpon-{$onu->gpon_port})# ont activate {$onuId}\nOLT(config-if-gpon-{$onu->gpon_port})# exit\nOLT(config)# save";
        }

        $onu->update(['status' => 'active']);

        return [
            'status' => 'active',
            'log' => $log,
        ];
    }

    /**
     * Simulate removing an ONU from the OLT configuration.
     */
    public function deleteOnu(OnuDevice $onu): array
    {
        $olt = $onu->oltDevice;
        $onuId = $onu->onu_id ?? 1;

        if (strtolower($olt->vendor) === 'zte') {
            $log = "ZXAN(config)# interface gpon-olt_{$onu->gpon_port}\nZXAN(config-if)# no ont {$onuId}\nZXAN(config-if)# exit\nZXAN(config)# write memory";
        } else {
            $log = "OLT(config)# interface gpon {$onu->gpon_port}\nOLT(config-if-gpon-{$onu->gpon_port})# ont delete {$onuId}\nOLT(config-if-gpon-{$onu->gpon_port})# exit\nOLT(config)# save";
        }

        $onu->update(['status' => 'deprovisioned', 'onu_id' => null]);

        return [
            'status' => 'deprovisioned',
            'log' => $log,
        ];
    }

    /**
     * Get simulated RX/TX Optical Power readings from OLT.
     */
    public function getOpticalPower(OnuDevice $onu): array
    {
        // RX power target should be between -15 dBm and -28 dBm. Higher or lower represents signal quality.
        $rx = -15.0 - (rand(0, 130) / 10.0);
        $tx = 1.5 + (rand(0, 20) / 10.0);
        
        $status = 'Good';
        if ($rx < -27.0) {
            $status = 'Critical';
        } elseif ($rx < -24.0) {
            $status = 'Warning';
        }

        return [
            'onu_rx_power_dbm' => sprintf('%.2f dBm', $rx),
            'onu_tx_power_dbm' => sprintf('%.2f dBm', $tx),
            'olt_rx_power_dbm' => sprintf('%.2f dBm', $rx - rand(5, 15)/10.0),
            'status' => $status,
            'last_update' => now()->toDateTimeString(),
        ];
    }
}
