<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Models;

use App\Core\Tenant\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebhookConfig extends Model
{
    use BelongsToTenant;

    protected $table = 'webhook_configs';

    protected $fillable = [
        'tenant_id',
        'name',
        'url',
        'secret_key',
        'events',
        'is_active',
    ];

    protected $casts = [
        'events' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get delivery logs for this webhook config.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(WebhookLog::class);
    }
}
