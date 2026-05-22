<div class="space-y-6 max-w-5xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4 bg-slate-900/80 backdrop-blur-xl border border-slate-800 p-6 rounded-2xl shadow-xl">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('support.tickets.index') }}" class="text-slate-400 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <h2 class="text-2xl font-bold text-white tracking-tight">{{ $ticket->title }}</h2>
            </div>
            <div class="flex items-center gap-4 text-sm text-slate-400">
                <span class="font-mono text-indigo-400">#{{ str_pad($ticket->id, 5, '0', STR_PAD_LEFT) }}</span>
                <span class="hidden md:inline">&bull;</span>
                <span class="flex items-center gap-1.5 text-slate-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    {{ $ticket->customer->full_name }}
                </span>
                <span class="hidden md:inline">&bull;</span>
                <span>Created {{ $ticket->created_at->format('M d, Y h:i A') }}</span>
            </div>
        </div>
        
        <div class="flex items-center gap-3">
            @if($ticket->status !== \App\Modules\Support\Enums\TicketStatus::CLOSED)
                <button wire:click="closeTicket" wire:confirm="Are you sure you want to close this ticket?" class="px-4 py-2 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/20 text-sm font-semibold rounded-lg transition-colors">
                    Mark as Resolved
                </button>
            @else
                <span class="px-4 py-2 bg-slate-800 text-slate-400 border border-slate-700 text-sm font-semibold rounded-lg">
                    Closed
                </span>
            @endif
        </div>
    </div>

    @if (session()->has('message'))
        <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-xl flex items-center gap-3">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            {{ session('message') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Conversation Thread -->
        <div class="lg:col-span-2 space-y-6">
            <div class="space-y-4">
                @foreach($ticket->replies as $reply)
                    <div class="flex gap-4 {{ $reply->is_internal ? 'opacity-90' : '' }}">
                        <div class="flex-shrink-0 mt-1">
                            @if($reply->is_customer_reply)
                                <div class="w-10 h-10 rounded-full bg-blue-500/20 border border-blue-500/30 flex items-center justify-center text-blue-400 font-bold">
                                    {{ substr($ticket->customer->first_name, 0, 1) }}
                                </div>
                            @else
                                <div class="w-10 h-10 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center text-slate-300 font-bold">
                                    {{ $reply->user ? substr($reply->user->name, 0, 1) : 'S' }}
                                </div>
                            @endif
                        </div>
                        <div class="flex-1">
                            <div class="bg-slate-900/80 backdrop-blur-sm border {{ $reply->is_internal ? 'border-amber-500/30 bg-amber-500/5' : 'border-slate-800' }} rounded-2xl p-5 shadow-sm">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="font-semibold text-white">
                                        {{ $reply->is_customer_reply ? $ticket->customer->full_name : ($reply->user ? $reply->user->name : 'Support Team') }}
                                        
                                        @if($reply->is_internal)
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-400 uppercase tracking-wider">Internal Note</span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        {{ $reply->created_at->diffForHumans() }}
                                    </div>
                                </div>
                                <div class="text-slate-300 text-sm whitespace-pre-wrap leading-relaxed">{{ $reply->message }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Reply Box -->
            @if($ticket->status !== \App\Modules\Support\Enums\TicketStatus::CLOSED)
                <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800 rounded-2xl shadow-xl p-6 mt-8">
                    <h3 class="text-lg font-bold text-white mb-4">Post a Reply</h3>
                    <form wire:submit="postReply">
                        <textarea wire:model="replyMessage" rows="4" class="w-full bg-slate-800 border border-slate-700 text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200" placeholder="Type your response here..." required></textarea>
                        
                        <div class="flex items-center justify-between mt-4">
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input type="checkbox" wire:model="isInternalNote" class="w-5 h-5 rounded bg-slate-800 border-slate-700 text-amber-500 focus:ring-amber-500 transition-colors">
                                <span class="text-sm font-medium text-slate-400 group-hover:text-slate-300 transition-colors">Private Internal Note</span>
                            </label>
                            
                            <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-xl transition duration-200 shadow-lg shadow-indigo-500/20 flex items-center gap-2">
                                <span>Send Reply</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>

        <!-- Sidebar Details -->
        <div class="space-y-6">
            <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800 rounded-2xl shadow-xl p-6">
                <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4 border-b border-slate-800 pb-2">Ticket Details</h3>
                
                <div class="space-y-4">
                    <div>
                        <div class="text-xs text-slate-500 mb-1">Status</div>
                        @php
                            $statusColors = [
                                'open' => 'text-amber-400 bg-amber-400/10',
                                'in_progress' => 'text-blue-400 bg-blue-400/10',
                                'waiting' => 'text-purple-400 bg-purple-400/10',
                                'closed' => 'text-emerald-400 bg-emerald-400/10',
                            ];
                            $sColor = $statusColors[$ticket->status->value] ?? 'text-slate-400 bg-slate-800';
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold {{ $sColor }}">
                            {{ $ticket->status->label() }}
                        </span>
                    </div>
                    
                    <div>
                        <div class="text-xs text-slate-500 mb-1">Priority</div>
                        <span class="text-sm text-white font-medium">{{ $ticket->priority->label() }}</span>
                    </div>

                    <div>
                        <div class="text-xs text-slate-500 mb-1">Category</div>
                        <span class="text-sm text-white font-medium">{{ $ticket->category->label() }}</span>
                    </div>
                    
                    @if($ticket->workOrder)
                        <div class="pt-2 border-t border-slate-800">
                            <div class="text-xs text-slate-500 mb-1">Linked Work Order</div>
                            <a href="/work-orders" class="text-sm text-indigo-400 hover:text-indigo-300 font-medium transition-colors">
                                #WO-{{ str_pad($ticket->workOrder->id, 5, '0', STR_PAD_LEFT) }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
            
            <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800 rounded-2xl shadow-xl p-6">
                <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4 border-b border-slate-800 pb-2">Customer Info</h3>
                
                <div class="space-y-3 text-sm">
                    <div>
                        <div class="text-white font-bold">{{ $ticket->customer->full_name }}</div>
                        <div class="text-xs text-slate-500">{{ $ticket->customer->customer_code }}</div>
                    </div>
                    
                    <div class="flex items-center gap-2 text-slate-300">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        <a href="mailto:{{ $ticket->customer->email }}" class="hover:text-indigo-400 transition-colors">{{ $ticket->customer->email }}</a>
                    </div>
                    
                    <div class="flex items-center gap-2 text-slate-300">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                        {{ $ticket->customer->phone }}
                    </div>
                    
                    <div class="pt-3 mt-3 border-t border-slate-800">
                        <a href="{{ route('tenant.customers.detail', $ticket->customer->id) }}" class="text-indigo-400 hover:text-indigo-300 text-sm font-medium transition-colors flex items-center gap-1">
                            View Customer Profile
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
