<div class="space-y-8 min-h-screen bg-slate-950 text-slate-100 font-sans p-6 lg:p-8" x-data="{ 
    notification: null,
    showNotification(type, message) {
        this.notification = { type, message };
        setTimeout(() => { this.notification = null; }, 4000);
    }
}" @notify.window="showNotification($event.detail[0].type, $event.detail[0].message)">

    <!-- Notification Toast -->
    <template x-if="notification">
        <div class="fixed bottom-5 right-5 z-50 flex items-center gap-3 px-5 py-4 rounded-2xl shadow-2xl border transition-all duration-300 transform translate-y-0"
             :class="{
                'bg-emerald-950 border-emerald-800 text-emerald-300': notification.type === 'success',
                'bg-rose-950 border-rose-800 text-rose-300': notification.type === 'error'
             }">
            <svg x-show="notification.type === 'success'" class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <svg x-show="notification.type === 'error'" class="w-5 h-5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <span class="text-sm font-semibold" x-text="notification.message"></span>
        </div>
    </template>

    <!-- Page Header -->
    <header class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-slate-800 pb-6 gap-4">
        <div>
            <div class="flex items-center gap-3">
                <span class="px-2.5 py-1 text-xs font-semibold tracking-wider text-rose-400 bg-rose-950/80 border border-rose-800 rounded-full uppercase flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-ping"></span>
                    SLA Monitor
                </span>
                <span class="px-2.5 py-1 text-xs font-semibold tracking-wider text-slate-400 bg-slate-900 border border-slate-800 rounded-full uppercase">
                    Support Module
                </span>
            </div>
            <h1 class="text-3xl font-extrabold text-white mt-2 tracking-tight">
                Support <span class="bg-gradient-to-r from-rose-500 to-indigo-500 bg-clip-text text-transparent">Tickets & SLA</span>
            </h1>
            <p class="text-slate-400 text-sm mt-1">Manage technical tickets, assign tasks, and monitor critical SLA resolution timers.</p>
        </div>
    </header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Left Column: Form to Create Ticket -->
        <div class="lg:col-span-1 bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl space-y-6 h-fit">
            <div>
                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                    <span class="w-2.5 h-6 bg-rose-500 rounded-full"></span>
                    Open Ticket
                </h2>
                <p class="text-slate-400 text-xs mt-1">Log a new support ticket and set urgency levels to calculate SLA response windows.</p>
            </div>

            <form wire:submit="createTicket" class="space-y-4">
                <!-- Customer -->
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Customer <span class="text-rose-500">*</span></label>
                    <select wire:model="customerId" class="w-full bg-slate-950 border border-slate-800 focus:border-rose-505 rounded-xl px-4 py-3 text-sm text-slate-100 outline-none transition duration-200">
                        <option value="">Select Customer</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}">{{ $c->full_name }} ({{ $c->customer_code }})</option>
                        @endforeach
                    </select>
                    @error('customerId') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <!-- Category -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Category <span class="text-rose-500">*</span></label>
                        <select wire:model="category" class="w-full bg-slate-950 border border-slate-800 focus:border-rose-505 rounded-xl px-4 py-3 text-sm text-slate-100 outline-none transition duration-200">
                            <option value="technical">Technical</option>
                            <option value="billing">Billing</option>
                            <option value="sales">Sales</option>
                            <option value="general">General</option>
                        </select>
                        @error('category') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Priority -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Priority <span class="text-rose-500">*</span></label>
                        <select wire:model="priority" class="w-full bg-slate-950 border border-slate-800 focus:border-rose-505 rounded-xl px-4 py-3 text-sm text-slate-100 outline-none transition duration-200">
                            <option value="low">Low (48h)</option>
                            <option value="medium">Medium (24h)</option>
                            <option value="high">High (4h)</option>
                            <option value="urgent">Urgent (2h)</option>
                        </select>
                        @error('priority') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Title -->
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Ticket Title <span class="text-rose-500">*</span></label>
                    <input wire:model="title" type="text" placeholder="e.g. PPPoE Session disconnected" class="w-full bg-slate-950 border border-slate-800 focus:border-rose-505 rounded-xl px-4 py-3 text-sm text-slate-100 placeholder-slate-600 outline-none transition duration-200">
                    @error('title') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Detailed Issue Description <span class="text-rose-500">*</span></label>
                    <textarea wire:model="description" rows="4" placeholder="Mention ONU status, optical power values, or specific error symptoms..." class="w-full bg-slate-950 border border-slate-800 focus:border-rose-505 rounded-xl px-4 py-3 text-sm text-slate-100 placeholder-slate-600 outline-none transition duration-200 resize-none"></textarea>
                    @error('description') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="w-full flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-rose-500 to-indigo-650 hover:from-rose-450 hover:to-indigo-550 text-white text-sm font-bold rounded-xl shadow-lg hover:shadow-rose-500/25 transition-all duration-200" wire:loading.attr="disabled">
                    <svg wire:loading.remove wire:target="createTicket" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    <svg wire:loading wire:target="createTicket" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span>Create Ticket</span>
                </button>
            </form>
        </div>

        <!-- Right Column: Tickets Table List -->
        <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl flex flex-col h-fit">
            <div class="p-6 border-b border-slate-800 flex justify-between items-center bg-slate-900/60 flex-wrap gap-4">
                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                    <span class="w-2.5 h-6 bg-indigo-500 rounded-full"></span>
                    Tickets Monitor
                </h2>
                
                <!-- POP/Zone Filter -->
                <div class="flex items-center gap-2 bg-slate-950 border border-slate-850 rounded-2xl px-3 py-1.5 shadow-xl">
                    <select wire:model.live="selectedPopId" wire:change="loadTickets" class="bg-transparent border-0 focus:ring-0 text-xs font-semibold text-slate-300 outline-none cursor-pointer">
                        <option value="" class="bg-slate-950 text-slate-300">All Zones / POPs</option>
                        @foreach($pops as $pop)
                            <option value="{{ $pop->id }}" class="bg-slate-950 text-slate-300">{{ $pop->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="text-xs uppercase bg-slate-950/50 text-slate-400 font-semibold tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Ticket ID & Title</th>
                            <th class="px-6 py-4">Customer Details</th>
                            <th class="px-6 py-4">Priority / Category</th>
                            <th class="px-6 py-4 text-center">SLA Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/80">
                        @forelse($tickets as $ticket)
                            @php
                                $isResolved = $ticket->status->value === 'resolved';
                                $isOverdue = !$isResolved && $ticket->sla_due_at && $ticket->sla_due_at->isPast();
                            @endphp
                            <tr class="hover:bg-slate-850/30 transition-colors">
                                <!-- ID & Title -->
                                <td class="px-6 py-4">
                                    <span class="font-mono text-xs font-bold text-slate-500">#{{ $ticket->id }}</span>
                                    <h4 class="font-bold text-slate-200 mt-1 select-all">{{ $ticket->title }}</h4>
                                    <span class="block text-[10px] text-slate-500 mt-1">Logged: {{ $ticket->created_at->format('Y-m-d H:i') }}</span>
                                </td>

                                <!-- Customer details -->
                                <td class="px-6 py-4">
                                    <span class="text-sm font-semibold text-slate-355 block">{{ $ticket->customer->full_name ?? 'Unknown' }}</span>
                                    <span class="text-[10px] text-slate-500 tracking-wider">Zone: {{ $ticket->customer->distributionBox->pop->name ?? 'Not Set' }}</span>
                                </td>

                                <!-- Priority/Category -->
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-1.5">
                                        <!-- PriorityBadge -->
                                        @if($ticket->priority->value === 'high' || $ticket->priority->value === 'urgent')
                                            <span class="inline-block w-fit px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider text-rose-400 bg-rose-950/60 border border-rose-900 rounded-md">
                                                {{ $ticket->priority->label() }}
                                            </span>
                                        @elseif($ticket->priority->value === 'medium')
                                            <span class="inline-block w-fit px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider text-amber-400 bg-amber-950/60 border border-amber-900 rounded-md">
                                                {{ $ticket->priority->label() }}
                                            </span>
                                        @else
                                            <span class="inline-block w-fit px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider text-slate-400 bg-slate-950 border border-slate-850 rounded-md">
                                                {{ $ticket->priority->label() }}
                                            </span>
                                        @endif

                                        <span class="text-[10px] text-slate-500 uppercase tracking-widest font-semibold">
                                            {{ $ticket->category->label() }}
                                        </span>
                                    </div>
                                </td>

                                <!-- SLA Status -->
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if($isResolved)
                                        <div class="flex flex-col items-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-950 text-emerald-400 border border-emerald-900/50 shadow-lg shadow-emerald-950/20">
                                                Resolved
                                            </span>
                                            @if($ticket->resolved_at)
                                                <span class="text-[9px] text-slate-500 mt-1 select-none">
                                                    Took {{ $ticket->resolved_at->diffForHumans($ticket->created_at, true) }}
                                                </span>
                                            @endif
                                        </div>
                                                                   @elseif($isOverdue)
                                        <div class="flex flex-col items-center">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-950 text-rose-450 border border-rose-900 shadow-lg shadow-rose-950/20">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-ping"></span>
                                                Overdue
                                            </span>
                                            @if($ticket->sla_due_at)
                                                <span class="text-[9px] text-rose-400 mt-1">
                                                    Passed by {{ $ticket->sla_due_at->diffForHumans(null, true) }}
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <div class="flex flex-col items-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-950 text-indigo-400 border border-indigo-900/50">
                                                In SLA
                                            </span>
                                            @if($ticket->sla_due_at)
                                                <span class="text-[9px] text-slate-400 mt-1">
                                                    Due in {{ $ticket->sla_due_at->diffForHumans(null, true) }}
                                                </span>
                                            @else
                                                <span class="text-[9px] text-slate-500 mt-1">
                                                    No SLA Set
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    @if(!$isResolved)
                                        <button wire:click="resolveTicket({{ $ticket->id }})" 
                                                wire:confirm="Confirm resolving support ticket #{{ $ticket->id }}?"
                                                class="px-4 py-2 text-xs font-bold bg-slate-950 border border-slate-800 hover:border-emerald-500 hover:bg-emerald-950/20 text-slate-400 hover:text-emerald-450 rounded-xl transition duration-150">
                                            Resolve
                                        </button>
                                    @else
                                        <span class="text-xs text-slate-600 font-medium">No actions</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center">
                                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-950 border border-slate-850 mb-4 text-slate-700">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    </div>
                                    <h3 class="text-sm font-semibold text-slate-300">Clean Support Queue</h3>
                                    <p class="text-xs text-slate-500 mt-1">There are no support tickets registered or matching the filter.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
