<div class="space-y-8 min-h-screen bg-slate-950 text-slate-100 font-sans p-6 lg:p-8">
    
    <!-- Page Header & Filters -->
    <header class="flex flex-col md:flex-row md:items-end md:justify-between border-b border-slate-800 pb-6 gap-6">
        <div>
            <div class="flex items-center gap-3">
                <span class="px-2.5 py-1 text-xs font-semibold tracking-wider text-cyan-400 bg-cyan-950 border border-cyan-800 rounded-full uppercase">
                    Accounts Module
                </span>
            </div>
            <h1 class="text-3xl font-extrabold text-white mt-2 tracking-tight">
                Daily <span class="bg-gradient-to-r from-cyan-400 to-indigo-500 bg-clip-text text-transparent">Cash Book</span>
            </h1>
            <p class="text-slate-400 text-sm mt-1">Track daily cash flow, income, and operational expenses.</p>
        </div>

        <div class="flex items-center gap-4 bg-slate-900 border border-slate-800 p-2 rounded-2xl shadow-xl">
            <div class="flex items-center gap-3 px-2">
                <div class="flex flex-col">
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1 mb-1">From Date</label>
                    <input wire:model.live="fromDate" type="date" class="bg-slate-950 border border-slate-800 focus:border-cyan-500 rounded-xl px-3 py-2 text-sm text-slate-200 outline-none transition duration-200 [color-scheme:dark]">
                </div>
                <div class="text-slate-600 font-bold mt-4">→</div>
                <div class="flex flex-col">
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1 mb-1">To Date</label>
                    <input wire:model.live="toDate" type="date" class="bg-slate-950 border border-slate-800 focus:border-cyan-500 rounded-xl px-3 py-2 text-sm text-slate-200 outline-none transition duration-200 [color-scheme:dark]">
                </div>
            </div>
            <div wire:loading class="px-2">
                <svg class="animate-spin w-5 h-5 text-cyan-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            </div>
        </div>
    </header>

    @error('toDate') 
        <div class="px-4 py-3 bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm font-semibold rounded-2xl">
            {{ $message }}
        </div>
    @enderror

    <!-- Summary Bar -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Total Income -->
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 relative overflow-hidden group">
            <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
            <div class="flex items-center justify-between relative z-10">
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Total Income</p>
                    <h3 class="text-3xl font-extrabold text-white mt-1">{{ number_format($totalIncome, 2) }} <span class="text-sm font-medium text-slate-500">BDT</span></h3>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                </div>
            </div>
        </div>

        <!-- Total Expense -->
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 relative overflow-hidden group">
            <div class="absolute inset-0 bg-gradient-to-br from-rose-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
            <div class="flex items-center justify-between relative z-10">
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Total Expense</p>
                    <h3 class="text-3xl font-extrabold text-white mt-1">{{ number_format($totalExpense, 2) }} <span class="text-sm font-medium text-slate-500">BDT</span></h3>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                </div>
            </div>
        </div>

        <!-- Cash On Hand -->
        <div class="bg-gradient-to-br from-cyan-600 to-indigo-700 rounded-3xl p-6 relative overflow-hidden shadow-[0_0_40px_rgba(6,182,212,0.15)] group">
            <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMSIgY3k9IjEiIHI9IjEiIGZpbGw9InJnYmEoMjU1LDI1NSwyNTUsMC4wNSkiLz48L3N2Zz4=')] opacity-50"></div>
            <div class="flex items-center justify-between relative z-10">
                <div>
                    <p class="text-xs font-bold text-cyan-200 uppercase tracking-widest drop-shadow-md">Cash on Hand</p>
                    <h3 class="text-3xl font-extrabold text-white mt-1 drop-shadow-lg">{{ number_format($cashOnHand, 2) }} <span class="text-sm font-medium text-cyan-200">BDT</span></h3>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-white/10 border border-white/20 backdrop-blur-md flex items-center justify-center text-white shadow-inner">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Ledger View: Split Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Left: Incomes (Debit) -->
        <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl flex flex-col h-full">
            <div class="p-5 border-b border-slate-800 flex items-center justify-between bg-slate-950/30">
                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                    <span class="w-2.5 h-6 bg-emerald-500 rounded-full"></span>
                    Income Ledger
                </h2>
                <span class="px-3 py-1 bg-emerald-500/10 text-emerald-400 text-xs font-bold rounded-xl border border-emerald-500/20 uppercase tracking-wider">Credit</span>
            </div>
            
            <div class="flex-1 overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="text-xs uppercase bg-slate-950/80 text-slate-500 font-semibold tracking-wider">
                        <tr>
                            <th class="px-5 py-3">Date</th>
                            <th class="px-5 py-3">Particulars</th>
                            <th class="px-5 py-3 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse($incomes as $income)
                            <tr class="hover:bg-slate-800/40 transition-colors">
                                <td class="px-5 py-3 whitespace-nowrap text-xs text-slate-400">
                                    {{ $income->created_at->format('d M, y h:i A') }}
                                </td>
                                <td class="px-5 py-3">
                                    <p class="font-medium text-slate-200">Bill Collection</p>
                                    <p class="text-[11px] text-slate-500 mt-0.5">Customer: <strong class="text-emerald-400/80">{{ $income->customer->full_name ?? 'Unknown' }}</strong></p>
                                    @if($income->receipt_number)
                                        <p class="text-[10px] text-slate-600 font-mono mt-0.5">Rec: {{ $income->receipt_number }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-3 whitespace-nowrap text-right font-bold text-emerald-400">
                                    +{{ number_format((float)$income->amount, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-5 py-10 text-center">
                                    <p class="text-sm text-slate-500 italic">No income records found for this period.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-800 bg-slate-950/50 flex justify-between items-center">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Total Credit</span>
                <span class="text-lg font-bold text-emerald-400">{{ number_format($totalIncome, 2) }}</span>
            </div>
        </div>

        <!-- Right: Expenses (Credit) -->
        <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl flex flex-col h-full">
            <div class="p-5 border-b border-slate-800 flex items-center justify-between bg-slate-950/30">
                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                    <span class="w-2.5 h-6 bg-rose-500 rounded-full"></span>
                    Expense Ledger
                </h2>
                <span class="px-3 py-1 bg-rose-500/10 text-rose-400 text-xs font-bold rounded-xl border border-rose-500/20 uppercase tracking-wider">Debit</span>
            </div>
            
            <div class="flex-1 overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="text-xs uppercase bg-slate-950/80 text-slate-500 font-semibold tracking-wider">
                        <tr>
                            <th class="px-5 py-3">Date</th>
                            <th class="px-5 py-3">Particulars</th>
                            <th class="px-5 py-3 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse($expenses as $expense)
                            <tr class="hover:bg-slate-800/40 transition-colors">
                                <td class="px-5 py-3 whitespace-nowrap text-xs text-slate-400">
                                    {{ \Carbon\Carbon::parse($expense->expense_date)->format('d M, y') }}
                                </td>
                                <td class="px-5 py-3">
                                    <p class="font-medium text-slate-200">{{ $expense->category->name ?? 'Uncategorized' }}</p>
                                    @if($expense->description)
                                        <p class="text-[11px] text-slate-500 mt-0.5 truncate max-w-[200px]" title="{{ $expense->description }}">{{ $expense->description }}</p>
                                    @endif
                                    @if($expense->reference_no)
                                        <p class="text-[10px] text-slate-600 font-mono mt-0.5">Ref: {{ $expense->reference_no }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-3 whitespace-nowrap text-right font-bold text-rose-400">
                                    -{{ number_format((float)$expense->amount, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-5 py-10 text-center">
                                    <p class="text-sm text-slate-500 italic">No expense records found for this period.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-800 bg-slate-950/50 flex justify-between items-center">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Total Debit</span>
                <span class="text-lg font-bold text-rose-400">{{ number_format($totalExpense, 2) }}</span>
            </div>
        </div>

    </div>
</div>
