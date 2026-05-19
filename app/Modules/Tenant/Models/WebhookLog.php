<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Models;

use App\Core\Tenant\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookLog extends Model
{
    use BelongsToTenant;

    protected $table = 'webhook_logs';

    protected $fillable = [
        'tenant_id',
        'webhook_config_id',
        'event',
        'url',
        'payload',
        'signature',
        'status_code',
        'response_body',
        'status',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    /**
     * Get the webhook config associated with this log.
     */
    public function config(): BelongsTo
    {
        return $this->belongsTo(WebhookConfig::class, 'webhook_config_id');
    }
}
