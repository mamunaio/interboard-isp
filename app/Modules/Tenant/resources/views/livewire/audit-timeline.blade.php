<div class="min-h-screen bg-transparent p-6 lg:p-8">
    <header class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-slate-200 dark:border-slate-800 pb-6 mb-8 gap-4">
        <div>
            <div class="flex items-center gap-3">
                <span class="px-2.5 py-1 text-xs font-semibold tracking-wider text-cyan-600 dark:text-cyan-400 bg-cyan-50 dark:bg-cyan-950 border border-cyan-200 dark:border-cyan-800 rounded-full uppercase">
                    Audit Logging
                </span>
                <span class="text-xs text-slate-500 dark:text-slate-400">System Telemetry Logs</span>
            </div>
            <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white mt-1 tracking-tight">
                Billing <span class="bg-gradient-to-r from-cyan-500 to-indigo-500 dark:from-cyan-400 dark:to-indigo-500 bg-clip-text text-transparent">Operations Audit</span>
            </h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Immutable ledger trail & system modification log.</p>
        </div>
    </header>

    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm dark:shadow-2xl mb-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <!-- Search field -->
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Search Logs</label>
                <div class="relative">
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by ID, IP, User Agent..." class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 focus:border-cyan-500 rounded-2xl px-4 py-3 text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 outline-none transition duration-200">
                </div>
            </div>

            <!-- Model Type Filter -->
            <div>
                <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Filter Entity</label>
                <select wire:model.live="modelFilter" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 focus:border-cyan-500 rounded-2xl px-4 py-3 text-sm text-slate-900 dark:text-slate-100 outline-none transition duration-200">
                    <option value="">All Entities</option>
                    <option value="Invoice">Invoices</option>
                    <option value="PaymentReceipt">Payment Receipts</option>
                    <option value="LedgerEntry">Ledger / Wallets</option>
                    <option value="PaymentAllocation">Allocations</option>
                    <option value="SuspensionEvent">Suspensions</option>
                    <option value="ReversalEvent">Reversals</option>
                    <option value="GatewayPaymentAttempt">Gateway Attempts</option>
                </select>
            </div>

            <!-- Event Filter -->
            <div>
                <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Filter Action</label>
                <select wire:model.live="eventFilter" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 focus:border-cyan-500 rounded-2xl px-4 py-3 text-sm text-slate-900 dark:text-slate-100 outline-none transition duration-200">
                    <option value="">All Actions</option>
                    <option value="created">Created</option>
                    <option value="updated">Updated</option>
                    <option value="deleted">Deleted</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Audit Timeline -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm dark:shadow-2xl">
        <div class="flow-root">
            <ul role="list" class="-mb-8">
                @forelse($logs as $log)
                    <li>
                        <div class="relative pb-8">
                            @if (!$loop->last)
                                <span class="absolute top-5 left-5 -ml-px h-full w-0.5 bg-slate-200 dark:bg-slate-800" aria-hidden="true"></span>
                            @endif
                            <div class="relative flex items-start space-x-4">
                                <!-- Event icon badge -->
                                <div class="relative">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 shadow-sm dark:shadow-md">
                                        @if($log->event === 'created')
                                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        @elseif($log->event === 'updated')
                                            <span class="w-2.5 h-2.5 rounded-full bg-cyan-500"></span>
                                        @else
                                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                                        @endif
                                    </div>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div>
                                        <div class="text-sm">
                                            <span class="font-bold text-slate-900 dark:text-white">{{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}</span>
                                            <span class="ml-2 px-2 py-0.5 text-xs font-semibold rounded-md uppercase tracking-wider
                                                @if($log->event === 'created') text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-900/50
                                                @elseif($log->event === 'updated') text-cyan-600 dark:text-cyan-400 bg-cyan-50 dark:bg-cyan-950/60 border border-cyan-200 dark:border-cyan-900/50
                                                @else text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-900/50
                                                @endif">
                                                {{ $log->event }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            {{ $log->created_at->toDayDateTimeString() }} ({{ $log->created_at->diffForHumans() }})
                                        </p>
                                    </div>
                                    <div class="mt-2 text-sm text-slate-600 dark:text-slate-350">
                                        @if($log->event === 'updated' && !empty($log->new_values))
                                            <div class="bg-slate-50 dark:bg-slate-950/60 rounded-2xl p-4 border border-slate-200 dark:border-slate-850 mt-2 space-y-1.5">
                                                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">Attribute Modifications</p>
                                                @foreach($log->new_values as $key => $newVal)
                                                    <div class="flex items-center gap-2 text-xs">
                                                        <span class="font-semibold text-slate-600 dark:text-slate-400 font-mono">{{ $key }}:</span>
                                                        <span class="text-rose-600 dark:text-rose-400 line-through bg-rose-100 dark:bg-rose-950/20 px-1 py-0.5 rounded font-mono">{{ is_scalar($log->old_values[$key] ?? '') ? $log->old_values[$key] ?? 'NULL' : json_encode($log->old_values[$key]) }}</span>
                                                        <svg class="w-3.5 h-3.5 text-slate-400 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                                        <span class="text-emerald-600 dark:text-emerald-400 bg-emerald-100 dark:bg-emerald-950/20 px-1 py-0.5 rounded font-mono">{{ is_scalar($newVal) ? $newVal ?? 'NULL' : json_encode($newVal) }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @elseif($log->event === 'created' && !empty($log->new_values))
                                            <div class="bg-slate-50 dark:bg-slate-950/40 rounded-2xl p-4 border border-slate-200 dark:border-slate-850/50 mt-2">
                                                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Created Attributes</p>
                                                <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs font-mono">
                                                    @foreach(array_slice($log->new_values, 0, 8) as $key => $val)
                                                        @if(is_scalar($val))
                                                            <div class="truncate"><span class="text-slate-500">{{ $key }}:</span> <span class="text-slate-700 dark:text-slate-300">{{ $val }}</span></div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="mt-2 flex items-center gap-4 text-xs text-slate-500">
                                        <span>IP: <strong class="text-slate-700 dark:text-slate-400 font-mono">{{ $log->ip_address ?? 'CLI' }}</strong></span>
                                        <span>Agent: <strong class="text-slate-700 dark:text-slate-400">{{ $log->user_agent ?? 'System Process' }}</strong></span>
                                        @if($log->user_id)
                                            <span>Actor: <strong class="text-slate-700 dark:text-slate-400 font-mono">{{ class_basename($log->user_type) }} #{{ $log->user_id }}</strong></span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                @empty
                    <div class="flex flex-col items-center justify-center p-12 text-center bg-slate-50 dark:bg-slate-950/40 border border-dashed border-slate-300 dark:border-slate-800 rounded-3xl">
                        <svg class="w-12 h-12 text-slate-400 dark:text-slate-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">No matching audit logs found.</p>
                        <p class="text-xs text-slate-500 dark:text-slate-600 mt-0.5">Try widening filters or entering a different search query.</p>
                    </div>
                @endforelse
            </ul>
        </div>

        <div class="mt-8 border-t border-slate-200 dark:border-slate-850 pt-4">
            {{ $logs->links() }}
        </div>
    </div>
</div>
