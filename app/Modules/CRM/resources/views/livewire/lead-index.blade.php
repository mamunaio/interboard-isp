<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-white tracking-tight">CRM Leads</h2>
            <p class="text-sm text-slate-400 mt-1">Manage prospects and convert them to customers</p>
        </div>
        <button wire:click="openCreateModal" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-xl transition duration-200 shadow-lg shadow-indigo-500/20">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Add Lead
        </button>
    </div>

    @if (session()->has('message'))
        <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-xl flex items-center gap-3">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            {{ session('message') }}
        </div>
    @endif

    <!-- Filters -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-slate-900/50 backdrop-blur-xl border border-slate-800 p-4 rounded-2xl shadow-xl">
        <div>
            <label class="block text-sm font-medium text-slate-400 mb-1">Search Leads</label>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search name or phone..." class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-400 mb-1">Lead Status</label>
            <select wire:model.live="filterStatus" class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200">
                <option value="">All Statuses</option>
                @foreach($statuses as $statusOption)
                    <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-slate-900/50 backdrop-blur-xl border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-800/50 text-xs uppercase text-slate-400 border-b border-slate-700">
                    <tr>
                        <th class="px-6 py-4 font-medium">Prospect Info</th>
                        <th class="px-6 py-4 font-medium">Contact</th>
                        <th class="px-6 py-4 font-medium">Expected Plan</th>
                        <th class="px-6 py-4 font-medium">Status</th>
                        <th class="px-6 py-4 font-medium">Added</th>
                        <th class="px-6 py-4 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/50">
                    @forelse($leads as $lead)
                        <tr class="hover:bg-slate-800/25 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-medium text-white">{{ $lead->full_name }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-slate-300">{{ $lead->phone }}</div>
                                @if($lead->email)
                                    <div class="text-xs text-slate-500">{{ $lead->email }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($lead->expectedPlan)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-800 text-indigo-300">
                                        {{ $lead->expectedPlan->name }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-500">Not specified</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $statusColors = [
                                        'new' => 'text-blue-400 bg-blue-400/10 border-blue-500/20',
                                        'contacted' => 'text-amber-400 bg-amber-400/10 border-amber-500/20',
                                        'survey_pending' => 'text-purple-400 bg-purple-400/10 border-purple-500/20',
                                        'proposal_sent' => 'text-cyan-400 bg-cyan-400/10 border-cyan-500/20',
                                        'won' => 'text-emerald-400 bg-emerald-400/10 border-emerald-500/20',
                                        'lost' => 'text-rose-400 bg-rose-400/10 border-rose-500/20',
                                    ];
                                    $sColor = $statusColors[$lead->status->value] ?? 'text-slate-400 bg-slate-800 border-slate-700';
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold border {{ $sColor }}">
                                    {{ $lead->status->label() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-400">
                                {{ $lead->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('crm.leads.detail', $lead->id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-white text-xs font-medium rounded-lg transition-colors border border-slate-700">
                                    Manage
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                <p class="text-lg font-medium text-slate-400">No leads found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-800">
            {{ $leads->links(data: ['scrollTo' => false]) }}
        </div>
    </div>

    <!-- Create Modal -->
    @if($isCreateModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity"></div>
        <div class="relative bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl w-full max-w-2xl p-6 overflow-hidden">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-white">Add New Lead</h3>
                <button wire:click="closeCreateModal" class="text-slate-400 hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form wire:submit="createLead" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">First Name *</label>
                        <input type="text" wire:model="firstName" class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500" required>
                        @error('firstName') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Last Name</label>
                        <input type="text" wire:model="lastName" class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Phone Number *</label>
                        <input type="text" wire:model="phone" class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500" required>
                        @error('phone') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Email</label>
                        <input type="email" wire:model="email" class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1">Address / Location</label>
                    <textarea wire:model="address" rows="2" class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1">Expected Service Plan</label>
                    <select wire:model="expectedPlanId" class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500">
                        <option value="">Undecided / Discuss later</option>
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }} (${{ number_format($plan->price, 2) }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1">Initial Notes</label>
                    <textarea wire:model="notes" rows="2" class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>

                <div class="pt-4 flex justify-end gap-3">
                    <button type="button" wire:click="closeCreateModal" class="px-4 py-2 text-sm font-medium text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 rounded-lg transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-500 rounded-lg transition-colors shadow-lg shadow-indigo-500/20">
                        Add Lead
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
