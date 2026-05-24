<div class="p-6 lg:p-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-slate-800 pb-6 mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-white tracking-tight">
                Billing <span class="bg-gradient-to-r from-cyan-400 to-indigo-500 bg-clip-text text-transparent">Invoices</span>
            </h1>
            <p class="text-slate-400 text-sm mt-1">Manage system generated invoices, customer drafts, statutory tax rates, and allocations.</p>
        </div>
        <div class="flex-shrink-0">
            <a href="{{ route('tenant.invoices.create') }}" class="inline-flex items-center justify-center px-4 py-2 bg-cyan-600 hover:bg-cyan-500 text-white text-sm font-bold rounded-xl shadow-lg shadow-cyan-900/20 transition-all duration-200 focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2 focus:ring-offset-slate-900">
                <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Create Invoice
            </a>
        </div>
    </div>

    <!-- Search Control -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 mb-8 shadow-2xl">
        <div class="relative max-w-md">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </span>
            <input wire:model.live="search" type="text" placeholder="Search by invoice number or customer name..." class="w-full bg-slate-950 text-slate-100 placeholder-slate-500 pl-10 pr-4 py-3 rounded-xl border border-slate-800 focus:outline-none focus:border-cyan-500 transition duration-200 text-sm">
        </div>
    </div>

    <!-- Invoices Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-400 text-xs font-bold uppercase tracking-wider bg-slate-900/50">
                        <th class="py-4 px-6">Invoice Number</th>
                        <th class="py-4 px-6">Customer</th>
                        <th class="py-4 px-6">Issue Date</th>
                        <th class="py-4 px-6">Due Date</th>
                        <th class="py-4 px-6">Grand Total</th>
                        <th class="py-4 px-6">Status</th>
                        <th class="py-4 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-sm">
                    @forelse($invoices as $inv)
                        <tr class="hover:bg-slate-850/40 transition duration-200">
                            <td class="py-4 px-6 font-mono font-bold text-cyan-400">
                                {{ $inv->invoice_number }}
                            </td>
                            <td class="py-4 px-6 font-semibold text-white">
                                <a href="/customers/{{ $inv->customer_id }}" class="hover:text-cyan-300 transition duration-150">
                                    {{ $inv->customer->full_name }}
                                </a>
                            </td>
                            <td class="py-4 px-6 text-slate-350 font-mono">
                                {{ $inv->issue_date ? $inv->issue_date->format('Y-m-d') : 'N/A' }}
                            </td>
                            <td class="py-4 px-6 text-slate-350 font-mono">
                                {{ $inv->due_date ? $inv->due_date->format('Y-m-d') : 'N/A' }}
                            </td>
                            <td class="py-4 px-6 font-black text-slate-200">
                                {{ number_format((float)$inv->grand_total, 2) }} BDT
                            </td>
                            <td class="py-4 px-6">
                                @php
                                    $statusColor = match($inv->status->value) {
                                        'issued' => 'text-cyan-400 bg-cyan-950/60 border border-cyan-900',
                                        'paid' => 'text-emerald-400 bg-emerald-950/60 border border-emerald-900',
                                        'draft' => 'text-slate-400 bg-slate-900/60 border border-slate-800',
                                        default => 'text-rose-400 bg-rose-950/60 border border-rose-900'
                                    };
                                @endphp
                                <span class="px-2.5 py-1 text-xs font-bold tracking-wide rounded-full border {{ $statusColor }} uppercase">
                                    {{ $inv->status->value }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <button wire:click="showInvoice({{ $inv->id }})" class="px-3.5 py-1.5 bg-slate-800 border border-slate-700 hover:border-slate-650 hover:bg-slate-750 text-slate-200 rounded-lg text-xs font-bold transition duration-200">
                                    View Details
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-500">
                                <svg class="w-12 h-12 mx-auto text-slate-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <p class="text-sm font-semibold">No invoices found.</p>
                                <p class="text-xs text-slate-650 mt-1">Generate dynamic billing subscriptions to populate invoice lists.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($invoices->hasPages())
            <div class="px-6 py-4 border-t border-slate-800 bg-slate-900/30">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>

    <!-- Invoice Details Modal -->
    @if($isDetailModalOpen && $selectedInvoice)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl w-full max-w-2xl shadow-2xl overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-800 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-white">Invoice Details</h3>
                        <p class="text-xs text-slate-450 mt-0.5">Reference: {{ $selectedInvoice->invoice_number }}</p>
                    </div>
                    <button wire:click="closeDetailModal" class="p-1 text-slate-400 hover:text-white rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <div class="p-6 space-y-6 max-h-[70vh] overflow-y-auto">
                    <!-- Top section / Summary -->
                    <div class="grid grid-cols-2 gap-4 bg-slate-950 border border-slate-850 p-5 rounded-2xl">
                        <div>
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">Customer</span>
                            <p class="text-sm font-bold text-white mt-1">{{ $selectedInvoice->customer->full_name }}</p>
                            <p class="text-xs text-slate-400">{{ $selectedInvoice->customer->phone }}</p>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">Invoice Status</span>
                            <div class="mt-1">
                                <span class="inline-block px-2.5 py-0.5 text-xs font-bold rounded-full border uppercase
                                    {{ $selectedInvoice->status->value === 'paid' ? 'text-emerald-400 bg-emerald-950/50 border-emerald-900' : 'text-cyan-400 bg-cyan-950/50 border-cyan-900' }}">
                                    {{ $selectedInvoice->status->value }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Dates & Type -->
                    <div class="grid grid-cols-3 gap-4 text-xs text-slate-400">
                        <div>
                            <span class="font-bold text-slate-500 uppercase">Billing Type</span>
                            <p class="text-slate-200 mt-1 font-semibold capitalize">{{ $selectedInvoice->invoice_type->value }}</p>
                        </div>
                        <div>
                            <span class="font-bold text-slate-500 uppercase">Issue Date</span>
                            <p class="text-slate-200 mt-1 font-semibold">{{ $selectedInvoice->issue_date ? $selectedInvoice->issue_date->format('Y-m-d') : 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="font-bold text-slate-500 uppercase">Due Date</span>
                            <p class="text-slate-200 mt-1 font-semibold">{{ $selectedInvoice->due_date ? $selectedInvoice->due_date->format('Y-m-d') : 'N/A' }}</p>
                        </div>
                    </div>

                    <!-- Line items table -->
                    <div>
                        <h4 class="text-sm font-bold text-white mb-3">Line Items</h4>
                        <div class="bg-slate-950 border border-slate-850 rounded-2xl overflow-hidden">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="bg-slate-900/60 text-slate-450 border-b border-slate-850 font-bold uppercase">
                                        <th class="py-3 px-4">Description</th>
                                        <th class="py-3 px-4 text-center">Qty</th>
                                        <th class="py-3 px-4 text-right">Unit Price</th>
                                        <th class="py-3 px-4 text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-850 text-slate-300">
                                    @foreach($selectedInvoice->lineItems as $item)
                                        <tr>
                                            <td class="py-3 px-4 font-medium text-slate-200">{{ $item->description }}</td>
                                            <td class="py-3 px-4 text-center">{{ number_format((float)$item->quantity, 0) }}</td>
                                            <td class="py-3 px-4 text-right">{{ number_format((float)$item->unit_price, 2) }} BDT</td>
                                            <td class="py-3 px-4 text-right font-semibold text-white">{{ number_format((float)$item->line_subtotal, 2) }} BDT</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Allocations & Receipt History -->
                    @if($selectedInvoice->allocations->isNotEmpty())
                        <div>
                            <h4 class="text-sm font-bold text-white mb-3">Payment Allocations & Receipt History</h4>
                            <div class="bg-slate-950 border border-slate-850 rounded-2xl p-4 space-y-3">
                                @foreach($selectedInvoice->allocations as $alloc)
                                    <div class="flex items-center justify-between text-xs border-b border-slate-900 pb-2.5 last:border-0 last:pb-0">
                                        <div>
                                            <p class="font-bold text-slate-200">
                                                Receipt: 
                                                @if($alloc->receipt)
                                                    <span class="text-cyan-400 font-mono">{{ $alloc->receipt->receipt_number }}</span>
                                                    <span class="text-slate-500 font-normal">({{ $alloc->receipt->payment_method->value }})</span>
                                                @else
                                                    <span class="text-slate-500 font-mono">N/A</span>
                                                @endif
                                            </p>
                                            <p class="text-slate-500 mt-0.5 font-mono text-[10px]">Date: {{ $alloc->created_at->format('Y-m-d H:i') }}</p>
                                        </div>
                                        <div class="text-right font-mono">
                                            <span class="font-bold text-emerald-400">+ {{ number_format((float)$alloc->allocation_amount, 2) }} BDT</span>
                                            <p class="text-slate-600 text-[10px] uppercase font-semibold">Order: #{{ $alloc->allocation_order }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Totals break down -->
                    <div class="flex justify-end">
                        <div class="w-full sm:w-64 space-y-2 text-sm">
                            <div class="flex justify-between text-slate-400">
                                <span>Subtotal:</span>
                                <span class="font-mono text-slate-200">{{ number_format((float)$selectedInvoice->subtotal, 2) }} BDT</span>
                            </div>
                            <div class="flex justify-between text-slate-400">
                                <span>Tax Total:</span>
                                <span class="font-mono text-slate-200">+ {{ number_format((float)$selectedInvoice->tax_total, 2) }} BDT</span>
                            </div>
                            <div class="flex justify-between text-slate-400">
                                <span>Discount:</span>
                                <span class="font-mono text-slate-200">- {{ number_format((float)$selectedInvoice->discount_total, 2) }} BDT</span>
                            </div>
                            <div class="flex justify-between text-white font-bold pt-2 border-t border-slate-800">
                                <span>Grand Total:</span>
                                <span class="font-mono text-cyan-400">{{ number_format((float)$selectedInvoice->grand_total, 2) }} BDT</span>
                            </div>
                            <div class="flex justify-between text-emerald-400 font-semibold">
                                <span>Amount Paid:</span>
                                <span class="font-mono">{{ number_format((float)$selectedInvoice->amount_paid, 2) }} BDT</span>
                            </div>
                            <div class="flex justify-between text-amber-400 font-bold border-t border-slate-800/60 pt-2">
                                <span>Outstanding Balance:</span>
                                <span class="font-mono">{{ number_format((float)$selectedInvoice->amount_due, 2) }} BDT</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-5 border-t border-slate-800 bg-slate-900/50 flex justify-end">
                    <button type="button" wire:click="closeDetailModal" class="px-5 py-2.5 bg-slate-850 hover:bg-slate-800 border border-slate-800 rounded-xl text-sm font-semibold text-slate-300 transition duration-150">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
