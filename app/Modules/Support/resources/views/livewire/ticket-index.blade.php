<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-white tracking-tight">Support Tickets</h2>
            <p class="text-sm text-slate-400 mt-1">Manage and resolve customer issues</p>
        </div>
        <button wire:click="openCreateModal" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-xl transition duration-200 shadow-lg shadow-indigo-500/20">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            New Ticket
        </button>
    </div>

    @if (session()->has('message'))
        <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-xl flex items-center gap-3">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            {{ session('message') }}
        </div>
    @endif

    <!-- Filters -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-slate-900/50 backdrop-blur-xl border border-slate-800 p-4 rounded-2xl shadow-xl">
        <div>
            <label class="block text-sm font-medium text-slate-400 mb-1">Search</label>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search title or customer..." class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-400 mb-1">Status</label>
            <select wire:model.live="filterStatus" class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200">
                <option value="">All Statuses</option>
                @foreach($statuses as $statusOption)
                    <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-400 mb-1">Priority</label>
            <select wire:model.live="filterPriority" class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200">
                <option value="">All Priorities</option>
                @foreach($priorities as $priorityOption)
                    <option value="{{ $priorityOption->value }}">{{ $priorityOption->label() }}</option>
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
                        <th class="px-6 py-4 font-medium">Ticket / Customer</th>
                        <th class="px-6 py-4 font-medium">Category</th>
                        <th class="px-6 py-4 font-medium">Status</th>
                        <th class="px-6 py-4 font-medium">Priority</th>
                        <th class="px-6 py-4 font-medium">Last Updated</th>
                        <th class="px-6 py-4 font-medium text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/50">
                    @forelse($tickets as $ticket)
                        <tr class="hover:bg-slate-800/25 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="font-medium text-white text-base mb-1">{{ $ticket->title }}</div>
                                <div class="flex items-center gap-2 text-xs text-slate-400">
                                    <span class="text-indigo-400 font-mono">#{{ str_pad($ticket->id, 5, '0', STR_PAD_LEFT) }}</span>
                                    <span>&bull;</span>
                                    <span>{{ $ticket->customer->full_name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-800 text-slate-300">
                                    {{ $ticket->category->label() }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $statusColors = [
                                        'open' => 'text-amber-400 bg-amber-400/10 border-amber-500/20',
                                        'in_progress' => 'text-blue-400 bg-blue-400/10 border-blue-500/20',
                                        'waiting' => 'text-purple-400 bg-purple-400/10 border-purple-500/20',
                                        'closed' => 'text-emerald-400 bg-emerald-400/10 border-emerald-500/20',
                                    ];
                                    $sColor = $statusColors[$ticket->status->value] ?? 'text-slate-400 bg-slate-800 border-slate-700';
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold border {{ $sColor }}">
                                    {{ $ticket->status->label() }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $priorityColors = [
                                        'low' => 'text-slate-400',
                                        'medium' => 'text-blue-400',
                                        'high' => 'text-orange-400',
                                        'urgent' => 'text-rose-400 font-bold',
                                    ];
                                    $pColor = $priorityColors[$ticket->priority->value] ?? 'text-slate-400';
                                @endphp
                                <span class="text-sm {{ $pColor }}">
                                    {{ $ticket->priority->label() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-400">
                                {{ $ticket->updated_at->diffForHumans() }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('support.tickets.detail', $ticket->id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-white text-xs font-medium rounded-lg transition-colors border border-slate-700">
                                    View
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                <svg class="w-12 h-12 mx-auto text-slate-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                <p class="text-lg font-medium text-slate-400">No support tickets found</p>
                                <p class="text-sm mt-1">Customers are happy and there are no ongoing issues.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-800">
            {{ $tickets->links(data: ['scrollTo' => false]) }}
        </div>
    </div>

    <!-- Create Ticket Modal -->
    @if($isCreateModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity"></div>
        <div class="relative bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl w-full max-w-2xl p-6 overflow-hidden">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-white">Create Support Ticket</h3>
                <button wire:click="closeCreateModal" class="text-slate-400 hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form wire:submit="createTicket" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1">Customer *</label>
                    <select wire:model="customerId" class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500" required>
                        <option value="">Select Customer...</option>
                        @foreach($customers as $cust)
                            <option value="{{ $cust->id }}">{{ $cust->full_name }} ({{ $cust->customer_code }})</option>
                        @endforeach
                    </select>
                    @error('customerId') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1">Issue Title / Subject *</label>
                    <input type="text" wire:model="title" class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500" placeholder="Brief summary of the issue..." required>
                    @error('title') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Category *</label>
                        <select wire:model="category" class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500">
                            @foreach($categories as $cat)
                                <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                            @endforeach
                        </select>
                        @error('category') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Priority *</label>
                        <select wire:model="priority" class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500">
                            @foreach($priorities as $pri)
                                <option value="{{ $pri->value }}">{{ $pri->label() }}</option>
                            @endforeach
                        </select>
                        @error('priority') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1">Initial Message / Description *</label>
                    <textarea wire:model="message" rows="5" class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500" placeholder="Provide detailed information about the issue..." required></textarea>
                    @error('message') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="pt-4 flex justify-end gap-3">
                    <button type="button" wire:click="closeCreateModal" class="px-4 py-2 text-sm font-medium text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 rounded-lg transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-500 rounded-lg transition-colors shadow-lg shadow-indigo-500/20">
                        Create Ticket
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
