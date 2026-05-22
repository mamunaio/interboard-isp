<div class="space-y-6">
    <!-- Welcome Banner -->
    <div class="bg-gradient-to-r from-purple-900/40 via-fuchsia-900/20 to-slate-900 border border-purple-800/30 rounded-2xl p-8 shadow-xl">
        <h1 class="text-3xl font-extrabold text-white tracking-tight">Platform Overview</h1>
        <p class="text-slate-400 mt-2">Welcome back, Super Admin. Here's a snapshot of your entire SaaS platform.</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total ISPs -->
        <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800 rounded-2xl p-6 shadow-xl relative overflow-hidden group hover:border-purple-800/50 transition-all duration-300">
            <div class="absolute -top-4 -right-4 w-20 h-20 bg-purple-500/5 rounded-full group-hover:bg-purple-500/10 transition-all duration-300"></div>
            <div class="relative">
                <div class="w-12 h-12 rounded-xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                </div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Total ISPs</p>
                <p class="text-3xl font-extrabold text-white">{{ $totalTenants }}</p>
            </div>
        </div>

        <!-- Active ISPs -->
        <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800 rounded-2xl p-6 shadow-xl relative overflow-hidden group hover:border-emerald-800/50 transition-all duration-300">
            <div class="absolute -top-4 -right-4 w-20 h-20 bg-emerald-500/5 rounded-full group-hover:bg-emerald-500/10 transition-all duration-300"></div>
            <div class="relative">
                <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Active ISPs</p>
                <p class="text-3xl font-extrabold text-emerald-400">{{ $activeTenants }}</p>
            </div>
        </div>

        <!-- Suspended ISPs -->
        <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800 rounded-2xl p-6 shadow-xl relative overflow-hidden group hover:border-rose-800/50 transition-all duration-300">
            <div class="absolute -top-4 -right-4 w-20 h-20 bg-rose-500/5 rounded-full group-hover:bg-rose-500/10 transition-all duration-300"></div>
            <div class="relative">
                <div class="w-12 h-12 rounded-xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                </div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Suspended ISPs</p>
                <p class="text-3xl font-extrabold text-rose-400">{{ $suspendedTenants }}</p>
            </div>
        </div>

        <!-- Total Customers (Platform-wide) -->
        <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800 rounded-2xl p-6 shadow-xl relative overflow-hidden group hover:border-cyan-800/50 transition-all duration-300">
            <div class="absolute -top-4 -right-4 w-20 h-20 bg-cyan-500/5 rounded-full group-hover:bg-cyan-500/10 transition-all duration-300"></div>
            <div class="relative">
                <div class="w-12 h-12 rounded-xl bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Total Customers</p>
                <p class="text-3xl font-extrabold text-cyan-400">{{ $totalCustomers }}</p>
            </div>
        </div>
    </div>

    <!-- Recent Tenants -->
    <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-800 flex justify-between items-center">
            <h3 class="text-lg font-bold text-white">Recently Onboarded ISPs</h3>
            <a href="/saas/tenants" class="text-sm text-purple-400 hover:text-purple-300 font-medium transition-colors">View All →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-800/50 text-xs uppercase text-slate-400 border-b border-slate-700">
                    <tr>
                        <th class="px-6 py-3 font-medium">ISP Name</th>
                        <th class="px-6 py-3 font-medium">Domain</th>
                        <th class="px-6 py-3 font-medium">Status</th>
                        <th class="px-6 py-3 font-medium">Joined</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/50">
                    @forelse($recentTenants as $tenant)
                        <tr class="hover:bg-slate-800/25 transition-colors">
                            <td class="px-6 py-4 font-medium text-white">{{ $tenant->name }}</td>
                            <td class="px-6 py-4 font-mono text-xs text-slate-400">{{ $tenant->domains->first()?->domain ?? 'N/A' }}</td>
                            <td class="px-6 py-4">
                                @if($tenant->status === 'active')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold text-emerald-400 bg-emerald-400/10 border border-emerald-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold text-rose-400 bg-rose-400/10 border border-rose-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span> Suspended
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500">{{ $tenant->created_at->format('M d, Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-slate-500">No tenants onboarded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
