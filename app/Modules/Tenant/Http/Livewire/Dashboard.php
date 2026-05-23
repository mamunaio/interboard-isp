<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Http\Livewire;

use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Enums\CustomerStatus;
use App\Modules\Customer\Models\CustomerServiceSubscription;
use App\Modules\Customer\Enums\SubscriptionStatus;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Enums\InvoiceStatus;
use App\Modules\Billing\Models\Wallet;
use App\Modules\Billing\Models\PaymentReceipt;
use App\Modules\Billing\Models\SuspensionEvent;
use App\Modules\Billing\Models\ReversalEvent;
use App\Modules\Billing\Models\GatewayPaymentAttempt;
use App\Modules\Billing\Enums\GatewayPaymentStatus;
use Livewire\Component;

class Dashboard extends Component
{
    public array $stats = [];
    public array $recentReceipts = [];
    public array $recentSuspensions = [];
    public array $recentReversals = [];
    public array $gatewaySummary = [];
    public array $notifications = [];

    /**
     * Get the listeners for real-time Reverb broadcasts.
     */
    public function getListeners(): array
    {
        return [
            "echo-private:tenant.dashboard,PaymentReceived" => 'onPaymentReceived',
            "echo-private:tenant.dashboard,SuspensionTriggered" => 'onSuspensionTriggered',
            "echo-private:tenant.dashboard,ReversalCompleted" => 'onReversalCompleted',
            "echo-private:tenant.dashboard,GatewayStatusUpdated" => 'onGatewayStatusUpdated',
        ];
    }

    public function mount(): void
    {
        $this->refreshStats();
    }

    public function refreshStats(): void
    {
        // 1. Total active customers
        $activeCustomers = Customer::where('status', CustomerStatus::ACTIVE->value)->count();

        // 2. Total active subscriptions
        $activeSubscriptions = CustomerServiceSubscription::where('status', SubscriptionStatus::ACTIVE->value)->count();

        // 3. Unpaid invoices count
        $unpaidInvoices = Invoice::whereIn('status', [
            InvoiceStatus::ISSUED->value,
            InvoiceStatus::PARTIALLY_PAID->value,
        ])->count();

        // 4. Overdue invoices count
        $overdueInvoices = Invoice::whereIn('status', [
            InvoiceStatus::ISSUED->value,
            InvoiceStatus::PARTIALLY_PAID->value,
        ])->where('due_date', '<', now()->toDateString())->count();

        // 5. Total wallet balances
        $totalWalletBalance = Wallet::sum('current_balance');

        $this->stats = [
            'active_customers' => $activeCustomers,
            'active_subscriptions' => $activeSubscriptions,
            'unpaid_invoices' => $unpaidInvoices,
            'overdue_invoices' => $overdueInvoices,
            'total_wallet_balance' => $totalWalletBalance,
        ];

        // 6. Recent payment receipts
        $this->recentReceipts = PaymentReceipt::with('customer')
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get()
            ->toArray();

        // 7. Recent suspension events
        $this->recentSuspensions = SuspensionEvent::with(['subscription.customer'])
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get()
            ->toArray();

        // 8. Recent reversal events
        $this->recentReversals = ReversalEvent::with(['originalEntity'])
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get()
            ->toArray();

        // 9. Gateway attempt status summary
        $statuses = [
            GatewayPaymentStatus::INITIATED->value => 0,
            GatewayPaymentStatus::PENDING->value => 0,
            GatewayPaymentStatus::VERIFIED->value => 0,
            GatewayPaymentStatus::FAILED->value => 0,
            GatewayPaymentStatus::EXPIRED->value => 0,
        ];
        $counts = GatewayPaymentAttempt::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        $this->gatewaySummary = array_merge($statuses, $counts);
    }

    public function onPaymentReceived(array $event): void
    {
        array_unshift($this->notifications, [
            'type' => 'payment',
            'message' => "Payment received: BDT {$event['amount']} for {$event['customerName']} (Receipt: {$event['receiptNumber']})",
            'time' => $event['timestamp'],
        ]);
        $this->refreshStats();
    }

    public function onSuspensionTriggered(array $event): void
    {
        array_unshift($this->notifications, [
            'type' => 'suspension',
            'message' => "Subscription suspended: {$event['subscriptionCode']} for {$event['customerName']} (Reason: {$event['reason']})",
            'time' => $event['timestamp'],
        ]);
        $this->refreshStats();
    }

    public function onReversalCompleted(array $event): void
    {
        array_unshift($this->notifications, [
            'type' => 'reversal',
            'message' => "Payment reversed: Receipt {$event['receiptNumber']} of BDT {$event['amount']} for {$event['customerName']}",
            'time' => $event['timestamp'],
        ]);
        $this->refreshStats();
    }

    public function onGatewayStatusUpdated(array $event): void
    {
        array_unshift($this->notifications, [
            'type' => 'gateway',
            'message' => "Gateway attempt {$event['attemptReference']} ({$event['gateway']}) status updated to: {$event['status']} (Amount: {$event['amount']})",
            'time' => $event['timestamp'],
        ]);
        $this->refreshStats();
    }

    public function clearNotifications(): void
    {
        $this->notifications = [];
    }

    public function render()
    {
        return view('tenant::livewire.dashboard');
    }
}
