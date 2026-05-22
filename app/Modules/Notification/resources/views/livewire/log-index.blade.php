<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-white tracking-tight">Notification Logs</h2>
            <p class="text-sm text-slate-400 mt-1">History of all messages sent to customers</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-slate-900/50 backdrop-blur-xl border border-slate-800 p-4 rounded-2xl shadow-xl">
        <div>
            <label class="block text-sm font-medium text-slate-400 mb-1">Search Recipient</label>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Phone, email or customer..." class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-400 mb-1">Channel</label>
            <select wire:model.live="filterChannel" class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200">
                <option value="">All Channels</option>
                <option value="sms">SMS</option>
                <option value="email">Email</option>
                <option value="whatsapp">WhatsApp</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-400 mb-1">Status</label>
            <select wire:model.live="filterStatus" class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200">
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="sent">Sent</option>
                <option value="failed">Failed</option>
            </select>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-slate-900/50 backdrop-blur-xl border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-800/50 text-xs uppercase text-slate-400 border-b border-slate-700">
                    <tr>
                        <th class="px-6 py-4 font-medium">Date & Time</th>
                        <th class="px-6 py-4 font-medium">Customer / Recipient</th>
                        <th class="px-6 py-4 font-medium">Channel</th>
                        <th class="px-6 py-4 font-medium">Message Details</th>
                        <th class="px-6 py-4 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/50">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-800/25 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-white font-medium">{{ $log->created_at->format('M d, Y') }}</div>
                                <div class="text-xs text-slate-500">{{ $log->created_at->format('h:i A') }}</div>
                            </td>
                            <td class="px-6 py-4">
                                @if($log->customer)
                                    <div class="font-medium text-white">{{ $log->customer->full_name }}</div>
                                @endif
                                <div class="text-xs text-slate-400 font-mono mt-0.5">{{ $log->recipient }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-slate-800 text-slate-300 uppercase tracking-wider">
                                    {{ $log->channel }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="max-w-xs md:max-w-md truncate text-slate-300 cursor-help" title="{{ $log->message }}">
                                    {{ $log->message }}
                                </div>
                                @if($log->error_message)
                                    <div class="text-xs text-rose-400 mt-1 truncate max-w-xs" title="{{ $log->error_message }}">
                                        Error: {{ $log->error_message }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($log->status === 'sent')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold text-emerald-400 bg-emerald-400/10 border border-emerald-500/20">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        Sent
                                    </span>
                                @elseif($log->status === 'failed')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold text-rose-400 bg-rose-400/10 border border-rose-500/20">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        Failed
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold text-amber-400 bg-amber-400/10 border border-amber-500/20">
                                        <svg class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                        Pending
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                <svg class="w-12 h-12 mx-auto text-slate-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                <p class="text-lg font-medium text-slate-400">No logs found</p>
                                <p class="text-sm mt-1">No notifications have been sent yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-800">
            {{ $logs->links(data: ['scrollTo' => false]) }}
        </div>
    </div>
</div>
