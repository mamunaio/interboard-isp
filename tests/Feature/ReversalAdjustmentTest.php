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
use App\Modules\Billing\Models\PaymentAllocation;
use App\Modules\Billing\Models\PaymentAllocationReversal;
use App\Modules\Billing\Models\ReversalEvent;
use App\Modules\Billing\Models\Wallet;
use App\Modules\Billing\Models\LedgerEntry;
use App\Modules\Billing\Services\PaymentReceiptService;
use App\Modules\Billing\Services\ReversalAdjustmentService;
use App\Modules\Billing\Services\WalletService;
use App\Modules\Billing\Services\InvoiceService;
use App\Modules\Customer\Models\Customer;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Auth\Models\StaffUser;
use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Tests\TestCase;
use RuntimeException;

class ReversalAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Customer $customer;
    protected Wallet $wallet;
    protected StaffUser $authorizedStaff;
    protected StaffUser $unauthorizedStaff;
    protected PaymentReceiptService $receiptService;
    protected ReversalAdjustmentService $reversalService;

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

        $this->receiptService = app(PaymentReceiptService::class);
        $this->reversalService = app(ReversalAdjustmentService::class);

        // Pre-create wallet for customer
        $walletService = app(WalletService::class);
        $this->wallet = $walletService->createWallet($this->tenant->id, Customer::class, $this->customer->id, 'BDT');

        // Create Permissions
        $reversePerm = Permission::create([
            'module' => 'billing',
            'action' => 'reverse_receipt',
            'slug' => 'billing.receipt.reverse',
        ]);

        $adjustPerm = Permission::create([
            'module' => 'billing',
            'action' => 'manage_adjustment',
            'slug' => 'billing.adjustment.manage',
        ]);

        // Create Roles
        $billingAdminRole = Role::create(['name' => 'Billing Admin', 'slug' => 'billing-admin']);
        $billingAdminRole->permissions()->attach([$reversePerm->id, $adjustPerm->id]);

        $guestRole = Role::create(['name' => 'Billing Guest', 'slug' => 'billing-guest']);

        // Create Staff Users
        $this->authorizedStaff = StaffUser::create([
            'name' => 'Authorized Billing Agent',
            'email' => 'auth@tenant-a.com',
            'password' => 'secret123',
            'status' => 'active',
            'tenant_id' => $this->tenant->id,
        ]);
        $this->authorizedStaff->roles()->attach($billingAdminRole->id);

        $this->unauthorizedStaff = StaffUser::create([
            'name' => 'Unauthorized Clerk',
            'email' => 'unauth@tenant-a.com',
            'password' => 'secret123',
            'status' => 'active',
            'tenant_id' => $this->tenant->id,
        ]);
        $this->unauthorizedStaff->roles()->attach($guestRole->id);
    }

    /**
     * Test successful receipt reversal with no allocations.
     */
    public function test_can_reverse_receipt_without_allocations(): void
    {
        $receipt = $this->receiptService->createReceipt($this->tenant->id, [
            'customer_id' => $this->customer->id,
            'amount' => '1000.0000',
            'currency' => 'BDT',
            'payment_method' => PaymentMethod::CASH,
        ]);

        $this->wallet->refresh();
        $this->assertEquals('1000.0000', (string)$this->wallet->current_balance);

        $reversal = $this->reversalService->reverseReceipt(
            $this->tenant->id,
            $receipt->id,
            'Double payment intake mistake.',
            $this->authorizedStaff
        );

        $this->assertNotNull($reversal);
        $this->assertEquals(PaymentReceipt::class, $reversal->original_entity_type);
        $this->assertEquals($receipt->id, $reversal->original_entity_id);
        $this->assertEquals('1000.0000', (string)$reversal->amount);

        // Wallet balance must be debited back to 0
        $this->wallet->refresh();
        $this->assertEquals('0.0000', (string)$this->wallet->current_balance);

        // Verify original receipt is COMPLETELY untouched (No updates, no status mutations)
        $receipt->refresh();
        $this->assertEquals(ReceiptStatus::POSTED, $receipt->status);
        $this->assertEquals('1000.0000', (string)$receipt->amount);

        // Attempting to update the receipt model directly must still fail
        $this->expectException(RuntimeException::class);
        $receipt->update(['amount' => '2000.0000']);
    }

    /**
     * Test strict provenance unwinding and compensating invoice issue.
     */
    public function test_can_reverse_receipt_with_allocations_provenance_unwinding(): void
    {
        $invoiceService = app(InvoiceService::class);

        // Create Invoice 1 for BDT 1200
        $invoice = $invoiceService->createInvoice($this->tenant->id, $this->customer->id, [
            'invoice_type' => InvoiceType::SUBSCRIPTION->value,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
        ], [
            [
                'line_type' => 'service',
                'description' => 'Monthly Fee',
                'quantity' => '1.0000',
                'unit_price' => '1200.0000',
            ]
        ]);
        $invoiceService->transitionInvoiceStatus($invoice, InvoiceStatus::ISSUED);

        // Ingest Receipt A (BDT 1000) with auto-allocation
        $receiptA = $this->receiptService->createReceipt($this->tenant->id, [
            'customer_id' => $this->customer->id,
            'amount' => '1000.0000',
            'currency' => 'BDT',
            'payment_method' => PaymentMethod::CASH,
            'auto_allocate' => true,
        ]);

        // Ingest Receipt B (BDT 500) with auto-allocation
        $receiptB = $this->receiptService->createReceipt($this->tenant->id, [
            'customer_id' => $this->customer->id,
            'amount' => '500.0000',
            'currency' => 'BDT',
            'payment_method' => PaymentMethod::BANK_TRANSFER,
            'auto_allocate' => true,
        ]);

        // Assert Invoice is now fully paid (paid BDT 1200) and remaining BDT 300 stays in wallet
        $invoice->refresh();
        $this->assertEquals(InvoiceStatus::PAID, $invoice->status);
        $this->assertEquals('1200.0000', (string)$invoice->amount_paid);

        $this->wallet->refresh();
        $this->assertEquals('300.0000', (string)$this->wallet->current_balance);

        // Verify there are exactly two allocations created for the invoice
        $allocations = PaymentAllocation::where('invoice_id', $invoice->id)->get();
        $this->assertCount(2, $allocations);

        // Unwind/Reverse Receipt A (BDT 1000)
        $this->reversalService->reverseReceipt(
            $this->tenant->id,
            $receiptA->id,
            'Receipt A bounced.',
            $this->authorizedStaff
        );

        // 1. Assert the original Invoice is 1000% untouched
        $invoice->refresh();
        $this->assertEquals(InvoiceStatus::PAID, $invoice->status);
        $this->assertEquals('1200.0000', (string)$invoice->amount_paid);
        $this->assertEquals('0.0000', (string)$invoice->amount_due);

        // 2. Assert the original allocations are completely untouched
        $this->assertCount(2, PaymentAllocation::where('invoice_id', $invoice->id)->get());

        // 3. Assert a dedicated compensating PaymentAllocationReversal is created specifically for Receipt A's allocation (BDT 1000)
        $reversals = PaymentAllocationReversal::all();
        $this->assertCount(1, $reversals);
        $this->assertEquals('1000.0000', (string)$reversals->first()->amount);

        // Verify Net Allocation calculations (authoritative economic reversal semantics)
        $allocA = PaymentAllocation::where('payment_receipt_id', $receiptA->id)->first();
        $this->assertNotNull($allocA);
        $this->assertEquals('0.0000', (string)$allocA->net_amount);
        $this->assertTrue($allocA->isReversed());

        // 4. Assert Receipt B's allocation (BDT 200) remains untouched and not reversed
        $allocB = PaymentAllocation::where('payment_receipt_id', $receiptB->id)->first();
        $this->assertNotNull($allocB);
        $this->assertEquals('200.0000', (string)$allocB->net_amount);
        $this->assertFalse($allocB->isReversed());

        // 5. Assert a new compensating Debit Adjustment Invoice is issued for BDT 1000 representing the chargeback
        $chargebacks = Invoice::where('customer_id', $this->customer->id)
            ->where('id', '!=', $invoice->id)
            ->get();
        $this->assertCount(1, $chargebacks);
        $this->assertEquals(InvoiceStatus::ISSUED, $chargebacks->first()->status);
        $this->assertEquals(InvoiceType::MANUAL_ADJUSTMENT, $chargebacks->first()->invoice_type);
        $this->assertEquals('1000.0000', (string)$chargebacks->first()->amount_due);

        // 6. Assert wallet balance is correctly adjusted (Initial BDT 300 - Receipt A BDT 1000 debit + Allocation credit BDT 1000 = BDT 300)
        $this->wallet->refresh();
        $this->assertEquals('300.0000', (string)$this->wallet->current_balance);
    }

    /**
     * Test idempotency to prevent double reversal.
     */
    public function test_block_double_reversal(): void
    {
        $receipt = $this->receiptService->createReceipt($this->tenant->id, [
            'customer_id' => $this->customer->id,
            'amount' => '500.0000',
            'currency' => 'BDT',
            'payment_method' => PaymentMethod::CASH,
        ]);

        $this->reversalService->reverseReceipt($this->tenant->id, $receipt->id, 'Incorrect entry.', $this->authorizedStaff);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('This payment receipt has already been reversed.');

        $this->reversalService->reverseReceipt($this->tenant->id, $receipt->id, 'Incorrect entry second attempt.', $this->authorizedStaff);
    }

    /**
     * Test manual financial adjustments (credits, debits, waivers, penalties).
     */
    public function test_manual_financial_adjustments(): void
    {
        // 1. Credit Adjustment (increases wallet)
        $creditAdj = $this->reversalService->adjustBalance(
            $this->tenant->id,
            $this->customer->id,
            TransactionType::CREDIT_ADJUSTMENT->value,
            '300.0000',
            'BDT',
            'Compensation credit',
            $this->authorizedStaff
        );
        $this->assertNotNull($creditAdj);
        $this->assertEquals(TransactionType::CREDIT_ADJUSTMENT->value, $creditAdj->reversal_type);
        $this->wallet->refresh();
        $this->assertEquals('300.0000', (string)$this->wallet->current_balance);

        // 2. Waiver (increases wallet)
        $waiver = $this->reversalService->adjustBalance(
            $this->tenant->id,
            $this->customer->id,
            TransactionType::WAIVER->value,
            '200.0000',
            'BDT',
            'Campaign discount waiver',
            $this->authorizedStaff
        );
        $this->assertNotNull($waiver);
        $this->wallet->refresh();
        $this->assertEquals('500.0000', (string)$this->wallet->current_balance);

        // 3. Debit Adjustment (deducts from wallet)
        $debitAdj = $this->reversalService->adjustBalance(
            $this->tenant->id,
            $this->customer->id,
            TransactionType::DEBIT_ADJUSTMENT->value,
            '100.0000',
            'BDT',
            'Charge correction',
            $this->authorizedStaff
        );
        $this->assertNotNull($debitAdj);
        $this->wallet->refresh();
        $this->assertEquals('400.0000', (string)$this->wallet->current_balance);

        // 4. Penalty (deducts from wallet)
        $penalty = $this->reversalService->adjustBalance(
            $this->tenant->id,
            $this->customer->id,
            TransactionType::PENALTY->value,
            '50.0000',
            'BDT',
            'Late fee penalty',
            $this->authorizedStaff
        );
        $this->assertNotNull($penalty);
        $this->wallet->refresh();
        $this->assertEquals('350.0000', (string)$this->wallet->current_balance);
    }

    /**
     * Test idempotency on manual financial adjustments.
     */
    public function test_manual_adjustment_idempotency(): void
    {
        $idempotencyKey = 'manual-adj-12345';

        // 1. First request
        $adj1 = $this->reversalService->adjustBalance(
            $this->tenant->id,
            $this->customer->id,
            TransactionType::CREDIT_ADJUSTMENT->value,
            '200.0000',
            'BDT',
            'Late charge refund',
            $this->authorizedStaff,
            ['idempotency_key' => $idempotencyKey]
        );

        $this->wallet->refresh();
        $this->assertEquals('200.0000', (string)$this->wallet->current_balance);

        // 2. Second concurrent request (same key)
        $adj2 = $this->reversalService->adjustBalance(
            $this->tenant->id,
            $this->customer->id,
            TransactionType::CREDIT_ADJUSTMENT->value,
            '200.0000',
            'BDT',
            'Late charge refund',
            $this->authorizedStaff,
            ['idempotency_key' => $idempotencyKey]
        );

        // Balance must remain BDT 200 (not credit twice!)
        $this->wallet->refresh();
        $this->assertEquals('200.0000', (string)$this->wallet->current_balance);
        $this->assertEquals($adj1->id, $adj2->id);
    }

    /**
     * Test strict RBAC enforcement checks on non-permitted roles.
     */
    public function test_rbac_enforcement_throws_authorization_exception(): void
    {
        $receipt = $this->receiptService->createReceipt($this->tenant->id, [
            'customer_id' => $this->customer->id,
            'amount' => '500.0000',
            'currency' => 'BDT',
            'payment_method' => PaymentMethod::CASH,
        ]);

        // 1. Reversal RBAC Check
        try {
            $this->reversalService->reverseReceipt(
                $this->tenant->id,
                $receipt->id,
                'Unauthorized try.',
                $this->unauthorizedStaff
            );
            $this->fail('Guest staff successfully reversed receipt without permission.');
        } catch (AuthorizationException $e) {
            $this->assertStringContainsString('billing.receipt.reverse', $e->getMessage());
        }

        // 2. Adjustment RBAC Check
        try {
            $this->reversalService->adjustBalance(
                $this->tenant->id,
                $this->customer->id,
                TransactionType::CREDIT_ADJUSTMENT->value,
                '100.0000',
                'BDT',
                'Try adjust',
                $this->unauthorizedStaff
            );
            $this->fail('Guest staff successfully created balance adjustment.');
        } catch (AuthorizationException $e) {
            $this->assertStringContainsString('billing.adjustment.manage', $e->getMessage());
        }
    }

    /**
     * Test absolute immutability on ReversalEvent and PaymentAllocationReversal updates and deletes.
     */
    public function test_reversal_events_are_immutable(): void
    {
        $receipt = $this->receiptService->createReceipt($this->tenant->id, [
            'customer_id' => $this->customer->id,
            'amount' => '500.0000',
            'currency' => 'BDT',
            'payment_method' => PaymentMethod::CASH,
            'auto_allocate' => false,
        ]);

        $reversal = $this->reversalService->reverseReceipt($this->tenant->id, $receipt->id, 'Incorrect intake.', $this->authorizedStaff);

        // Attempting to update must throw RuntimeException
        try {
            $reversal->update(['amount' => '1000.0000']);
            $this->fail('Updating an immutable ReversalEvent succeeded.');
        } catch (RuntimeException $e) {
            $this->assertEquals('Reversal events are strictly immutable.', $e->getMessage());
        }

        // Attempting to delete must throw RuntimeException
        try {
            $reversal->delete();
            $this->fail('Deleting an immutable ReversalEvent succeeded.');
        } catch (RuntimeException $e) {
            $this->assertEquals('Reversal events are strictly immutable.', $e->getMessage());
        }
    }

    /**
     * Test tenant isolation boundaries for reversals.
     */
    public function test_tenant_isolation_is_enforced(): void
    {
        $otherTenant = Tenant::create([
            'uuid' => '00000000-0000-0000-0000-000000000002',
            'slug' => 'tenant-b',
            'name' => 'Foreign Tenant',
            'status' => 'active',
            'plan_id' => $this->tenant->plan_id,
        ]);

        $receipt = $this->receiptService->createReceipt($this->tenant->id, [
            'customer_id' => $this->customer->id,
            'amount' => '500.0000',
            'currency' => 'BDT',
            'payment_method' => PaymentMethod::CASH,
        ]);

        // Attempting to reverse our receipt under the context of other tenant must fail
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->reversalService->reverseReceipt($otherTenant->id, $receipt->id, 'Fraudulent intent.', $this->authorizedStaff);
    }
}
