<?php

declare(strict_types=1);

namespace App\Modules\Support\Services;

use App\Core\Tenant\TenantManager;
use App\Modules\Customer\Models\Customer;
use App\Modules\Support\Enums\WorkOrderPriority;
use App\Modules\Support\Enums\WorkOrderStatus;
use App\Modules\Support\Enums\WorkOrderType;
use App\Modules\Support\Models\WorkOrder;
use Carbon\Carbon;

class WorkOrderService
{
    private TenantManager $tenantManager;

    public function __construct(TenantManager $tenantManager)
    {
        $this->tenantManager = $tenantManager;
    }

    /**
     * Create a new work order for a customer.
     */
    public function createWorkOrder(Customer $customer, WorkOrderType $type, string $description, ?WorkOrderPriority $priority = null): WorkOrder
    {
        return WorkOrder::create([
            'tenant_id' => $customer->tenant_id,
            'customer_id' => $customer->id,
            'type' => $type,
            'status' => WorkOrderStatus::PENDING,
            'priority' => $priority ?? WorkOrderPriority::MEDIUM,
            'description' => $description,
        ]);
    }

    /**
     * Auto-generate an installation work order when a customer is created.
     */
    public function createInstallationOrder(Customer $customer): WorkOrder
    {
        return $this->createWorkOrder(
            $customer,
            WorkOrderType::INSTALLATION,
            'Auto-generated installation work order for new customer.',
            WorkOrderPriority::HIGH
        );
    }

    /**
     * Update the status of a work order.
     */
    public function updateStatus(WorkOrder $workOrder, WorkOrderStatus $newStatus, ?string $resolutionNotes = null): bool
    {
        $workOrder->status = $newStatus;

        if ($resolutionNotes) {
            $workOrder->resolution_notes = $resolutionNotes;
        }

        if ($newStatus === WorkOrderStatus::COMPLETED) {
            $workOrder->completed_at = Carbon::now();
        }

        return $workOrder->save();
    }
}
