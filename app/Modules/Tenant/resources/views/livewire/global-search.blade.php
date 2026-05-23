<div x-data="{ searchOpen: false }"
     @open-search.window="searchOpen = true; $nextTick(() => $refs.searchInput.focus())"
     @keydown.window.prevent.ctrl.k="searchOpen = true; $nextTick(() => $refs.searchInput.focus())"
     @keydown.escape.window="searchOpen = false"
     x-show="searchOpen"
     style="display: none;"
     class="fixed inset-0 z-[100] flex items-start justify-center pt-16 sm:pt-24 px-4 sm:px-0">
     
    <!-- Backdrop -->
    <div x-show="searchOpen" 
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="searchOpen = false"
         class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity"></div>

    <!-- Modal Panel -->
    <div x-show="searchOpen"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         class="relative w-full max-w-4xl transform overflow-hidden rounded-3xl bg-slate-900 border border-slate-800 shadow-[0_0_50px_rgba(0,0,0,0.5)] ring-1 ring-white/5 transition-all flex flex-col">
        
        <!-- Search Input Header -->
        <div class="relative border-b border-slate-800/80 bg-slate-900/50 backdrop-blur-md">
            <svg class="pointer-events-none absolute left-6 top-5 h-6 w-6 text-cyan-500 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            <input x-ref="searchInput" wire:model.live.debounce.300ms="query" type="text" class="h-16 w-full border-0 bg-transparent pl-16 pr-6 text-white placeholder-slate-500 focus:ring-0 sm:text-lg font-medium outline-none" placeholder="Search customers, invoices, subscriptions..." autocomplete="off">
            <!-- Spinner for loading state -->
            <div wire:loading wire:target="query" class="absolute right-6 top-5">
                <svg class="w-6 h-6 text-cyan-500 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            </div>
        </div>

        @if(strlen($query) > 0 && strlen($query) < 2)
            <div class="px-6 py-3 text-xs font-semibold text-amber-400 bg-amber-500/10 border-b border-amber-500/20 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Please enter at least 2 characters to search.
            </div>
        @endif

        @if(strlen($query) >= 2)
            <!-- Search Results Scrollable Area -->
            <div class="max-h-[60vh] overflow-y-auto p-6 scrollbar-thin scrollbar-thumb-slate-700 scrollbar-track-transparent">
                
                @if(count($customers) === 0 && count($subscriptions) === 0 && count($invoices) === 0 && count($receipts) === 0)
                    <div class="py-10 text-center">
                        <div class="w-16 h-16 bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-700">
                            <svg class="w-8 h-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <p class="text-sm font-semibold text-slate-300">No results found for "{{ $query }}"</p>
                        <p class="text-xs text-slate-500 mt-1">Try adjusting your search terms or check for typos.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Customers Category -->
                        @if(count($customers) > 0)
                            <div class="space-y-3">
                                <h2 class="text-xs font-bold text-slate-400 uppercase tracking-widest flex items-center gap-2 mb-3">
                                    <span class="w-2 h-2 bg-cyan-500 rounded-full"></span>
                                    Customers
                                </h2>
                                @foreach($customers as $customer)
                                    <a href="/customers/{{ $customer['id'] }}" class="group block p-4 bg-slate-950/40 hover:bg-slate-800 transition duration-200 border border-slate-800/80 hover:border-cyan-500/50 rounded-2xl relative overflow-hidden">
                                        <div class="absolute inset-0 bg-gradient-to-r from-cyan-500/0 via-cyan-500/0 to-cyan-500/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                        <p class="text-sm font-bold text-slate-200 group-hover:text-cyan-400 transition-colors">{{ $customer['full_name'] }}</p>
                                        <p class="text-[11px] text-slate-500 mt-1">Code: <strong class="text-cyan-500/80 font-mono">{{ $customer['customer_code'] }}</strong> • Email: {{ $customer['email'] }}</p>
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        <!-- Subscriptions Category -->
                        @if(count($subscriptions) > 0)
                            <div class="space-y-3">
                                <h2 class="text-xs font-bold text-slate-400 uppercase tracking-widest flex items-center gap-2 mb-3">
                                    <span class="w-2 h-2 bg-indigo-500 rounded-full"></span>
                                    Subscriptions
                                </h2>
                                @foreach($subscriptions as $sub)
                                    <a href="/customers/{{ $sub['customer_id'] }}" class="group block p-4 bg-slate-950/40 hover:bg-slate-800 transition duration-200 border border-slate-800/80 hover:border-indigo-500/50 rounded-2xl relative overflow-hidden">
                                        <div class="absolute inset-0 bg-gradient-to-r from-indigo-500/0 via-indigo-500/0 to-indigo-500/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                        <p class="text-sm font-bold text-slate-200 group-hover:text-indigo-400 transition-colors">Sub: {{ $sub['subscription_code'] }}</p>
                                        <p class="text-[11px] text-slate-500 mt-1">Customer: {{ $sub['customer']['full_name'] ?? 'Unknown' }} • <span class="uppercase font-bold text-[10px] text-cyan-400">{{ $sub['status'] }}</span></p>
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        <!-- Invoices Category -->
                        @if(count($invoices) > 0)
                            <div class="space-y-3">
                                <h2 class="text-xs font-bold text-slate-400 uppercase tracking-widest flex items-center gap-2 mb-3">
                                    <span class="w-2 h-2 bg-amber-500 rounded-full"></span>
                                    Invoices
                                </h2>
                                @foreach($invoices as $inv)
                                    <a href="/invoices?search={{ $inv['invoice_number'] }}" class="group block p-4 bg-slate-950/40 hover:bg-slate-800 transition duration-200 border border-slate-800/80 hover:border-amber-500/50 rounded-2xl relative overflow-hidden">
                                        <div class="absolute inset-0 bg-gradient-to-r from-amber-500/0 via-amber-500/0 to-amber-500/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                        <p class="text-sm font-bold text-slate-200 group-hover:text-amber-400 transition-colors">Invoice: {{ $inv['invoice_number'] }}</p>
                                        <p class="text-[11px] text-slate-500 mt-1">Type: <span class="capitalize">{{ $inv['invoice_type'] }}</span> • <span class="uppercase font-bold tracking-widest text-[10px] text-amber-500/80">{{ $inv['status'] }}</span></p>
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        <!-- Payment Receipts Category -->
                        @if(count($receipts) > 0)
                            <div class="space-y-3">
                                <h2 class="text-xs font-bold text-slate-400 uppercase tracking-widest flex items-center gap-2 mb-3">
                                    <span class="w-2 h-2 bg-emerald-500 rounded-full"></span>
                                    Payment Receipts
                                </h2>
                                @foreach($receipts as $rcpt)
                                    <a href="/reversals?search={{ $rcpt['receipt_number'] }}" class="group block p-4 bg-slate-950/40 hover:bg-slate-800 transition duration-200 border border-slate-800/80 hover:border-emerald-500/50 rounded-2xl relative overflow-hidden">
                                        <div class="absolute inset-0 bg-gradient-to-r from-emerald-500/0 via-emerald-500/0 to-emerald-500/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                        <p class="text-sm font-bold text-slate-200 group-hover:text-emerald-400 transition-colors">Receipt: {{ $rcpt['receipt_number'] }}</p>
                                        <p class="text-[11px] text-slate-500 mt-1">Amount: <strong class="text-emerald-400/90">{{ number_format((float)$rcpt['amount'], 2) }} BDT</strong> • Customer: {{ $rcpt['customer']['full_name'] ?? 'Unknown' }}</p>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @endif
        
        <!-- Footer -->
        <div class="flex flex-wrap items-center justify-between bg-slate-900/80 border-t border-slate-800 px-6 py-4 text-xs text-slate-500">
            <div class="flex items-center gap-4">
                <span class="flex items-center gap-1.5">
                    <kbd class="rounded border border-slate-700 bg-slate-800 px-2 py-0.5 font-bold text-[10px] text-slate-400">↑↓</kbd> to navigate
                </span>
                <span class="flex items-center gap-1.5">
                    <kbd class="rounded border border-slate-700 bg-slate-800 px-2 py-0.5 font-bold text-[10px] text-slate-400">Enter</kbd> to select
                </span>
            </div>
            <span class="flex items-center gap-1.5">
                <kbd class="rounded border border-slate-700 bg-slate-800 px-2 py-0.5 font-bold text-[10px] text-slate-400">Esc</kbd> to close
            </span>
        </div>
    </div>
</div>
