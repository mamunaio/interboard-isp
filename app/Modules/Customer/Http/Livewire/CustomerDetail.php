<?php

declare(strict_types=1);

namespace App\Modules\Customer\Http\Livewire;

use App\Core\Tenant\TenantManager;
use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Models\CustomerServiceSubscription;
use App\Modules\Customer\Models\ServicePlan;
use App\Modules\Customer\Enums\SubscriptionStatus;
use App\Modules\Customer\Enums\ServiceType;
use App\Modules\Billing\Enums\BillingMode;
use App\Modules\Billing\Enums\BillingCycleType;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Services\BillingGenerationService;
use App\Modules\Customer\Services\CustomerLifecycleService;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

use App\Modules\Billing\Enums\InvoiceStatus;
use App\Modules\Billing\Models\LedgerEntry;
use App\Modules\Provisioning\Models\ServiceProvisioningEvent;
use App\Modules\Provisioning\Enums\ProvisioningActionType;
use App\Modules\Provisioning\Enums\ProvisioningStatus;
use App\Modules\Provisioning\Jobs\ProcessProvisioningJob;

class CustomerDetail extends Component
{
    public int $customerId;
    
    // Assign Plan Form States
    public bool $isAssignModalOpen = false;
    public ?int $selectedPlanId = null;
    public string $billingMode = 'prepaid';
    public string $billingCycleType = 'anniversary';
    public string $nextBillingDate = '';
    public string $notes = '';

    public function mount(int $id): void
    {
        $this->customerId = $id;
        $this->nextBillingDate = now()->toDateString();
    }

    public function getCustomerProperty(): Customer
    {
        $tenantId = app(TenantManager::class)->getTenantId();
        return Customer::where('tenant_id', $tenantId)->findOrFail($this->customerId);
    }

    public function openAssignModal(): void
    {
        $this->selectedPlanId = null;
        $this->billingMode = 'prepaid';
        $this->billingCycleType = 'anniversary';
        $this->nextBillingDate = now()->toDateString();
        $this->notes = '';
        $this->isAssignModalOpen = true;
    }

    public function assignPlan(): void
    {
        $this->validate([
            'selectedPlanId' => 'required|exists:service_plans,id',
            'billingMode' => 'required|string|in:prepaid,postpaid',
            'billingCycleType' => 'required|string|in:anniversary,calendar',
            'nextBillingDate' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $tenantId = app(TenantManager::class)->getTenantId();
        $plan = ServicePlan::where('tenant_id', $tenantId)->findOrFail($this->selectedPlanId);

        DB::transaction(function () use ($tenantId, $plan) {
            // Generate unique subscription code
            $count = CustomerServiceSubscription::where('tenant_id', $tenantId)->count() + 1;
            $subCode = 'SUB-' . now()->format('Y') . '-' . str_pad((string)$count, 6, '0', STR_PAD_LEFT);

            // Align next billing date if CALENDAR mode
            $nbd = \Carbon\Carbon::parse($this->nextBillingDate);
            if ($this->billingCycleType === 'calendar') {
                $nbd = $nbd->startOfMonth(); // Enforce day 1
            }

            $sub = CustomerServiceSubscription::create([
                'tenant_id' => $tenantId,
                'customer_id' => $this->customerId,
                'service_plan_id' => $plan->id,
                'subscription_code' => $subCode,
                'service_type_snapshot' => ServiceType::BROADBAND,
                'status' => SubscriptionStatus::ACTIVE,
                'billing_mode' => BillingMode::from($this->billingMode),
                'billing_cycle_type' => BillingCycleType::from($this->billingCycleType),
                'next_billing_date' => $nbd->toDateString(),
                'activated_at' => now(),
                'notes' => $this->notes ?: null,
            ]);

            // Dispatch Provisioning Event to connect the user
            $provisioningEvent = ServiceProvisioningEvent::create([
                'tenant_id' => $tenantId,
                'customer_id' => $sub->customer_id,
                'subscription_id' => $sub->id,
                'action_type' => ProvisioningActionType::PROVISION,
                'status' => ProvisioningStatus::PENDING,
                'provider' => 'mikrotik',
                'request_payload_json' => [
                    'username' => $sub->subscription_code,
                    'password' => \Illuminate\Support\Str::random(10), // Generate a strong random PPP password
                    'profile' => $plan->plan_code,
                ],
            ]);

            ProcessProvisioningJob::dispatch($provisioningEvent->id);
        });

        $this->isAssignModalOpen = false;
        session()->flash('message', 'Plan assigned and subscription activated successfully.');
    }

    public function generateInvoice(int $subscriptionId): void
    {
        $tenantId = app(TenantManager::class)->getTenantId();
        
        try {
            $billingService = app(BillingGenerationService::class);
            $invoice = $billingService->generateInvoiceForSubscription(
                $tenantId, 
                $subscriptionId, 
                now()->toDateString()
            );

            session()->flash('message', "Invoice {$invoice->invoice_number} generated successfully.");
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function suspendCustomer(): void
    {
        try {
            $lifecycleService = app(CustomerLifecycleService::class);
            $lifecycleService->suspendCustomer($this->customer, 'Manual suspension by admin');
            session()->flash('message', 'Customer has been suspended successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to suspend: ' . $e->getMessage());
        }
    }

    public function reactivateCustomer(): void
    {
        try {
            $lifecycleService = app(CustomerLifecycleService::class);
            $lifecycleService->activateCustomer($this->customer, 'Manual reactivation by admin');
            session()->flash('message', 'Customer has been reactivated successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to reactivate: ' . $e->getMessage());
        }
    }

    public function terminateCustomer(): void
    {
        try {
            $lifecycleService = app(CustomerLifecycleService::class);
            $lifecycleService->churnCustomer($this->customer, 'Manual termination by admin');
            session()->flash('message', 'Customer has been permanently disconnected (Churned).');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to terminate: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $customer = $this->customer;
        $tenantId = app(TenantManager::class)->getTenantId();

        $subscriptions = CustomerServiceSubscription::where('tenant_id', $tenantId)
            ->where('customer_id', $this->customerId)
            ->with(['servicePlan', 'servicePackage'])
            ->orderBy('id', 'desc')
            ->get();

        $invoices = Invoice::where('tenant_id', $tenantId)
            ->where('customer_id', $this->customerId)
            ->orderBy('id', 'desc')
            ->get();

        $availablePlans = ServicePlan::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        $wallet = \App\Modules\Billing\Models\Wallet::where('tenant_id', $tenantId)
            ->where('owner_type', Customer::class)
            ->where('owner_id', $this->customerId)
            ->first();

        $recentReceipts = \App\Modules\Billing\Models\PaymentReceipt::where('tenant_id', $tenantId)
            ->where('customer_id', $this->customerId)
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        $totalInvoicesCount = $invoices->count();
        $paidInvoicesCount = $invoices->where('status', InvoiceStatus::PAID)->count();
        $unpaidInvoicesCount = $invoices->whereIn('status', [InvoiceStatus::ISSUED, InvoiceStatus::PARTIALLY_PAID])->count();
        $overdueAmount = $invoices->whereIn('status', [InvoiceStatus::ISSUED, InvoiceStatus::PARTIALLY_PAID])
            ->where('due_date', '<', now()->startOfDay())
            ->sum(fn ($inv) => (float)$inv->amount_due);

        $recentTransactions = [];
        if ($wallet) {
            $recentTransactions = LedgerEntry::where('tenant_id', $tenantId)
                ->where('wallet_id', $wallet->id)
                ->orderBy('id', 'desc')
                ->take(5)
                ->get();
        }

        return view('customer::livewire.customer-detail', [
            'customer' => $customer,
            'subscriptions' => $subscriptions,
            'invoices' => $invoices,
            'availablePlans' => $availablePlans,
            'wallet' => $wallet,
            'recentReceipts' => $recentReceipts,
            'totalInvoicesCount' => $totalInvoicesCount,
            'paidInvoicesCount' => $paidInvoicesCount,
            'unpaidInvoicesCount' => $unpaidInvoicesCount,
            'overdueAmount' => $overdueAmount,
            'recentTransactions' => $recentTransactions,
        ]);
    }
}
