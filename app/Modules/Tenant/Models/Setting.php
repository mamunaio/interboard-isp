<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Models;

use App\Core\Tenant\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    use BelongsToTenant;

    protected $table = 'settings';

    protected $fillable = [
        'tenant_id',
        'key',
        'value',
        'type',
        'is_encrypted',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    /**
     * Get the tenant that owns this setting.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
