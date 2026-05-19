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
                Enterprise <span class="bg-gradient-to-r from-cyan-400 to-indigo-500 bg-clip-text text-transparent">Operations Control</span>
            </h1>
            <p class="text-slate-400 text-sm mt-1">Real-time MikroTik traffic & GPON status overview.</p>
        </div>
        <div class="flex items-center gap-4 bg-slate-900 border border-slate-800 px-4 py-3 rounded-2xl backdrop-blur-md">
            <div class="w-3 h-3 bg-emerald-500 rounded-full animate-ping"></div>
            <div>
                <p class="text-xs text-slate-400 font-medium">Reverb Connection</p>
                <p class="text-sm font-semibold text-slate-200">Listening to Live Stream</p>
            </div>
        </div>
    </header>

    <!-- KPI status cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Card 1: Active customers -->
        <div class="relative overflow-hidden bg-slate-900 border border-slate-800 hover:border-cyan-500/50 transition duration-300 rounded-3xl p-6 shadow-2xl group">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-cyan-500/10 rounded-full blur-xl group-hover:bg-cyan-500/20 transition duration-300"></div>
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-slate-400">Active Subscribers</span>
                <div class="p-2.5 bg-cyan-950/50 text-cyan-400 border border-cyan-900 rounded-2xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </div>
            </div>
            <div class="text-3xl font-black text-white tracking-tight">{{ $stats['active_customers'] ?? 0 }}</div>
            <div class="flex items-center gap-2 mt-2">
                <span class="text-xs font-semibold text-emerald-400 bg-emerald-950/50 px-2 py-0.5 rounded-full border border-emerald-900">+4.2%</span>
                <span class="text-xs text-slate-500">since last billing</span>
            </div>
        </div>

        <!-- Card 2: Suspended Customers -->
        <div class="relative overflow-hidden bg-slate-900 border border-slate-800 hover:border-rose-500/50 transition duration-300 rounded-3xl p-6 shadow-2xl group">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-rose-500/10 rounded-full blur-xl group-hover:bg-rose-500/20 transition duration-300"></div>
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-slate-400">Suspended Users</span>
                <div class="p-2.5 bg-rose-950/50 text-rose-400 border border-rose-900 rounded-2xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                </div>
            </div>
            <div class="text-3xl font-black text-white tracking-tight">{{ $stats['suspended_customers'] ?? 0 }}</div>
            <div class="flex items-center gap-2 mt-2">
                <span class="text-xs font-semibold text-slate-400 bg-slate-950/50 px-2 py-0.5 rounded-full border border-slate-800">Critical</span>
                <span class="text-xs text-slate-500">requiring follow-up</span>
            </div>
        </div>

        <!-- Card 3: GPON ONU Online -->
        <div class="relative overflow-hidden bg-slate-900 border border-slate-800 hover:border-indigo-500/50 transition duration-300 rounded-3xl p-6 shadow-2xl group">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-indigo-500/10 rounded-full blur-xl group-hover:bg-indigo-500/20 transition duration-300"></div>
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-slate-400">GPON ONU Online</span>
                <div class="p-2.5 bg-indigo-950/50 text-indigo-400 border border-indigo-900 rounded-2xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                </div>
            </div>
            <div class="text-3xl font-black text-white tracking-tight">{{ $stats['gpon_onu_online'] ?? 0 }}</div>
            <div class="flex items-center gap-2 mt-2">
                <span class="text-xs font-semibold text-emerald-400 bg-emerald-950/50 px-2 py-0.5 rounded-full border border-emerald-900">99.4%</span>
                <span class="text-xs text-slate-500">uptime check ratio</span>
            </div>
        </div>

        <!-- Card 4: GPON ONU Offline -->
        <div class="relative overflow-hidden bg-slate-900 border border-slate-800 hover:border-amber-500/50 transition duration-300 rounded-3xl p-6 shadow-2xl group">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-amber-500/10 rounded-full blur-xl group-hover:bg-amber-500/20 transition duration-300"></div>
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-slate-400">ONU Anomalies</span>
                <div class="p-2.5 bg-amber-950/50 text-amber-400 border border-amber-900 rounded-2xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
            </div>
            <div class="text-3xl font-black text-rose-500 tracking-tight">{{ $stats['gpon_onu_offline'] ?? 0 }}</div>
            <div class="flex items-center gap-2 mt-2">
                <span class="text-xs font-semibold text-rose-400 bg-rose-950/50 px-2 py-0.5 rounded-full border border-rose-900">Offline</span>
                <span class="text-xs text-slate-500">needs fiber inspection</span>
            </div>
        </div>
    </div>

    <!-- Live Bandwidth & Activity logs stream layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Live traffic analyzer -->
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl lg:col-span-1">
            <h3 class="text-lg font-bold text-white mb-4">MikroTik Live Traffic</h3>
            <div class="flex flex-col items-center justify-center p-6 bg-slate-950 rounded-2xl border border-slate-800/80 mb-6">
                <!-- Pulsing Radar Graphic -->
                <div class="relative w-40 h-40 flex items-center justify-center mb-6">
                    <div class="absolute inset-0 rounded-full border-2 border-cyan-500/20 animate-ping"></div>
                    <div class="absolute inset-4 rounded-full border-2 border-cyan-500/40 animate-ping" style="animation-delay: 0.5s;"></div>
                    <div class="absolute inset-8 rounded-full border border-cyan-500/60"></div>
                    <div class="z-10 text-center">
                        <span class="text-4xl font-black text-white tracking-tighter">{{ $trafficSpeed }}</span>
                        <span class="block text-xs font-bold uppercase tracking-widest text-cyan-400 mt-1">Gbps</span>
                    </div>
                </div>
                <div class="w-full bg-slate-800 h-2 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-cyan-400 to-indigo-500 rounded-full" style="width: {{ min(100, floatval($trafficSpeed) * 2) }}%"></div>
                </div>
                <div class="flex items-center justify-between w-full mt-3 text-xs text-slate-400">
                    <span>Min: 12.4 Gbps</span>
                    <span>Max: 100 Gbps</span>
                </div>
            </div>
            <button wire:click="refreshStats" class="w-full py-3 bg-slate-800 hover:bg-slate-750 border border-slate-700 hover:border-slate-600 rounded-2xl font-semibold text-sm transition duration-200">
                Force Metric Query
            </button>
        </div>

        <!-- Activity Audit logs list -->
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl lg:col-span-2">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-white">System Audit Activity</h3>
                <span wire:loading.class="animate-spin" class="text-slate-400 cursor-pointer p-1.5 hover:bg-slate-850 rounded-lg">
                    <svg wire:click="refreshLogs" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.253 8H18"></path></svg>
                </span>
            </div>
            
            <div class="space-y-4">
                @forelse($recentLogs as $log)
                    <div class="flex items-start gap-4 p-4 bg-slate-950/50 hover:bg-slate-950 transition duration-200 border border-slate-850 rounded-2xl">
                        <div class="p-2 bg-slate-900 border border-slate-800 text-slate-400 rounded-xl mt-0.5">
                            @if($log['event'] === 'created')
                                <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            @elseif($log['event'] === 'updated')
                                <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.253 8H18"></path></svg>
                            @else
                                <svg class="w-5 h-5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-4">
                                <p class="text-sm font-semibold text-slate-200 truncate">
                                    {{ class_basename($log['auditable_type']) }} #{{ $log['auditable_id'] }} {{ ucfirst($log['event']) }}
                                </p>
                                <span class="text-xs text-slate-500 font-medium whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($log['created_at'])->diffForHumans() }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-400 mt-1 leading-relaxed truncate">
                                IP: {{ $log['ip_address'] ?? 'CLI' }} • User-Agent: {{ $log['user_agent'] ?? 'System Process' }}
                            </p>
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center p-12 text-center bg-slate-950/40 border border-dashed border-slate-800 rounded-3xl">
                        <svg class="w-12 h-12 text-slate-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                        <p class="text-sm font-medium text-slate-400">No recent activity logged yet.</p>
                        <p class="text-xs text-slate-600 mt-0.5">Start updating system entities to record activities.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
