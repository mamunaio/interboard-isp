<div class="min-h-screen bg-slate-950 text-slate-100 font-sans antialiased p-6 lg:p-8">
    <!-- Topbar navigation context -->
    <header class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-slate-800 pb-6 mb-8 gap-4">
        <div>
            <div class="flex items-center gap-3">
                <span class="px-2.5 py-1 text-xs font-semibold tracking-wider text-cyan-400 bg-cyan-950 border border-cyan-800 rounded-full uppercase">
                    ISP Portal
                </span>
                <span class="text-xs text-slate-400">v1.0.0 Stable</span>
            </div>
            <h1 class="text-3xl font-extrabold text-white mt-1 tracking-tight">
                Billing <span class="bg-gradient-to-r from-cyan-400 to-indigo-500 bg-clip-text text-transparent">Monitoring & Control</span>
            </h1>
            <p class="text-slate-400 text-sm mt-1">Real-time collections telemetry & system audit control.</p>
        </div>
        <div class="flex items-center gap-4 bg-slate-900 border border-slate-800 px-4 py-3 rounded-2xl backdrop-blur-md">
            <div class="w-3 h-3 bg-emerald-500 rounded-full animate-ping"></div>
            <div>
                <p class="text-xs text-slate-400 font-medium">Reverb Connection</p>
                <p class="text-sm font-semibold text-slate-200">Listening to Live Stream</p>
            </div>
        </div>
    </header>

    <!-- Real-time Notifications Toast Panel -->
    @if(count($notifications) > 0)
        <div class="mb-8 bg-slate-900/80 border border-cyan-800/80 rounded-3xl p-6 shadow-2xl backdrop-blur-md relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-r from-cyan-500/10 to-transparent pointer-events-none"></div>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-bold text-cyan-400 uppercase tracking-widest flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-cyan-400 animate-ping"></span>
                    Live Broadcast Stream
                </h3>
                <button wire:click="clearNotifications" class="text-xs text-slate-400 hover:text-white font-semibold transition duration-155">
                    Clear Alerts
                </button>
            </div>
            <div class="space-y-3">
                @foreach($notifications as $notif)
                    <div class="flex items-center justify-between p-3.5 bg-slate-950/60 border border-slate-800/80 rounded-2xl text-sm gap-4 transition duration-200">
                        <div class="flex items-center gap-3">
                            <span class="flex-shrink-0 p-1.5 rounded-lg
                                @if($notif['type'] === 'payment') bg-emerald-950 text-emerald-400 border border-emerald-900
                                @elseif($notif['type'] === 'suspension') bg-rose-950 text-rose-400 border border-rose-900
                                @elseif($notif['type'] === 'reversal') bg-amber-950 text-amber-400 border border-amber-900
                                @else bg-cyan-950 text-cyan-400 border border-cyan-900
                                @endif">
                                @if($notif['type'] === 'payment')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                @elseif($notif['type'] === 'suspension')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                @elseif($notif['type'] === 'reversal')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.334 4zM4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z"></path></svg>
                                @else
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                @endif
                            </span>
                            <span class="text-slate-200 font-medium">{{ $notif['message'] }}</span>
                        </div>
                        <span class="text-xs text-slate-500 font-mono whitespace-nowrap">{{ $notif['time'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- KPI status cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
        <!-- Card 1: Active customers -->
        <div class="relative overflow-hidden bg-slate-900 border border-slate-800 hover:border-cyan-500/50 transition duration-300 rounded-3xl p-6 shadow-2xl group">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-cyan-500/10 rounded-full blur-xl group-hover:bg-cyan-500/20 transition duration-300"></div>
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-slate-400">Active Customers</span>
                <div class="p-2.5 bg-cyan-950/50 text-cyan-400 border border-cyan-900 rounded-2xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </div>
            </div>
            <div class="text-3xl font-black text-white tracking-tight">{{ $stats['active_customers'] }}</div>
        </div>

        <!-- Card 2: Active Subscriptions -->
        <div class="relative overflow-hidden bg-slate-900 border border-slate-800 hover:border-indigo-500/50 transition duration-300 rounded-3xl p-6 shadow-2xl group">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-indigo-500/10 rounded-full blur-xl group-hover:bg-indigo-500/20 transition duration-300"></div>
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-slate-400">Active Subscriptions</span>
                <div class="p-2.5 bg-indigo-950/50 text-indigo-400 border border-indigo-900 rounded-2xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                </div>
            </div>
            <div class="text-3xl font-black text-white tracking-tight">{{ $stats['active_subscriptions'] }}</div>
        </div>

        <!-- Card 3: Unpaid Invoices -->
        <div class="relative overflow-hidden bg-slate-900 border border-slate-800 hover:border-amber-500/50 transition duration-300 rounded-3xl p-6 shadow-2xl group">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-amber-500/10 rounded-full blur-xl group-hover:bg-amber-500/20 transition duration-300"></div>
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-slate-400">Unpaid Invoices</span>
                <div class="p-2.5 bg-amber-950/50 text-amber-400 border border-amber-900 rounded-2xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
            </div>
            <div class="text-3xl font-black text-white tracking-tight">{{ $stats['unpaid_invoices'] }}</div>
        </div>

        <!-- Card 4: Overdue Invoices -->
        <div class="relative overflow-hidden bg-slate-900 border border-slate-800 hover:border-rose-500/50 transition duration-300 rounded-3xl p-6 shadow-2xl group">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-rose-500/10 rounded-full blur-xl group-hover:bg-rose-500/20 transition duration-300"></div>
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-slate-400">Overdue Invoices</span>
                <div class="p-2.5 bg-rose-950/50 text-rose-400 border border-rose-900 rounded-2xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
            <div class="text-3xl font-black text-rose-500 tracking-tight">{{ $stats['overdue_invoices'] }}</div>
        </div>

        <!-- Card 5: Wallet Balances -->
        <div class="relative overflow-hidden bg-slate-900 border border-slate-800 hover:border-emerald-500/50 transition duration-300 rounded-3xl p-6 shadow-2xl group">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-500/10 rounded-full blur-xl group-hover:bg-emerald-500/20 transition duration-300"></div>
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-slate-400">Total Wallets</span>
                <div class="p-2.5 bg-emerald-950/50 text-emerald-400 border border-emerald-900 rounded-2xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                </div>
            </div>
            <div class="text-2xl font-black text-white tracking-tight">{{ $stats['total_wallet_balance'] }} BDT</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left: Gateway attempts stats & Reverb controls -->
        <div class="space-y-8 lg:col-span-1">
            <!-- Gateway summary -->
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl">
                <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                    <span class="w-2 h-4 bg-indigo-500 rounded-full"></span>
                    Gateway Status Summary
                </h3>
                <div class="space-y-3.5">
                    @foreach($gatewaySummary as $status => $count)
                        <div class="flex items-center justify-between p-3 bg-slate-950/60 border border-slate-850 rounded-2xl">
                            <span class="text-xs font-semibold uppercase tracking-wider
                                @if($status === 'verified') text-emerald-400
                                @elseif($status === 'failed') text-rose-400
                                @elseif($status === 'pending') text-amber-400
                                @else text-slate-400
                                @endif">
                                {{ $status }}
                            </span>
                            <span class="text-sm font-bold text-white">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Manual Force Metric update -->
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl">
                <button wire:click="refreshStats" class="w-full py-4 bg-gradient-to-r from-cyan-600 to-indigo-600 hover:from-cyan-550 hover:to-indigo-550 text-white rounded-2xl font-bold text-sm transition duration-155 shadow-lg shadow-indigo-950/50">
                    Force Metric Refresh
                </button>
            </div>
        </div>

        <!-- Right: Activity audit trails -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Recent receipts -->
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl">
                <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                    <span class="w-2 h-4 bg-emerald-500 rounded-full"></span>
                    Recent Payment Receipts
                </h3>
                <div class="space-y-4">
                    @forelse($recentReceipts as $rcpt)
                        <div class="flex items-center justify-between p-4 bg-slate-950/40 border border-slate-850 rounded-2xl hover:bg-slate-950 transition duration-150">
                            <div>
                                <p class="text-sm font-semibold text-slate-200">Receipt #{{ $rcpt['receipt_number'] }}</p>
                                <p class="text-xs text-slate-400 mt-0.5">Customer: {{ $rcpt['customer']['full_name'] ?? 'Unknown' }} • Method: <span class="capitalize">{{ $rcpt['payment_method'] }}</span></p>
                            </div>
                            <span class="text-sm font-bold text-emerald-400">+{{ number_format((float)$rcpt['amount'], 2) }} BDT</span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 italic py-4">No payment receipts registered yet.</p>
                    @endforelse
                </div>
            </div>

            <!-- Recent suspension events -->
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl">
                <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                    <span class="w-2 h-4 bg-rose-500 rounded-full"></span>
                    Recent Suspension & Action Events
                </h3>
                <div class="space-y-4">
                    @forelse($recentSuspensions as $evt)
                        <div class="flex items-center justify-between p-4 bg-slate-950/40 border border-slate-850 rounded-2xl hover:bg-slate-950 transition duration-150">
                            <div>
                                <p class="text-sm font-semibold text-slate-200">Subscription: {{ $evt['subscription']['subscription_code'] ?? 'N/A' }}</p>
                                <p class="text-xs text-slate-400 mt-0.5">Action: <span class="uppercase font-bold tracking-widest text-[9px] text-rose-400">{{ $evt['action_type'] }}</span> • Reason: {{ $evt['reason'] }}</p>
                            </div>
                            <span class="text-xs text-slate-500 font-mono">{{ \Carbon\Carbon::parse($evt['created_at'])->diffForHumans() }}</span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 italic py-4">No suspension events recorded yet.</p>
                    @endforelse
                </div>
            </div>

            <!-- Recent reversal events -->
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl">
                <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                    <span class="w-2 h-4 bg-amber-500 rounded-full"></span>
                    Recent Reversals & Adjusts
                </h3>
                <div class="space-y-4">
                    @forelse($recentReversals as $rev)
                        <div class="flex items-center justify-between p-4 bg-slate-950/40 border border-slate-850 rounded-2xl hover:bg-slate-950 transition duration-150">
                            <div>
                                <p class="text-sm font-semibold text-slate-200">Reversal Event</p>
                                <p class="text-xs text-slate-400 mt-0.5">Reason: {{ $rev['reason'] }} • Type: <span class="capitalize text-slate-300 font-semibold">{{ str_replace('_', ' ', $rev['reversal_type']) }}</span></p>
                            </div>
                            <span class="text-sm font-bold text-rose-400">-{{ number_format((float)$rev['amount'], 2) }} BDT</span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 italic py-4">No reversals recorded yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
