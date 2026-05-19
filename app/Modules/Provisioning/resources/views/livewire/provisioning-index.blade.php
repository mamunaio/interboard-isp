<div class="p-6 lg:p-8 min-h-screen bg-slate-950 text-slate-100 font-sans antialiased">
    <!-- Header -->
    <header class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-slate-800 pb-6 mb-8 gap-4">
        <div>
            <div class="flex items-center gap-3">
                <span class="px-2.5 py-1 text-xs font-semibold tracking-wider text-indigo-400 bg-indigo-950 border border-indigo-800 rounded-full uppercase">
                    Service Provisioning
                </span>
                <span class="text-xs text-slate-400">RouterOS Sync Engine</span>
            </div>
            <h1 class="text-3xl font-extrabold text-white mt-1 tracking-tight">
                Network <span class="bg-gradient-to-r from-indigo-400 to-cyan-500 bg-clip-text text-transparent">Entitlement Sync</span>
            </h1>
            <p class="text-slate-400 text-sm mt-1">Real-time status of NAS provisioning operations.</p>
        </div>
    </header>

    @if (session()->has('message'))
        <div class="mb-6 p-4 bg-emerald-950/60 border border-emerald-800 text-emerald-400 rounded-2xl flex items-center gap-3">
            <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span class="text-sm font-semibold">{{ session('message') }}</span>
        </div>
    @endif

    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl mb-8">
        <div class="flex flex-col md:flex-row gap-4 mb-6">
            <div class="flex-1">
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search customer, sub code..." class="w-full bg-slate-950 border border-slate-800 focus:border-indigo-500 rounded-2xl px-4 py-2.5 text-sm outline-none transition">
            </div>
            <div class="w-full md:w-64">
                <select wire:model.live="statusFilter" class="w-full bg-slate-950 border border-slate-800 focus:border-indigo-500 rounded-2xl px-4 py-2.5 text-sm outline-none transition">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="completed">Completed</option>
                    <option value="failed">Failed</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-800">
                        <th class="py-3 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Date</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Customer & Sub</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Action</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Status</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider text-right">Options</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($events as $event)
                        <tr class="hover:bg-slate-800/30 transition duration-150">
                            <td class="py-4 px-4 align-middle">
                                <p class="text-sm text-slate-300 font-mono">{{ $event->created_at->format('Y-m-d H:i') }}</p>
                            </td>
                            <td class="py-4 px-4 align-middle">
                                <p class="text-sm font-bold text-white">{{ $event->customer->full_name ?? 'N/A' }}</p>
                                <p class="text-xs text-indigo-400 font-mono">{{ $event->subscription->subscription_code ?? 'N/A' }}</p>
                            </td>
                            <td class="py-4 px-4 align-middle">
                                <span class="px-2 py-1 text-[10px] font-bold uppercase rounded border bg-slate-950 text-slate-300 border-slate-700">
                                    {{ $event->action_type->value }}
                                </span>
                            </td>
                            <td class="py-4 px-4 align-middle">
                                @php
                                    $statusColor = match($event->status->value) {
                                        'completed' => 'text-emerald-400 bg-emerald-950 border-emerald-900',
                                        'failed' => 'text-rose-400 bg-rose-950 border-rose-900',
                                        'processing' => 'text-amber-400 bg-amber-950 border-amber-900',
                                        default => 'text-slate-400 bg-slate-900 border-slate-800'
                                    };
                                @endphp
                                <span class="px-2 py-1 text-[10px] font-bold uppercase rounded border {{ $statusColor }}">
                                    {{ $event->status->value }}
                                </span>
                            </td>
                            <td class="py-4 px-4 align-middle text-right">
                                <button wire:click="viewDetails({{ $event->id }})" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300">View Details</button>
                                @if($event->status->value === 'failed')
                                    <button wire:click="retryAction({{ $event->id }})" class="ml-3 text-xs font-semibold text-rose-400 hover:text-rose-300">Retry</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-500 text-sm">
                                No provisioning events found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-6">
            {{ $events->links() }}
        </div>
    </div>

    <!-- Event Detail Modal -->
    @if($isModalOpen && $selectedEvent)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl w-full max-w-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
                <div class="px-6 py-5 border-b border-slate-800 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-white">Event #{{ $selectedEvent->id }} Details</h3>
                    <button wire:click="$set('isModalOpen', false)" class="text-slate-400 hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                <div class="p-6 overflow-y-auto space-y-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="block text-xs font-semibold text-slate-500 uppercase">Action</span>
                            <span class="text-sm font-bold text-indigo-400">{{ $selectedEvent->action_type->value }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-slate-500 uppercase">Status</span>
                            <span class="text-sm font-bold text-slate-200">{{ $selectedEvent->status->value }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-slate-500 uppercase">Retries</span>
                            <span class="text-sm font-bold text-slate-200">{{ $selectedEvent->retry_count }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-slate-500 uppercase">Executed At</span>
                            <span class="text-sm font-bold text-slate-200">{{ $selectedEvent->executed_at?->format('Y-m-d H:i:s') ?? 'N/A' }}</span>
                        </div>
                    </div>

                    @if($selectedEvent->error_message)
                        <div>
                            <span class="block text-xs font-semibold text-rose-500 uppercase mb-2">Error Message</span>
                            <div class="p-4 bg-rose-950/30 border border-rose-900/50 rounded-xl">
                                <p class="text-xs text-rose-400 font-mono break-words">{{ $selectedEvent->error_message }}</p>
                            </div>
                        </div>
                    @endif

                    <div>
                        <span class="block text-xs font-semibold text-slate-500 uppercase mb-2">Request Payload</span>
                        <div class="p-4 bg-slate-950 border border-slate-800 rounded-xl overflow-x-auto">
                            <pre class="text-xs text-slate-300 font-mono">{{ json_encode($selectedEvent->request_payload_json, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    </div>

                    <div>
                        <span class="block text-xs font-semibold text-slate-500 uppercase mb-2">Response Payload</span>
                        <div class="p-4 bg-slate-950 border border-slate-800 rounded-xl overflow-x-auto">
                            <pre class="text-xs text-slate-300 font-mono">{{ json_encode($selectedEvent->response_payload_json, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
