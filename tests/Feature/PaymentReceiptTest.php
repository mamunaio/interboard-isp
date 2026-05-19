<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Billing\Enums\InvoiceStatus;
use App\Modules\Billing\Enums\InvoiceType;
use App\Modules\Billing\Enums\PaymentMethod;
use App\Modules\Billing\Enums\ReceiptStatus;
use App\Modules\Billing\Enums\TransactionDirection;
use App\Modules\Billing\Enums\TransactionType;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Models\InvoiceLineItem;
use App\Modules\Billing\Models\PaymentReceipt;
use App\Modules\Billing\Models\Wallet;
use App\Modules\Billing\Models\LedgerEntry;
use App\Modules\Billing\Services\PaymentReceiptService;
use App\Modules\Billing\Services\WalletService;
use App\Modules\Billing\Services\InvoiceService;
use App\Modules\Customer\Models\Customer;
use App\Modules\Tenant\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use RuntimeException;

class PaymentReceiptTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Customer $customer;
    protected Wallet $wallet;
    protected PaymentReceiptService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = \App\Modules\Tenant\Models\Plan::create([
            'name' => 'Premium Plan',
            'slug' => 'premium',
            'price_monthly' => 50.00,
            'price_yearly' => 500.00,
        ]);

        $this->tenant = Tenant::create([
            'uuid' => '00000000-0000-0000-0000-000000000001',
            'slug' => 'tenant-a',
            'name' => 'Test Tenant',
            'status' => 'active',
            'plan_id' => $plan->id,
        ]);

        $this->customer = app(\App\Modules\Customer\Services\CustomerService::class)->createCustomer($this->tenant->id, [
            'type' => \App\Modules\Customer\Enums\CustomerType::RESIDENTIAL,
            'full_name' => 'John Doe',
            'phone' => '01700000001',
            'email' => 'john@example.com',
        ]);

        // Resolve service
        $this->service = app(PaymentReceiptService::class);

        // Pre-create wallet for customer
        $walletService = app(WalletService::class);
        $this->wallet = $walletService->createWallet($this->tenant->id, Customer::class, $this->customer->id, 'BDT');
    }

    /**
     * Test successful receipt creation.
     */
    public function test_can_create_payment_receipt(): void
    {
        $receipt = $this->service->createReceipt($this->tenant->id, [
            'customer_id' => $this->customer->id,
            'amount' => '1500.5000',
            'currency' => 'BDT',
            'payment_method' => PaymentMethod::CASH,
            'reference' => 'TXN-12345',
            'notes' => 'Hand cash payment received.',
        ]);

        $this->assertNotNull($receipt);
        $this->assertEquals('1500.5000', (string)$receipt->amount);
        $this->assertEquals('BDT', $receipt->currency);
        $this->assertEquals(PaymentMethod::CASH, $receipt->payment_method);
        $this->assertEquals(ReceiptStatus::POSTED, $receipt->status);
        $this->assertStringMatchesFormat('RCPT-%d-%s', $receipt->receipt_number);

        // Verify Wallet balance is credited
        $this->wallet->refresh();
        $this->assertEquals('1500.5000', (string)$this->wallet->current_balance);

        // Verify Ledger Entry is created correctly
        $this->assertNotNull($receipt->wallet_ledger_entry_id);
        $ledger = LedgerEntry::find($receipt->wallet_ledger_entry_id);
        $this->assertNotNull($ledger);
        $this->assertEquals(TransactionType::MANUAL_PAYMENT_RECEIPT, $ledger->transaction_type);
        $this->assertEquals(TransactionDirection::CREDIT, $ledger->direction);
        $this->assertEquals('1500.5000', (string)$ledger->amount);
    }

    /**
     * Test exception is thrown if no wallet exists for currency.
     */
    public function test_creation_fails_if_no_wallet_exists(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('No active wallet initialized for currency USD on this customer.');

        $this->service->createReceipt($this->tenant->id, [
            'customer_id' => $this->customer->id,
            'amount' => '100.0000',
            'currency' => 'USD',
            'payment_method' => PaymentMethod::CASH,
        ]);
    }

    /**
     * Test absolute immutability of PaymentReceipt records.
     */
    public function test_enforces_immutability_on_receipt_updates_and_deletes(): void
    {
        $receipt = $this->service->createReceipt($this->tenant->id, [
            'customer_id' => $this->customer->id,
            'amount' => '500.0000',
            'currency' => 'BDT',
            'payment_method' => PaymentMethod::CASH,
        ]);

        // Attempting to update must throw RuntimeException
        try {
            $receipt->update(['amount' => '1000.0000']);
            $this->fail('Updating an immutable payment receipt succeeded unexpectedly.');
        } catch (RuntimeException $e) {
            $this->assertEquals('Payment receipts are strictly immutable and cannot be updated.', $e->getMessage());
        }

        // Attempting to delete must throw RuntimeException
        try {
            $receipt->delete();
            $this->fail('Deleting an immutable payment receipt succeeded unexpectedly.');
        } catch (RuntimeException $e) {
            $this->assertEquals('Payment receipts are strictly immutable and cannot be deleted.', $e->getMessage());
        }
    }

    /**
     * Test duplicate reference protection per payment method and tenant.
     */
    public function test_duplicate_reference_idempotency_is_blocked(): void
    {
        $this->service->createReceipt($this->tenant->id, [
            'customer_id' => $this->customer->id,
            'amount' => '500.0000',
            'currency' => 'BDT',
            'payment_method' => PaymentMethod::BANK_TRANSFER,
            'reference' => 'UNIQUE-REF-101',
        ]);

        // A duplicate reference with same method and tenant must fail
        try {
            $this->service->createReceipt($this->tenant->id, [
                'customer_id' => $this->customer->id,
                'amount' => '200.0000',
                'currency' => 'BDT',
                'payment_method' => PaymentMethod::BANK_TRANSFER,
                'reference' => 'UNIQUE-REF-101',
            ]);
            $this->fail('Duplicate reference check was bypassed.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('reference', $e->errors());
        }

        // A duplicate reference with a DIFFERENT method is ALLOWED
        $allowedReceipt = $this->service->createReceipt($this->tenant->id, [
            'customer_id' => $this->customer->id,
            'amount' => '200.0000',
            'currency' => 'BDT',
            'payment_method' => PaymentMethod::MOBILE_BANKING_MANUAL,
            'reference' => 'UNIQUE-REF-101',
        ]);
        $this->assertNotNull($allowedReceipt);
    }

    /**
     * Test validation fails for invalid amounts.
     */
    public function test_invalid_amounts_are_blocked(): void
    {
        // Zero amount should fail
        try {
            $this->service->createReceipt($this->tenant->id, [
                'customer_id' => $this->customer->id,
                'amount' => '0.0000',
                'currency' => 'BDT',
                'payment_method' => PaymentMethod::CASH,
            ]);
            $this->fail('Zero receipt amount was accepted.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('amount', $e->errors());
        }

        // Negative amount should fail
        try {
            $this->service->createReceipt($this->tenant->id, [
                'customer_id' => $this->customer->id,
                'amount' => '-150.0000',
                'currency' => 'BDT',
                'payment_method' => PaymentMethod::CASH,
            ]);
            $this->fail('Negative receipt amount was accepted.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('amount', $e->errors());
        }
    }

    /**
     * Test strict tenant isolation boundaries.
     */
    public function test_tenant_isolation_is_enforced(): void
    {
        $plan = \App\Modules\Tenant\Models\Plan::first();
        $otherTenant = Tenant::create([
            'uuid' => '00000000-0000-0000-0000-000000000002',
            'slug' => 'tenant-b',
            'name' => 'Foreign Tenant',
            'status' => 'active',
            'plan_id' => $plan->id,
        ]);

        // Attempting to ingest a payment for our customer under the foreign tenant context must fail
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Customer not found or tenant mismatch.');

        $this->service->createReceipt($otherTenant->id, [
            'customer_id' => $this->customer->id,
            'amount' => '500.0000',
            'currency' => 'BDT',
            'payment_method' => PaymentMethod::CASH,
        ]);
    }

    /**
     * Test successful FIFO auto-allocation integration.
     */
    public function test_auto_allocation_fifo_works_flawlessly(): void
    {
        $invoiceService = app(InvoiceService::class);

        // Create Invoice 1 (BDT 100)
        $invoice1 = $invoiceService->createInvoice($this->tenant->id, $this->customer->id, [
            'invoice_type' => InvoiceType::SUBSCRIPTION->value,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
        ], [
            [
                'line_type' => 'service',
                'description' => 'Internet Pack A',
                'quantity' => '1.0000',
                'unit_price' => '100.0000',
            ]
        ]);
        $invoiceService->transitionInvoiceStatus($invoice1, InvoiceStatus::ISSUED);

        // Create Invoice 2 (BDT 200)
        $invoice2 = $invoiceService->createInvoice($this->tenant->id, $this->customer->id, [
            'invoice_type' => InvoiceType::SUBSCRIPTION->value,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
        ], [
            [
                'line_type' => 'service',
                'description' => 'Internet Pack B',
                'quantity' => '1.0000',
                'unit_price' => '200.0000',
            ]
        ]);
        $invoiceService->transitionInvoiceStatus($invoice2, InvoiceStatus::ISSUED);

        // Perform Receipt intake of BDT 150 with auto_allocate => true
        $receipt = $this->service->createReceipt($this->tenant->id, [
            'customer_id' => $this->customer->id,
            'amount' => '150.0000',
            'currency' => 'BDT',
            'payment_method' => PaymentMethod::CASH,
            'auto_allocate' => true,
        ]);

        $this->assertNotNull($receipt);

        // Invoice 1 (BDT 100) should be fully paid (amount_due = 0)
        $invoice1->refresh();
        $this->assertEquals(InvoiceStatus::PAID, $invoice1->status);
        $this->assertEquals('0.0000', (string)$invoice1->amount_due);

        // Invoice 2 (BDT 200) should be partially paid with BDT 50 allocated (amount_due = 150)
        $invoice2->refresh();
        $this->assertEquals(InvoiceStatus::PARTIALLY_PAID, $invoice2->status);
        $this->assertEquals('150.0000', (string)$invoice2->amount_due);

        // Wallet balance has BDT 0.0000 left
        $this->wallet->refresh();
        $this->assertEquals('0.0000', (string)$this->wallet->current_balance);
    }
}
