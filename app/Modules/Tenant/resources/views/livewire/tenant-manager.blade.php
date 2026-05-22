<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-white tracking-tight">Tenant Manager</h2>
            <p class="text-sm text-slate-400 mt-1">Create and manage ISP organizations on your platform</p>
        </div>
        <button wire:click="openCreateModal" class="flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-500 text-white text-sm font-medium rounded-xl transition duration-200 shadow-lg shadow-purple-500/20">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Onboard New ISP
        </button>
    </div>

    @if (session()->has('message'))
        <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-xl flex items-center gap-3">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            {{ session('message') }}
        </div>
    @endif

    <!-- Search -->
    <div class="bg-slate-900/50 backdrop-blur-xl border border-slate-800 p-4 rounded-2xl shadow-xl">
        <label class="block text-sm font-medium text-slate-400 mb-1">Search Tenants</label>
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by name, slug, or domain..." class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200">
    </div>

    <!-- Table -->
    <div class="bg-slate-900/50 backdrop-blur-xl border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-800/50 text-xs uppercase text-slate-400 border-b border-slate-700">
                    <tr>
                        <th class="px-6 py-4 font-medium">ID</th>
                        <th class="px-6 py-4 font-medium">ISP Name</th>
                        <th class="px-6 py-4 font-medium">Domain(s)</th>
                        <th class="px-6 py-4 font-medium">Status</th>
                        <th class="px-6 py-4 font-medium">Created</th>
                        <th class="px-6 py-4 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/50">
                    @forelse($tenants as $tenant)
                        <tr class="hover:bg-slate-800/25 transition-colors">
                            <td class="px-6 py-4 font-mono text-xs text-slate-500">#{{ $tenant->id }}</td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-white">{{ $tenant->name }}</div>
                                <div class="text-xs text-slate-500 font-mono">{{ $tenant->slug }}</div>
                            </td>
                            <td class="px-6 py-4">
                                @foreach($tenant->domains as $domain)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-slate-800 text-slate-300 border border-slate-700 mr-1 mb-1">
                                        {{ $domain->domain }}
                                    </span>
                                @endforeach
                            </td>
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
                            <td class="px-6 py-4 text-xs text-slate-400">{{ $tenant->created_at->format('M d, Y') }}</td>
                            <td class="px-6 py-4 text-right">
                                @if($tenant->status === 'active')
                                    <button wire:click="toggleStatus({{ $tenant->id }})" wire:confirm="Suspend this ISP? Their staff will lose access." class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 text-xs font-medium rounded-lg transition-colors border border-rose-500/20">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                        Suspend
                                    </button>
                                @else
                                    <button wire:click="toggleStatus({{ $tenant->id }})" wire:confirm="Reactivate this ISP?" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 text-xs font-medium rounded-lg transition-colors border border-emerald-500/20">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        Reactivate
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                <svg class="w-12 h-12 mx-auto text-slate-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                <p class="text-lg font-medium text-slate-400">No ISPs onboarded yet</p>
                                <p class="text-sm mt-1">Click "Onboard New ISP" to add your first tenant.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-800">
            {{ $tenants->links(data: ['scrollTo' => false]) }}
        </div>
    </div>

    <!-- Create Modal -->
    @if($isCreateModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity"></div>
        <div class="relative bg-slate-900 border border-purple-800/30 rounded-2xl shadow-2xl w-full max-w-lg p-6 overflow-hidden">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-white">Onboard New ISP</h3>
                <button wire:click="closeCreateModal" class="text-slate-400 hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form wire:submit="createTenant" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1">ISP / Company Name *</label>
                    <input type="text" wire:model="tenantName" class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500" placeholder="e.g. Mamun ISP" required>
                    @error('tenantName') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1">Domain / Subdomain *</label>
                    <input type="text" wire:model="tenantDomain" class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500" placeholder="e.g. mamun.interboard.local or 192.168.1.100" required>
                    <p class="text-xs text-slate-500 mt-1">This is how the system identifies which ISP to load.</p>
                    @error('tenantDomain') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1">Admin Staff Email *</label>
                    <input type="email" wire:model="adminEmail" class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500" placeholder="admin@company.com" required>
                    <p class="text-xs text-slate-500 mt-1">A staff user will be created with the default password: <code class="text-amber-400">password</code></p>
                    @error('adminEmail') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="pt-4 flex justify-end gap-3">
                    <button type="button" wire:click="closeCreateModal" class="px-4 py-2 text-sm font-medium text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 rounded-lg transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-purple-600 hover:bg-purple-500 rounded-lg transition-colors shadow-lg shadow-purple-500/20">
                        Create Tenant
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
