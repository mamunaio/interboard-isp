<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Billing\Enums\InvoiceStatus;
use App\Modules\Billing\Enums\InvoiceType;
use App\Modules\Billing\Enums\PaymentMethod;
use App\Modules\Billing\Enums\PaymentGateway;
use App\Modules\Billing\Enums\GatewayPaymentStatus;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Models\PaymentReceipt;
use App\Modules\Billing\Models\PaymentAllocation;
use App\Modules\Billing\Models\GatewayPaymentAttempt;
use App\Modules\Billing\Models\Wallet;
use App\Modules\Billing\Services\GatewayOrchestrationService;
use App\Modules\Billing\Services\WalletService;
use App\Modules\Billing\Services\InvoiceService;
use App\Modules\Customer\Models\Customer;
use App\Modules\Tenant\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use RuntimeException;

class GatewayOrchestrationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Customer $customer;
    protected Wallet $wallet;
    protected GatewayOrchestrationService $orchestrationService;

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

        $this->orchestrationService = app(GatewayOrchestrationService::class);

        // Pre-create wallet for customer
        $walletService = app(WalletService::class);
        $this->wallet = $walletService->createWallet($this->tenant->id, Customer::class, $this->customer->id, 'BDT');
    }

    /**
     * Test successful gateway payment flow with zero-trust auto-inference and auto-allocation.
     */
    public function test_successful_gateway_checkout_and_verification(): void
    {
        $invoiceService = app(InvoiceService::class);

        // Create outstanding invoice
        $invoice = $invoiceService->createInvoice($this->tenant->id, $this->customer->id, [
            'invoice_type' => InvoiceType::SUBSCRIPTION->value,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
        ], [
            [
                'line_type' => 'service',
                'description' => 'Internet Fee',
                'quantity' => '1.0000',
                'unit_price' => '1000.0000',
            ]
        ]);
        $invoiceService->transitionInvoiceStatus($invoice, InvoiceStatus::ISSUED);

        // 1. Initiate checkout
        $initiation = $this->orchestrationService->initiatePayment(
            $this->tenant->id,
            $this->customer->id,
            PaymentGateway::BKASH->value,
            '1000.0000'
        );

        $attempt = $initiation['attempt'];
        $this->assertNotNull($attempt);
        $this->assertEquals(GatewayPaymentStatus::PENDING->value, $attempt->status);
        $this->assertNotNull($initiation['redirect_url']);

        // 2. Simulate incoming callback (verifyPayment)
        $payload = [
            'attempt_reference' => $attempt->attempt_reference,
            'gateway_transaction_id' => 'TXN-BKASH-889900',
            'signature_key' => 'shh-private-secret-key-12345',
            'auth_token' => 'bkash-dynamic-auth-jwt',
        ];

        // Zero-Trust: verifyPayment infers tenant automatically without receiving tenantId input
        $verifiedAttempt = $this->orchestrationService->verifyPayment(
            PaymentGateway::BKASH->value,
            $payload
        );

        $this->assertNotNull($verifiedAttempt);
        $this->assertEquals(GatewayPaymentStatus::VERIFIED->value, $verifiedAttempt->status);
        $this->assertEquals('TXN-BKASH-889900', $verifiedAttempt->gateway_transaction_id);
        $this->assertNotNull($verifiedAttempt->payment_receipt_id);

        // Check explicit gateway payment method
        $receipt = PaymentReceipt::findOrFail($verifiedAttempt->payment_receipt_id);
        $this->assertEquals(PaymentMethod::BKASH_GATEWAY, $receipt->payment_method);
        $this->assertEquals('1000.0000', (string)$receipt->amount);

        // Check wallet balance
        $this->wallet->refresh();
        $this->assertEquals('0.0000', (string)$this->wallet->current_balance); // Allocated entirely to the invoice

        // Check automatic FIFO allocation
        $invoice->refresh();
        $this->assertEquals(InvoiceStatus::PAID, $invoice->status);
        $this->assertEquals('1000.0000', (string)$invoice->amount_paid);

        // Check secrets redaction
        $storedPayload = $verifiedAttempt->gateway_payload;
        $this->assertEquals('[REDACTED]', $storedPayload['signature_key']);
        $this->assertEquals('[REDACTED]', $storedPayload['auth_token']);
    }

    /**
     * Test idempotency matrix: same attempt + same transaction ID returns idempotent success.
     */
    public function test_same_attempt_same_tx_id_returns_idempotent_success(): void
    {
        $initiation = $this->orchestrationService->initiatePayment(
            $this->tenant->id,
            $this->customer->id,
            PaymentGateway::NAGAD->value,
            '500.0000'
        );
        $attempt = $initiation['attempt'];

        $payload = [
            'attempt_reference' => $attempt->attempt_reference,
            'gateway_transaction_id' => 'TXN-NAGAD-112233',
        ];

        // First verification succeeds
        $firstResult = $this->orchestrationService->verifyPayment(PaymentGateway::NAGAD->value, $payload);

        // Second verification returns first result idempotently without duplicate receipt
        $secondResult = $this->orchestrationService->verifyPayment(PaymentGateway::NAGAD->value, $payload);

        $this->assertEquals($firstResult->id, $secondResult->id);
        $this->assertEquals(GatewayPaymentStatus::VERIFIED->value, $secondResult->status);

        // Wallet must have only BDT 500 credited once
        $this->wallet->refresh();
        $this->assertEquals('500.0000', (string)$this->wallet->current_balance);
        $this->assertEquals(1, PaymentReceipt::count());
    }

    /**
     * Test idempotency matrix: same attempt + different transaction ID rejects.
     */
    public function test_same_attempt_different_tx_id_fails(): void
    {
        $initiation = $this->orchestrationService->initiatePayment(
            $this->tenant->id,
            $this->customer->id,
            PaymentGateway::NAGAD->value,
            '500.0000'
        );
        $attempt = $initiation['attempt'];

        $payload1 = [
            'attempt_reference' => $attempt->attempt_reference,
            'gateway_transaction_id' => 'TXN-NAGAD-112233',
        ];

        $payload2 = [
            'attempt_reference' => $attempt->attempt_reference,
            'gateway_transaction_id' => 'TXN-NAGAD-445566',
        ];

        $this->orchestrationService->verifyPayment(PaymentGateway::NAGAD->value, $payload1);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("already verified with a different transaction ID");

        $this->orchestrationService->verifyPayment(PaymentGateway::NAGAD->value, $payload2);
    }

    /**
     * Test idempotency matrix: different attempt trying to reuse an already verified transaction ID rejects.
     */
    public function test_different_attempt_reusing_same_tx_id_rejects(): void
    {
        $initiation1 = $this->orchestrationService->initiatePayment(
            $this->tenant->id,
            $this->customer->id,
            PaymentGateway::SSLCOMMERZ->value,
            '400.0000'
        );
        $attempt1 = $initiation1['attempt'];

        $initiation2 = $this->orchestrationService->initiatePayment(
            $this->tenant->id,
            $this->customer->id,
            PaymentGateway::SSLCOMMERZ->value,
            '400.0000'
        );
        $attempt2 = $initiation2['attempt'];

        $payload1 = [
            'attempt_reference' => $attempt1->attempt_reference,
            'gateway_transaction_id' => 'TXN-SSL-7788',
        ];

        $payload2 = [
            'attempt_reference' => $attempt2->attempt_reference,
            'gateway_transaction_id' => 'TXN-SSL-7788', // Re-using same transaction ID!
        ];

        // Attempt 1 succeeds
        $this->orchestrationService->verifyPayment(PaymentGateway::SSLCOMMERZ->value, $payload1);

        // Attempt 2 must be rejected immediately to block double credit fraud!
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("has already been verified on a different payment attempt");

        $this->orchestrationService->verifyPayment(PaymentGateway::SSLCOMMERZ->value, $payload2);
    }

    /**
     * Test signature verification failure rejects webhook payload.
     */
    public function test_invalid_signature_rejects_verification(): void
    {
        $initiation = $this->orchestrationService->initiatePayment(
            $this->tenant->id,
            $this->customer->id,
            PaymentGateway::BKASH->value,
            '1000.0000'
        );
        $attempt = $initiation['attempt'];

        $payload = [
            'attempt_reference' => $attempt->attempt_reference,
            'gateway_transaction_id' => 'TXN-BKASH-77',
            'signature_valid' => false, // Instructs BkashDriver to return false
        ];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Payment verification failed.");

        $this->orchestrationService->verifyPayment(PaymentGateway::BKASH->value, $payload);
    }

    /**
     * Test tenant boundary isolation checks for attempts.
     */
    public function test_tenant_isolation_prevents_unauthorized_crossover(): void
    {
        $otherTenant = Tenant::create([
            'uuid' => '00000000-0000-0000-0000-000000000002',
            'slug' => 'tenant-b',
            'name' => 'Foreign Tenant',
            'status' => 'active',
            'plan_id' => $this->tenant->plan_id,
        ]);

        $otherCustomer = app(\App\Modules\Customer\Services\CustomerService::class)->createCustomer($otherTenant->id, [
            'type' => \App\Modules\Customer\Enums\CustomerType::RESIDENTIAL,
            'full_name' => 'Jane Smith',
            'phone' => '01800000002',
            'email' => 'jane@example.com',
        ]);

        $otherWallet = app(WalletService::class)->createWallet($otherTenant->id, Customer::class, $otherCustomer->id, 'BDT');

        // Initiate payment under Tenant B
        $initiation = $this->orchestrationService->initiatePayment(
            $otherTenant->id,
            $otherCustomer->id,
            PaymentGateway::BKASH->value,
            '300.0000'
        );
        $attempt = $initiation['attempt'];

        $payload = [
            'attempt_reference' => $attempt->attempt_reference,
            'gateway_transaction_id' => 'TXN-ISO-99',
        ];

        // Process verify Payment
        $verifiedAttempt = $this->orchestrationService->verifyPayment(
            PaymentGateway::BKASH->value,
            $payload
        );

        // Verify that the receipt is correctly resolved under Tenant B's boundary (isolation)
        $this->assertEquals($otherTenant->id, $verifiedAttempt->tenant_id);

        $receipt = PaymentReceipt::withoutGlobalScopes()
            ->where('tenant_id', $otherTenant->id)
            ->first();

        $this->assertNotNull($receipt);
        $this->assertEquals($otherCustomer->id, $receipt->customer_id);
    }

    /**
     * Test that FAILED is a terminal state and cannot transition to VERIFIED.
     */
    public function test_failed_attempt_is_terminal_and_cannot_be_verified(): void
    {
        $initiation = $this->orchestrationService->initiatePayment(
            $this->tenant->id,
            $this->customer->id,
            PaymentGateway::BKASH->value,
            '250.0000'
        );
        $attempt = $initiation['attempt'];

        // Force transition to FAILED status
        $attempt->status = GatewayPaymentStatus::FAILED->value;
        $attempt->save();

        $payload = [
            'attempt_reference' => $attempt->attempt_reference,
            'gateway_transaction_id' => 'TXN-FAIL-TERMINAL',
        ];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Payment verification failed.");

        $this->orchestrationService->verifyPayment(PaymentGateway::BKASH->value, $payload);
    }
}
