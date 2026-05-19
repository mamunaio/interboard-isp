<div class="p-6 lg:p-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-slate-800 pb-6 mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-white tracking-tight">
                Service <span class="bg-gradient-to-r from-cyan-400 to-indigo-500 bg-clip-text text-transparent">Subscriptions</span>
            </h1>
            <p class="text-slate-400 text-sm mt-1">Monitor assigned customer plans, active billing status, and next anniversary runs.</p>
        </div>
    </div>

    <!-- Search Control -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 mb-8 shadow-2xl">
        <div class="relative max-w-md">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </span>
            <input wire:model.live="search" type="text" placeholder="Search by subscription code or customer name..." class="w-full bg-slate-950 text-slate-100 placeholder-slate-500 pl-10 pr-4 py-3 rounded-xl border border-slate-800 focus:outline-none focus:border-cyan-500 transition duration-200 text-sm">
        </div>
    </div>

    <!-- Subscriptions Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-400 text-xs font-bold uppercase tracking-wider bg-slate-900/50">
                        <th class="py-4 px-6">Subscription Code</th>
                        <th class="py-4 px-6">Customer</th>
                        <th class="py-4 px-6">Assigned Plan</th>
                        <th class="py-4 px-6">Billing Mode</th>
                        <th class="py-4 px-6">Cycle Type</th>
                        <th class="py-4 px-6">Next Billing Date</th>
                        <th class="py-4 px-6">Status</th>
                        <th class="py-4 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-sm">
                    @forelse($subscriptions as $sub)
                        <tr class="hover:bg-slate-850/40 transition duration-200">
                            <td class="py-4 px-6 font-mono font-bold text-cyan-400">
                                {{ $sub->subscription_code }}
                            </td>
                            <td class="py-4 px-6 font-semibold text-white">
                                <a href="/customers/{{ $sub->customer_id }}" class="hover:text-cyan-300 transition duration-150">
                                    {{ $sub->customer->full_name }}
                                </a>
                            </td>
                            <td class="py-4 px-6 font-semibold text-slate-200">
                                {{ $sub->servicePlan ? $sub->servicePlan->name : ($sub->servicePackage ? $sub->servicePackage->name : 'N/A') }}
                            </td>
                            <td class="py-4 px-6 capitalize text-slate-350">
                                {{ $sub->billing_mode->value }}
                            </td>
                            <td class="py-4 px-6 capitalize text-slate-350">
                                {{ $sub->billing_cycle_type->value }}
                            </td>
                            <td class="py-4 px-6 text-amber-400 font-mono font-semibold">
                                {{ $sub->next_billing_date ? $sub->next_billing_date->format('Y-m-d') : 'N/A' }}
                            </td>
                            <td class="py-4 px-6">
                                <span class="px-2.5 py-1 text-xs font-bold rounded-full border text-emerald-400 bg-emerald-950/50 border-emerald-900 capitalize">
                                    {{ $sub->status->value }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <a href="/customers/{{ $sub->customer_id }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-slate-800 border border-slate-700 hover:border-slate-650 hover:bg-slate-750 text-slate-200 rounded-lg text-xs font-bold transition duration-200">
                                    Manage Customer
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-500">
                                <svg class="w-12 h-12 mx-auto text-slate-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                <p class="text-sm font-semibold">No subscriptions found.</p>
                                <p class="text-xs text-slate-650 mt-1">Try refining your search keyword or search by active customer.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($subscriptions->hasPages())
            <div class="px-6 py-4 border-t border-slate-800 bg-slate-900/30">
                {{ $subscriptions->links() }}
            </div>
        @endif
    </div>
</div>
