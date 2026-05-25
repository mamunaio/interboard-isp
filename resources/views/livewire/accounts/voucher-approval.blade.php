<div class="space-y-8 min-h-screen bg-slate-950 text-slate-100 font-sans p-6 lg:p-8" x-data="{ 
    notification: null,
    showNotification(type, message) {
        this.notification = { type, message };
        setTimeout(() => { this.notification = null; }, 4000);
    }
}" @notify.window="showNotification($event.detail[0].type, $event.detail[0].message)">

    <!-- Notification Toast -->
    <template x-if="notification">
        <div class="fixed bottom-5 right-5 z-50 flex items-center gap-3 px-5 py-4 rounded-2xl shadow-2xl border transition-all duration-300 transform translate-y-0"
             :class="{
                'bg-emerald-950 border-emerald-800 text-emerald-300': notification.type === 'success',
                'bg-rose-950 border-rose-800 text-rose-300': notification.type === 'error'
             }">
            <svg x-show="notification.type === 'success'" class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <svg x-show="notification.type === 'error'" class="w-5 h-5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <span class="text-sm font-semibold" x-text="notification.message"></span>
        </div>
    </template>

    <!-- Page Header -->
    <header class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-slate-800 pb-6 gap-4">
        <div>
            <div class="flex items-center gap-3">
                <span class="px-2.5 py-1 text-xs font-semibold tracking-wider text-amber-400 bg-amber-950/80 border border-amber-800 rounded-full uppercase flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                    Maker-Checker Workflow
                </span>
                <span class="px-2.5 py-1 text-xs font-semibold tracking-wider text-slate-400 bg-slate-900 border border-slate-800 rounded-full uppercase">
                    Accounts Approval
                </span>
            </div>
            <h1 class="text-3xl font-extrabold text-white mt-2 tracking-tight">
                Pending Voucher <span class="bg-gradient-to-r from-amber-400 to-rose-500 bg-clip-text text-transparent">Approvals</span>
            </h1>
            <p class="text-slate-400 text-sm mt-1">Review and approve operational expense vouchers submitted by makers.</p>
        </div>
    </header>

    <!-- Approvals Main Section -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl flex flex-col">
        <div class="p-6 border-b border-slate-800 flex justify-between items-center bg-slate-900/60">
            <h2 class="text-lg font-bold text-white flex items-center gap-2">
                <span class="w-2.5 h-6 bg-amber-500 rounded-full"></span>
                Vouchers Awaiting Verification
            </h2>
            <span class="px-3 py-1 text-xs font-bold text-slate-300 bg-slate-950 border border-slate-850 rounded-full">
                {{ $pendingVouchers->count() }} Vouchers Pending
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="text-xs uppercase bg-slate-950/50 text-slate-400 font-semibold tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Expense Date</th>
                        <th class="px-6 py-4">Category</th>
                        <th class="px-6 py-4">Submitted By</th>
                        <th class="px-6 py-4">Description</th>
                        <th class="px-6 py-4 text-right">Amount</th>
                        <th class="px-6 py-4 text-center">Receipt</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80">
                    @forelse($pendingVouchers as $voucher)
                        <tr class="hover:bg-slate-850/40 transition-colors duration-150">
                            <!-- Date -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-semibold text-slate-200">
                                    {{ $voucher->expense_date->format('d M, Y') }}
                                </span>
                                @if($voucher->reference_no)
                                    <span class="block text-[10px] text-slate-500 mt-1 uppercase tracking-wider">Ref: {{ $voucher->reference_no }}</span>
                                @endif
                            </td>

                            <!-- Category -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-semibold bg-slate-950 text-slate-300 border border-slate-800">
                                    {{ $voucher->category->name ?? 'Uncategorized' }}
                                </span>
                            </td>

                            <!-- Submitted By -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-slate-950 border border-slate-850 flex items-center justify-center text-[10px] font-bold text-slate-300">
                                        {{ substr($voucher->user->name ?? 'M', 0, 1) }}
                                    </div>
                                    <span class="text-xs text-slate-300 font-medium">{{ $voucher->user->name ?? 'Maker / Agent' }}</span>
                                </div>
                            </td>

                            <!-- Description -->
                            <td class="px-6 py-4">
                                <div class="text-xs text-slate-400 max-w-xs truncate" title="{{ $voucher->description }}">
                                    {{ $voucher->description ?: '-' }}
                                </div>
                            </td>

                            <!-- Amount -->
                            <td class="px-6 py-4 whitespace-nowrap text-right font-bold text-slate-200">
                                {{ number_format((float)$voucher->amount, 2) }} BDT
                            </td>

                            <!-- Receipt -->
                            <td class="px-6 py-4 text-center">
                                @if($voucher->receipt_path)
                                    <a href="{{ asset('storage/' . $voucher->receipt_path) }}" target="_blank" class="inline-flex items-center justify-center p-2 rounded-xl bg-slate-950 hover:bg-slate-800 text-slate-400 hover:text-cyan-400 border border-slate-850 hover:border-slate-800 transition duration-200" title="View Receipt File">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </a>
                                @else
                                    <span class="text-xs text-slate-650">-</span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="inline-flex gap-2">
                                    <!-- Reject -->
                                    <button wire:click="reject({{ $voucher->id }})" 
                                            wire:confirm="Are you sure you want to REJECT this expense voucher for BDT {{ number_format((float)$voucher->amount, 2) }}?"
                                            class="px-3.5 py-2 text-xs font-bold bg-slate-950 border border-slate-850 hover:border-rose-500/50 hover:bg-rose-950/20 text-slate-400 hover:text-rose-400 rounded-xl transition duration-150">
                                        Reject
                                    </button>

                                    <!-- Approve -->
                                    <button wire:click="approve({{ $voucher->id }})" 
                                            wire:confirm="Are you sure you want to APPROVE this expense voucher for BDT {{ number_format((float)$voucher->amount, 2) }}?"
                                            class="px-4 py-2 text-xs font-bold bg-emerald-500 hover:bg-emerald-400 text-white rounded-xl shadow-lg hover:shadow-emerald-500/10 transition duration-150">
                                        Approve
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-950 border border-slate-800 mb-4">
                                    <svg class="w-8 h-8 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <h3 class="text-sm font-semibold text-slate-300">Clean Approval Queue</h3>
                                <p class="text-xs text-slate-500 mt-1">There are no pending expense vouchers awaiting approval at this time.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
