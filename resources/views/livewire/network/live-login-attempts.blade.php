<div class="space-y-8 min-h-screen bg-slate-950 text-slate-100 font-sans p-6 lg:p-8" 
     wire:poll.10s="fetchLogs">

    <!-- Page Header -->
    <header class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-slate-800 pb-6 gap-4">
        <div>
            <div class="flex items-center gap-3">
                <span class="px-2.5 py-1 text-xs font-semibold tracking-wider text-rose-400 bg-rose-950/80 border border-rose-800 rounded-full uppercase flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-ping"></span>
                    Live Streaming
                </span>
                <span class="px-2.5 py-1 text-xs font-semibold tracking-wider text-slate-400 bg-slate-900 border border-slate-800 rounded-full uppercase">
                    MikroTik logs
                </span>
            </div>
            <h1 class="text-3xl font-extrabold text-white mt-2 tracking-tight">
                PPPoE <span class="bg-gradient-to-r from-rose-500 to-amber-500 bg-clip-text text-transparent">Auth Failures</span>
            </h1>
            <p class="text-slate-400 text-sm mt-1">Real-time tracking of unsuccessful PPPoE authentication attempts across configured NAS routers.</p>
        </div>

        <!-- Top Action Bar -->
        <div class="flex flex-wrap items-center gap-4">
            <!-- Router Dropdown Selector -->
            <div class="flex items-center gap-2 bg-slate-900 border border-slate-800 rounded-2xl px-3 py-1.5 shadow-xl">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                <select wire:model.live="selectedRouterId" class="bg-transparent border-0 focus:ring-0 text-sm font-semibold text-slate-200 outline-none cursor-pointer pr-8">
                    @foreach($routers as $router)
                        @php $r = (object) $router; @endphp
                        <option value="{{ $r->id }}" class="bg-slate-900 text-slate-200">{{ $r->name }} ({{ $r->ip_address ?? 'No IP' }})</option>
                    @endforeach
                </select>
            </div>

            <!-- Manual Refresh Button -->
            <button wire:click="fetchLogs" class="relative overflow-hidden flex items-center gap-2 px-5 py-2.5 bg-slate-900 hover:bg-slate-850 border border-slate-800 hover:border-rose-500/50 text-slate-200 hover:text-white text-sm font-semibold rounded-2xl shadow-xl transition-all duration-200" wire:loading.attr="disabled">
                <!-- Sparkle rotation loader inside wire:loading -->
                <svg wire:loading wire:target="fetchLogs" class="animate-spin w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg wire:loading.remove wire:target="fetchLogs" class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 8H18.25"></path>
                </svg>
                <span>Refresh</span>
            </button>
        </div>
    </header>

    <!-- Error Banner -->
    @if($errorMessage)
        <div class="bg-rose-950/60 border border-rose-900 rounded-3xl p-5 shadow-2xl flex items-start gap-4">
            <div class="p-3 bg-rose-900/40 border border-rose-800 rounded-2xl text-rose-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            <div>
                <h4 class="font-bold text-white">Router Communication Warning</h4>
                <p class="text-xs text-rose-300/80 mt-1">{{ $errorMessage }}</p>
                <p class="text-[10px] text-rose-400 mt-2 uppercase tracking-widest font-bold">Displaying high-fidelity local cache simulated feed</p>
            </div>
        </div>
    @endif

    <!-- Main Container: Logs Streaming Board -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl flex flex-col">
        <div class="p-6 border-b border-slate-800 flex justify-between items-center bg-slate-900/60">
            <h2 class="text-lg font-bold text-white flex items-center gap-2">
                <span class="w-2.5 h-6 bg-rose-500 rounded-full"></span>
                Authentication Failure Records
            </h2>
            <div class="flex items-center gap-3">
                <!-- Subtle indicator to show state of polling -->
                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <svg wire:loading class="animate-spin h-3.5 w-3.5 text-rose-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span wire:loading>Querying API...</span>
                    <span wire:loading.remove class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Auto-refreshing every 10s
                    </span>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="text-xs uppercase bg-slate-950/50 text-slate-400 font-semibold tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Time</th>
                        <th class="px-6 py-4">PPPoE User</th>
                        <th class="px-6 py-4">RouterOS Log Message</th>
                        <th class="px-6 py-4 text-center">Status / Reason</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80">
                    @forelse($loginLogs as $log)
                        <tr class="hover:bg-slate-850/40 transition-colors duration-150">
                            <!-- Time Column -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-mono text-xs font-bold text-slate-400 bg-slate-950 px-2.5 py-1 rounded-lg border border-slate-800/80">
                                    {{ $log['time'] }}
                                </span>
                            </td>

                            <!-- PPPoE User Column -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-center text-xs font-mono font-bold text-slate-300">
                                        @
                                    </div>
                                    <span class="font-semibold text-slate-200 hover:text-rose-400 transition-colors cursor-pointer">
                                        {{ $log['username'] }}
                                    </span>
                                </div>
                            </td>

                            <!-- Log Message Column -->
                            <td class="px-6 py-4">
                                <div class="text-xs font-mono text-slate-400 select-all max-w-xl truncate" title="{{ $log['message'] }}">
                                    {{ $log['message'] }}
                                </div>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <span class="text-[9px] font-bold uppercase tracking-wider text-slate-600">Topics:</span>
                                    <span class="text-[9px] font-mono text-slate-500">{{ $log['topics'] }}</span>
                                </div>
                            </td>

                            <!-- Status Badge Column -->
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($log['reason'] === 'Invalid Password')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-950 text-rose-400 border border-rose-800/60 shadow-lg shadow-rose-950/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                                        {{ $log['reason'] }}
                                    </span>
                                @elseif($log['reason'] === 'User Not Found')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-950 text-amber-400 border border-amber-800/60 shadow-lg shadow-amber-950/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        {{ $log['reason'] }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-slate-950 text-slate-400 border border-slate-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span>
                                        {{ $log['reason'] }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-16 text-center">
                                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-950 border border-slate-800 mb-4">
                                    <svg class="w-8 h-8 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-sm font-semibold text-slate-300">All Systems Operational</h3>
                                <p class="text-xs text-slate-500 mt-1">No PPPoE authentication errors detected on the selected router.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
