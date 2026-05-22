<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-white tracking-tight">Work Orders</h2>
            <p class="text-sm text-slate-400 mt-1">Manage technician assignments and tasks</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-slate-900/50 backdrop-blur-xl border border-slate-800 p-4 rounded-2xl shadow-xl">
        <div>
            <label class="block text-sm font-medium text-slate-400 mb-1">Search</label>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by customer name or code..." class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-400 mb-1">Status</label>
            <select wire:model.live="filterStatus" class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                <option value="">All Statuses</option>
                @foreach($statuses as $statusOption)
                    <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-400 mb-1">Type</label>
            <select wire:model.live="filterType" class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                <option value="">All Types</option>
                @foreach($types as $typeOption)
                    <option value="{{ $typeOption->value }}">{{ $typeOption->label() }}</option>
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
                        <th class="px-6 py-4 font-medium">Work Order ID</th>
                        <th class="px-6 py-4 font-medium">Customer</th>
                        <th class="px-6 py-4 font-medium">Type</th>
                        <th class="px-6 py-4 font-medium">Priority</th>
                        <th class="px-6 py-4 font-medium">Status</th>
                        <th class="px-6 py-4 font-medium">Assignee</th>
                        <th class="px-6 py-4 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/50">
                    @forelse($workOrders as $workOrder)
                        <tr class="hover:bg-slate-800/25 transition-colors group">
                            <td class="px-6 py-4 font-medium text-white">#WO-{{ str_pad($workOrder->id, 5, '0', STR_PAD_LEFT) }}</td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-white">{{ $workOrder->customer->full_name }}</div>
                                <div class="text-xs text-slate-500">{{ $workOrder->customer->customer_code }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-800 text-slate-300">
                                    {{ $workOrder->type->label() }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $priorityColors = [
                                        'low' => 'text-slate-400 bg-slate-800',
                                        'medium' => 'text-blue-400 bg-blue-400/10',
                                        'high' => 'text-amber-400 bg-amber-400/10',
                                        'urgent' => 'text-red-400 bg-red-400/10',
                                    ];
                                    $pColor = $priorityColors[$workOrder->priority->value] ?? 'text-slate-400 bg-slate-800';
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $pColor }}">
                                    {{ $workOrder->priority->label() }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $statusColors = [
                                        'pending' => 'text-slate-400 bg-slate-800',
                                        'scheduled' => 'text-blue-400 bg-blue-400/10',
                                        'in_progress' => 'text-amber-400 bg-amber-400/10',
                                        'on_hold' => 'text-orange-400 bg-orange-400/10',
                                        'completed' => 'text-emerald-400 bg-emerald-400/10',
                                        'cancelled' => 'text-red-400 bg-red-400/10',
                                    ];
                                    $sColor = $statusColors[$workOrder->status->value] ?? 'text-slate-400 bg-slate-800';
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $sColor }}">
                                    {{ $workOrder->status->label() }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($workOrder->assignee)
                                    <div class="flex items-center gap-2">
                                        <div class="h-6 w-6 rounded-full bg-indigo-500/20 flex items-center justify-center text-indigo-400 text-xs font-bold">
                                            {{ substr($workOrder->assignee->name, 0, 1) }}
                                        </div>
                                        <span>{{ $workOrder->assignee->name }}</span>
                                    </div>
                                @else
                                    <span class="text-slate-500 text-xs italic">Unassigned</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button wire:click="editWorkOrder({{ $workOrder->id }})" class="text-indigo-400 hover:text-indigo-300 font-medium text-sm transition-colors">
                                    Update
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                <svg class="w-12 h-12 mx-auto text-slate-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                                <p class="text-lg font-medium text-slate-400">No work orders found</p>
                                <p class="text-sm mt-1">Try adjusting your filters or search query.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-800">
            {{ $workOrders->links(data: ['scrollTo' => false]) }}
        </div>
    </div>

    <!-- Edit Modal -->
    @if($isEditModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity"></div>
        <div class="relative bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl w-full max-w-lg p-6 overflow-hidden">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-white">Update Work Order</h3>
                <button wire:click="closeEditModal" class="text-slate-400 hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form wire:submit="updateWorkOrder" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1">Status</label>
                    <select wire:model="status" class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500">
                        @foreach($statuses as $statusOption)
                            <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                        @endforeach
                    </select>
                    @error('status') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1">Assign To</label>
                    <select wire:model="assigned_to" class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500">
                        <option value="">-- Unassigned --</option>
                        @foreach($technicians as $tech)
                            <option value="{{ $tech->id }}">{{ $tech->name }}</option>
                        @endforeach
                    </select>
                    @error('assigned_to') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1">Resolution Notes</label>
                    <textarea wire:model="resolution_notes" rows="3" class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500" placeholder="Add notes about the work done..."></textarea>
                    @error('resolution_notes') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="pt-4 flex justify-end gap-3">
                    <button type="button" wire:click="closeEditModal" class="px-4 py-2 text-sm font-medium text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 rounded-lg transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-500 rounded-lg transition-colors shadow-lg shadow-indigo-500/20">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
