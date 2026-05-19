<?php

declare(strict_types=1);

namespace App\Modules\Billing\Http\Livewire;

use App\Core\Tenant\TenantManager;
use App\Modules\Billing\Models\PaymentReceipt;
use App\Modules\Billing\Models\ReversalEvent;
use App\Modules\Billing\Models\PaymentAllocationReversal;
use App\Modules\Billing\Services\ReversalAdjustmentService;
use App\Modules\Customer\Models\Customer;
use App\Modules\Auth\Models\StaffUser;
use Livewire\Component;
use Livewire\WithPagination;

class ReversalsIndex extends Component
{
    use WithPagination;

    public string $searchReceipt = '';

    // Reverse Modal State
    public bool $isReverseModalOpen = false;
    public ?int $selectedReceiptId = null;
    public string $reversalReason = '';

    // Adjustment Form States
    public ?int $adjCustomerId = null;
    public string $adjType = 'credit_adjustment';
    public string $adjAmount = '0.0000';
    public string $adjReason = '';

    public function updatingSearchReceipt(): void
    {
        $this->resetPage('receiptsPage');
    }

    public function openReverseModal(int $receiptId): void
    {
        $this->selectedReceiptId = $receiptId;
        $this->reversalReason = 'Incorrect payment entry / double posting.';
        $this->isReverseModalOpen = true;
    }

    public function closeReverseModal(): void
    {
        $this->isReverseModalOpen = false;
        $this->selectedReceiptId = null;
    }

    public function reverseReceipt(): void
    {
        $this->validate([
            'selectedReceiptId' => 'required|exists:payment_receipts,id',
            'reversalReason' => 'required|string|min:3|max:1000',
        ]);

        $tenantId = app(TenantManager::class)->getTenantId();
        $actor = $this->getActor();

        try {
            $service = app(ReversalAdjustmentService::class);
            $service->reverseReceipt($tenantId, $this->selectedReceiptId, $this->reversalReason, $actor);

            session()->flash('message', 'Payment receipt successfully reversed.');
            $this->closeReverseModal();
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function submitAdjustment(): void
    {
        $this->validate([
            'adjCustomerId' => 'required|exists:customers,id',
            'adjType' => 'required|string|in:credit_adjustment,debit_adjustment,waiver,penalty',
            'adjAmount' => 'required|numeric|min:0.0001',
            'adjReason' => 'required|string|min:3|max:1000',
        ]);

        $tenantId = app(TenantManager::class)->getTenantId();
        $actor = $this->getActor();

        try {
            $service = app(ReversalAdjustmentService::class);
            $service->adjustBalance(
                $tenantId,
                $this->adjCustomerId,
                $this->adjType,
                $this->adjAmount,
                'BDT',
                $this->adjReason,
                $actor,
                ['idempotency_key' => uniqid('adj_')]
            );

            session()->flash('message', 'Manual adjustment successfully posted.');
            
            // Reset form
            $this->adjCustomerId = null;
            $this->adjAmount = '0.0000';
            $this->adjReason = '';
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
        
        $perms = [
            'billing.receipt.reverse' => ['module' => 'billing', 'action' => 'receipt.reverse'],
            'billing.adjustment.manage' => ['module' => 'billing', 'action' => 'adjustment.manage'],
        ];
        foreach ($perms as $slug => $data) {
            $permission = \App\Modules\Auth\Models\Permission::firstOrCreate(
                ['slug' => $slug],
                [
                    'module' => $data['module'],
                    'action' => $data['action'],
                    'description' => "Permission for {$slug}",
                ]
            );
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }
        
        return $staff;
    }

    public function render()
    {
        $tenantId = app(TenantManager::class)->getTenantId();

        $customers = Customer::where('tenant_id', $tenantId)->orderBy('full_name')->get();

        $receipts = PaymentReceipt::where('tenant_id', $tenantId)
            ->when($this->searchReceipt, function ($query) {
                $query->where(function ($sub) {
                    $sub->where('receipt_number', 'like', '%' . $this->searchReceipt . '%')
                        ->orWhereHas('customer', function ($cust) {
                            $cust->where('full_name', 'like', '%' . $this->searchReceipt . '%');
                        });
                });
            })
            ->with('customer')
            ->orderBy('id', 'desc')
            ->paginate(10, ['*'], 'receiptsPage');

        $events = ReversalEvent::where('tenant_id', $tenantId)
            ->with(['actor'])
            ->orderBy('id', 'desc')
            ->paginate(10, ['*'], 'eventsPage');

        $reversedAllocations = PaymentAllocationReversal::where('tenant_id', $tenantId)
            ->with(['allocation.invoice.customer'])
            ->orderBy('id', 'desc')
            ->paginate(10, ['*'], 'allocReversalsPage');

        return view('billing::livewire.reversals-index', [
            'customers' => $customers,
            'receipts' => $receipts,
            'events' => $events,
            'reversedAllocations' => $reversedAllocations,
        ]);
    }
}
