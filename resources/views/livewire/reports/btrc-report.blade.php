<div class="space-y-8 min-h-screen bg-slate-950 text-slate-100 font-sans p-6 lg:p-8">

    <!-- Page Header -->
    <header class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-slate-800 pb-6 gap-4">
        <div>
            <div class="flex items-center gap-3">
                <span class="px-2.5 py-1 text-xs font-semibold tracking-wider text-cyan-450 bg-cyan-950/80 border border-cyan-800 rounded-full uppercase flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Compliance Module
                </span>
                <span class="px-2.5 py-1 text-xs font-semibold tracking-wider text-slate-400 bg-slate-900 border border-slate-800 rounded-full uppercase">
                    Statutory Report
                </span>
            </div>
            <h1 class="text-3xl font-extrabold text-white mt-2 tracking-tight">
                BTRC Regulatory <span class="bg-gradient-to-r from-cyan-400 to-indigo-500 bg-clip-text text-transparent">Report</span>
            </h1>
            <p class="text-slate-400 text-sm mt-1">Generate, review, and export the standardized BTRC compliance spreadsheet for active subscriber sessions.</p>
        </div>

        <!-- Top Action Bar -->
        <div class="flex flex-wrap items-center gap-4">
            <!-- POP/Zone Filter -->
            <div class="flex items-center gap-2 bg-slate-900 border border-slate-800 rounded-2xl px-3 py-1.5 shadow-xl">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <select wire:model.live="selectedPopId" class="bg-transparent border-0 focus:ring-0 text-sm font-semibold text-slate-200 outline-none cursor-pointer pr-8">
                    <option value="" class="bg-slate-900 text-slate-200">All POP Zones</option>
                    @foreach($pops as $pop)
                        <option value="{{ $pop->id }}" class="bg-slate-900 text-slate-200">{{ $pop->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Export Button -->
            <button wire:click="exportCsv" class="relative overflow-hidden flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-cyan-500 to-indigo-650 hover:from-cyan-400 hover:to-indigo-550 text-white text-sm font-bold rounded-2xl shadow-lg hover:shadow-cyan-500/25 transition-all duration-200" wire:loading.attr="disabled">
                <svg wire:loading.remove wire:target="exportCsv" class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <svg wire:loading wire:target="exportCsv" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Export CSV</span>
            </button>
        </div>
    </header>

    <!-- Table Section -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl flex flex-col">
        <div class="p-6 border-b border-slate-800 flex justify-between items-center bg-slate-900/60">
            <h2 class="text-lg font-bold text-white flex items-center gap-2">
                <span class="w-2.5 h-6 bg-cyan-500 rounded-full"></span>
                Report Preview (First 50 records)
            </h2>
            <div wire:loading wire:target="selectedPopId" class="text-xs text-cyan-400 flex items-center gap-2">
                <svg class="animate-spin h-3 w-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                Filtering data...
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300 min-w-[1500px]">
                <thead class="text-xs uppercase bg-slate-950/60 text-slate-400 font-semibold tracking-wider border-b border-slate-800">
                    <tr>
                        <th class="px-6 py-4">Name of Operator</th>
                        <th class="px-6 py-4">Type of Client</th>
                        <th class="px-6 py-4">Distribution Location</th>
                        <th class="px-6 py-4">Name of Client</th>
                        <th class="px-6 py-4">Connection Type</th>
                        <th class="px-6 py-4">Connectivity Type</th>
                        <th class="px-6 py-4">Activation Date</th>
                        <th class="px-6 py-4">Bandwidth (Mbps)</th>
                        <th class="px-6 py-4">Allocated IP</th>
                        <th class="px-6 py-4">Address</th>
                        <th class="px-6 py-4">Phone</th>
                        <th class="px-6 py-4 text-right">Selling Price (BDT)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80">
                    @forelse($previewData as $row)
                        <tr class="hover:bg-slate-850/30 transition-colors">
                            <td class="px-6 py-4 font-semibold text-slate-300">{{ $row['Name of Operator'] }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-md border border-slate-800 bg-slate-950 text-slate-400">
                                    {{ $row['Type of Client'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-slate-300">{{ $row['Distribution Location'] }}</td>
                            <td class="px-6 py-4 font-bold text-white">{{ $row['Name of Client'] }}</td>
                            <td class="px-6 py-4 text-slate-400">{{ $row['Connection Type'] }}</td>
                            <td class="px-6 py-4 text-slate-400">{{ $row['Connectivity Type'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-slate-400">{{ $row['Activation Date'] }}</td>
                            <td class="px-6 py-4 font-mono font-bold text-cyan-400">{{ $row['Bandwidth (Mbps)'] }}</td>
                            <td class="px-6 py-4 font-mono text-slate-300 text-xs">{{ $row['Allocated IP'] }}</td>
                            <td class="px-6 py-4 max-w-xs truncate text-slate-400" title="{{ $row['Address'] }}">{{ $row['Address'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-slate-300">{{ $row['Phone'] }}</td>
                            <td class="px-6 py-4 text-right font-bold text-slate-200">{{ number_format((float)$row['Selling Price (BDT)'], 2) }} BDT</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="px-6 py-16 text-center">
                                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-950 border border-slate-800 mb-4 text-slate-700">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                </div>
                                <h3 class="text-sm font-semibold text-slate-300">No Subscriber Data Found</h3>
                                <p class="text-xs text-slate-500 mt-1">There are no subscriber profiles matching the selection criteria.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
