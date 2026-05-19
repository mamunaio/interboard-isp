<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Models;

use App\Core\Tenant\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantBranding extends Model
{
    use BelongsToTenant;

    protected $table = 'tenant_branding';

    protected $fillable = [
        'tenant_id',
        'logo_path',
        'dark_logo_path',
        'favicon_path',
        'primary_color',
        'secondary_color',
        'custom_css',
    ];

    /**
     * Get the tenant that owns this branding.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
