<div class="p-6 lg:p-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-slate-800 pb-6 mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-white tracking-tight">
                Gateway <span class="bg-gradient-to-r from-cyan-400 to-indigo-500 bg-clip-text text-transparent">Attempts</span>
            </h1>
            <p class="text-slate-400 text-sm mt-1">Audit logs of all online checkout flows, API callbacks, and verification states.</p>
        </div>
    </div>

    <!-- Search Control -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 mb-8 shadow-2xl">
        <div class="relative max-w-md">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </span>
            <input wire:model.live="search" type="text" placeholder="Search by reference, gateway, customer..." class="w-full bg-slate-950 text-slate-100 placeholder-slate-500 pl-10 pr-4 py-3 rounded-xl border border-slate-800 focus:outline-none focus:border-cyan-500 transition duration-200 text-sm">
        </div>
    </div>

    <!-- Attempts Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-400 text-xs font-bold uppercase tracking-wider bg-slate-900/50">
                        <th class="py-4 px-6">Customer</th>
                        <th class="py-4 px-6">Gateway</th>
                        <th class="py-4 px-6">Attempt Reference</th>
                        <th class="py-4 px-6">Gateway Transaction ID</th>
                        <th class="py-4 px-6 text-right">Requested Amount</th>
                        <th class="py-4 px-6">Status</th>
                        <th class="py-4 px-6">Verified At</th>
                        <th class="py-4 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-sm">
                    @forelse($attempts as $attempt)
                        <tr class="hover:bg-slate-850/40 transition duration-200">
                            <td class="py-4 px-6 font-semibold text-white">
                                {{ $attempt->customer ? $attempt->customer->full_name : 'N/A' }}
                            </td>
                            <td class="py-4 px-6 font-mono font-bold text-slate-400 uppercase">
                                {{ $attempt->gateway }}
                            </td>
                            <td class="py-4 px-6 font-mono text-slate-350">
                                {{ $attempt->attempt_reference }}
                            </td>
                            <td class="py-4 px-6 font-mono text-slate-400">
                                {{ $attempt->gateway_transaction_id ?? 'Pending' }}
                            </td>
                            <td class="py-4 px-6 text-right font-black text-white">
                                {{ number_format((float)$attempt->requested_amount, 2) }} {{ $attempt->currency }}
                            </td>
                            <td class="py-4 px-6">
                                @if($attempt->status === 'success' || $attempt->status === 'completed')
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-full border text-emerald-400 bg-emerald-950/50 border-emerald-900">
                                        Success
                                    </span>
                                @elseif($attempt->status === 'failed')
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-full border text-rose-400 bg-rose-950/50 border-rose-900">
                                        Failed
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 text-xs font-bold rounded-full border text-amber-400 bg-amber-950/50 border-amber-900">
                                        {{ ucfirst($attempt->status) }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-slate-500 font-mono">
                                {{ $attempt->verified_at ? $attempt->verified_at->format('Y-m-d H:i') : 'Unverified' }}
                            </td>
                            <td class="py-4 px-6 text-right">
                                <button wire:click="showPayload({{ $attempt->id }})" class="px-3.5 py-1.5 bg-slate-800 border border-slate-700 hover:border-slate-650 hover:bg-slate-750 text-slate-200 rounded-lg text-xs font-bold transition duration-200">
                                    Raw Payload
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-500">
                                <svg class="w-12 h-12 mx-auto text-slate-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <p class="text-sm font-semibold">No gateway attempts found.</p>
                                <p class="text-xs text-slate-650 mt-1">Gateway transaction logs will appear once customer checkout links are generated.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($attempts->hasPages())
            <div class="px-6 py-4 border-t border-slate-800 bg-slate-900/30">
                {{ $attempts->links() }}
            </div>
        @endif
    </div>

    <!-- Payload Details Modal -->
    @if($isPayloadModalOpen && $selectedAttempt)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl w-full max-w-2xl shadow-2xl overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-800 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-white">Gateway Payload Metadata</h3>
                        <p class="text-xs text-slate-450 mt-0.5">Attempt: {{ $selectedAttempt->attempt_reference }}</p>
                    </div>
                    <button wire:click="closePayloadModal" class="p-1 text-slate-400 hover:text-white rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <div class="p-6 max-h-[60vh] overflow-y-auto space-y-4">
                    <div class="bg-slate-950 border border-slate-850 rounded-2xl p-4 overflow-x-auto">
                        <pre class="text-xs text-cyan-400 font-mono leading-relaxed">{{ json_encode($selectedAttempt->gateway_payload, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                </div>
                <div class="px-6 py-5 border-t border-slate-800 bg-slate-900/50 flex justify-end">
                    <button type="button" wire:click="closePayloadModal" class="px-5 py-2.5 bg-slate-850 hover:bg-slate-800 border border-slate-800 rounded-xl text-sm font-semibold text-slate-300 transition duration-150">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
