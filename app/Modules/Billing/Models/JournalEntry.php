<?php

declare(strict_types=1);

namespace App\Modules\Billing\Models;

use App\Core\Tenant\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class JournalEntry extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'entry_date',
        'reference_number',
        'source_type',
        'source_id',
        'description',
        'status',
    ];

    protected $casts = [
        'entry_date' => 'date',
    ];

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }
}
