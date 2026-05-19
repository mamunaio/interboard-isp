<div class="p-6 lg:p-8 min-h-screen bg-slate-950 text-slate-100 font-sans antialiased">
    <!-- Header -->
    <header class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-slate-800 pb-6 mb-8 gap-4">
        <div>
            <div class="flex items-center gap-3">
                <span class="px-2.5 py-1 text-xs font-semibold tracking-wider text-emerald-400 bg-emerald-950 border border-emerald-800 rounded-full uppercase">
                    Network
                </span>
                <span class="text-xs text-slate-400">AAA Server</span>
            </div>
            <h1 class="text-3xl font-extrabold text-white mt-1 tracking-tight">
                Online <span class="bg-gradient-to-r from-emerald-400 to-cyan-500 bg-clip-text text-transparent">Users</span>
            </h1>
            <p class="text-slate-400 text-sm mt-1">Real-time view of active PPPoE and Hotspot sessions.</p>
        </div>
    </header>

    @if (session()->has('message'))
        <div class="mb-6 p-4 bg-emerald-950/60 border border-emerald-800 text-emerald-400 rounded-2xl flex items-center gap-3">
            <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span class="text-sm font-semibold">{{ session('message') }}</span>
        </div>
    @endif
    
    @if (session()->has('error'))
        <div class="mb-6 p-4 bg-rose-950/60 border border-rose-800 text-rose-400 rounded-2xl flex items-center gap-3">
            <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span class="text-sm font-semibold">{{ session('error') }}</span>
        </div>
    @endif

    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl mb-8">
        <div class="flex flex-col md:flex-row gap-4 mb-6">
            <div class="flex-1">
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by username, IP, customer name..." class="w-full bg-slate-950 border border-slate-800 focus:border-emerald-500 rounded-2xl px-4 py-2.5 text-sm outline-none transition">
            </div>
            <div class="w-full md:w-48 flex items-center justify-end">
                <button wire:click="$refresh" class="flex items-center gap-2 text-sm font-semibold text-emerald-400 hover:text-emerald-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    Refresh
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-800">
                        <th class="py-3 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Customer / Username</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Assigned IP</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">MAC Address</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Uptime</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Data (DL / UL)</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($sessions as $session)
                        <tr class="hover:bg-slate-800/30 transition duration-150">
                            <td class="py-4 px-4 align-middle">
                                <p class="text-sm font-bold text-white">{{ $session->subscription->customer->full_name ?? 'Unknown Customer' }}</p>
                                <p class="text-xs text-emerald-400 font-mono">{{ $session->username }}</p>
                            </td>
                            <td class="py-4 px-4 align-middle">
                                <span class="px-2 py-1 text-xs font-mono bg-slate-950 border border-slate-800 rounded text-slate-300">
                                    {{ $session->framedipaddress ?: 'N/A' }}
                                </span>
                            </td>
                            <td class="py-4 px-4 align-middle">
                                <span class="text-xs font-mono text-slate-400 uppercase">{{ $session->callingstationid ?: 'N/A' }}</span>
                            </td>
                            <td class="py-4 px-4 align-middle">
                                <p class="text-sm text-slate-200">
                                    {{ $session->acctstarttime ? $session->acctstarttime->diffForHumans(null, true, true) : 'Unknown' }}
                                </p>
                                <p class="text-[10px] text-slate-500 uppercase">{{ $session->acctstarttime?->format('Y-m-d H:i') }}</p>
                            </td>
                            <td class="py-4 px-4 align-middle">
                                <p class="text-xs text-indigo-300 font-mono" title="Download">DL: {{ number_format(($session->acctoutputoctets ?? 0) / 1048576, 2) }} MB</p>
                                <p class="text-xs text-rose-300 font-mono mt-0.5" title="Upload">UL: {{ number_format(($session->acctinputoctets ?? 0) / 1048576, 2) }} MB</p>
                            </td>
                            <td class="py-4 px-4 align-middle text-right">
                                <button 
                                    wire:click="disconnectUser('{{ $session->username }}')"
                                    wire:confirm="Are you sure you want to forcefully disconnect {{ $session->username }}?"
                                    class="px-3 py-1.5 text-xs font-bold text-white bg-rose-600 hover:bg-rose-500 rounded-lg transition shadow-lg shadow-rose-900/20"
                                >
                                    Disconnect
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-500 text-sm">
                                No active online users found right now.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-6">
            {{ $sessions->links() }}
        </div>
    </div>
</div>
