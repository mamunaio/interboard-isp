<div class="space-y-8 min-h-screen bg-slate-950 text-slate-100 font-sans p-6 lg:p-8">

    <!-- Page Header -->
    <header class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-slate-800 pb-6 gap-4">
        <div>
            <div class="flex items-center gap-3">
                <span class="px-2.5 py-1 text-xs font-semibold tracking-wider text-emerald-400 bg-emerald-950/80 border border-emerald-800 rounded-full uppercase flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    Capacity Monitor
                </span>
                <span class="px-2.5 py-1 text-xs font-semibold tracking-wider text-slate-400 bg-slate-900 border border-slate-800 rounded-full uppercase">
                    Route Matrix
                </span>
            </div>
            <h1 class="text-3xl font-extrabold text-white mt-2 tracking-tight">
                Box Load <span class="bg-gradient-to-r from-emerald-400 to-teal-500 bg-clip-text text-transparent">Matrix</span>
            </h1>
            <p class="text-slate-400 text-sm mt-1">Real-time port allocation & capacity utilization index across active splitters and distribution boxes.</p>
        </div>

        <!-- Top Action Bar -->
        <div class="flex items-center gap-4">
            <!-- POP Selector -->
            <div class="flex items-center gap-2 bg-slate-900 border border-slate-800 rounded-2xl px-4 py-2 shadow-xl">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <select wire:model.live="selectedPopId" class="bg-transparent border-0 focus:ring-0 text-sm font-semibold text-slate-200 outline-none cursor-pointer pr-8">
                    <option value="" class="bg-slate-900 text-slate-200">Select POP Zone</option>
                    @foreach($pops as $pop)
                        <option value="{{ $pop->id }}" class="bg-slate-900 text-slate-200">{{ $pop->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </header>

    <!-- Main Content Section -->
    @if($activePop)
        <div class="space-y-6">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                    <span class="w-2.5 h-6 bg-emerald-500 rounded-full"></span>
                    Splitters inside "{{ $activePop->name }}"
                </h3>
                <div wire:loading wire:target="selectedPopId" class="text-xs text-emerald-400 flex items-center gap-2">
                    <svg class="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span>Calculating loads...</span>
                </div>
            </div>

            <!-- Route Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($boxData as $box)
                    @php
                        $util = $box['utilization_percentage'];
                        // Color coding utilization state
                        if ($util < 70) {
                            $colorName = 'emerald';
                            $textClass = 'text-emerald-400 bg-emerald-950/80 border-emerald-800';
                            $progressClass = 'bg-gradient-to-r from-emerald-500 to-teal-500';
                        } elseif ($util >= 70 && $util <= 90) {
                            $colorName = 'amber';
                            $textClass = 'text-amber-400 bg-amber-950/80 border-amber-800';
                            $progressClass = 'bg-gradient-to-r from-amber-500 to-orange-500';
                        } else {
                            $colorName = 'rose';
                            $textClass = 'text-rose-450 bg-rose-950/80 border-rose-800';
                            $progressClass = 'bg-gradient-to-r from-rose-500 to-red-500 animate-pulse';
                        }
                    @endphp
                    
                    <div class="relative bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl hover:border-slate-700/80 transition-all duration-300 transform hover:-translate-y-1">
                        
                        <!-- Header Details -->
                        <div class="flex justify-between items-start gap-3">
                            <div>
                                <span class="text-[10px] uppercase tracking-widest font-bold text-slate-500">Splitter Box</span>
                                <h4 class="text-lg font-bold text-white mt-1">{{ $box['name'] }}</h4>
                                <span class="text-xs text-slate-450 block mt-1 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                                    {{ $box['location_details'] }}
                                </span>
                            </div>

                            <!-- Utilization pill badge -->
                            <span class="px-2.5 py-1 text-xs font-mono font-bold rounded-xl border {{ $textClass }}">
                                {{ $util }}%
                            </span>
                        </div>

                        <!-- Progress Bar Section -->
                        <div class="mt-6 space-y-2">
                            <div class="flex justify-between text-xs font-medium">
                                <span class="text-slate-450">Capacity Utilization</span>
                                <span class="text-slate-300 font-bold font-mono">{{ $box['used_ports'] }} / {{ $box['total_capacity'] }} Ports</span>
                            </div>
                            
                            <!-- Custom HTML5 slider-like progress bar -->
                            <div class="w-full bg-slate-950 border border-slate-850 rounded-full h-3 overflow-hidden">
                                <div class="h-full rounded-full {{ $progressClass }}" style="width: {{ min(100, $util) }}%"></div>
                            </div>
                        </div>

                        <!-- Port Grid Statistics -->
                        <div class="grid grid-cols-2 gap-4 border-t border-slate-800/80 pt-4 mt-5">
                            <div>
                                <span class="text-[10px] uppercase tracking-wider text-slate-500 font-semibold">Available</span>
                                <span class="block text-sm font-bold text-emerald-400 font-mono mt-0.5">{{ $box['available_ports'] }} Ports</span>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] uppercase tracking-wider text-slate-500 font-semibold">Total Load</span>
                                <span class="block text-sm font-bold text-slate-300 font-mono mt-0.5">{{ $box['total_capacity'] }} Ports</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="md:col-span-2 lg:col-span-3 bg-slate-900/30 border border-dashed border-slate-800 rounded-3xl p-16 text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-900 border border-slate-800 mb-4 text-slate-700">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        </div>
                        <h3 class="text-sm font-semibold text-slate-300">No Distribution Boxes Mapped</h3>
                        <p class="text-xs text-slate-500 mt-1">There are no splitters configured under this POP zone.</p>
                    </div>
                @endforelse
            </div>
        </div>
    @else
        <!-- Placeholder View -->
        <div class="bg-slate-900/30 border border-dashed border-slate-800 rounded-3xl p-20 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-900 border border-slate-850 mb-4 text-slate-650">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
            </div>
            <h3 class="text-sm font-semibold text-slate-300">Select POP Zone</h3>
            <p class="text-xs text-slate-500 mt-1">Please select an active POP zone from the top dropdown to view its route splitter load index.</p>
        </div>
    @endif
</div>
