<?php

declare(strict_types=1);

namespace App\Modules\Customer\Models;

use App\Core\Tenant\Traits\BelongsToTenant;
use App\Core\Audit\Traits\LogsActivity;
use App\Modules\Customer\Enums\ServiceType;
use Illuminate\Database\Eloquent\Model;

class ServicePackage extends Model
{
    use BelongsToTenant, LogsActivity;

    protected $table = 'service_packages';

    protected $fillable = [
        'tenant_id',
        'package_code',
        'name',
        'description',
        'service_type',
        'price_monthly',
        'customer_type_compatibility',
        'download_speed_kbps',
        'upload_speed_kbps',
        'burst_download_kbps',
        'burst_upload_kbps',
        'burst_threshold_kb',
        'burst_time_seconds',
        'mikrotik_profile_name',
        'radius_profile_name',
        'gpon_line_profile',
        'gpon_service_profile',
        'is_active',
    ];

    protected $casts = [
        'service_type' => ServiceType::class,
        'price_monthly' => 'decimal:4',
        'customer_type_compatibility' => 'array',
        'is_active' => 'boolean',
        'download_speed_kbps' => 'integer',
        'upload_speed_kbps' => 'integer',
        'burst_download_kbps' => 'integer',
        'burst_upload_kbps' => 'integer',
        'burst_threshold_kb' => 'integer',
        'burst_time_seconds' => 'integer',
    ];
}
