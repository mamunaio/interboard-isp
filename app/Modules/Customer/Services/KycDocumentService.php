<?php

declare(strict_types=1);

namespace App\Modules\Customer\Services;

use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Models\CustomerDocument;
use App\Modules\Customer\Enums\DocumentType;
use App\Modules\Customer\Enums\KycStatus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KycDocumentService
{
    /**
     * Strict file size limit: 10MB
     */
    protected const MAX_FILE_SIZE = 10 * 1024 * 1024;

    /**
     * Strict MIME allowlist
     */
    protected const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'application/pdf',
    ];

    /**
     * Strict File Extension allowlist matching the mimes
     */
    protected const ALLOWED_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'webp', 'pdf'
    ];

    /**
     * Upload and store a KYC document cleanly.
     */
    public function uploadDocument(int $tenantId, int $customerId, UploadedFile $file, array $data): CustomerDocument
    {
        // 1. Verify tenant-scoped Customer ownership strictly
        $customer = $this->resolveAndValidateCustomer($tenantId, $customerId);

        // 2. Validate file size limits
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw ValidationException::withMessages([
                'file' => ['File size exceeds the limit of 10MB.']
            ]);
        }

        // 3. Do NOT trust client MIME type or extension. Validate server-side!
        $mimeType = $file->getMimeType();
        $extension = Str::lower($file->guessExtension() ?? $file->getClientOriginalExtension());

        if (!in_array($mimeType, self::ALLOWED_MIMES, true) || !in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw ValidationException::withMessages([
                'file' => ['Unsupported file format. Executable/script files are strictly prohibited. Only JPEG, PNG, WEBP, and PDF files are allowed.']
            ]);
        }

        // 4. Customer-scoped duplicate detection by SHA-256 checksum hash
        $checksumHash = hash_file('sha256', $file->getRealPath());
        $duplicateExists = CustomerDocument::withoutGlobalScopes()
            ->where('customer_id', $customer->id)
            ->where('checksum_hash', $checksumHash)
            ->exists();

        if ($duplicateExists) {
            throw ValidationException::withMessages([
                'file' => ['This exact document has already been uploaded for this customer.']
            ]);
        }

        // 5. Validate metadata payload structure (No arbitrary junk JSON)
        $metadata = isset($data['metadata']) ? $this->validateAndNormalizeMetadata($data['metadata']) : null;

        // 6. Generate unguessable random storage filename (UUID-based)
        $storedFilename = Str::uuid() . '.' . $extension;

        // 7. Store securely inside local private storage
        $storedPath = $file->storeAs('private/documents', $storedFilename, 'local');
        if (!$storedPath) {
            throw new \RuntimeException('Failed to store private file.');
        }

        // 8. Create database record
        return CustomerDocument::create([
            'tenant_id' => $customer->tenant_id,
            'customer_id' => $customer->id,
            'type' => $data['type'] instanceof DocumentType ? $data['type'] : DocumentType::from($data['type']),
            'title' => trim($data['title']),
            'original_filename' => $file->getClientOriginalName(),
            'stored_filename' => $storedFilename,
            'mime_type' => $mimeType,
            'file_size' => $file->getSize(),
            'storage_disk' => 'local',
            'checksum_hash' => $checksumHash,
            'verification_status' => KycStatus::PENDING,
            'expiry_date' => $data['expiry_date'] ?? null,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Transition document verification status cleanly.
     */
    public function verifyDocument(
        CustomerDocument $document,
        KycStatus $status,
        int $staffUserId,
        ?string $rejectionReason = null
    ): CustomerDocument {
        $updateData = [
            'verification_status' => $status,
            'verified_by' => $staffUserId,
            'verified_at' => now(),
        ];

        if ($status === KycStatus::REJECTED) {
            if (empty($rejectionReason)) {
                throw ValidationException::withMessages([
                    'rejection_reason' => ['A rejection reason is required when rejecting a document.']
                ]);
            }
            $updateData['rejection_reason'] = trim($rejectionReason);
        } else {
            $updateData['rejection_reason'] = null;
        }

        $document->update($updateData);

        return $document;
    }

    /**
     * Serve secure streamed downloads cleanly.
     * Absolutely NO public URL exposure, secure streamed downloads only!
     */
    public function downloadDocument(CustomerDocument $document): StreamedResponse
    {
        $filePath = 'private/documents/' . $document->stored_filename;

        if (!Storage::disk($document->storage_disk)->exists($filePath)) {
            abort(404, 'Private document file not found.');
        }

        return Storage::disk($document->storage_disk)->download($filePath, $document->original_filename);
    }

    /**
     * Retrieve and validate that the customer belongs to the active tenant strictly.
     */
    protected function resolveAndValidateCustomer(int $tenantId, int $customerId): Customer
    {
        $customer = Customer::withoutGlobalScopes()
            ->where('id', $customerId)
            ->first();

        if (!$customer || (int)$customer->tenant_id !== $tenantId) {
            throw ValidationException::withMessages([
                'customer_id' => ['The selected customer does not belong to the active tenant context.']
            ]);
        }

        return $customer;
    }

    /**
     * Validate and normalize metadata payload structure.
     * Prevents arbitrary junk JSON.
     */
    protected function validateAndNormalizeMetadata(array $metadata): array
    {
        $allowedKeys = ['document_number', 'issue_date'];
        $cleanMetadata = [];

        foreach ($metadata as $key => $value) {
            if (!in_array($key, $allowedKeys, true)) {
                throw ValidationException::withMessages([
                    'metadata' => ["Arbitrary metadata key '{$key}' is prohibited. Only 'document_number' and 'issue_date' are allowed."]
                ]);
            }
            
            if ($key === 'issue_date' && !empty($value)) {
                // Ensure date format is YYYY-MM-DD
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$value)) {
                    throw ValidationException::withMessages([
                        'metadata' => ['The issue_date metadata must be in YYYY-MM-DD format.']
                    ]);
                }
            }

            $cleanMetadata[$key] = $value !== null ? trim((string)$value) : null;
        }

        return $cleanMetadata;
    }
}
