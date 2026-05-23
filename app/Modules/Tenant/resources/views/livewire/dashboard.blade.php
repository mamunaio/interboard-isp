<div class="min-h-screen bg-slate-950 text-slate-100 font-sans antialiased p-6 lg:p-8">
    <!-- Topbar navigation context -->
    <header class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-slate-800/60 pb-6 mb-8 gap-4">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <span class="px-2.5 py-1 text-[10px] font-bold tracking-widest text-cyan-400 bg-cyan-950/50 border border-cyan-800/60 rounded-full uppercase">
                    ISP Portal
                </span>
                <span class="text-[10px] font-bold tracking-widest text-slate-500 uppercase">v1.0.0 Stable</span>
            </div>
            <h1 class="text-3xl font-extrabold text-white tracking-tight">
                Billing <span class="bg-gradient-to-r from-cyan-400 to-indigo-500 bg-clip-text text-transparent">Monitoring & Control</span>
            </h1>
            <p class="text-slate-400 text-sm mt-1 font-medium">Real-time collections telemetry & system audit control.</p>
        </div>
        <div class="flex items-center gap-4">
            <button wire:click="refreshStats" class="flex items-center justify-center w-11 h-11 rounded-2xl bg-slate-900 border border-slate-800 hover:border-cyan-500/50 hover:bg-slate-800 text-slate-400 hover:text-cyan-400 transition-all duration-200 group shadow-lg" title="Force Metric Refresh">
                <svg class="w-5 h-5 group-hover:rotate-180 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            </button>
            <div class="flex items-center gap-4 bg-slate-900/80 border border-slate-800/80 px-5 py-3 rounded-2xl shadow-lg backdrop-blur-md">
                <div class="w-2.5 h-2.5 bg-emerald-500 rounded-full animate-pulse shadow-[0_0_10px_rgba(16,185,129,0.7)]"></div>
                <div>
                    <p class="text-[10px] text-slate-500 font-bold tracking-wider uppercase">Reverb Connection</p>
                    <p class="text-sm font-semibold text-slate-200">Listening to Live Stream</p>
                </div>
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
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
        <!-- Card 1: Active customers -->
        <div class="relative overflow-hidden bg-slate-900/80 border border-slate-800 hover:border-cyan-500/40 hover:-translate-y-1 transition-all duration-300 rounded-3xl p-6 shadow-xl hover:shadow-cyan-500/10 group cursor-default">
            <div class="absolute -right-6 -top-6 w-32 h-32 bg-cyan-500/10 rounded-full blur-2xl group-hover:bg-cyan-500/20 transition duration-300"></div>
            <div class="flex items-center justify-between mb-6">
                <span class="text-xs font-bold uppercase tracking-widest text-slate-400">Active Customers</span>
                <div class="p-2.5 bg-cyan-950/50 text-cyan-400 border border-cyan-900/60 rounded-xl group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </div>
            </div>
            <div class="text-4xl font-black text-white tracking-tighter">{{ $stats['active_customers'] }}</div>
        </div>

        <!-- Card 2: Active Subscriptions -->
        <div class="relative overflow-hidden bg-slate-900/80 border border-slate-800 hover:border-indigo-500/40 hover:-translate-y-1 transition-all duration-300 rounded-3xl p-6 shadow-xl hover:shadow-indigo-500/10 group cursor-default">
            <div class="absolute -right-6 -top-6 w-32 h-32 bg-indigo-500/10 rounded-full blur-2xl group-hover:bg-indigo-500/20 transition duration-300"></div>
            <div class="flex items-center justify-between mb-6">
                <span class="text-xs font-bold uppercase tracking-widest text-slate-400">Subscriptions</span>
                <div class="p-2.5 bg-indigo-950/50 text-indigo-400 border border-indigo-900/60 rounded-xl group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                </div>
            </div>
            <div class="text-4xl font-black text-white tracking-tighter">{{ $stats['active_subscriptions'] }}</div>
        </div>

        <!-- Card 3: Unpaid Invoices -->
        <div class="relative overflow-hidden bg-slate-900/80 border border-slate-800 hover:border-amber-500/40 hover:-translate-y-1 transition-all duration-300 rounded-3xl p-6 shadow-xl hover:shadow-amber-500/10 group cursor-default">
            <div class="absolute -right-6 -top-6 w-32 h-32 bg-amber-500/10 rounded-full blur-2xl group-hover:bg-amber-500/20 transition duration-300"></div>
            <div class="flex items-center justify-between mb-6">
                <span class="text-xs font-bold uppercase tracking-widest text-slate-400">Unpaid Invoices</span>
                <div class="p-2.5 bg-amber-950/50 text-amber-400 border border-amber-900/60 rounded-xl group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
            </div>
            <div class="text-4xl font-black text-white tracking-tighter">{{ $stats['unpaid_invoices'] }}</div>
        </div>

        <!-- Card 4: Overdue Invoices -->
        <div class="relative overflow-hidden bg-slate-900/80 border border-slate-800 hover:border-rose-500/40 hover:-translate-y-1 transition-all duration-300 rounded-3xl p-6 shadow-xl hover:shadow-rose-500/10 group cursor-default">
            <div class="absolute -right-6 -top-6 w-32 h-32 bg-rose-500/10 rounded-full blur-2xl group-hover:bg-rose-500/20 transition duration-300"></div>
            <div class="flex items-center justify-between mb-6">
                <span class="text-xs font-bold uppercase tracking-widest text-slate-400">Overdue Invoices</span>
                <div class="p-2.5 bg-rose-950/50 text-rose-400 border border-rose-900/60 rounded-xl group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
            <div class="text-4xl font-black text-rose-500 tracking-tighter">{{ $stats['overdue_invoices'] }}</div>
        </div>

        <!-- Card 5: Wallet Balances -->
        <div class="relative overflow-hidden bg-slate-900/80 border border-slate-800 hover:border-emerald-500/40 hover:-translate-y-1 transition-all duration-300 rounded-3xl p-6 shadow-xl hover:shadow-emerald-500/10 group cursor-default lg:col-span-1 md:col-span-2 sm:col-span-1">
            <div class="absolute -right-6 -top-6 w-32 h-32 bg-emerald-500/10 rounded-full blur-2xl group-hover:bg-emerald-500/20 transition duration-300"></div>
            <div class="flex items-center justify-between mb-6">
                <span class="text-xs font-bold uppercase tracking-widest text-slate-400">Total Wallets</span>
                <div class="p-2.5 bg-emerald-950/50 text-emerald-400 border border-emerald-900/60 rounded-xl group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                </div>
            </div>
            <div class="text-3xl font-black text-white tracking-tighter">{{ number_format((float)$stats['total_wallet_balance'], 2) }} <span class="text-sm font-bold text-slate-500 ml-1">BDT</span></div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
        <!-- Left: Gateway attempts stats -->
        <div class="space-y-8 xl:col-span-1">
            <!-- Gateway summary -->
            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-b from-indigo-500/5 to-transparent pointer-events-none"></div>
                <h3 class="text-sm font-bold text-slate-300 uppercase tracking-widest mb-6 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-indigo-500/20 flex items-center justify-center text-indigo-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                    Gateway Status
                </h3>
                
                @php
                    $totalGateways = array_sum($gatewaySummary) ?: 1;
                @endphp
                
                <div class="space-y-5 relative z-10">
                    @foreach($gatewaySummary as $status => $count)
                        @php
                            $percentage = ($count / $totalGateways) * 100;
                            $textClass = match($status) {
                                'verified' => 'text-emerald-400',
                                'failed' => 'text-rose-400',
                                'pending' => 'text-amber-400',
                                default => 'text-slate-400'
                            };
                            $bgClass = match($status) {
                                'verified' => 'bg-emerald-500',
                                'failed' => 'bg-rose-500',
                                'pending' => 'bg-amber-500',
                                default => 'bg-slate-500'
                            };
                        @endphp
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold uppercase tracking-wider {{ $textClass }}">{{ $status }}</span>
                                <span class="text-sm font-black text-white">{{ $count }}</span>
                            </div>
                            <div class="w-full bg-slate-950 border border-slate-800/80 rounded-full h-2 overflow-hidden">
                                <div class="{{ $bgClass }} h-2 rounded-full transition-all duration-1000 ease-out" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Right: Activity audit trails -->
        <div class="xl:col-span-2 space-y-8">
            <!-- Recent receipts -->
            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-b from-emerald-500/5 to-transparent pointer-events-none"></div>
                <h3 class="text-sm font-bold text-slate-300 uppercase tracking-widest mb-6 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/20 flex items-center justify-center text-emerald-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    Recent Payment Receipts
                </h3>
                <div class="space-y-3 relative z-10">
                    @forelse($recentReceipts as $rcpt)
                        <div class="group flex flex-col sm:flex-row sm:items-center justify-between p-4 bg-slate-950/40 border border-slate-800/60 rounded-2xl hover:bg-slate-800/40 hover:border-slate-700 transition duration-200">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-full bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 flex-shrink-0 group-hover:bg-emerald-500/20 transition-colors duration-200">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-white flex items-center gap-2">
                                        #{{ $rcpt['receipt_number'] }}
                                        <span class="px-2 py-0.5 rounded text-[9px] uppercase tracking-wider bg-slate-800 text-slate-400">{{ $rcpt['payment_method'] }}</span>
                                    </p>
                                    <p class="text-xs text-slate-400 mt-1 font-medium">{{ $rcpt['customer']['full_name'] ?? 'Unknown Customer' }}</p>
                                </div>
                            </div>
                            <div class="mt-3 sm:mt-0 sm:text-right">
                                <span class="text-base font-black text-emerald-400 bg-emerald-400/10 px-3 py-1 rounded-lg">+{{ number_format((float)$rcpt['amount'], 2) }} <span class="text-[10px] text-emerald-500/80">BDT</span></span>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center bg-slate-950/40 border border-slate-800/60 rounded-2xl border-dashed">
                            <svg class="w-8 h-8 text-slate-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4M8 16l-4-4 4-4"></path></svg>
                            <p class="text-sm text-slate-400 font-medium">No payment receipts registered yet.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Recent suspension events & Reversals Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Suspensions -->
                <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-b from-rose-500/5 to-transparent pointer-events-none"></div>
                    <h3 class="text-sm font-bold text-slate-300 uppercase tracking-widest mb-6 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-rose-500/20 flex items-center justify-center text-rose-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                        Suspensions
                    </h3>
                    <div class="space-y-3 relative z-10">
                        @forelse($recentSuspensions as $evt)
                            <div class="p-4 bg-slate-950/40 border border-slate-800/60 rounded-2xl hover:border-rose-900 transition duration-200">
                                <div class="flex items-start justify-between mb-2">
                                    <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-rose-500/10 text-rose-400">{{ $evt['action_type'] }}</span>
                                    <span class="text-[10px] text-slate-500 font-mono">{{ \Carbon\Carbon::parse($evt['created_at'])->diffForHumans(null, true, true) }}</span>
                                </div>
                                <p class="text-sm font-bold text-slate-200 mb-1">{{ $evt['subscription']['subscription_code'] ?? 'N/A' }}</p>
                                <p class="text-xs text-slate-500 line-clamp-1">{{ $evt['reason'] }}</p>
                            </div>
                        @empty
                            <p class="text-xs text-slate-500 italic">No events.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Reversals -->
                <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-b from-amber-500/5 to-transparent pointer-events-none"></div>
                    <h3 class="text-sm font-bold text-slate-300 uppercase tracking-widest mb-6 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-amber-500/20 flex items-center justify-center text-amber-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.334 4zM4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z"></path></svg>
                        </div>
                        Reversals
                    </h3>
                    <div class="space-y-3 relative z-10">
                        @forelse($recentReversals as $rev)
                            <div class="p-4 bg-slate-950/40 border border-slate-800/60 rounded-2xl hover:border-amber-900 transition duration-200">
                                <div class="flex items-start justify-between mb-2">
                                    <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-amber-500/10 text-amber-400">{{ str_replace('_', ' ', $rev['reversal_type']) }}</span>
                                    <span class="text-[11px] font-black text-rose-400">-{{ number_format((float)$rev['amount'], 2) }}</span>
                                </div>
                                <p class="text-xs text-slate-400 line-clamp-2 mt-1">{{ $rev['reason'] }}</p>
                            </div>
                        @empty
                            <p class="text-xs text-slate-500 italic">No reversals.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
