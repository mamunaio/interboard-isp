<div class="p-6 lg:p-8 min-h-screen bg-slate-950 text-slate-100 font-sans antialiased">
    <!-- Header -->
    <header class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-slate-800 pb-6 mb-8 gap-4">
        <div>
            <div class="flex items-center gap-3">
                <span class="px-2.5 py-1 text-xs font-semibold tracking-wider text-fuchsia-400 bg-fuchsia-950 border border-fuchsia-800 rounded-full uppercase">
                    Network
                </span>
                <span class="text-xs text-slate-400">AAA Server</span>
            </div>
            <h1 class="text-3xl font-extrabold text-white mt-1 tracking-tight">
                Historical <span class="bg-gradient-to-r from-fuchsia-400 to-indigo-500 bg-clip-text text-transparent">Sessions</span>
            </h1>
            <p class="text-slate-400 text-sm mt-1">Audit log of all network access and data usage metrics.</p>
        </div>
    </header>

    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl mb-8">
        <div class="flex flex-col md:flex-row gap-4 mb-6">
            <div class="flex-1">
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by username, IP, MAC..." class="w-full bg-slate-950 border border-slate-800 focus:border-fuchsia-500 rounded-2xl px-4 py-2.5 text-sm outline-none transition">
            </div>
            <div class="w-full md:w-64">
                <select wire:model.live="statusFilter" class="w-full bg-slate-950 border border-slate-800 focus:border-fuchsia-500 rounded-2xl px-4 py-2.5 text-sm outline-none transition">
                    <option value="">All Sessions</option>
                    <option value="online">Currently Online</option>
                    <option value="offline">Disconnected / Offline</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-800">
                        <th class="py-3 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Session Time (Start & Stop)</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Identity</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Device & IP</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Usage (DL / UL)</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider text-right">Termination</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($sessions as $session)
                        <tr class="hover:bg-slate-800/30 transition duration-150">
                            <td class="py-4 px-4 align-middle">
                                <p class="text-sm font-bold text-white">{{ $session->acctstarttime?->format('M d, H:i:s') ?? 'N/A' }}</p>
                                @if($session->acctstoptime)
                                    <p class="text-[10px] text-slate-500 font-mono mt-0.5">End: {{ $session->acctstoptime->format('M d, H:i:s') }}</p>
                                    <p class="text-[10px] text-fuchsia-400/80 font-mono">Dur: {{ gmdate("H:i:s", $session->acctsessiontime ?? 0) }}</p>
                                @else
                                    <span class="inline-block mt-1 px-1.5 py-0.5 text-[9px] font-bold text-emerald-400 bg-emerald-950/80 border border-emerald-900 rounded uppercase">Active</span>
                                @endif
                            </td>
                            <td class="py-4 px-4 align-middle">
                                <p class="text-sm font-semibold text-slate-200">{{ $session->subscription->customer->full_name ?? 'N/A' }}</p>
                                <p class="text-xs text-indigo-400 font-mono">{{ $session->username }}</p>
                            </td>
                            <td class="py-4 px-4 align-middle">
                                <p class="text-xs font-mono text-slate-300">IP: {{ $session->framedipaddress ?: 'N/A' }}</p>
                                <p class="text-[10px] font-mono text-slate-500">MAC: {{ $session->callingstationid ?: 'N/A' }}</p>
                                <p class="text-[10px] font-mono text-slate-600">NAS: {{ $session->nasipaddress }}</p>
                            </td>
                            <td class="py-4 px-4 align-middle">
                                <p class="text-xs text-indigo-300 font-mono" title="Download">DL: {{ number_format(($session->acctoutputoctets ?? 0) / 1048576, 2) }} MB</p>
                                <p class="text-xs text-rose-300 font-mono mt-0.5" title="Upload">UL: {{ number_format(($session->acctinputoctets ?? 0) / 1048576, 2) }} MB</p>
                            </td>
                            <td class="py-4 px-4 align-middle text-right">
                                @if($session->acctstoptime)
                                    <span class="px-2 py-1 text-[10px] font-bold text-slate-400 bg-slate-950 border border-slate-800 rounded uppercase">
                                        {{ $session->acctterminatecause ?: 'Normal' }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-600 italic">Ongoing</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-500 text-sm">
                                No network sessions recorded yet.
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
