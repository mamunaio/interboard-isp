<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Tenant\Models\Plan;
use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Models\CustomerAddress;
use App\Modules\Customer\Models\CustomerServiceSubscription;
use App\Modules\Customer\Models\ServicePackage;
use App\Modules\Customer\Enums\CustomerType;
use App\Modules\Customer\Enums\CustomerStatus;
use App\Modules\Customer\Enums\ServiceType;
use App\Modules\Customer\Enums\SubscriptionStatus;
use App\Modules\Billing\Models\Wallet;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Models\InvoiceLineItem;
use App\Modules\Billing\Models\PaymentReceipt;
use App\Modules\Billing\Models\GatewayPaymentAttempt;
use App\Modules\Billing\Models\ReversalEvent;
use App\Modules\Billing\Models\SuspensionEvent;
use App\Modules\Billing\Enums\InvoiceStatus;
use App\Modules\Billing\Enums\InvoiceType;
use App\Modules\Billing\Enums\PaymentMethod;
use App\Modules\Billing\Enums\ReceiptStatus;
use App\Modules\Billing\Enums\PaymentGateway;
use App\Modules\Billing\Enums\GatewayPaymentStatus;
use App\Modules\Billing\Services\WalletService;
use App\Modules\Billing\Services\InvoiceService;
use App\Modules\Billing\Services\PaymentReceiptService;
use App\Modules\Billing\Services\ReversalAdjustmentService;
use App\Modules\Customer\Services\CustomerService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Default Admin User
        User::updateOrCreate(
            ['email' => 'admin@interboard.io'],
            [
                'name' => 'Interboard Executive Admin',
                'password' => bcrypt('password'),
                'is_superadmin' => true,
            ]
        );

        // 2. Create Tenant Plans
        $starterPlan = Plan::updateOrCreate(
            ['slug' => 'starter-50mbps'],
            ['name' => 'Starter 50 Mbps', 'price_monthly' => 1200.00, 'price_yearly' => 12000.00]
        );

        $ultraPlan = Plan::updateOrCreate(
            ['slug' => 'ultra-100mbps'],
            ['name' => 'Ultra 100 Mbps', 'price_monthly' => 2000.00, 'price_yearly' => 20000.00]
        );

        // 3. Create Tenant
        $tenant = Tenant::updateOrCreate(
            ['slug' => 'interboard-isp-dhaka'],
            [
                'uuid' => '00000000-0000-0000-0000-000000000001',
                'name' => 'Interboard ISP Dhaka Division',
                'status' => 'active',
                'plan_id' => $starterPlan->id,
            ]
        );

        // Map local domains to this tenant context to prevent 404 on localhost/127.0.0.1
        \App\Modules\Tenant\Models\TenantDomain::updateOrCreate(
            ['domain' => '127.0.0.1'],
            ['tenant_id' => $tenant->id, 'is_primary' => true]
        );

        \App\Modules\Tenant\Models\TenantDomain::updateOrCreate(
            ['domain' => 'localhost'],
            ['tenant_id' => $tenant->id, 'is_primary' => false]
        );

        // Bind current tenant to context
        app(\App\Core\Tenant\TenantManager::class)->setTenant($tenant);

        // Create realistic Service Packages
        $fiberStarterPackage = ServicePackage::updateOrCreate(
            ['package_code' => 'PKG-50M-FIBER'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Fiber Starter 50Mbps',
                'description' => 'High speed fiber broadband connection.',
                'service_type' => ServiceType::BROADBAND,
                'customer_type_compatibility' => ['residential'],
                'download_speed_kbps' => 51200,
                'upload_speed_kbps' => 51200,
                'is_active' => true,
            ]
        );

        $fiberEnterprisePackage = ServicePackage::updateOrCreate(
            ['package_code' => 'PKG-100M-BIZ'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Fiber Corporate 100Mbps',
                'description' => 'Dedicated symmetrical broadband connection.',
                'service_type' => ServiceType::BROADBAND,
                'customer_type_compatibility' => ['business'],
                'download_speed_kbps' => 102400,
                'upload_speed_kbps' => 102400,
                'is_active' => true,
            ]
        );

        // Services
        $customerService = app(CustomerService::class);
        $walletService = app(WalletService::class);
        $invoiceService = app(InvoiceService::class);
        $receiptService = app(PaymentReceiptService::class);
        $reversalService = app(ReversalAdjustmentService::class);

        // 4. Create Customers, Subscriptions, Wallets, and Invoices
        $customersData = [
            [
                'full_name' => 'Rahim Uddin',
                'phone' => '01711223344',
                'email' => 'rahim@gmail.com',
                'type' => CustomerType::RESIDENTIAL,
                'status' => CustomerStatus::ACTIVE,
                'sub_status' => SubscriptionStatus::ACTIVE,
                'package' => $fiberStarterPackage,
                'balance' => '500.0000',
                'invoice_amount' => '1200.0000',
                'is_paid' => true,
            ],
            [
                'full_name' => 'Nusrat Jahan',
                'phone' => '01822334455',
                'email' => 'nusrat@outlook.com',
                'type' => CustomerType::RESIDENTIAL,
                'status' => CustomerStatus::ACTIVE,
                'sub_status' => SubscriptionStatus::ACTIVE,
                'package' => $fiberStarterPackage,
                'balance' => '0.0000',
                'invoice_amount' => '2000.0000',
                'is_paid' => false,
            ],
            [
                'full_name' => 'Anisur Rahman',
                'phone' => '01933445566',
                'email' => 'anis@corporate-bd.com',
                'type' => CustomerType::BUSINESS,
                'status' => CustomerStatus::SUSPENDED,
                'sub_status' => SubscriptionStatus::SUSPENDED,
                'package' => $fiberEnterprisePackage,
                'balance' => '0.0000',
                'invoice_amount' => '5000.0000',
                'is_paid' => false,
            ],
            [
                'full_name' => 'Tariqul Islam',
                'phone' => '01544556677',
                'email' => 'tariq@gmail.com',
                'type' => CustomerType::RESIDENTIAL,
                'status' => CustomerStatus::ACTIVE,
                'sub_status' => SubscriptionStatus::ACTIVE,
                'package' => $fiberStarterPackage,
                'balance' => '1500.0000',
                'invoice_amount' => '1200.0000',
                'is_paid' => true,
            ],
        ];

        foreach ($customersData as $data) {
            // Create Customer
            $customer = $customerService->createCustomer($tenant->id, [
                'type' => $data['type'],
                'full_name' => $data['full_name'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'status' => $data['status'],
            ]);

            // Create Subscription
            $sub = CustomerServiceSubscription::create([
                'tenant_id' => $tenant->id,
                'customer_id' => $customer->id,
                'service_package_id' => $data['package']->id,
                'subscription_code' => 'SUB-' . strtoupper(bin2hex(random_bytes(4))),
                'service_type_snapshot' => ServiceType::BROADBAND,
                'status' => $data['sub_status'],
            ]);

            // Create Customer Address
            CustomerAddress::create([
                'tenant_id' => $tenant->id,
                'customer_id' => $customer->id,
                'label' => 'Primary',
                'type' => 'billing',
                'address_line_1' => 'Dhaka Division, Bangladesh',
                'city' => 'Dhaka',
                'postal_code' => '1200',
                'country' => 'Bangladesh',
            ]);

            // Initialize Wallet
            $wallet = $walletService->createWallet($tenant->id, Customer::class, $customer->id, 'BDT');

            // Create Invoice
            $invoice = $invoiceService->createInvoice($tenant->id, $customer->id, [
                'invoice_type' => InvoiceType::SUBSCRIPTION->value,
                'issue_date' => now()->subDays(5)->toDateString(),
                'due_date' => now()->addDays(20)->toDateString(),
            ], [
                [
                    'line_type' => 'service',
                    'description' => 'Monthly Fiber Connectivity Package',
                    'quantity' => '1.0000',
                    'unit_price' => $data['invoice_amount'],
                ]
            ]);

            $invoiceService->transitionInvoiceStatus($invoice, InvoiceStatus::ISSUED);

            // If Paid, create a payment receipt
            if ($data['is_paid']) {
                $receipt = $receiptService->createReceipt($tenant->id, [
                    'customer_id' => $customer->id,
                    'amount' => $data['invoice_amount'],
                    'currency' => 'BDT',
                    'payment_method' => PaymentMethod::BKASH_GATEWAY,
                    'reference' => 'BKASH-' . strtoupper(bin2hex(random_bytes(4))),
                    'auto_allocate' => true,
                    'notes' => 'Customer online payment checkout.',
                ]);
            }

            // Add excess balance if requested
            if (bccomp($data['balance'], '0.0000', 4) > 0) {
                $receiptService->createReceipt($tenant->id, [
                    'customer_id' => $customer->id,
                    'amount' => $data['balance'],
                    'currency' => 'BDT',
                    'payment_method' => PaymentMethod::CASH,
                    'reference' => 'CASH-DEP-' . strtoupper(bin2hex(random_bytes(3))),
                    'auto_allocate' => true,
                    'notes' => 'Excess credit deposit.',
                ]);
            }

            // Seed RADIUS check and reply rules
            \App\Modules\Network\Models\RadCheck::create([
                'tenant_id' => $tenant->id,
                'subscription_id' => $sub->id,
                'username' => $sub->subscription_code,
                'attribute' => 'Cleartext-Password',
                'op' => ':=',
                'value' => 'pass_' . substr($sub->subscription_code, 4),
            ]);

            $rateLimit = '10M/10M';
            if ($data['package']->download_speed_kbps > 0 && $data['package']->upload_speed_kbps > 0) {
                $rateLimit = sprintf(
                    '%dK/%dK',
                    $data['package']->upload_speed_kbps,
                    $data['package']->download_speed_kbps
                );
            }

            \App\Modules\Network\Models\RadReply::create([
                'tenant_id' => $tenant->id,
                'subscription_id' => $sub->id,
                'username' => $sub->subscription_code,
                'attribute' => 'Mikrotik-Rate-Limit',
                'op' => '=',
                'value' => $rateLimit,
            ]);

            // If Suspended, create a Suspension Event and manual adjustment
            if ($data['status'] === CustomerStatus::SUSPENDED) {
                SuspensionEvent::create([
                    'tenant_id' => $tenant->id,
                    'customer_service_subscription_id' => $sub->id,
                    'action_type' => 'suspended',
                    'reason' => 'Non-payment of monthly billing invoice.',
                    'triggering_metadata' => ['invoice_id' => $invoice->id],
                    'created_by_type' => null,
                    'created_by_id' => null,
                ]);

                // Seed Permission and Role
                $perm = \App\Modules\Auth\Models\Permission::firstOrCreate(
                    ['slug' => 'billing.adjustment.manage'],
                    [
                        'module' => 'billing',
                        'action' => 'manage_adjustment',
                        'description' => 'Allows managing manual billing adjustments',
                    ]
                );

                $managerRole = \App\Modules\Auth\Models\Role::firstOrCreate(
                    ['slug' => 'billing-manager', 'tenant_id' => $tenant->id],
                    [
                        'name' => 'Billing Manager Role',
                        'description' => 'Role for billing managers.',
                    ]
                );

                // Assign permission to role
                $managerRole->permissions()->syncWithoutDetaching([$perm->id]);

                // Create a manual debit adjustment for recovery
                $staff = \App\Modules\Auth\Models\StaffUser::firstOrCreate(
                    ['email' => 'manager@interboard.io'],
                    [
                        'name' => 'Billing Manager',
                        'password' => bcrypt('password'),
                        'tenant_id' => $tenant->id,
                    ]
                );

                // Assign role to staff
                $staff->roles()->syncWithoutDetaching([$managerRole->id]);
                
                $reversalService->adjustBalance(
                    $tenant->id,
                    $customer->id,
                    \App\Modules\Billing\Enums\TransactionType::CREDIT_ADJUSTMENT->value,
                    '150.0000',
                    'BDT',
                    'Manual Connection Restoration Fee.',
                    $staff
                );
            }
        }

        // 5. Create realistic Gateway Payment Attempts
        $customers = Customer::all();
        $attemptCount = 0;
        foreach ($customers as $c) {
            $attemptCount++;
            GatewayPaymentAttempt::create([
                'tenant_id' => $tenant->id,
                'customer_id' => $c->id,
                'gateway' => PaymentGateway::BKASH->value,
                'attempt_reference' => 'ATT-BKASH-' . $attemptCount,
                'requested_amount' => '1200.0000',
                'currency' => 'BDT',
                'status' => $attemptCount === 1 ? GatewayPaymentStatus::PENDING->value : GatewayPaymentStatus::VERIFIED->value,
                'gateway_transaction_id' => $attemptCount === 1 ? null : 'TXN-' . strtoupper(bin2hex(random_bytes(4))),
                'verified_at' => $attemptCount === 1 ? null : now(),
            ]);
        }

        // 6. Seed OLT Devices
        $olt1 = \App\Modules\Network\Models\OltDevice::create([
            'tenant_id' => $tenant->id,
            'name' => 'Core OLT Dhaka (ZTE)',
            'ip_address' => '10.100.10.10',
            'port' => 23,
            'username' => 'admin',
            'password' => 'zteolt123',
            'vendor' => 'zte',
            'status' => 'online',
        ]);

        $olt2 = \App\Modules\Network\Models\OltDevice::create([
            'tenant_id' => $tenant->id,
            'name' => 'Banani POP OLT (Huawei)',
            'ip_address' => '10.200.20.10',
            'port' => 23,
            'username' => 'superadmin',
            'password' => 'huawei456',
            'vendor' => 'huawei',
            'status' => 'online',
        ]);

        // 7. Seed ONU Devices
        // Find one customer subscription to link
        $firstSub = CustomerServiceSubscription::where('tenant_id', $tenant->id)->first();
        if ($firstSub) {
            \App\Modules\Network\Models\OnuDevice::create([
                'tenant_id' => $tenant->id,
                'subscription_id' => $firstSub->id,
                'olt_device_id' => $olt1->id,
                'serial_number' => 'ZTEG8B2C4D6E',
                'gpon_port' => '0/1/1',
                'onu_id' => 1,
                'line_profile' => 'GPON-LINE-DEFAULT',
                'service_profile' => 'GPON-SERV-DEFAULT',
                'status' => 'active',
            ]);
        }
    }
}
