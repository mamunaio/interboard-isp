<?php

declare(strict_types=1);

namespace App\Modules\Billing\Services;

use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Models\InvoiceLineItem;
use App\Modules\Billing\Enums\InvoiceStatus;
use App\Modules\Billing\Enums\InvoiceType;
use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Models\CustomerServiceSubscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    /**
     * Create a new high-precision invoice securely.
     */
    public function createInvoice(int $tenantId, int $customerId, array $data, array $lineItems): Invoice
    {
        // 1. Tenant ownership and customer validation
        $customer = $this->resolveAndValidateCustomer($tenantId, $customerId);

        // 2. Validate subscription matches tenant if provided
        if (isset($data['customer_service_subscription_id']) && $data['customer_service_subscription_id'] !== null) {
            $this->resolveAndValidateSubscription($tenantId, (int)$data['customer_service_subscription_id']);
        }

        // 3. Strict metadata schema validation
        $metadata = isset($data['metadata']) ? $this->validateInvoiceMetadata($data['metadata']) : null;

        // 4. Force default initial draft status
        $status = isset($data['status'])
            ? ($data['status'] instanceof InvoiceStatus ? $data['status'] : InvoiceStatus::from($data['status']))
            : InvoiceStatus::DRAFT;

        if ($status !== InvoiceStatus::DRAFT) {
            throw ValidationException::withMessages([
                'status' => ['Invoices must strictly be created with a status of draft. Direct release or other states are blocked.']
            ]);
        }

        return DB::transaction(function () use ($tenantId, $customer, $data, $lineItems, $metadata) {
            // 5. Generate deterministic, collision-safe invoice number (Database pessimistic lock)
            $invoiceNumber = $this->generateInvoiceNumber($tenantId);

            // 6. Recalculate line subtotals and invoice totals server-side (DECIMAL math)
            $calculatedLines = [];
            $subtotalSum = '0.0000';

            if (empty($lineItems)) {
                throw ValidationException::withMessages([
                    'line_items' => ['At least one invoice line item is required.']
                ]);
            }

            foreach ($lineItems as $item) {
                $qty = (string)($item['quantity'] ?? '1.0000');
                $price = (string)($item['unit_price'] ?? '0.0000');

                // High precision decimal calculation using BCMath (NO floats!)
                $lineSubtotalCalculated = bcmul($qty, $price, 4);

                // Verify and reject mismatches if line_subtotal is passed by client
                if (isset($item['line_subtotal'])) {
                    $passedSubtotal = bcadd((string)$item['line_subtotal'], '0', 4);
                    if (bccomp($passedSubtotal, $lineSubtotalCalculated, 4) !== 0) {
                        throw ValidationException::withMessages([
                            'line_items' => ["Subtotal mismatch: Expected {$lineSubtotalCalculated}, got {$passedSubtotal}."]
                        ]);
                    }
                }

                $calculatedLines[] = [
                    'tenant_id' => $tenantId,
                    'line_type' => $item['line_type'] ?? 'service',
                    'description' => $item['description'] ?? 'Line Item Description',
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'line_subtotal' => $lineSubtotalCalculated,
                    'metadata' => $item['metadata'] ?? null,
                ];

                $subtotalSum = bcadd($subtotalSum, $lineSubtotalCalculated, 4);
            }

            $taxTotal = bcadd((string)($data['tax_total'] ?? '0.0000'), '0', 4);
            $discountTotal = bcadd((string)($data['discount_total'] ?? '0.0000'), '0', 4);
            
            $subAndTax = bcadd($subtotalSum, $taxTotal, 4);
            if (bccomp($subAndTax, $discountTotal, 4) === -1) {
                throw ValidationException::withMessages([
                    'discount_total' => ['The discount total cannot exceed the sum of the subtotal and the tax total.']
                ]);
            }
            $grandTotal = bcsub($subAndTax, $discountTotal, 4);

            $invoice = Invoice::create([
                'tenant_id' => $tenantId,
                'customer_id' => $customer->id,
                'customer_service_subscription_id' => $data['customer_service_subscription_id'] ?? null,
                'invoice_number' => $invoiceNumber,
                'invoice_type' => $data['invoice_type'] ?? InvoiceType::SUBSCRIPTION->value,
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'],
                'currency' => $data['currency'] ?? 'BDT',
                'subtotal' => $subtotalSum,
                'tax_total' => $taxTotal,
                'discount_total' => $discountTotal,
                'grand_total' => $grandTotal,
                'status' => InvoiceStatus::DRAFT,
                'notes' => $data['notes'] ?? null,
                'metadata' => $metadata,
            ]);

            foreach ($calculatedLines as $line) {
                $line['invoice_id'] = $invoice->id;
                InvoiceLineItem::create($line);
            }

            return $invoice;
        });
    }

    /**
     * Update a draft invoice securely with strict immutability checks.
     */
    public function updateInvoice(Invoice $invoice, array $data, ?array $lineItems = null): Invoice
    {
        // 1. Strict Immutability Check: If invoice status is NOT draft, block updates
        if ($invoice->status !== InvoiceStatus::DRAFT) {
            throw ValidationException::withMessages([
                'status' => ['Invoices are completely immutable once they are issued, voided, or cancelled.']
            ]);
        }

        $tenantId = (int)$invoice->tenant_id;

        return DB::transaction(function () use ($invoice, $tenantId, $data, $lineItems) {
            // Resolve custom metadata if provided
            if (isset($data['metadata'])) {
                $data['metadata'] = $this->validateInvoiceMetadata($data['metadata']);
            }

            if ($lineItems !== null) {
                if (empty($lineItems)) {
                    throw ValidationException::withMessages([
                        'line_items' => ['At least one invoice line item is required.']
                    ]);
                }

                // Delete old lines
                $invoice->lineItems()->delete();

                $calculatedLines = [];
                $subtotalSum = '0.0000';

                foreach ($lineItems as $item) {
                    $qty = (string)($item['quantity'] ?? '1.0000');
                    $price = (string)($item['unit_price'] ?? '0.0000');
                    
                    // High precision decimal calculation using BCMath (NO floats!)
                    $lineSubtotalCalculated = bcmul($qty, $price, 4);

                    if (isset($item['line_subtotal'])) {
                        $passedSubtotal = bcadd((string)$item['line_subtotal'], '0', 4);
                        if (bccomp($passedSubtotal, $lineSubtotalCalculated, 4) !== 0) {
                            throw ValidationException::withMessages([
                                'line_items' => ["Subtotal mismatch: Expected {$lineSubtotalCalculated}, got {$passedSubtotal}."]
                            ]);
                        }
                    }

                    $calculatedLines[] = [
                        'tenant_id' => $tenantId,
                        'line_type' => $item['line_type'] ?? 'service',
                        'description' => $item['description'] ?? 'Line Item Description',
                        'quantity' => $qty,
                        'unit_price' => $price,
                        'line_subtotal' => $lineSubtotalCalculated,
                        'metadata' => $item['metadata'] ?? null,
                    ];

                    $subtotalSum = bcadd($subtotalSum, $lineSubtotalCalculated, 4);
                }

                $taxTotal = bcadd((string)($data['tax_total'] ?? $invoice->tax_total), '0', 4);
                $discountTotal = bcadd((string)($data['discount_total'] ?? $invoice->discount_total), '0', 4);
                
                $subAndTax = bcadd($subtotalSum, $taxTotal, 4);
                if (bccomp($subAndTax, $discountTotal, 4) === -1) {
                    throw ValidationException::withMessages([
                        'discount_total' => ['The discount total cannot exceed the sum of the subtotal and the tax total.']
                    ]);
                }
                $grandTotal = bcsub($subAndTax, $discountTotal, 4);

                $data['subtotal'] = $subtotalSum;
                $data['tax_total'] = $taxTotal;
                $data['discount_total'] = $discountTotal;
                $data['grand_total'] = $grandTotal;

                foreach ($calculatedLines as $line) {
                    $line['invoice_id'] = $invoice->id;
                    InvoiceLineItem::create($line);
                }
            } else {
                // If lineItems is null, we still need to validate grand total of the updated invoice
                $subtotalSum = bcadd((string)$invoice->subtotal, '0', 4);
                $taxTotal = bcadd((string)($data['tax_total'] ?? $invoice->tax_total), '0', 4);
                $discountTotal = bcadd((string)($data['discount_total'] ?? $invoice->discount_total), '0', 4);
                
                $subAndTax = bcadd($subtotalSum, $taxTotal, 4);
                if (bccomp($subAndTax, $discountTotal, 4) === -1) {
                    throw ValidationException::withMessages([
                        'discount_total' => ['The discount total cannot exceed the sum of the subtotal and the tax total.']
                    ]);
                }
                $grandTotal = bcsub($subAndTax, $discountTotal, 4);

                if (isset($data['tax_total'])) {
                    $data['tax_total'] = $taxTotal;
                }
                if (isset($data['discount_total'])) {
                    $data['discount_total'] = $discountTotal;
                }
                $data['grand_total'] = $grandTotal;
            }

            $invoice->update($data);

            return $invoice->fresh();
        });
    }

    /**
     * Transition the invoice lifecycle status securely.
     */
    public function transitionInvoiceStatus(Invoice $invoice, InvoiceStatus $newStatus): Invoice
    {
        $currentStatus = $invoice->status;

        // Enforce explicit status transitions
        if (!$currentStatus->canTransitionTo($newStatus)) {
            throw ValidationException::withMessages([
                'status' => ["Invalid status transition: Cannot transition from '{$currentStatus->value}' to '{$newStatus->value}'."]
            ]);
        }

        $updateData = [
            'status' => $newStatus,
        ];

        if ($newStatus === InvoiceStatus::ISSUED) {
            $updateData['amount_due'] = $invoice->grand_total;
            $updateData['amount_paid'] = '0.0000';
        } elseif ($newStatus === InvoiceStatus::PAID) {
            $updateData['amount_due'] = '0.0000';
            $updateData['amount_paid'] = $invoice->grand_total;
        } elseif (in_array($newStatus, [InvoiceStatus::VOID, InvoiceStatus::CANCELLED])) {
            $updateData['amount_due'] = '0.0000';
            $updateData['amount_paid'] = '0.0000';
        }

        $invoice->update($updateData);

        return $invoice;
    }

    /**
     * Generate deterministic, collision-safe invoice sequence codes.
     */
    public function generateInvoiceNumber(int $tenantId): string
    {
        $year = date('Y');

        $sequence = DB::transaction(function () use ($tenantId, $year) {
            $seq = DB::table('invoice_number_sequences')
                ->where('tenant_id', $tenantId)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (!$seq) {
                DB::table('invoice_number_sequences')->insert([
                    'tenant_id' => $tenantId,
                    'year' => $year,
                    'last_value' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                return 1;
            }

            $newValue = (int)$seq->last_value + 1;
            
            DB::table('invoice_number_sequences')
                ->where('tenant_id', $tenantId)
                ->where('year', $year)
                ->update([
                    'last_value' => $newValue,
                    'updated_at' => now(),
                ]);

            return $newValue;
        });

        $seqFormatted = str_pad((string)$sequence, 6, '0', STR_PAD_LEFT);
        
        return "INV-{$year}-{$seqFormatted}";
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
     * Retrieve and validate that the subscription belongs to the active tenant strictly.
     */
    protected function resolveAndValidateSubscription(int $tenantId, int $subscriptionId): CustomerServiceSubscription
    {
        $subscription = CustomerServiceSubscription::withoutGlobalScopes()
            ->where('id', $subscriptionId)
            ->first();

        if (!$subscription || (int)$subscription->tenant_id !== $tenantId) {
            throw ValidationException::withMessages([
                'customer_service_subscription_id' => ['The selected subscription does not belong to the active tenant context.']
            ]);
        }

        return $subscription;
    }

    /**
     * Validate structured metadata payload strictly.
     */
    protected function validateInvoiceMetadata(array $payload): array
    {
        $allowedKeys = ['billing_period_start', 'billing_period_end'];
        $cleanPayload = [];

        foreach ($payload as $key => $value) {
            if (!in_array($key, $allowedKeys, true)) {
                throw ValidationException::withMessages([
                    'metadata' => ["Arbitrary metadata field '{$key}' is prohibited."]
                ]);
            }
            $cleanPayload[$key] = trim((string)$value);
        }

        return $cleanPayload;
    }
}
