<div class="p-6 lg:p-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-slate-800 pb-6 mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-white tracking-tight">
                Customer <span class="bg-gradient-to-r from-cyan-400 to-indigo-500 bg-clip-text text-transparent">Wallets</span>
            </h1>
            <p class="text-slate-400 text-sm mt-1">Track customer ledger balances, credit histories, deposits, and refunds.</p>
        </div>
    </div>

    <!-- Search Control -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 mb-8 shadow-2xl">
        <div class="relative max-w-md">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </span>
            <input wire:model.live="search" type="text" placeholder="Search by customer name or currency..." class="w-full bg-slate-950 text-slate-100 placeholder-slate-500 pl-10 pr-4 py-3 rounded-xl border border-slate-800 focus:outline-none focus:border-cyan-500 transition duration-200 text-sm">
        </div>
    </div>

    <!-- Wallets Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-400 text-xs font-bold uppercase tracking-wider bg-slate-900/50">
                        <th class="py-4 px-6">Customer Owner</th>
                        <th class="py-4 px-6">Currency</th>
                        <th class="py-4 px-6">Current Balance</th>
                        <th class="py-4 px-6">Status</th>
                        <th class="py-4 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-sm">
                    @forelse($wallets as $wallet)
                        <tr class="hover:bg-slate-850/40 transition duration-200">
                            <td class="py-4 px-6 font-semibold text-white">
                                {{ $wallet->owner ? $wallet->owner->full_name : 'System Owner' }}
                                @if($wallet->owner && isset($wallet->owner->customer_code))
                                    <span class="block text-xs font-mono font-normal text-slate-550 mt-0.5">{{ $wallet->owner->customer_code }}</span>
                                @endif
                            </td>
                            <td class="py-4 px-6 font-mono font-bold text-slate-400">
                                {{ $wallet->currency }}
                            </td>
                            <td class="py-4 px-6 font-black text-cyan-400">
                                {{ number_format((float)$wallet->current_balance, 2) }} {{ $wallet->currency }}
                            </td>
                            <td class="py-4 px-6">
                                @if($wallet->active)
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-full border text-emerald-400 bg-emerald-950/50 border-emerald-900">
                                        Active
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-full border text-rose-400 bg-rose-950/50 border-rose-900">
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-right">
                                <button wire:click="showLedger({{ $wallet->id }})" class="px-3.5 py-1.5 bg-slate-800 border border-slate-700 hover:border-slate-650 hover:bg-slate-750 text-slate-200 rounded-lg text-xs font-bold transition duration-200">
                                    Ledger Log
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-slate-500">
                                <svg class="w-12 h-12 mx-auto text-slate-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                <p class="text-sm font-semibold">No wallets found.</p>
                                <p class="text-xs text-slate-650 mt-1">Submit manual payments to auto-initialize subscriber wallets.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($wallets->hasPages())
            <div class="px-6 py-4 border-t border-slate-800 bg-slate-900/30">
                {{ $wallets->links() }}
            </div>
        @endif
    </div>

    <!-- Ledger Details Modal -->
    @if($isLedgerModalOpen && $selectedWallet)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl w-full max-w-4xl shadow-2xl overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-800 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-white">Wallet Ledger</h3>
                        <p class="text-xs text-slate-450 mt-0.5">Owner: {{ $selectedWallet->owner ? $selectedWallet->owner->full_name : 'System' }}</p>
                    </div>
                    <button wire:click="closeLedgerModal" class="p-1 text-slate-400 hover:text-white rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <div class="p-6 max-h-[60vh] overflow-y-auto space-y-4">
                    <div class="overflow-x-auto bg-slate-950 border border-slate-850 rounded-2xl">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-slate-900/60 text-slate-450 border-b border-slate-850 font-bold uppercase">
                                    <th class="py-3 px-4">Transaction Type</th>
                                    <th class="py-3 px-4">Direction</th>
                                    <th class="py-3 px-4 text-right">Amount</th>
                                    <th class="py-3 px-4 text-right">Balance Before</th>
                                    <th class="py-3 px-4 text-right">Balance After</th>
                                    <th class="py-3 px-4">Description/Notes</th>
                                    <th class="py-3 px-4">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-850 text-slate-300">
                                @forelse($ledgerEntries as $entry)
                                    <tr class="hover:bg-slate-900/40">
                                        <td class="py-3 px-4 font-mono uppercase text-slate-400">
                                            {{ str_replace('_', ' ', $entry->transaction_type->value) }}
                                        </td>
                                        <td class="py-3 px-4 font-bold capitalize">
                                            @if($entry->direction->value === 'credit')
                                                <span class="text-emerald-400">+ Credit</span>
                                            @else
                                                <span class="text-rose-455">- Debit</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-right font-semibold text-white">
                                            {{ number_format((float)$entry->amount, 2) }}
                                        </td>
                                        <td class="py-3 px-4 text-right text-slate-400 font-mono">
                                            {{ number_format((float)$entry->balance_before, 2) }}
                                        </td>
                                        <td class="py-3 px-4 text-right text-cyan-400 font-mono">
                                            {{ number_format((float)$entry->balance_after, 2) }}
                                        </td>
                                        <td class="py-3 px-4 text-slate-400 max-w-xs truncate" title="{{ $entry->notes }}">
                                            {{ $entry->notes ?? 'N/A' }}
                                        </td>
                                        <td class="py-3 px-4 text-slate-450 font-mono">
                                            {{ $entry->created_at->format('Y-m-d H:i') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="py-6 text-center text-slate-550">No transaction records found on ledger.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="px-6 py-5 border-t border-slate-800 bg-slate-900/50 flex justify-end">
                    <button type="button" wire:click="closeLedgerModal" class="px-5 py-2.5 bg-slate-850 hover:bg-slate-800 border border-slate-800 rounded-xl text-sm font-semibold text-slate-300 transition duration-150">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
