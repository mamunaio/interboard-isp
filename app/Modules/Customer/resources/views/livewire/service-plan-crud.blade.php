<div class="p-6 lg:p-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-slate-800 pb-6 mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-white tracking-tight">
                Service <span class="bg-gradient-to-r from-cyan-400 to-indigo-500 bg-clip-text text-transparent">Catalog</span>
            </h1>
            <p class="text-slate-400 text-sm mt-1">Configure service plans, pricing structures, bandwidth limits, and tax associations.</p>
        </div>
        <div>
            <button wire:click="openCreateModal" class="px-5 py-3 bg-gradient-to-r from-cyan-500 to-indigo-500 hover:from-cyan-600 hover:to-indigo-650 text-white text-sm font-bold rounded-xl shadow-lg transition duration-200 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Create Service Plan
            </button>
        </div>
    </div>

    <!-- Alerts -->
    @if (session()->has('message'))
        <div class="mb-6 p-4 bg-emerald-950/60 border border-emerald-800 text-emerald-400 rounded-2xl flex items-center gap-3">
            <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span class="text-sm font-semibold">{{ session('message') }}</span>
        </div>
    @endif

    <!-- Search Control -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 mb-8 shadow-2xl">
        <div class="relative max-w-md">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </span>
            <input wire:model.live="search" type="text" placeholder="Search plans by name or code..." class="w-full bg-slate-950 text-slate-100 placeholder-slate-500 pl-10 pr-4 py-3 rounded-xl border border-slate-800 focus:outline-none focus:border-cyan-500 transition duration-200 text-sm">
        </div>
    </div>

    <!-- Plans Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-400 text-xs font-bold uppercase tracking-wider bg-slate-900/50">
                        <th class="py-4 px-6">Plan Code</th>
                        <th class="py-4 px-6">Plan Name</th>
                        <th class="py-4 px-6">Monthly Rate</th>
                        <th class="py-4 px-6">Download Speed</th>
                        <th class="py-4 px-6">Upload Speed</th>
                        <th class="py-4 px-6">Status</th>
                        <th class="py-4 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-sm">
                    @forelse($plans as $plan)
                        <tr class="hover:bg-slate-850/40 transition duration-200">
                            <td class="py-4 px-6 font-mono font-bold text-cyan-400">
                                {{ $plan->plan_code }}
                            </td>
                            <td class="py-4 px-6 font-semibold text-white">
                                {{ $plan->name }}
                            </td>
                            <td class="py-4 px-6 font-black text-slate-200">
                                {{ number_format((float)$plan->monthly_price, 2) }} BDT
                            </td>
                            <td class="py-4 px-6 text-slate-300">
                                {{ $plan->download_speed }}
                            </td>
                            <td class="py-4 px-6 text-slate-300">
                                {{ $plan->upload_speed }}
                            </td>
                            <td class="py-4 px-6">
                                @if($plan->is_active)
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-full border text-emerald-400 bg-emerald-950/50 border-emerald-900">
                                        Active
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-full border text-rose-400 bg-rose-950/50 border-rose-900">
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-right space-x-2">
                                <button wire:click="editPlan({{ $plan->id }})" class="px-3.5 py-1.5 bg-slate-800 border border-slate-700 hover:border-slate-650 hover:bg-slate-750 text-slate-200 rounded-lg text-xs font-bold transition duration-200">
                                    Edit
                                </button>
                                <button wire:click="toggleActive({{ $plan->id }})" class="px-3.5 py-1.5 {{ $plan->is_active ? 'bg-rose-950 border border-rose-800 text-rose-400 hover:bg-rose-900' : 'bg-emerald-950 border border-emerald-800 text-emerald-400 hover:bg-emerald-900' }} rounded-lg text-xs font-bold transition duration-200">
                                    {{ $plan->is_active ? 'Disable' : 'Enable' }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-500">
                                <svg class="w-12 h-12 mx-auto text-slate-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                <p class="text-sm font-semibold">No service plans configured.</p>
                                <p class="text-xs text-slate-650 mt-1">Get started by creating your first service plan above.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($plans->hasPages())
            <div class="px-6 py-4 border-t border-slate-800 bg-slate-900/30">
                {{ $plans->links() }}
            </div>
        @endif
    </div>

    <!-- Create Service Plan Modal -->
    @if($isCreateModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl w-full max-w-lg shadow-2xl overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-800 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-white">Create Service Plan</h3>
                    <button wire:click="closeCreateModal" class="p-1 text-slate-400 hover:text-white rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <form wire:submit.prevent="createPlan" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Plan Code</label>
                            <input wire:model="plan_code" type="text" placeholder="PLAN-10M" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500">
                            @error('plan_code') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Plan Name</label>
                            <input wire:model="name" type="text" placeholder="10 Mbps Premium" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500">
                            @error('name') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Monthly Price (BDT)</label>
                            <input wire:model="monthly_price" type="number" step="0.0001" placeholder="500.00" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500">
                            @error('monthly_price') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Billing Cycle</label>
                            <select wire:model="billing_cycle" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500">
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                            @error('billing_cycle') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Download Speed</label>
                            <input wire:model="download_speed" type="text" placeholder="10 Mbps" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500">
                            @error('download_speed') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Upload Speed</label>
                            <input wire:model="upload_speed" type="text" placeholder="5 Mbps" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500">
                            @error('upload_speed') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Associated Tax Rule (Optional)</label>
                        <select wire:model="tax_rule_id" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500">
                            <option value="">No Tax Association</option>
                            @foreach($taxRules as $rule)
                                <option value="{{ $rule->id }}">{{ $rule->name }} ({{ number_format((float)$rule->rate, 2) }}%)</option>
                            @endforeach
                        </select>
                        @error('tax_rule_id') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <input wire:model="is_active" type="checkbox" id="is_active_create" class="rounded border-slate-800 bg-slate-950 text-cyan-500 focus:ring-0">
                        <label for="is_active_create" class="text-sm font-semibold text-slate-300">Set plan as Active</label>
                    </div>

                    <div class="pt-4 border-t border-slate-800 flex justify-end gap-3">
                        <button type="button" wire:click="closeCreateModal" class="px-5 py-2.5 bg-slate-850 hover:bg-slate-800 border border-slate-800 hover:border-slate-700 rounded-xl text-sm font-semibold text-slate-350 transition duration-150">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-cyan-500 to-indigo-500 hover:from-cyan-600 hover:to-indigo-650 text-white rounded-xl text-sm font-semibold transition duration-155">
                            Save Plan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Edit Service Plan Modal -->
    @if($isEditModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl w-full max-w-lg shadow-2xl overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-800 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-white">Edit Service Plan</h3>
                    <button wire:click="resetForm; $set('isEditModalOpen', false)" class="p-1 text-slate-400 hover:text-white rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <form wire:submit.prevent="updatePlan" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Plan Code</label>
                            <input wire:model="plan_code" type="text" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500">
                            @error('plan_code') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Plan Name</label>
                            <input wire:model="name" type="text" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500">
                            @error('name') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Monthly Price (BDT)</label>
                            <input wire:model="monthly_price" type="number" step="0.0001" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500">
                            @error('monthly_price') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Billing Cycle</label>
                            <select wire:model="billing_cycle" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500">
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                            @error('billing_cycle') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Download Speed</label>
                            <input wire:model="download_speed" type="text" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500">
                            @error('download_speed') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Upload Speed</label>
                            <input wire:model="upload_speed" type="text" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500">
                            @error('upload_speed') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Associated Tax Rule</label>
                        <select wire:model="tax_rule_id" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500">
                            <option value="">No Tax Association</option>
                            @foreach($taxRules as $rule)
                                <option value="{{ $rule->id }}">{{ $rule->name }} ({{ number_format((float)$rule->rate, 2) }}%)</option>
                            @endforeach
                        </select>
                        @error('tax_rule_id') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <input wire:model="is_active" type="checkbox" id="is_active_edit" class="rounded border-slate-800 bg-slate-950 text-cyan-500 focus:ring-0">
                        <label for="is_active_edit" class="text-sm font-semibold text-slate-300">Set plan as Active</label>
                    </div>

                    <div class="pt-4 border-t border-slate-800 flex justify-end gap-3">
                        <button type="button" wire:click="resetForm; $set('isEditModalOpen', false)" class="px-5 py-2.5 bg-slate-850 hover:bg-slate-800 border border-slate-800 hover:border-slate-700 rounded-xl text-sm font-semibold text-slate-350 transition duration-150">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-cyan-500 to-indigo-500 hover:from-cyan-600 hover:to-indigo-650 text-white rounded-xl text-sm font-semibold transition duration-155">
                            Update Plan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
