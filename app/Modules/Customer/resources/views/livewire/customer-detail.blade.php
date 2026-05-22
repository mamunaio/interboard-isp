<div class="p-6 lg:p-8">
    <!-- Back Header -->
    <div class="mb-6 flex items-center justify-between">
        <a href="/customers" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-400 hover:text-white transition duration-150">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Back to Directory
        </a>
    </div>

    <!-- Alert Messages -->
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Customer Profile Column -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl">
                <div class="flex items-center gap-4 mb-6 pb-6 border-b border-slate-800">
                    <div class="w-14 h-14 rounded-2xl bg-cyan-950 text-cyan-400 border border-cyan-900 flex items-center justify-center font-black text-xl">
                        {{ substr($customer->full_name, 0, 2) }}
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-white tracking-tight">{{ $customer->full_name }}</h2>
                        <span class="text-xs text-slate-500 font-mono">{{ $customer->customer_code }}</span>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">Status</span>
                        <div class="mt-1">
                            @php
                                $statusColor = match($customer->status->value) {
                                    'active' => 'text-emerald-400 bg-emerald-950/50 border-emerald-900',
                                    'suspended' => 'text-rose-400 bg-rose-950/50 border-rose-900',
                                    'disconnected' => 'text-amber-400 bg-amber-950/50 border-amber-900',
                                    default => 'text-slate-350 bg-slate-800/80 border-slate-700'
                                };
                            @endphp
                            <span class="inline-block px-3 py-1 text-xs font-bold rounded-full border {{ $statusColor }} capitalize">
                                {{ $customer->status->value }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">Wallet Balance</span>
                        <p class="text-lg font-black text-cyan-400 mt-1 font-mono">
                            {{ $wallet ? number_format((float)$wallet->current_balance, 2) : '0.00' }} BDT
                        </p>
                    </div>

                    <div>
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">Mobile Phone</span>
                        <p class="text-sm font-medium text-slate-200 mt-0.5">{{ $customer->phone }}</p>
                    </div>

                    <div>
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">Email Address</span>
                        <p class="text-sm font-medium text-slate-200 mt-0.5">{{ $customer->email ?? 'N/A' }}</p>
                    </div>

                    @if($customer->company_name)
                        <div>
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">Company</span>
                            <p class="text-sm font-medium text-slate-200 mt-0.5">{{ $customer->company_name }}</p>
                        </div>
                    @endif

                    <div>
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">Customer Type</span>
                        <p class="text-sm font-medium text-slate-200 mt-0.5 capitalize">{{ $customer->type->value }}</p>
                    </div>

                    <div>
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">National ID</span>
                        <p class="text-sm font-medium text-slate-200 mt-0.5">{{ $customer->national_id ?? 'N/A' }}</p>
                    </div>

                    <div>
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">Primary Address</span>
                        <p class="text-sm font-medium text-slate-300 mt-0.5 leading-relaxed">
                            {{ $customer->addresses()->where('label', 'Primary')->first()?->address_line_1 ?? 'No address registered' }}
                        </p>
                    </div>
                </div>

                <!-- Lifecycle Actions -->
                <div class="mt-8 pt-6 border-t border-slate-800 flex flex-wrap gap-3">
                    @if($customer->status->value !== 'active')
                        <button wire:click="reactivateCustomer" class="flex-1 px-4 py-2 bg-emerald-950/60 hover:bg-emerald-900 border border-emerald-900 text-emerald-400 text-xs font-bold rounded-xl transition duration-150 flex items-center justify-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Reactivate
                        </button>
                    @endif
                    
                    @if($customer->status->value === 'active')
                        <button wire:click="suspendCustomer" onclick="confirm('Are you sure you want to suspend this customer? They will be disconnected from the internet.') || event.stopImmediatePropagation()" class="flex-1 px-4 py-2 bg-amber-950/60 hover:bg-amber-900 border border-amber-900 text-amber-400 text-xs font-bold rounded-xl transition duration-150 flex items-center justify-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            Suspend
                        </button>
                    @endif

                    @if($customer->status->value !== 'churned')
                        <button wire:click="terminateCustomer" onclick="confirm('DANGER: Are you sure you want to permanently churn/terminate this customer?') || event.stopImmediatePropagation()" class="w-full px-4 py-2 mt-2 bg-rose-950/60 hover:bg-rose-900 border border-rose-900 text-rose-400 text-xs font-bold rounded-xl transition duration-150 flex items-center justify-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            Terminate (Churn)
                        </button>
                    @endif
                </div>
            </div>

            <!-- Financial Snapshot Widget -->
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl">
                <h3 class="text-lg font-bold text-white mb-6 pb-4 border-b border-slate-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Financial Snapshot
                </h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-4 bg-slate-950/60 border border-slate-850 rounded-2xl">
                        <span class="text-xs text-slate-500 font-bold uppercase tracking-wider">Total Invoices</span>
                        <p class="text-xl font-bold text-slate-200 mt-1">{{ $totalInvoicesCount }}</p>
                    </div>
                    <div class="p-4 bg-slate-950/60 border border-slate-850 rounded-2xl">
                        <span class="text-xs text-slate-500 font-bold uppercase tracking-wider">Paid Invoices</span>
                        <p class="text-xl font-bold text-emerald-400 mt-1">{{ $paidInvoicesCount }}</p>
                    </div>
                    <div class="p-4 bg-slate-950/60 border border-slate-850 rounded-2xl">
                        <span class="text-xs text-slate-500 font-bold uppercase tracking-wider">Unpaid Invoices</span>
                        <p class="text-xl font-bold text-amber-400 mt-1">{{ $unpaidInvoicesCount }}</p>
                    </div>
                    <div class="p-4 bg-slate-950/60 border border-slate-850 rounded-2xl col-span-2">
                        <span class="text-xs text-slate-500 font-bold uppercase tracking-wider">Overdue Dues</span>
                        <p class="text-xl font-bold text-rose-500 mt-1">{{ number_format((float)$overdueAmount, 2) }} BDT</p>
                    </div>
                </div>
            </div>

            <!-- Lifecycle Timeline -->
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl">
                @include('customer::livewire.components.lifecycle-timeline', ['customerId' => $customer->id])
            </div>
        </div>

        <!-- Subscriptions & Invoices Column -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Active Subscriptions -->
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl">
                <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-800">
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        Service Subscriptions
                    </h3>
                    <button wire:click="openAssignModal" class="px-4 py-2 bg-cyan-950 border border-cyan-800 text-cyan-400 hover:bg-cyan-900 text-xs font-bold rounded-xl transition duration-150">
                        Assign Plan
                    </button>
                </div>

                <div class="space-y-4">
                    @forelse($subscriptions as $sub)
                        <div class="bg-slate-950 border border-slate-800/80 rounded-2xl p-5 hover:border-slate-700 transition duration-200 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-3">
                                    <span class="font-mono font-bold text-sm text-cyan-400">{{ $sub->subscription_code }}</span>
                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded bg-emerald-950 text-emerald-400 border border-emerald-900">
                                        {{ $sub->status->value }}
                                    </span>
                                </div>
                                <h4 class="text-base font-bold text-white mt-1">
                                    {{ $sub->servicePlan ? $sub->servicePlan->name : ($sub->servicePackage ? $sub->servicePackage->name : 'N/A') }}
                                </h4>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-x-4 gap-y-1 mt-3 text-xs text-slate-400">
                                    <p>Mode: <span class="text-slate-200 capitalize">{{ $sub->billing_mode->value }}</span></p>
                                    <p>Cycle: <span class="text-slate-200 capitalize">{{ $sub->billing_cycle_type->value }}</span></p>
                                    <p>Speed: <span class="text-slate-200">{{ $sub->servicePlan ? $sub->servicePlan->download_speed : 'N/A' }}</span></p>
                                    <p class="col-span-2">Next Billing: <span class="text-amber-400 font-semibold">{{ $sub->next_billing_date ? $sub->next_billing_date->format('Y-m-d') : 'N/A' }}</span></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 self-start md:self-center">
                                <button wire:click="generateInvoice({{ $sub->id }})" class="w-full md:w-auto px-4 py-2 bg-gradient-to-r from-cyan-500 to-indigo-500 hover:from-cyan-600 hover:to-indigo-650 text-white text-xs font-bold rounded-xl shadow-lg transition duration-205 flex items-center justify-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                    Generate Invoice
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="text-center p-8 bg-slate-950/40 border border-dashed border-slate-800 rounded-2xl">
                            <p class="text-sm font-semibold text-slate-500">No active subscriptions found.</p>
                            <p class="text-xs text-slate-650 mt-1">Assign a service plan to generate billing invoices.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Invoices Ledger -->
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl">
                <h3 class="text-lg font-bold text-white mb-6 pb-4 border-b border-slate-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Invoice Ledger History
                </h3>

                <div class="space-y-4">
                    @forelse($invoices as $inv)
                        <div class="bg-slate-950 border border-slate-800/80 rounded-2xl p-4 flex items-center justify-between hover:border-slate-700 transition duration-200">
                            <div>
                                <div class="flex items-center gap-2.5">
                                    <span class="font-mono font-bold text-sm text-indigo-455">{{ $inv->invoice_number }}</span>
                                    @php
                                        $invColor = match($inv->status->value) {
                                            'issued' => 'text-cyan-400 bg-cyan-950 border-cyan-900',
                                            'paid' => 'text-emerald-400 bg-emerald-950 border-emerald-900',
                                            'draft' => 'text-slate-400 bg-slate-900 border-slate-850',
                                            default => 'text-rose-400 bg-rose-950 border-rose-900'
                                        };
                                    @endphp
                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded border {{ $invColor }}">
                                        {{ $inv->status->value }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-400 mt-1">
                                    Period: {{ $inv->billing_period_start ? $inv->billing_period_start->format('Y-m-d') : 'N/A' }} to {{ $inv->billing_period_end ? $inv->billing_period_end->format('Y-m-d') : 'N/A' }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-black text-white">{{ number_format((float)$inv->grand_total, 2) }} BDT</p>
                                <a href="/invoices" class="text-xs font-bold text-cyan-400 hover:text-cyan-300 transition duration-150 inline-block mt-0.5">Details →</a>
                            </div>
                        </div>
                    @empty
                        <div class="text-center p-8 bg-slate-950/40 border border-dashed border-slate-800 rounded-2xl">
                            <p class="text-sm font-semibold text-slate-500">No invoices billed to this customer.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Recent Payment Receipts -->
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl">
                <h3 class="text-lg font-bold text-white mb-6 pb-4 border-b border-slate-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                    Recent Payment Receipts
                </h3>

                <div class="space-y-4">
                    @forelse($recentReceipts as $rcpt)
                        <div class="bg-slate-950 border border-slate-800/80 rounded-2xl p-4 flex items-center justify-between hover:border-slate-700 transition duration-200">
                            <div>
                                <div class="flex items-center gap-2.5">
                                    <span class="font-mono font-bold text-sm text-cyan-400">{{ $rcpt->receipt_number }}</span>
                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded border bg-slate-900 text-slate-450 border-slate-800">
                                        {{ $rcpt->payment_method->value }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 mt-1 font-mono">
                                    Ref: {{ $rcpt->reference ?? 'N/A' }} | Date: {{ $rcpt->created_at->format('Y-m-d H:i') }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-black text-emerald-400">+ {{ number_format((float)$rcpt->amount, 2) }} BDT</p>
                                <span class="text-[10px] text-slate-450 font-medium">Posted</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center p-8 bg-slate-950/40 border border-dashed border-slate-800 rounded-2xl">
                            <p class="text-sm font-semibold text-slate-500">No payment receipts registered.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Ledger Entries History -->
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl">
                <h3 class="text-lg font-bold text-white mb-6 pb-4 border-b border-slate-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                    Wallet Ledger Mutation History
                </h3>
                <div class="space-y-4">
                    @forelse($recentTransactions as $tx)
                        <div class="bg-slate-950 border border-slate-800/80 rounded-2xl p-4 flex items-center justify-between hover:border-slate-700 transition duration-200">
                            <div>
                                <div class="flex items-center gap-2.5">
                                    <span class="text-xs font-semibold uppercase tracking-wider
                                        @if($tx->direction->value === 'credit') text-emerald-400
                                        @else text-rose-400
                                        @endif">
                                        {{ $tx->direction->value }}
                                    </span>
                                    <span class="px-2 py-0.5 text-[9px] font-bold uppercase rounded border bg-slate-900 text-slate-450 border-slate-800">
                                        {{ str_replace('_', ' ', $tx->transaction_type->value) }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 mt-1 font-mono">
                                    Bal: {{ number_format((float)$tx->balance_after, 2) }} BDT | Date: {{ $tx->created_at->format('Y-m-d H:i') }}
                                    @if($tx->notes)
                                        | <span class="text-slate-400 font-sans italic">{{ $tx->notes }}</span>
                                    @endif
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-black 
                                    @if($tx->direction->value === 'credit') text-emerald-400
                                    @else text-rose-400
                                    @endif">
                                    {{ $tx->direction->value === 'credit' ? '+' : '-' }} {{ number_format((float)$tx->amount, 2) }} BDT
                                </p>
                            </div>
                        </div>
                    @empty
                        <div class="text-center p-8 bg-slate-950/40 border border-dashed border-slate-800 rounded-2xl">
                            <p class="text-sm font-semibold text-slate-500">No ledger transactions registered.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Assign Plan Modal -->
    @if($isAssignModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl w-full max-w-lg shadow-2xl overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-800 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-white">Assign Service Plan</h3>
                    <button wire:click="$set('isAssignModalOpen', false)" class="p-1 text-slate-400 hover:text-white rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <form wire:submit.prevent="assignPlan" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Select Service Plan</label>
                        <select wire:model="selectedPlanId" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500">
                            <option value="">-- Choose Plan --</option>
                            @foreach($availablePlans as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} ({{ number_format((float)$p->monthly_price, 2) }} BDT)</option>
                            @endforeach
                        </select>
                        @error('selectedPlanId') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Billing Mode</label>
                            <select wire:model="billingMode" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500">
                                <option value="prepaid">Prepaid (Billed before service)</option>
                                <option value="postpaid">Postpaid (Billed after service)</option>
                            </select>
                            @error('billingMode') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Billing Cycle Type</label>
                            <select wire:model="billingCycleType" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500">
                                <option value="anniversary">Anniversary Date</option>
                                <option value="calendar">Calendar (1st of month)</option>
                            </select>
                            @error('billingCycleType') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">First / Next Billing Date</label>
                        <input wire:model="nextBillingDate" type="date" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500">
                        @error('nextBillingDate') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Notes</label>
                        <textarea wire:model="notes" placeholder="Any internal notes relating to this installation..." rows="2" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500"></textarea>
                        @error('notes') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="pt-4 border-t border-slate-800 flex justify-end gap-3">
                        <button type="button" wire:click="$set('isAssignModalOpen', false)" class="px-5 py-2.5 bg-slate-850 hover:bg-slate-800 border border-slate-800 hover:border-slate-700 rounded-xl text-sm font-semibold text-slate-350 transition duration-150">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-cyan-500 to-indigo-500 hover:from-cyan-600 hover:to-indigo-650 text-white rounded-xl text-sm font-semibold transition duration-155">
                            Activate Subscription
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
