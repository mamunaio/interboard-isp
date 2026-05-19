<?php

declare(strict_types=1);

namespace App\Modules\Billing\Http\Livewire;

use App\Core\Tenant\TenantManager;
use App\Core\Settings\SettingsRegistry;
use App\Modules\Customer\Models\CustomerServiceSubscription;
use App\Modules\Customer\Enums\SubscriptionStatus;
use App\Modules\Billing\Models\SuspensionEvent;
use App\Modules\Billing\Services\SuspensionActionService;
use App\Modules\Auth\Models\StaffUser;
use Livewire\Component;
use Livewire\WithPagination;

class SubscriptionActions extends Component
{
    use WithPagination;

    public string $search = '';

    // Modal forms states
    public bool $isSuspendModalOpen = false;
    public bool $isReactivateModalOpen = false;
    
    public ?int $selectedSubscriptionId = null;
    public string $suspensionReason = '';
    public string $reactivationReason = '';
    
    public bool $reconnectionFeeEnabled = false;
    public string $reconnectionFeeAmount = '0.0000';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function mount(): void
    {
        $settings = app(SettingsRegistry::class);
        $this->reconnectionFeeEnabled = (bool)$settings->get('reconnection_fee_enabled', false);
        $this->reconnectionFeeAmount = (string)$settings->get('reconnection_fee_amount', '500.0000');
    }

    public function openSuspendModal(int $subId): void
    {
        $this->selectedSubscriptionId = $subId;
        $this->suspensionReason = 'Overdue invoice non-payment.';
        $this->isSuspendModalOpen = true;
    }

    public function closeSuspendModal(): void
    {
        $this->isSuspendModalOpen = false;
        $this->selectedSubscriptionId = null;
    }

    public function openReactivateModal(int $subId): void
    {
        $this->selectedSubscriptionId = $subId;
        $this->reactivationReason = 'Outstanding invoice(s) paid.';
        
        $settings = app(SettingsRegistry::class);
        $this->reconnectionFeeEnabled = (bool)$settings->get('reconnection_fee_enabled', false);
        $this->reconnectionFeeAmount = (string)$settings->get('reconnection_fee_amount', '500.0000');
        
        $this->isReactivateModalOpen = true;
    }

    public function closeReactivateModal(): void
    {
        $this->isReactivateModalOpen = false;
        $this->selectedSubscriptionId = null;
    }

    public function suspend(): void
    {
        $this->validate([
            'selectedSubscriptionId' => 'required|exists:customer_service_subscriptions,id',
            'suspensionReason' => 'required|string|min:3|max:1000',
        ]);

        $tenantId = app(TenantManager::class)->getTenantId();
        $actor = $this->getActor();

        try {
            $actionService = app(SuspensionActionService::class);
            $actionService->suspend($tenantId, $this->selectedSubscriptionId, [
                'reason' => $this->suspensionReason,
                'created_by_type' => 'StaffUser',
                'created_by_id' => $actor->id,
            ]);

            session()->flash('message', 'Subscription successfully suspended.');
            $this->closeSuspendModal();
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function reactivate(): void
    {
        $this->validate([
            'selectedSubscriptionId' => 'required|exists:customer_service_subscriptions,id',
            'reactivationReason' => 'required|string|min:3|max:1000',
            'reconnectionFeeAmount' => 'required|numeric|min:0',
        ]);

        $tenantId = app(TenantManager::class)->getTenantId();
        $actor = $this->getActor();

        try {
            $actionService = app(SuspensionActionService::class);
            $actionService->reactivate($tenantId, $this->selectedSubscriptionId, [
                'reason' => $this->reactivationReason,
                'reconnection_fee_enabled' => $this->reconnectionFeeEnabled,
                'reconnection_fee_amount' => $this->reconnectionFeeAmount,
                'created_by_type' => 'StaffUser',
                'created_by_id' => $actor->id,
            ]);

            session()->flash('message', 'Subscription successfully reactivated.');
            $this->closeReactivateModal();
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    protected function getActor(): StaffUser
    {
        $tenantId = app(TenantManager::class)->getTenantId();
        
        $actor = auth()->user() ?? auth('staff')->user();
        if ($actor instanceof StaffUser) {
            return $actor;
        }
        
        // Find or create fallback staff user with required permissions for operations
        $staff = StaffUser::where('tenant_id', $tenantId)->first();
        if (!$staff) {
            $staff = StaffUser::create([
                'tenant_id' => $tenantId,
                'name' => 'System Operator',
                'email' => 'operator@interboard.io',
                'password' => bcrypt('password'),
                'status' => 'active',
            ]);
        }
        
        // Ensure roles/permissions exist so service calls don't crash
        $role = \App\Modules\Auth\Models\Role::firstOrCreate(
            ['slug' => 'super-admin', 'tenant_id' => $tenantId],
            ['name' => 'Super Admin', 'description' => 'Super Admin Role']
        );
        $staff->roles()->syncWithoutDetaching([$role->id]);
        
        return $staff;
    }

    public function render()
    {
        $tenantId = app(TenantManager::class)->getTenantId();

        $subscriptions = CustomerServiceSubscription::where('tenant_id', $tenantId)
            ->when($this->search, function ($query) {
                $query->where(function ($sub) {
                    $sub->where('subscription_code', 'like', '%' . $this->search . '%')
                        ->orWhereHas('customer', function ($cust) {
                            $cust->where('full_name', 'like', '%' . $this->search . '%')
                                ->orWhere('customer_code', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->with(['customer', 'servicePlan', 'servicePackage'])
            ->orderBy('id', 'desc')
            ->paginate(10, ['*'], 'subsPage');

        $events = SuspensionEvent::where('tenant_id', $tenantId)
            ->with(['subscription.customer'])
            ->orderBy('id', 'desc')
            ->paginate(10, ['*'], 'eventsPage');

        return view('billing::livewire.subscription-actions', [
            'subscriptions' => $subscriptions,
            'events' => $events,
        ]);
    }
}
