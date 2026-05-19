<div class="p-6 lg:p-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-slate-800 pb-6 mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-white tracking-tight">
                Payment <span class="bg-gradient-to-r from-cyan-400 to-indigo-500 bg-clip-text text-transparent">Intake</span>
            </h1>
            <p class="text-slate-400 text-sm mt-1">Ingest manual payments, allocate balances, and update ledger balances atomically.</p>
        </div>
    </div>

    <!-- Alert Messages -->
    @if (session()->has('message'))
        <div class="mb-6 p-4 bg-emerald-950/60 border border-emerald-800 text-emerald-400 rounded-2xl flex items-center gap-3">
            <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span class="text-sm font-semibold">{{ session('message') }}</span>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-6 p-4 bg-rose-950/60 border border-rose-800 text-rose-400 rounded-2xl flex items-center gap-3">
            <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span class="text-sm font-semibold">{{ session('error') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Intake Form -->
        <div class="lg:col-span-2">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl">
                <form wire:submit.prevent="submitPayment" class="space-y-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Select Customer</label>
                        <select wire:model.live="selectedCustomerId" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500">
                            <option value="">-- Choose Customer --</option>
                            @foreach($customers as $cust)
                                <option value="{{ $cust->id }}">{{ $cust->full_name }} ({{ $cust->customer_code }})</option>
                            @endforeach
                        </select>
                        @error('selectedCustomerId') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Payment Amount (BDT)</label>
                            <input wire:model="amount" type="number" step="0.0001" placeholder="1000.00" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500">
                            @error('amount') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Payment Method</label>
                            <select wire:model="paymentMethod" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500">
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="mobile_banking_manual">Mobile Banking (Manual)</option>
                                <option value="manual_adjustment">Manual Adjustment</option>
                            </select>
                            @error('paymentMethod') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Reference / TxID</label>
                            <input wire:model="reference" type="text" placeholder="REF12345" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500">
                            @error('reference') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Idempotency Key</label>
                            <div class="flex gap-2">
                                <input wire:model="idempotencyKey" readonly type="text" class="w-full bg-slate-950 border border-slate-800 text-slate-450 rounded-xl px-4 py-2.5 text-sm focus:outline-none">
                                <button type="button" wire:click="regenerateIdempotencyKey" class="px-3 bg-slate-800 hover:bg-slate-750 text-slate-300 rounded-xl border border-slate-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 8H18.2M7 9a7 7 0 1111.59 5.25"></path></svg>
                                </button>
                            </div>
                            @error('idempotencyKey') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Payment Notes / Description</label>
                        <textarea wire:model="notes" placeholder="e.g. Cleared bill for Mirpur connection..." rows="3" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500"></textarea>
                        @error('notes') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex items-center gap-2">
                        <input wire:model="autoAllocate" type="checkbox" id="auto_allocate" class="rounded border-slate-800 bg-slate-950 text-cyan-500 focus:ring-0">
                        <label for="auto_allocate" class="text-sm font-semibold text-slate-350">Auto-allocate payment to outstanding invoices in FIFO order</label>
                    </div>

                    <div class="pt-4 border-t border-slate-800 flex justify-end">
                        <button type="submit" class="px-6 py-3 bg-gradient-to-r from-cyan-500 to-indigo-500 hover:from-cyan-600 hover:to-indigo-650 text-white font-bold rounded-xl shadow-lg transition duration-200">
                            Post Payment Receipt
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Instruction Panel / Invoices Summary -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl">
                <h3 class="text-base font-bold text-white mb-4">Outstanding Invoices</h3>

                @if($selectedCustomerId)
                    <div class="space-y-3">
                        @forelse($outstandingInvoices as $inv)
                            <div class="bg-slate-950 border border-slate-800 p-4 rounded-xl flex items-center justify-between">
                                <div>
                                    <span class="font-mono font-bold text-xs text-cyan-400">{{ $inv->invoice_number }}</span>
                                    <p class="text-[10px] text-slate-500 mt-0.5">Due: {{ $inv->due_date ? $inv->due_date->format('Y-m-d') : 'N/A' }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs font-black text-slate-200">{{ number_format((float)$inv->amount_due, 2) }} BDT</p>
                                    <span class="text-[9px] font-bold text-slate-400 capitalize">{{ $inv->status->value }}</span>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-slate-500 text-center py-4">No outstanding invoices for this customer.</p>
                        @endforelse
                    </div>
                @else
                    <p class="text-xs text-slate-500 text-center py-6">Select a customer to view their outstanding invoices.</p>
                @endif
            </div>

            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl text-xs text-slate-400 space-y-3 leading-relaxed">
                <h4 class="font-bold text-white">System Invariants</h4>
                <p>1. **Race-safe Sequence**: Receipt numbers are generated using a pessimism locked database sequence.</p>
                <p>2. **Auto-Allocation**: Payments are auto-allocated using a FIFO scheduler across due dates.</p>
                <p>3. **Immutability**: Once payment receipts are posted, they cannot be updated or deleted.</p>
            </div>
        </div>
    </div>
</div>
