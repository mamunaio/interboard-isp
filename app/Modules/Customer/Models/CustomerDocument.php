<?php

declare(strict_types=1);

namespace App\Modules\Customer\Models;

use App\Core\Tenant\Traits\BelongsToTenant;
use App\Core\Audit\Traits\LogsActivity;
use App\Modules\Customer\Enums\DocumentType;
use App\Modules\Customer\Enums\KycStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerDocument extends Model
{
    use BelongsToTenant, LogsActivity;

    protected $table = 'customer_documents';

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'type',
        'title',
        'original_filename',
        'stored_filename',
        'mime_type',
        'file_size',
        'storage_disk',
        'checksum_hash',
        'verification_status',
        'verified_by',
        'verified_at',
        'rejection_reason',
        'expiry_date',
        'metadata',
    ];

    protected $casts = [
        'type' => DocumentType::class,
        'verification_status' => KycStatus::class,
        'metadata' => 'array',
        'verified_at' => 'datetime',
        'expiry_date' => 'date',
        'file_size' => 'integer',
    ];

    /**
     * Get the customer that owns the document.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
