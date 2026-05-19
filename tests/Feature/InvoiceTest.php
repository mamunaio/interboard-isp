<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Tenant\TenantManager;
use App\Modules\Tenant\Models\Plan;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Models\ServicePackage;
use App\Modules\Customer\Models\CustomerServiceSubscription;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Models\InvoiceLineItem;
use App\Modules\Billing\Enums\InvoiceType;
use App\Modules\Billing\Enums\InvoiceStatus;
use App\Modules\Customer\Enums\CustomerType;
use App\Modules\Customer\Enums\ServiceType;
use App\Modules\Customer\Services\CustomerService;
use App\Modules\Customer\Services\ContractPackageService;
use App\Modules\Customer\Services\SubscriptionService;
use App\Modules\Billing\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected Plan $plan;
    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected CustomerService $customerService;
    protected ContractPackageService $contractPackageService;
    protected SubscriptionService $subscriptionService;
    protected InvoiceService $invoiceService;
    protected Customer $customerA;
    protected Customer $customerB;
    protected CustomerServiceSubscription $subscriptionA;
    protected CustomerServiceSubscription $subscriptionB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plan = Plan::create([
            'name' => 'Premium Plan',
            'slug' => 'premium',
            'price_monthly' => 50.00,
            'price_yearly' => 500.00,
        ]);

        $this->tenantA = Tenant::create([
            'uuid' => '00000000-0000-0000-0000-000000000201',
            'slug' => 'tenant-201',
            'name' => 'Tenant 201',
            'status' => 'active',
            'plan_id' => $this->plan->id,
        ]);

        $this->tenantB = Tenant::create([
            'uuid' => '00000000-0000-0000-0000-000000000202',
            'slug' => 'tenant-202',
            'name' => 'Tenant 202',
            'status' => 'active',
            'plan_id' => $this->plan->id,
        ]);

        $this->customerService = app(CustomerService::class);
        $this->contractPackageService = app(ContractPackageService::class);
        $this->subscriptionService = app(SubscriptionService::class);
        $this->invoiceService = app(InvoiceService::class);

        $tenantManager = app(TenantManager::class);

        // Setup Tenant A Customer & Subscription
        $tenantManager->setTenant($this->tenantA);
        $this->customerA = $this->customerService->createCustomer($this->tenantA->id, [
            'type' => CustomerType::RESIDENTIAL,
            'full_name' => 'Customer A',
            'phone' => '01900000001',
        ]);

        $pkgA = $this->contractPackageService->createServicePackage($this->tenantA->id, [
            'package_code' => 'PKG-BB-A',
            'name' => 'Broadband 10M',
            'service_type' => ServiceType::BROADBAND,
            'download_speed_kbps' => 10240,
            'upload_speed_kbps' => 10240,
            'customer_type_compatibility' => [CustomerType::RESIDENTIAL->value],
        ]);

        $this->subscriptionA = $this->subscriptionService->createSubscription($this->tenantA->id, (int)$this->customerA->id, [
            'service_package_id' => $pkgA->id,
        ]);

        // Setup Tenant B Customer & Subscription
        $tenantManager->setTenant($this->tenantB);
        $this->customerB = $this->customerService->createCustomer($this->tenantB->id, [
            'type' => CustomerType::RESIDENTIAL,
            'full_name' => 'Customer B',
            'phone' => '01900000002',
        ]);

        $pkgB = $this->contractPackageService->createServicePackage($this->tenantB->id, [
            'package_code' => 'PKG-BB-B',
            'name' => 'Broadband 20M',
            'service_type' => ServiceType::BROADBAND,
            'download_speed_kbps' => 20480,
            'upload_speed_kbps' => 20480,
            'customer_type_compatibility' => [CustomerType::RESIDENTIAL->value],
        ]);

        $this->subscriptionB = $this->subscriptionService->createSubscription($this->tenantB->id, (int)$this->customerB->id, [
            'service_package_id' => $pkgB->id,
        ]);
    }

    /**
     * Test high-precision decimal calculations (DECIMAL(14,4) for quantites, unit prices, and subtotals).
     */
    public function test_invoice_creation_calculates_high_precision_decimals(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $lineItems = [
            [
                'line_type' => 'service',
                'description' => 'Dedicated Bandwidth Connection',
                'quantity' => '1.5000', // Fractional quantity supported!
                'unit_price' => '150.2500',
            ],
            [
                'line_type' => 'hardware',
                'description' => 'SFP Optical Transceiver',
                'quantity' => '2.0000',
                'unit_price' => '25.0000',
            ]
        ];

        $invoice = $this->invoiceService->createInvoice($this->tenantA->id, (int)$this->customerA->id, [
            'invoice_type' => InvoiceType::SUBSCRIPTION->value,
            'issue_date' => '2026-05-19',
            'due_date' => '2026-05-26',
            'tax_total' => '10.5000',
            'discount_total' => '5.0000',
            'customer_service_subscription_id' => $this->subscriptionA->id,
        ], $lineItems);

        $this->assertNotNull($invoice);
        // Expected subtotal: (1.5 * 150.25) + (2.0 * 25.0) = 225.375 + 50.0 = 275.3750
        $this->assertEquals('275.3750', $invoice->subtotal);

        // Expected grand total: 275.3750 + 10.5000 - 5.0000 = 280.8750
        $this->assertEquals('280.8750', $invoice->grand_total);
        $this->assertEquals(InvoiceStatus::DRAFT, $invoice->status);
    }

    /**
     * Test strict subtotal verification: throws ValidationException if custom line subtotal mismatches.
     */
    public function test_invoice_creation_rejects_mismatched_client_subtotals(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $lineItems = [
            [
                'line_type' => 'service',
                'description' => 'Bandwidth',
                'quantity' => '1.0000',
                'unit_price' => '100.0000',
                'line_subtotal' => '95.0000', // Mismatch! (Malicious discount injection)
            ]
        ];

        try {
            $this->invoiceService->createInvoice($this->tenantA->id, (int)$this->customerA->id, [
                'issue_date' => '2026-05-19',
                'due_date' => '2026-05-26',
            ], $lineItems);
            $this->fail('Invoice created with mismatched client subtotal.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('line_items', $e->errors());
        }
    }

    /**
     * Test deterministic sequence number INV-2026-000001 generation.
     */
    public function test_invoice_creation_uses_deterministic_invoice_numbers(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $lines = [['quantity' => '1.0000', 'unit_price' => '10.0000']];

        $inv1 = $this->invoiceService->createInvoice($this->tenantA->id, (int)$this->customerA->id, [
            'issue_date' => '2026-05-19',
            'due_date' => '2026-05-26',
        ], $invLine1 = $lines);

        $inv2 = $this->invoiceService->createInvoice($this->tenantA->id, (int)$this->customerA->id, [
            'issue_date' => '2026-05-19',
            'due_date' => '2026-05-26',
        ], $invLine2 = $lines);

        $this->assertNotEquals($inv1->invoice_number, $inv2->invoice_number);
        $this->assertMatchesRegularExpression('/^INV-\d{4}-000001$/', $inv1->invoice_number);
        $this->assertMatchesRegularExpression('/^INV-\d{4}-000002$/', $inv2->invoice_number);
    }

    /**
     * Test strict issued immutability rule: Issued invoices cannot be edited.
     */
    public function test_issued_invoices_are_completely_immutable(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $lines = [['quantity' => '1.0000', 'unit_price' => '10.0000']];

        $invoice = $this->invoiceService->createInvoice($this->tenantA->id, (int)$this->customerA->id, [
            'issue_date' => '2026-05-19',
            'due_date' => '2026-05-26',
        ], $lines);

        // Transition to issued
        $this->invoiceService->transitionInvoiceStatus($invoice, InvoiceStatus::ISSUED);
        $this->assertEquals(InvoiceStatus::ISSUED, $invoice->status);

        // Attempting to update details or lines should fail
        try {
            $this->invoiceService->updateInvoice($invoice, [
                'notes' => 'New notes details description',
            ]);
            $this->fail('Issued invoice notes was modified.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
        }
    }

    /**
     * Test strict status transition bounds.
     */
    public function test_invoice_status_transitions_limitations(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $lines = [['quantity' => '1.0000', 'unit_price' => '10.0000']];

        $invoice = $this->invoiceService->createInvoice($this->tenantA->id, (int)$this->customerA->id, [
            'issue_date' => '2026-05-19',
            'due_date' => '2026-05-26',
        ], $lines);

        // 1. draft -> void is NOT allowed (must draft -> issued first or draft -> cancelled)
        try {
            $this->invoiceService->transitionInvoiceStatus($invoice, InvoiceStatus::VOID);
            $this->fail('Draft invoice transitioned to void.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
        }

        // 2. draft -> issued is allowed
        $this->invoiceService->transitionInvoiceStatus($invoice, InvoiceStatus::ISSUED);
        $this->assertEquals(InvoiceStatus::ISSUED, $invoice->fresh()->status);

        // 3. issued -> void is allowed
        $this->invoiceService->transitionInvoiceStatus($invoice, InvoiceStatus::VOID);
        $this->assertEquals(InvoiceStatus::VOID, $invoice->fresh()->status);
    }

    /**
     * Test tenant leakage protection.
     */
    public function test_invoice_creation_validates_customer_and_subscription_tenant_leakage(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $lines = [['quantity' => '1.0000', 'unit_price' => '10.0000']];

        // 1. Try creating invoice under Tenant A using Tenant B customer (must fail)
        try {
            $this->invoiceService->createInvoice($this->tenantA->id, (int)$this->customerB->id, [
                'issue_date' => '2026-05-19',
                'due_date' => '2026-05-26',
            ], $lines);
            $this->fail('Invoice created using different tenant customer.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('customer_id', $e->errors());
        }

        // 2. Try creating invoice under Tenant A using Tenant B subscription (must fail)
        try {
            $this->invoiceService->createInvoice($this->tenantA->id, (int)$this->customerA->id, [
                'issue_date' => '2026-05-19',
                'due_date' => '2026-05-26',
                'customer_service_subscription_id' => $this->subscriptionB->id,
            ], $lines);
            $this->fail('Invoice created using different tenant subscription.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('customer_service_subscription_id', $e->errors());
        }
    }

    /**
     * Test negative grand total rejection (discount exceeding subtotal + tax).
     */
    public function test_invoice_creation_rejects_negative_grand_total(): void
    {
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenantA);

        $lines = [['quantity' => '1.0000', 'unit_price' => '10.0000']];

        try {
            $this->invoiceService->createInvoice($this->tenantA->id, (int)$this->customerA->id, [
                'issue_date' => '2026-05-19',
                'due_date' => '2026-05-26',
                'tax_total' => '2.0000',
                'discount_total' => '15.0000', // Exceeds 10 + 2 = 12!
            ], $lines);
            $this->fail('Invoice created with negative grand total (discount exceeding subtotal + tax).');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('discount_total', $e->errors());
        }
    }
}

