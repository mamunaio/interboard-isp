<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Services;

use App\Core\Tenant\TenantManager;
use App\Modules\Inventory\Enums\InventoryStatus;
use App\Modules\Inventory\Models\InventoryAssignment;
use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Inventory\Models\InventorySerial;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    private TenantManager $tenantManager;

    public function __construct(TenantManager $tenantManager)
    {
        $this->tenantManager = $tenantManager;
    }

    /**
     * Add a serialized device to inventory.
     */
    public function addSerializedItem(InventoryItem $item, string $serialNumber, ?string $macAddress = null): InventorySerial
    {
        if (!$item->track_serial) {
            throw new \InvalidArgumentException("This item does not track serials.");
        }

        return DB::transaction(function () use ($item, $serialNumber, $macAddress) {
            $serial = InventorySerial::create([
                'inventory_item_id' => $item->id,
                'serial_number' => $serialNumber,
                'mac_address' => $macAddress,
                'status' => InventoryStatus::IN_STOCK,
            ]);

            $item->increment('current_stock');

            return $serial;
        });
    }

    /**
     * Assign a serialized item to a customer or technician (user).
     */
    public function assignSerial(InventorySerial $serial, Model $assignee, ?string $notes = null): InventoryAssignment
    {
        if ($serial->status !== InventoryStatus::IN_STOCK && $serial->status !== InventoryStatus::PENDING_RECOVERY) {
            throw new \LogicException("Device is not available for assignment.");
        }

        return DB::transaction(function () use ($serial, $assignee, $notes) {
            // End previous assignments if any are still open
            $serial->assignments()->whereNull('returned_at')->update([
                'returned_at' => Carbon::now(),
            ]);

            $assignment = InventoryAssignment::create([
                'inventory_serial_id' => $serial->id,
                'assigned_to_type' => get_class($assignee),
                'assigned_to_id' => $assignee->id,
                'assigned_at' => Carbon::now(),
                'notes' => $notes,
            ]);

            // Update status based on assignee
            $newStatus = (get_class($assignee) === \App\Modules\Customer\Models\Customer::class) 
                ? InventoryStatus::DEPLOYED 
                : InventoryStatus::WITH_TECHNICIAN;

            $serial->update(['status' => $newStatus]);
            $serial->item->decrement('current_stock');

            return $assignment;
        });
    }

    /**
     * Recover/Return a device to inventory.
     */
    public function recoverSerial(InventorySerial $serial, InventoryStatus $condition = InventoryStatus::IN_STOCK): bool
    {
        return DB::transaction(function () use ($serial, $condition) {
            $serial->assignments()->whereNull('returned_at')->update([
                'returned_at' => Carbon::now(),
            ]);

            $serial->update(['status' => $condition]);

            if ($condition === InventoryStatus::IN_STOCK) {
                $serial->item->increment('current_stock');
            }

            return true;
        });
    }
}
