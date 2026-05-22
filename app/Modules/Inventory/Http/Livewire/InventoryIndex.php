<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Http\Livewire;

use App\Core\Tenant\TenantManager;
use App\Modules\Inventory\Enums\InventoryCategory;
use App\Modules\Inventory\Models\InventoryItem;
use Livewire\Component;
use Livewire\WithPagination;

class InventoryIndex extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterCategory = '';
    
    public ?int $tenantId = null;
    
    // Modal state for creating a new item
    public bool $isCreateModalOpen = false;
    
    // Form fields
    public string $name = '';
    public string $sku = '';
    public string $category = 'accessories';
    public bool $track_serial = false;
    public int $low_stock_threshold = 5;
    public int $current_stock = 0;

    public function mount(): void
    {
        $this->tenantId = app(TenantManager::class)->getTenantId();
        $this->checkLowStockAlerts();
    }

    public function checkLowStockAlerts(): void
    {
        $hasLowStock = InventoryItem::where('tenant_id', $this->tenantId)
            ->whereRaw('current_stock <= low_stock_threshold')
            ->exists();

        if ($hasLowStock) {
            $this->dispatch('low-stock-alert');
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterCategory(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->name = '';
        $this->sku = '';
        $this->category = 'accessories';
        $this->track_serial = false;
        $this->low_stock_threshold = 5;
        $this->current_stock = 0;
        
        $this->isCreateModalOpen = true;
    }

    public function closeCreateModal(): void
    {
        $this->isCreateModalOpen = false;
    }

    public function createItem(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:255|unique:inventory_items,sku,NULL,id,tenant_id,' . $this->tenantId,
            'category' => 'required|string',
            'track_serial' => 'boolean',
            'low_stock_threshold' => 'required|integer|min:0',
            'current_stock' => 'required|integer|min:0',
        ]);

        InventoryItem::create([
            'tenant_id' => $this->tenantId,
            'name' => $this->name,
            'sku' => $this->sku ?: null,
            'category' => InventoryCategory::from($this->category),
            'track_serial' => $this->track_serial,
            'low_stock_threshold' => $this->low_stock_threshold,
            'current_stock' => $this->current_stock,
        ]);

        $this->closeCreateModal();
        session()->flash('message', 'Inventory Item created successfully.');
        $this->checkLowStockAlerts();
    }

    public function incrementStock(int $id, int $amount = 1): void
    {
        $item = InventoryItem::where('tenant_id', $this->tenantId)->findOrFail($id);
        if (!$item->track_serial) {
            $item->increment('current_stock', $amount);
            $this->checkLowStockAlerts();
        } else {
            session()->flash('error', 'Cannot increment stock for serialized items directly. Use Add Serial.');
        }
    }

    public function render()
    {
        $query = InventoryItem::where('tenant_id', $this->tenantId);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('sku', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterCategory) {
            $query->where('category', $this->filterCategory);
        }

        $items = $query->orderBy('name', 'asc')->paginate(15);

        return view('inventory::livewire.inventory-index', [
            'items' => $items,
            'categories' => InventoryCategory::cases(),
        ]);
    }
}
