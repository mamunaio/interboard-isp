<div class="p-6 lg:p-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-slate-800 pb-6 mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-white tracking-tight">
                Reversals & <span class="bg-gradient-to-r from-rose-450 to-indigo-500 bg-clip-text text-transparent">Adjustments</span>
            </h1>
            <p class="text-slate-400 text-sm mt-1">Audit payment reversals, post manual credits or penalties, and review ledger audit adjustments.</p>
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

    <!-- Main Content Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Receipts Lookup & Reversals Left Grid -->
        <div class="lg:col-span-2 space-y-8">
            
            <!-- Receipt Lookup Box -->
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl">
                <div class="flex items-center justify-between mb-4 gap-4">
                    <h3 class="text-lg font-bold text-white">Reverse Payment Receipts</h3>
                    <div class="relative max-w-xs">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </span>
                        <input wire:model.live="searchReceipt" type="text" placeholder="Search receipt/customer..." class="w-full bg-slate-950 text-slate-100 placeholder-slate-500 pl-9 pr-3 py-2 rounded-xl border border-slate-800 focus:outline-none focus:border-cyan-500 transition duration-200 text-xs">
                    </div>
                </div>

                <div class="overflow-x-auto rounded-2xl border border-slate-800">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-950/60 text-slate-450 border-b border-slate-800 font-bold uppercase">
                                <th class="py-3 px-4">Receipt Number</th>
                                <th class="py-3 px-4">Customer</th>
                                <th class="py-3 px-4">Amount</th>
                                <th class="py-3 px-4">Method</th>
                                <th class="py-3 px-4">Date</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-850 text-slate-350">
                            @forelse($receipts as $rcpt)
                                <tr class="hover:bg-slate-950/40 transition">
                                    <td class="py-3 px-4 font-mono font-bold text-cyan-400">{{ $rcpt->receipt_number }}</td>
                                    <td class="py-3 px-4">
                                        <p class="font-bold text-white">{{ $rcpt->customer->full_name }}</p>
                                        <p class="text-[10px] text-slate-500">{{ $rcpt->customer->customer_code }}</p>
                                    </td>
                                    <td class="py-3 px-4 font-mono font-bold text-white">{{ number_format((float)$rcpt->amount, 2) }} BDT</td>
                                    <td class="py-3 px-4 capitalize">{{ $rcpt->payment_method->value }}</td>
                                    <td class="py-3 px-4 text-slate-500 font-mono">{{ $rcpt->created_at->format('Y-m-d') }}</td>
                                    <td class="py-3 px-4 text-right">
                                        <button wire:click="openReverseModal({{ $rcpt->id }})" class="px-2.5 py-1 bg-rose-950 hover:bg-rose-900 border border-rose-800 text-rose-400 rounded-lg font-bold transition">
                                            Reverse
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-slate-550">No payment receipts found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($receipts->hasPages())
                    <div class="mt-4">
                        {{ $receipts->links() }}
                    </div>
                @endif
            </div>

            <!-- Reversal History Event Audit -->
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl">
                <h3 class="text-lg font-bold text-white mb-4">Reversal Audit History Log</h3>
                
                <div class="overflow-x-auto rounded-2xl border border-slate-800">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-950/60 text-slate-450 border-b border-slate-800 font-bold uppercase">
                                <th class="py-3 px-4">Event ID</th>
                                <th class="py-3 px-4">Type</th>
                                <th class="py-3 px-4">Amount</th>
                                <th class="py-3 px-4">Reason</th>
                                <th class="py-3 px-4">Operator</th>
                                <th class="py-3 px-4">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-850 text-slate-350">
                            @forelse($events as $event)
                                <tr class="hover:bg-slate-950/40 transition">
                                    <td class="py-3 px-4 font-mono text-slate-400 font-bold">#{{ $event->id }}</td>
                                    <td class="py-3 px-4">
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-black uppercase bg-slate-950 text-slate-400 border border-slate-800">
                                            {{ str_replace('_', ' ', $event->reversal_type) }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 font-mono font-bold text-rose-450">- {{ number_format((float)$event->amount, 2) }} BDT</td>
                                    <td class="py-3 px-4 text-slate-200">{{ $event->reason }}</td>
                                    <td class="py-3 px-4">{{ $event->actor ? $event->actor->name : 'System' }}</td>
                                    <td class="py-3 px-4 text-slate-500 font-mono">{{ $event->created_at->format('Y-m-d H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-slate-550">No events logged.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($events->hasPages())
                    <div class="mt-4">
                        {{ $events->links() }}
                    </div>
                @endif
            </div>

            <!-- Reversed Allocations History -->
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl">
                <h3 class="text-lg font-bold text-white mb-4">Reversed Allocations</h3>
                
                <div class="overflow-x-auto rounded-2xl border border-slate-800">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-950/60 text-slate-450 border-b border-slate-800 font-bold uppercase">
                                <th class="py-3 px-4">Reversal ID</th>
                                <th class="py-3 px-4">Customer</th>
                                <th class="py-3 px-4">Invoice</th>
                                <th class="py-3 px-4">Reversed Amount</th>
                                <th class="py-3 px-4">Reason</th>
                                <th class="py-3 px-4">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-850 text-slate-350">
                            @forelse($reversedAllocations as $revAlloc)
                                <tr class="hover:bg-slate-950/40 transition">
                                    <td class="py-3 px-4 font-mono text-slate-400 font-bold">#{{ $revAlloc->id }}</td>
                                    <td class="py-3 px-4 font-semibold text-white">
                                        {{ $revAlloc->allocation->invoice->customer->full_name }}
                                    </td>
                                    <td class="py-3 px-4 font-mono font-bold text-cyan-400">
                                        {{ $revAlloc->allocation->invoice->invoice_number }}
                                    </td>
                                    <td class="py-3 px-4 font-mono font-bold text-amber-500">
                                        {{ number_format((float)$revAlloc->amount, 2) }} BDT
                                    </td>
                                    <td class="py-3 px-4 text-slate-300">{{ $revAlloc->reason }}</td>
                                    <td class="py-3 px-4 text-slate-500 font-mono">{{ $revAlloc->created_at->format('Y-m-d H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-slate-550">No reversed allocations.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($reversedAllocations->hasPages())
                    <div class="mt-4">
                        {{ $reversedAllocations->links() }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Manual Adjustments Right Column Form -->
        <div class="lg:col-span-1">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl">
                <h3 class="text-base font-bold text-white mb-6">Create Manual Adjustment</h3>
                
                <form wire:submit.prevent="submitAdjustment" class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-450 uppercase mb-1">Select Customer</label>
                        <select wire:model="adjCustomerId" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-cyan-500">
                            <option value="">-- Choose Customer --</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->full_name }} ({{ $c->customer_code }})</option>
                            @endforeach
                        </select>
                        @error('adjCustomerId') <span class="text-rose-500 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-450 uppercase mb-1">Adjustment Type</label>
                        <select wire:model="adjType" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-cyan-500">
                            <option value="credit_adjustment">Credit Adjustment (Add balance)</option>
                            <option value="debit_adjustment">Debit Adjustment (Deduct balance)</option>
                            <option value="waiver">Waiver (Forgive debt)</option>
                            <option value="penalty">Penalty (Charge fee)</option>
                        </select>
                        @error('adjType') <span class="text-rose-500 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-450 uppercase mb-1">Adjustment Amount (BDT)</label>
                        <input wire:model="adjAmount" type="number" step="0.0001" placeholder="0.00" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-cyan-500">
                        @error('adjAmount') <span class="text-rose-500 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-450 uppercase mb-1">Reason / Notes</label>
                        <textarea wire:model="adjReason" placeholder="Enter formal description..." rows="3" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-cyan-500"></textarea>
                        @error('adjReason') <span class="text-rose-500 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full py-2.5 bg-gradient-to-r from-cyan-500 to-indigo-500 hover:from-cyan-600 hover:to-indigo-650 text-white font-bold rounded-xl text-xs shadow-lg transition duration-200">
                            Post Manual Adjustment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reverse Receipt Confirmation Modal -->
    @if($isReverseModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl w-full max-w-md shadow-2xl overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-800 flex items-center justify-between">
                    <h3 class="text-base font-bold text-white">Reverse Payment Receipt</h3>
                    <button wire:click="closeReverseModal" class="p-1 text-slate-400 hover:text-white rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <form wire:submit.prevent="reverseReceipt" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Reversal Reason</label>
                        <textarea wire:model="reversalReason" placeholder="Enter explicit reason for audit tracking..." rows="3" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500"></textarea>
                        @error('reversalReason') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="pt-4 border-t border-slate-800 flex justify-end gap-3">
                        <button type="button" wire:click="closeReverseModal" class="px-4 py-2 bg-slate-850 hover:bg-slate-800 border border-slate-800 rounded-xl text-xs font-bold text-slate-350 transition">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition">
                            Confirm Reversal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
