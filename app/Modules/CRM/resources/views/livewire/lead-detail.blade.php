<div class="space-y-6 max-w-6xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-4 bg-slate-900/80 backdrop-blur-xl border border-slate-800 p-6 rounded-2xl shadow-xl">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('crm.leads.index') }}" class="text-slate-400 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <h2 class="text-2xl font-bold text-white tracking-tight">{{ $lead->full_name }}</h2>
                @if($lead->is_converted)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-emerald-400/10 text-emerald-400 border border-emerald-500/20">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Converted
                    </span>
                @endif
            </div>
            <div class="flex items-center gap-4 text-sm text-slate-400">
                <span class="flex items-center gap-1.5 text-slate-300">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                    {{ $lead->phone }}
                </span>
                <span class="hidden md:inline">&bull;</span>
                <span>Added {{ $lead->created_at->format('M d, Y') }}</span>
            </div>
        </div>
        
        <div class="flex flex-col items-end gap-3">
            @if(!$lead->is_converted)
                <button wire:click="convertToCustomer" wire:confirm="Convert this lead to a Customer? This action cannot be undone." class="px-5 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-white text-sm font-bold rounded-xl transition-all shadow-lg shadow-emerald-500/20 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                    Convert to Customer
                </button>
            @endif
            
            <div class="flex items-center gap-2 bg-slate-800 rounded-lg p-1">
                @foreach($statuses as $status)
                    <button wire:click="updateStatus('{{ $status->value }}')" 
                            class="px-3 py-1.5 text-xs font-semibold rounded-md transition-colors {{ $lead->status->value === $status->value ? 'bg-slate-700 text-white shadow' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-700/50' }}"
                            @if($lead->is_converted) disabled @endif>
                        {{ $status->label() }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-xl flex items-center gap-3">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="p-4 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-xl flex items-center gap-3">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Interactions Column -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Log new interaction -->
            @if(!$lead->is_converted)
            <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800 rounded-2xl shadow-xl p-6">
                <h3 class="text-lg font-bold text-white mb-4">Log Interaction</h3>
                <form wire:submit="logInteraction" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-1">Interaction Type</label>
                            <select wire:model="interactionType" class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500">
                                @foreach($interactionTypes as $type)
                                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-1">Next Follow-up Date (Optional)</label>
                            <input type="datetime-local" wire:model="nextFollowUpDate" class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Summary / Notes</label>
                        <textarea wire:model="interactionSummary" rows="3" class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500" placeholder="What was discussed?" required></textarea>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-xl transition duration-200 shadow-lg shadow-indigo-500/20">
                            Save Interaction
                        </button>
                    </div>
                </form>
            </div>
            @endif

            <!-- History Timeline -->
            <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800 rounded-2xl shadow-xl p-6">
                <h3 class="text-lg font-bold text-white mb-6">Interaction History</h3>
                
                <div class="space-y-6 relative before:absolute before:inset-0 before:ml-5 before:-translate-x-px md:before:ml-[8.5rem] md:before:translate-x-0 before:h-full before:w-0.5 before:bg-gradient-to-b before:from-transparent before:via-slate-800 before:to-transparent">
                    @forelse($lead->interactions as $interaction)
                        <div class="relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse group is-active">
                            <!-- Icon -->
                            <div class="flex items-center justify-center w-10 h-10 rounded-full border-4 border-slate-950 bg-slate-800 text-slate-400 group-[.is-active]:bg-indigo-500/20 group-[.is-active]:text-indigo-400 group-[.is-active]:border-indigo-500/30 shadow shrink-0 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2 z-10">
                                @if($interaction->type->value === 'call')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                @elseif($interaction->type->value === 'email')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                @elseif($interaction->type->value === 'meeting')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                @else
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                @endif
                            </div>
                            <!-- Card -->
                            <div class="w-[calc(100%-4rem)] md:w-[calc(50%-2.5rem)] bg-slate-800/50 p-4 rounded-xl border border-slate-700/50 shadow">
                                <div class="flex items-center justify-between mb-1">
                                    <div class="font-semibold text-slate-200">{{ $interaction->type->label() }}</div>
                                    <div class="text-[10px] text-slate-500">{{ $interaction->created_at->format('M d, g:i A') }}</div>
                                </div>
                                <div class="text-sm text-slate-300 whitespace-pre-wrap">{{ $interaction->summary }}</div>
                                
                                @if($interaction->next_follow_up_date)
                                    <div class="mt-3 pt-3 border-t border-slate-700/50 flex items-center gap-1.5 text-xs text-amber-400">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        Follow-up: {{ $interaction->next_follow_up_date->format('M d, Y g:i A') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-slate-500 py-4">No interactions logged yet.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Sidebar Details -->
        <div class="space-y-6">
            <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800 rounded-2xl shadow-xl p-6">
                <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4 border-b border-slate-800 pb-2">Lead Information</h3>
                
                <div class="space-y-4 text-sm">
                    <div>
                        <div class="text-xs text-slate-500 mb-0.5">Email</div>
                        <div class="text-white">{{ $lead->email ?: 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500 mb-0.5">Address</div>
                        <div class="text-white">{{ $lead->address ?: 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500 mb-0.5">Expected Plan</div>
                        @if($lead->expectedPlan)
                            <div class="text-indigo-400 font-medium">{{ $lead->expectedPlan->name }} (${{ number_format($lead->expectedPlan->price, 2) }})</div>
                        @else
                            <div class="text-slate-400">Undecided</div>
                        @endif
                    </div>
                    @if($lead->notes)
                    <div class="pt-2">
                        <div class="text-xs text-slate-500 mb-0.5">Initial Notes</div>
                        <div class="text-slate-300 italic">{{ $lead->notes }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
