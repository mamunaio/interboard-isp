<div>
    <h3 class="text-lg font-semibold text-slate-200 mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        Lifecycle History
    </h3>
    
    @php
        $events = \App\Modules\Customer\Models\CustomerLifecycleEvent::where('customer_id', $customerId)
                    ->orderBy('created_at', 'desc')
                    ->get();
    @endphp

    @if($events->isEmpty())
        <div class="text-slate-400 text-sm py-4 text-center bg-slate-800/30 rounded-xl border border-slate-700/50">
            No lifecycle events recorded yet.
        </div>
    @else
        <div class="relative border-l border-slate-700 ml-3 space-y-6">
            @foreach($events as $event)
                <div class="pl-6 relative">
                    <!-- Timeline Dot -->
                    @php
                        $color = 'bg-slate-500';
                        if($event->new_status === 'active') $color = 'bg-green-500';
                        if($event->new_status === 'suspended') $color = 'bg-amber-500';
                        if($event->new_status === 'churned') $color = 'bg-red-500';
                    @endphp
                    <div class="absolute w-3 h-3 rounded-full {{ $color }} -left-[6.5px] top-1.5 ring-4 ring-slate-900"></div>
                    
                    <div class="bg-slate-800/50 rounded-xl border border-slate-700/50 p-4">
                        <div class="flex justify-between items-start mb-1">
                            <span class="text-sm font-medium text-slate-200">
                                Status changed to <span class="capitalize text-white">{{ $event->new_status }}</span>
                            </span>
                            <span class="text-xs text-slate-400">{{ $event->created_at->format('M d, Y h:i A') }}</span>
                        </div>
                        @if($event->reason)
                            <p class="text-sm text-slate-400 mt-1">Reason: {{ $event->reason }}</p>
                        @endif
                        @if($event->previous_status)
                            <p class="text-xs text-slate-500 mt-2">Previous Status: <span class="capitalize">{{ $event->previous_status }}</span></p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
