<?php

declare(strict_types=1);

namespace App\Modules\CRM\Models;

use App\Models\User;
use App\Modules\CRM\Enums\InteractionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadInteraction extends Model
{
    protected $table = 'lead_interactions';

    protected $fillable = [
        'lead_id',
        'user_id',
        'type',
        'summary',
        'next_follow_up_date',
    ];

    protected $casts = [
        'type' => InteractionType::class,
        'next_follow_up_date' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
