<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-white tracking-tight">Inventory Management</h2>
            <p class="text-sm text-slate-400 mt-1">Manage hardware stock, cables, and ONUs</p>
        </div>
        <button wire:click="openCreateModal" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-xl transition duration-200 shadow-lg shadow-indigo-500/20">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Add Item
        </button>
    </div>

    @if (session()->has('message'))
        <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-xl flex items-center gap-3">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="p-4 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-xl flex items-center gap-3">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            {{ session('error') }}
        </div>
    @endif

    <!-- Filters -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-slate-900/50 backdrop-blur-xl border border-slate-800 p-4 rounded-2xl shadow-xl">
        <div>
            <label class="block text-sm font-medium text-slate-400 mb-1">Search</label>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by name or SKU..." class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-400 mb-1">Category</label>
            <select wire:model.live="filterCategory" class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200">
                <option value="">All Categories</option>
                @foreach($categories as $categoryOption)
                    <option value="{{ $categoryOption->value }}">{{ $categoryOption->label() }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-slate-900/50 backdrop-blur-xl border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-800/50 text-xs uppercase text-slate-400 border-b border-slate-700">
                    <tr>
                        <th class="px-6 py-4 font-medium">Item Name / SKU</th>
                        <th class="px-6 py-4 font-medium">Category</th>
                        <th class="px-6 py-4 font-medium text-center">Tracking</th>
                        <th class="px-6 py-4 font-medium text-center">Current Stock</th>
                        <th class="px-6 py-4 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/50">
                    @forelse($items as $item)
                        @php
                            $isLowStock = $item->current_stock <= $item->low_stock_threshold;
                        @endphp
                        <tr class="hover:bg-slate-800/25 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="font-medium text-white flex items-center gap-2">
                                    {{ $item->name }}
                                    @if($isLowStock)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/20 text-rose-400 uppercase tracking-wider animate-pulse">Low Stock</span>
                                    @endif
                                </div>
                                @if($item->sku)
                                    <div class="text-xs text-slate-500 font-mono mt-0.5">SKU: {{ $item->sku }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-800 text-slate-300 border border-slate-700">
                                    {{ $item->category->label() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($item->track_serial)
                                    <span class="text-indigo-400 text-xs font-medium bg-indigo-500/10 px-2 py-1 rounded-md border border-indigo-500/20">Serialized</span>
                                @else
                                    <span class="text-slate-400 text-xs bg-slate-800 px-2 py-1 rounded-md border border-slate-700">Bulk/Qty</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="text-lg font-bold {{ $isLowStock ? 'text-rose-400' : 'text-emerald-400' }}">
                                    {{ $item->current_stock }}
                                </div>
                                <div class="text-[10px] text-slate-500 uppercase tracking-wide">
                                    Min: {{ $item->low_stock_threshold }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if(!$item->track_serial)
                                    <button wire:click="incrementStock({{ $item->id }})" class="text-indigo-400 hover:text-indigo-300 font-medium text-sm transition-colors">
                                        + Add Stock
                                    </button>
                                @else
                                    <button class="text-slate-400 hover:text-slate-300 font-medium text-sm transition-colors">
                                        View Serials
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                <svg class="w-12 h-12 mx-auto text-slate-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                <p class="text-lg font-medium text-slate-400">No inventory items found</p>
                                <p class="text-sm mt-1">Add items to start tracking your hardware and cables.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-800">
            {{ $items->links(data: ['scrollTo' => false]) }}
        </div>
    </div>

    <!-- Create Item Modal -->
    @if($isCreateModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity"></div>
        <div class="relative bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl w-full max-w-lg p-6 overflow-hidden">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-white">Add New Item</h3>
                <button wire:click="closeCreateModal" class="text-slate-400 hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form wire:submit="createItem" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1">Item Name *</label>
                    <input type="text" wire:model="name" class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500" placeholder="e.g. Dual Band ONU" required>
                    @error('name') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">SKU</label>
                        <input type="text" wire:model="sku" class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500" placeholder="Optional code">
                        @error('sku') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Category *</label>
                        <select wire:model="category" class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500">
                            @foreach($categories as $categoryOption)
                                <option value="{{ $categoryOption->value }}">{{ $categoryOption->label() }}</option>
                            @endforeach
                        </select>
                        @error('category') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="flex items-center gap-3 p-3 bg-slate-800/50 border border-slate-700 rounded-xl">
                    <input type="checkbox" wire:model="track_serial" id="track_serial" class="w-5 h-5 rounded bg-slate-900 border-slate-700 text-indigo-600 focus:ring-indigo-500">
                    <label for="track_serial" class="text-sm text-slate-300 cursor-pointer">
                        <span class="font-bold block text-white">Track Serial Numbers</span>
                        Require serial & MAC address tracking for each unit.
                    </label>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Initial Stock</label>
                        <input type="number" wire:model="current_stock" min="0" class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500">
                        @error('current_stock') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Low Stock Alert At</label>
                        <input type="number" wire:model="low_stock_threshold" min="0" class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500">
                        @error('low_stock_threshold') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="pt-4 flex justify-end gap-3">
                    <button type="button" wire:click="closeCreateModal" class="px-4 py-2 text-sm font-medium text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 rounded-lg transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-500 rounded-lg transition-colors shadow-lg shadow-indigo-500/20">
                        Save Item
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Audio Element for Alert -->
    <audio id="lowStockSound" src="/sounds/alert.mp3" preload="auto"></audio>

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('low-stock-alert', () => {
                const audio = document.getElementById('lowStockSound');
                if (audio) {
                    audio.play().catch(e => console.log('Audio playback prevented by browser policy: ' + e));
                }
            });
        });
    </script>
</div>
