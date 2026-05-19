<div class="min-h-screen bg-slate-950 text-slate-100 font-sans antialiased p-6 lg:p-8">
    <header class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-slate-800 pb-6 mb-8 gap-4">
        <div>
            <div class="flex items-center gap-3">
                <span class="px-2.5 py-1 text-xs font-semibold tracking-wider text-cyan-400 bg-cyan-950 border border-cyan-800 rounded-full uppercase">
                    Admin Search
                </span>
                <span class="text-xs text-slate-400">Global Registry Lookup</span>
            </div>
            <h1 class="text-3xl font-extrabold text-white mt-1 tracking-tight">
                Enterprise <span class="bg-gradient-to-r from-cyan-400 to-indigo-500 bg-clip-text text-transparent">Search Engine</span>
            </h1>
            <p class="text-slate-400 text-sm mt-1">Lookup customers, invoices, payment receipts, and subscriptions instantly.</p>
        </div>
    </header>

    <!-- Search input -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl mb-8">
        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Search Query</label>
        <div class="relative">
            <input wire:model.live.debounce.300ms="query" type="text" placeholder="Type customer name, code, invoice #, receipt #, or subscription code..." class="w-full bg-slate-950 border border-slate-800 focus:border-cyan-500 rounded-2xl px-6 py-4 text-base text-slate-100 placeholder-slate-500 outline-none transition duration-200 shadow-inner">
            @if(strlen($query) > 0 && strlen($query) < 2)
                <p class="text-xs text-amber-400 mt-2">Please enter at least 2 characters to search.</p>
            @endif
        </div>
    </div>

    <!-- Search Results Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Customers Category -->
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl">
            <h2 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                <span class="w-2.5 h-6 bg-cyan-500 rounded-full"></span>
                Matching Customers ({{ count($customers) }})
            </h2>
            <div class="space-y-4">
                @forelse($customers as $customer)
                    <div class="flex items-center justify-between p-4 bg-slate-950/40 hover:bg-slate-950 transition duration-200 border border-slate-850 rounded-2xl">
                        <div>
                            <p class="text-sm font-semibold text-slate-200">{{ $customer['full_name'] }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">Code: <strong class="text-cyan-400 font-mono">{{ $customer['customer_code'] }}</strong> • Email: {{ $customer['email'] }}</p>
                        </div>
                        <a href="/customers/{{ $customer['id'] }}" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-white rounded-xl border border-slate-700 hover:border-slate-650 transition duration-200">
                            Profile Detail
                        </a>
                    </div>
                @empty
                    <p class="text-xs text-slate-500 italic p-4 bg-slate-950/20 border border-slate-850 border-dashed rounded-2xl">No matching customers found.</p>
                @endforelse
            </div>
        </div>

        <!-- Subscriptions Category -->
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl">
            <h2 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                <span class="w-2.5 h-6 bg-indigo-500 rounded-full"></span>
                Matching Subscriptions ({{ count($subscriptions) }})
            </h2>
            <div class="space-y-4">
                @forelse($subscriptions as $sub)
                    <div class="flex items-center justify-between p-4 bg-slate-950/40 hover:bg-slate-950 transition duration-200 border border-slate-850 rounded-2xl">
                        <div>
                            <p class="text-sm font-semibold text-slate-200">Subscription: {{ $sub['subscription_code'] }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">Customer: {{ $sub['customer']['full_name'] ?? 'Unknown' }} • Status: <span class="uppercase font-bold tracking-widest text-[10px] text-cyan-400">{{ $sub['status'] }}</span></p>
                        </div>
                        <a href="/customers/{{ $sub['customer_id'] }}" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-white rounded-xl border border-slate-700 hover:border-slate-650 transition duration-200">
                            View Customer
                        </a>
                    </div>
                @empty
                    <p class="text-xs text-slate-500 italic p-4 bg-slate-950/20 border border-slate-850 border-dashed rounded-2xl">No matching subscriptions found.</p>
                @endforelse
            </div>
        </div>

        <!-- Invoices Category -->
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl">
            <h2 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                <span class="w-2.5 h-6 bg-amber-500 rounded-full"></span>
                Matching Invoices ({{ count($invoices) }})
            </h2>
            <div class="space-y-4">
                @forelse($invoices as $inv)
                    <div class="flex items-center justify-between p-4 bg-slate-950/40 hover:bg-slate-950 transition duration-200 border border-slate-850 rounded-2xl">
                        <div>
                            <p class="text-sm font-semibold text-slate-200">Invoice: {{ $inv['invoice_number'] }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">Type: <span class="capitalize font-bold">{{ $inv['invoice_type'] }}</span> • Status: <span class="uppercase font-bold tracking-widest text-[10px] text-amber-400">{{ $inv['status'] }}</span></p>
                        </div>
                        <a href="/invoices?search={{ $inv['invoice_number'] }}" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-white rounded-xl border border-slate-700 hover:border-slate-650 transition duration-200">
                            View Invoices
                        </a>
                    </div>
                @empty
                    <p class="text-xs text-slate-500 italic p-4 bg-slate-950/20 border border-slate-850 border-dashed rounded-2xl">No matching invoices found.</p>
                @endforelse
            </div>
        </div>

        <!-- Payment Receipts Category -->
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl">
            <h2 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                <span class="w-2.5 h-6 bg-emerald-500 rounded-full"></span>
                Matching Payment Receipts ({{ count($receipts) }})
            </h2>
            <div class="space-y-4">
                @forelse($receipts as $rcpt)
                    <div class="flex items-center justify-between p-4 bg-slate-950/40 hover:bg-slate-950 transition duration-200 border border-slate-850 rounded-2xl">
                        <div>
                            <p class="text-sm font-semibold text-slate-200">Receipt: {{ $rcpt['receipt_number'] }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">Amount: <strong class="text-emerald-400">{{ number_format((float)$rcpt['amount'], 2) }} BDT</strong> • Customer: {{ $rcpt['customer']['full_name'] ?? 'Unknown' }}</p>
                        </div>
                        <a href="/reversals?search={{ $rcpt['receipt_number'] }}" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-white rounded-xl border border-slate-700 hover:border-slate-650 transition duration-200">
                            Reversals Page
                        </a>
                    </div>
                @empty
                    <p class="text-xs text-slate-500 italic p-4 bg-slate-950/20 border border-slate-850 border-dashed rounded-2xl">No matching payment receipts found.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
