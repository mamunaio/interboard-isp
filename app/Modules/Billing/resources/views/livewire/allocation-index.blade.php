<div class="p-6 lg:p-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-slate-800 pb-6 mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-white tracking-tight">
                Payment <span class="bg-gradient-to-r from-cyan-400 to-indigo-500 bg-clip-text text-transparent">Allocations</span>
            </h1>
            <p class="text-slate-400 text-sm mt-1">Audit how customer payments map against specific outstanding invoices.</p>
        </div>
    </div>

    <!-- Search Control -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 mb-8 shadow-2xl">
        <div class="relative max-w-md">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </span>
            <input wire:model.live="search" type="text" placeholder="Search by invoice, receipt number or customer..." class="w-full bg-slate-950 text-slate-100 placeholder-slate-500 pl-10 pr-4 py-3 rounded-xl border border-slate-800 focus:outline-none focus:border-cyan-500 transition duration-200 text-sm">
        </div>
    </div>

    <!-- Allocations Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-400 text-xs font-bold uppercase tracking-wider bg-slate-900/50">
                        <th class="py-4 px-6">Customer</th>
                        <th class="py-4 px-6">Invoice Number</th>
                        <th class="py-4 px-6">Receipt Number</th>
                        <th class="py-4 px-6 text-right">Allocated Amount</th>
                        <th class="py-4 px-6 text-center">FIFO Order</th>
                        <th class="py-4 px-6">Notes / Description</th>
                        <th class="py-4 px-6">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-sm">
                    @forelse($allocations as $alloc)
                        <tr class="hover:bg-slate-850/40 transition duration-200">
                            <td class="py-4 px-6 font-semibold text-white">
                                {{ $alloc->wallet && $alloc->wallet->owner ? $alloc->wallet->owner->full_name : 'System Owner' }}
                            </td>
                            <td class="py-4 px-6 font-mono font-bold text-cyan-400">
                                @if($alloc->invoice)
                                    {{ $alloc->invoice->invoice_number }}
                                @else
                                    <span class="text-slate-500">Deleted Invoice</span>
                                @endif
                            </td>
                            <td class="py-4 px-6 font-mono text-slate-350">
                                @if($alloc->receipt)
                                    {{ $alloc->receipt->receipt_number }}
                                @else
                                    <span class="text-slate-500">Manual Allocate</span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-right font-black text-white">
                                {{ number_format((float)$alloc->allocation_amount, 2) }} BDT
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="px-2 py-0.5 text-xs font-bold rounded bg-slate-800 text-slate-400 border border-slate-700">
                                    #{{ $alloc->allocation_order }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-slate-400 max-w-xs truncate" title="{{ $alloc->notes }}">
                                {{ $alloc->notes ?? 'Auto Allocation' }}
                            </td>
                            <td class="py-4 px-6 text-slate-500 font-mono">
                                {{ $alloc->created_at->format('Y-m-d H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-500">
                                <svg class="w-12 h-12 mx-auto text-slate-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                <p class="text-sm font-semibold">No allocations found.</p>
                                <p class="text-xs text-slate-650 mt-1">Allocations are posted when outstanding invoices are offset by wallet credits.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($allocations->hasPages())
            <div class="px-6 py-4 border-t border-slate-800 bg-slate-900/30">
                {{ $allocations->links() }}
            </div>
        @endif
    </div>
</div>
