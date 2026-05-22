<div class="p-6 lg:p-8 min-h-screen bg-slate-950 text-slate-100 font-sans antialiased">
    <header class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-slate-800 pb-6 mb-8 gap-4">
        <div>
            <div class="flex items-center gap-3">
                <span class="px-2.5 py-1 text-xs font-semibold tracking-wider text-amber-400 bg-amber-950 border border-amber-800 rounded-full uppercase">
                    Finance
                </span>
                <span class="text-xs text-slate-400">Ledger Engine</span>
            </div>
            <h1 class="text-3xl font-extrabold text-white mt-1 tracking-tight">
                Financial <span class="bg-gradient-to-r from-amber-400 to-orange-500 bg-clip-text text-transparent">Statements</span>
            </h1>
        </div>
    </header>

    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl mb-8">
        <form wire:submit.prevent="generateReport" class="flex flex-col md:flex-row gap-4 mb-6 items-end">
            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1">Report Type</label>
                <select wire:model="reportType" class="bg-slate-950 border border-slate-800 focus:border-amber-500 rounded-xl px-4 py-2 text-sm outline-none w-48">
                    <option value="pl">Profit & Loss (P&L)</option>
                    <option value="bs">Balance Sheet</option>
                </select>
            </div>
            @if($reportType === 'pl')
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1">Start Date</label>
                    <input type="date" wire:model="startDate" class="bg-slate-950 border border-slate-800 focus:border-amber-500 rounded-xl px-4 py-2 text-sm outline-none">
                </div>
            @endif
            <div>
                <label class="block text-xs font-semibold text-slate-400 mb-1">{{ $reportType === 'pl' ? 'End Date' : 'As of Date' }}</label>
                <input type="date" wire:model="endDate" class="bg-slate-950 border border-slate-800 focus:border-amber-500 rounded-xl px-4 py-2 text-sm outline-none">
            </div>
            <div>
                <button type="submit" class="bg-amber-600 hover:bg-amber-500 text-white font-bold py-2 px-6 rounded-xl shadow-lg transition">
                    Generate
                </button>
            </div>
        </form>

        @if(!empty($reportData))
            <div class="border border-slate-800 rounded-2xl p-6 bg-slate-950">
                @if($reportType === 'pl')
                    <h2 class="text-xl font-bold text-white mb-4 border-b border-slate-800 pb-2">Income Statement (P&L)</h2>
                    
                    <h3 class="text-sm font-bold text-emerald-400 mb-2 uppercase">Revenues</h3>
                    <div class="space-y-2 mb-4 pl-4 border-l-2 border-emerald-900/50">
                        @foreach($reportData['revenues'] as $rev)
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-300">{{ $rev['account_name'] }} ({{ $rev['account_code'] }})</span>
                                <span class="font-mono">{{ number_format($rev['balance'], 2) }}</span>
                            </div>
                        @endforeach
                        <div class="flex justify-between text-sm font-bold pt-2 border-t border-slate-800">
                            <span class="text-emerald-400">Total Revenue</span>
                            <span class="text-emerald-400 font-mono">{{ number_format($reportData['total_revenue'], 2) }}</span>
                        </div>
                    </div>

                    <h3 class="text-sm font-bold text-rose-400 mb-2 uppercase">Expenses</h3>
                    <div class="space-y-2 mb-4 pl-4 border-l-2 border-rose-900/50">
                        @foreach($reportData['expenses'] as $exp)
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-300">{{ $exp['account_name'] }} ({{ $exp['account_code'] }})</span>
                                <span class="font-mono">{{ number_format($exp['balance'], 2) }}</span>
                            </div>
                        @endforeach
                        <div class="flex justify-between text-sm font-bold pt-2 border-t border-slate-800">
                            <span class="text-rose-400">Total Expense</span>
                            <span class="text-rose-400 font-mono">{{ number_format($reportData['total_expense'], 2) }}</span>
                        </div>
                    </div>

                    <div class="flex justify-between text-lg font-bold pt-4 border-t-2 border-slate-700 mt-6">
                        <span class="text-white">Net Income</span>
                        <span class="font-mono {{ $reportData['net_income'] >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">
                            {{ number_format($reportData['net_income'], 2) }}
                        </span>
                    </div>

                @else
                    <h2 class="text-xl font-bold text-white mb-4 border-b border-slate-800 pb-2">Balance Sheet</h2>
                    
                    <h3 class="text-sm font-bold text-indigo-400 mb-2 uppercase">Assets</h3>
                    <div class="space-y-2 mb-4 pl-4 border-l-2 border-indigo-900/50">
                        @foreach($reportData['assets'] as $asset)
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-300">{{ $asset['account_name'] }} ({{ $asset['account_code'] }})</span>
                                <span class="font-mono">{{ number_format($asset['balance'], 2) }}</span>
                            </div>
                        @endforeach
                        <div class="flex justify-between text-sm font-bold pt-2 border-t border-slate-800">
                            <span class="text-indigo-400">Total Assets</span>
                            <span class="text-indigo-400 font-mono">{{ number_format($reportData['total_assets'], 2) }}</span>
                        </div>
                    </div>

                    <h3 class="text-sm font-bold text-amber-400 mb-2 uppercase">Liabilities</h3>
                    <div class="space-y-2 mb-4 pl-4 border-l-2 border-amber-900/50">
                        @foreach($reportData['liabilities'] as $liab)
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-300">{{ $liab['account_name'] }} ({{ $liab['account_code'] }})</span>
                                <span class="font-mono">{{ number_format($liab['balance'], 2) }}</span>
                            </div>
                        @endforeach
                        <div class="flex justify-between text-sm font-bold pt-2 border-t border-slate-800">
                            <span class="text-amber-400">Total Liabilities</span>
                            <span class="text-amber-400 font-mono">{{ number_format($reportData['total_liabilities'], 2) }}</span>
                        </div>
                    </div>

                    <h3 class="text-sm font-bold text-cyan-400 mb-2 uppercase">Equity</h3>
                    <div class="space-y-2 mb-4 pl-4 border-l-2 border-cyan-900/50">
                        @foreach($reportData['equity'] as $eq)
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-300">{{ $eq['account_name'] }} ({{ $eq['account_code'] }})</span>
                                <span class="font-mono">{{ number_format($eq['balance'], 2) }}</span>
                            </div>
                        @endforeach
                        <div class="flex justify-between text-sm font-bold pt-2 border-t border-slate-800">
                            <span class="text-cyan-400">Total Equity</span>
                            <span class="text-cyan-400 font-mono">{{ number_format($reportData['total_equity'], 2) }}</span>
                        </div>
                    </div>

                    <div class="flex justify-between text-sm font-bold pt-4 border-t border-slate-700 mt-6">
                        <span class="text-white">Total Liabilities & Equity</span>
                        <span class="font-mono text-white">
                            {{ number_format($reportData['total_liabilities_and_equity'], 2) }}
                        </span>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
