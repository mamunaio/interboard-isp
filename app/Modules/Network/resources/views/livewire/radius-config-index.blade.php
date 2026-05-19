<div class="p-6 lg:p-8 min-h-screen bg-slate-950 text-slate-100 font-sans antialiased">
    <!-- Header -->
    <header class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-slate-800 pb-6 mb-8 gap-4">
        <div>
            <div class="flex items-center gap-3">
                <span class="px-2.5 py-1 text-xs font-semibold tracking-wider text-rose-400 bg-rose-950 border border-rose-800 rounded-full uppercase">
                    Network
                </span>
                <span class="text-xs text-slate-400">AAA Server</span>
            </div>
            <h1 class="text-3xl font-extrabold text-white mt-1 tracking-tight">
                RADIUS <span class="bg-gradient-to-r from-rose-400 to-indigo-500 bg-clip-text text-transparent">Configuration</span>
            </h1>
            <p class="text-slate-400 text-sm mt-1">Manage dictionary attributes, speed profiles, and NAS authorization rules.</p>
        </div>
    </header>

    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl mb-8">
        <div class="flex flex-col md:flex-row gap-4 mb-6">
            <div class="flex-1">
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by attribute, username, or value..." class="w-full bg-slate-950 border border-slate-800 focus:border-rose-500 rounded-2xl px-4 py-2.5 text-sm outline-none transition">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-800">
                        <th class="py-3 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Identity (Username)</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Attribute</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Op</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Value</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($replies as $reply)
                        <tr class="hover:bg-slate-800/30 transition duration-150">
                            <td class="py-4 px-4 align-middle">
                                <p class="text-sm font-bold text-white">{{ $reply->subscription->customer->full_name ?? 'Static Rule' }}</p>
                                <p class="text-xs text-rose-400 font-mono">{{ $reply->username }}</p>
                            </td>
                            <td class="py-4 px-4 align-middle">
                                <span class="px-2 py-1 text-xs font-mono bg-slate-950 border border-slate-800 rounded text-slate-300">
                                    {{ $reply->attribute }}
                                </span>
                            </td>
                            <td class="py-4 px-4 align-middle text-center w-12">
                                <span class="text-xs font-bold text-slate-500">{{ $reply->op }}</span>
                            </td>
                            <td class="py-4 px-4 align-middle">
                                <span class="text-sm text-slate-200 font-mono bg-slate-900 px-2 py-1 rounded">
                                    {{ $reply->value }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-slate-500 text-sm">
                                No RADIUS reply rules configured yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-6">
            {{ $replies->links() }}
        </div>
    </div>
</div>
