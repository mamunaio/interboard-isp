<div class="p-6 lg:p-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-slate-800 pb-6 mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-white tracking-tight">
                Subscription <span class="bg-gradient-to-r from-amber-400 to-rose-500 bg-clip-text text-transparent">Actions</span>
            </h1>
            <p class="text-slate-400 text-sm mt-1">Manage tenant subscription suspensions, reconnection fee configurations, and reactivation evaluations.</p>
        </div>
    </div>

    <!-- Alert Messages -->
    @if (session()->has('message'))
        <div class="mb-6 p-4 bg-emerald-950/60 border border-emerald-850 text-emerald-400 rounded-2xl flex items-center gap-3">
            <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span class="text-sm font-semibold">{{ session('message') }}</span>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-6 p-4 bg-rose-950/60 border border-rose-850 text-rose-400 rounded-2xl flex items-center gap-3">
            <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span class="text-sm font-semibold text-rose-300">{{ session('error') }}</span>
        </div>
    @endif

    <!-- Main Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Search and Action Column -->
        <div class="lg:col-span-2 space-y-8">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-white">Active & Suspended Subscriptions</h3>
                    <div class="relative max-w-xs">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </span>
                        <input wire:model.live="search" type="text" placeholder="Search code/name..." class="w-full bg-slate-950 text-slate-100 placeholder-slate-500 pl-9 pr-3 py-2 rounded-xl border border-slate-800 focus:outline-none focus:border-cyan-500 transition duration-200 text-xs">
                    </div>
                </div>

                <div class="overflow-x-auto rounded-2xl border border-slate-800">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-950/60 text-slate-450 border-b border-slate-800 font-bold uppercase">
                                <th class="py-3 px-4">Subscription Code</th>
                                <th class="py-3 px-4">Customer</th>
                                <th class="py-3 px-4">Package</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-850 text-slate-350">
                            @forelse($subscriptions as $sub)
                                <tr class="hover:bg-slate-950/40 transition">
                                    <td class="py-3 px-4 font-mono font-bold text-cyan-400">{{ $sub->subscription_code }}</td>
                                    <td class="py-3 px-4">
                                        <p class="font-bold text-white">{{ $sub->customer->full_name }}</p>
                                        <p class="text-[10px] text-slate-500">{{ $sub->customer->customer_code }}</p>
                                    </td>
                                    <td class="py-3 px-4">
                                        {{ $sub->servicePlan ? $sub->servicePlan->name : ($sub->servicePackage ? $sub->servicePackage->name : 'N/A') }}
                                    </td>
                                    <td class="py-3 px-4">
                                        @php
                                            $subColor = match($sub->status->value) {
                                                'active' => 'text-emerald-400 bg-emerald-950/50 border-emerald-900',
                                                'suspended' => 'text-rose-400 bg-rose-950/50 border-rose-900',
                                                default => 'text-slate-450 bg-slate-800 border-slate-850'
                                            };
                                        @endphp
                                        <span class="px-2 py-0.5 rounded border text-[10px] font-black uppercase {{ $subColor }}">
                                            {{ $sub->status->value }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right space-x-1">
                                        @if($sub->status->value === 'active')
                                            <button wire:click="openSuspendModal({{ $sub->id }})" class="px-2.5 py-1 bg-rose-950 hover:bg-rose-900 border border-rose-800 text-rose-400 rounded-lg font-bold transition">
                                                Suspend
                                            </button>
                                        @elseif($sub->status->value === 'suspended')
                                            <button wire:click="openReactivateModal({{ $sub->id }})" class="px-2.5 py-1 bg-emerald-950 hover:bg-emerald-900 border border-emerald-800 text-emerald-400 rounded-lg font-bold transition">
                                                Reactivate
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-slate-550">No subscriptions matched.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($subscriptions->hasPages())
                    <div class="mt-4">
                        {{ $subscriptions->links() }}
                    </div>
                @endif
            </div>
        </div>

        <!-- History Audit Log Column -->
        <div class="lg:col-span-1">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl">
                <h3 class="text-base font-bold text-white mb-4">Suspension History Log</h3>
                
                <div class="space-y-4">
                    @forelse($events as $event)
                        <div class="bg-slate-950 border border-slate-850 p-4 rounded-2xl text-xs space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="font-mono text-slate-500 font-bold">#{{ $event->id }}</span>
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-black uppercase 
                                    {{ $event->action_type === 'suspended' ? 'bg-rose-950 text-rose-400' : 'bg-emerald-950 text-emerald-400' }}">
                                    {{ $event->action_type }}
                                </span>
                            </div>
                            <p class="text-slate-200 font-semibold">
                                {{ $event->subscription->customer->full_name }} ({{ $event->subscription->subscription_code }})
                            </p>
                            <p class="text-slate-400 leading-relaxed font-medium">
                                <strong class="text-slate-500">Reason:</strong> {{ $event->reason }}
                            </p>
                            @if($event->reconnection_invoice_id)
                                <p class="text-[10px] text-cyan-400">
                                    Reconnection Invoice: #{{ $event->reconnection_invoice_id }}
                                </p>
                            @endif
                            <div class="flex justify-between text-[10px] text-slate-500 border-t border-slate-850/60 pt-2 font-mono">
                                <span>By: {{ $event->created_by_type ? substr($event->created_by_type, strrpos($event->created_by_type, '\\') + 1) : 'System' }}</span>
                                <span>{{ $event->created_at->format('Y-m-d H:i') }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-center py-6 text-slate-500 text-xs">No logs registered yet.</p>
                    @endforelse
                </div>

                @if($events->hasPages())
                    <div class="mt-4">
                        {{ $events->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Suspend Modal -->
    @if($isSuspendModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl w-full max-w-md shadow-2xl overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-800 flex items-center justify-between">
                    <h3 class="text-base font-bold text-white">Suspend Subscription</h3>
                    <button wire:click="closeSuspendModal" class="p-1 text-slate-400 hover:text-white rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <form wire:submit.prevent="suspend" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Suspension Reason</label>
                        <textarea wire:model="suspensionReason" placeholder="Provide reason for this customer suspension..." rows="3" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500"></textarea>
                        @error('suspensionReason') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="pt-4 border-t border-slate-800 flex justify-end gap-3">
                        <button type="button" wire:click="closeSuspendModal" class="px-4 py-2 bg-slate-850 hover:bg-slate-800 border border-slate-800 rounded-xl text-xs font-bold text-slate-350 transition">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition">
                            Confirm Suspension
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Reactivate Modal -->
    @if($isReactivateModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl w-full max-w-md shadow-2xl overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-800 flex items-center justify-between">
                    <h3 class="text-base font-bold text-white">Reactivate Subscription</h3>
                    <button wire:click="closeReactivateModal" class="p-1 text-slate-400 hover:text-white rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <form wire:submit.prevent="reactivate" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Reactivation Notes</label>
                        <textarea wire:model="reactivationReason" placeholder="Notes detailing reactivation dues clearing..." rows="2" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500"></textarea>
                        @error('reactivationReason') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="bg-slate-950 border border-slate-850 p-4 rounded-2xl space-y-3">
                        <div class="flex items-center gap-2">
                            <input wire:model.live="reconnectionFeeEnabled" type="checkbox" id="fee_enabled" class="rounded border-slate-800 bg-slate-900 text-cyan-500 focus:ring-0">
                            <label for="fee_enabled" class="text-xs font-bold text-slate-350 uppercase">Generate Reconnection Fee</label>
                        </div>
                        
                        @if($reconnectionFeeEnabled)
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Fee Amount (BDT)</label>
                                <input wire:model="reconnectionFeeAmount" type="number" step="0.0001" class="w-full bg-slate-900 border border-slate-800 text-slate-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:border-cyan-500">
                                @error('reconnectionFeeAmount') <span class="text-rose-500 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        @endif
                    </div>

                    <div class="pt-4 border-t border-slate-800 flex justify-end gap-3">
                        <button type="button" wire:click="closeReactivateModal" class="px-4 py-2 bg-slate-850 hover:bg-slate-800 border border-slate-800 rounded-xl text-xs font-bold text-slate-350 transition">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition">
                            Confirm Reactivation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
