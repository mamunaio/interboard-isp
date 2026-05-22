<div class="p-6 lg:p-8 min-h-screen bg-slate-950 text-slate-100 font-sans antialiased">
    <!-- Header -->
    <header class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-slate-800 pb-6 mb-8 gap-4">
        <div>
            <div class="flex items-center gap-3">
                <span class="px-2.5 py-1 text-xs font-semibold tracking-wider text-cyan-400 bg-cyan-950 border border-cyan-800 rounded-full uppercase">
                    Network
                </span>
                <span class="text-xs text-slate-400">OLT GPON Integration</span>
            </div>
            <h1 class="text-3xl font-extrabold text-white mt-1 tracking-tight">
                OLT Devices <span class="bg-gradient-to-r from-cyan-400 to-indigo-500 bg-clip-text text-transparent">Configuration</span>
            </h1>
            <p class="text-slate-400 text-sm mt-1">Manage OLT devices, configure vendors (ZTE/Huawei), and monitor active connections.</p>
        </div>
        <div>
            <button wire:click="toggleCreateModal" class="inline-flex items-center gap-2 bg-gradient-to-r from-cyan-500 to-indigo-600 hover:from-cyan-450 hover:to-indigo-550 text-white font-semibold px-4 py-2.5 rounded-2xl shadow-lg transition duration-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Add OLT Device
            </button>
        </div>
    </header>

    @if (session()->has('message'))
        <div class="mb-6 p-4 rounded-2xl bg-emerald-950 border border-emerald-800 text-emerald-300 text-sm flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            {{ session('message') }}
        </div>
    @endif

    <!-- OLT List -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        @forelse($olts as $olt)
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl relative flex flex-col justify-between hover:border-slate-750 transition duration-200">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="px-2.5 py-1 text-xs font-bold font-mono rounded-lg bg-slate-950 border border-slate-800 text-slate-300 uppercase">
                            {{ $olt->vendor }}
                        </span>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full {{ $olt->status === 'online' ? 'bg-emerald-500 shadow-[0_0_8px_#10b981]' : ($olt->status === 'offline' ? 'bg-rose-500 shadow-[0_0_8px_#f43f5e]' : 'bg-amber-500 shadow-[0_0_8px_#f59e0b]') }}"></span>
                            <span class="text-xs font-bold text-slate-400 capitalize">{{ $olt->status ?? 'unknown' }}</span>
                        </div>
                    </div>
                    
                    <h3 class="text-lg font-bold text-white mb-1">{{ $olt->name }}</h3>
                    <p class="text-xs text-slate-500 font-mono mb-4">{{ $olt->ip_address }}:{{ $olt->port }}</p>

                    @if(isset($statusCheckResults[$olt->id]))
                        <div class="mt-3 p-3 rounded-2xl text-xs {{ $statusCheckResults[$olt->id]['status'] === 'online' ? 'bg-emerald-950/40 border border-emerald-900/50 text-emerald-400' : 'bg-rose-950/40 border border-rose-900/50 text-rose-400' }}">
                            <p class="font-semibold">{{ $statusCheckResults[$olt->id]['message'] }}</p>
                            <p class="text-[10px] text-slate-500 mt-1">Checked at: {{ $statusCheckResults[$olt->id]['time'] }}</p>
                        </div>
                    @endif
                </div>

                <div class="mt-6 flex items-center justify-between border-t border-slate-800/60 pt-4 gap-2">
                    <button wire:click="testConnection({{ $olt->id }})" wire:loading.attr="disabled" class="inline-flex items-center gap-1.5 text-xs text-cyan-400 hover:text-cyan-300 bg-cyan-950/40 hover:bg-cyan-950 border border-cyan-900/50 px-3 py-1.5 rounded-xl transition">
                        <svg wire:loading.remove wire:target="testConnection({{ $olt->id }})" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        <svg wire:loading wire:target="testConnection({{ $olt->id }})" class="animate-spin w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Ping
                    </button>
                    
                    <div class="flex gap-2">
                        <button wire:click="editOlt({{ $olt->id }})" class="p-1.5 text-slate-400 hover:text-white transition">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                        </button>
                        <button onclick="confirm('Are you sure you want to delete this OLT device?') || event.stopImmediatePropagation()" wire:click="deleteOlt({{ $olt->id }})" class="p-1.5 text-slate-500 hover:text-rose-400 transition">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-slate-900 border border-slate-800 rounded-3xl p-12 text-center text-slate-500 text-sm shadow-xl">
                No OLT Devices configured yet. Click "Add OLT Device" to get started.
            </div>
        @endforelse
    </div>

    <!-- Create/Edit Modal -->
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-lg shadow-2xl relative">
                <button wire:click="toggleCreateModal" class="absolute top-4 right-4 text-slate-400 hover:text-white transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>

                <h2 class="text-xl font-bold text-white mb-6">
                    {{ $editingOltId ? 'Edit OLT Device' : 'Add New OLT Device' }}
                </h2>

                <form wire:submit.prevent="saveOlt" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-450 mb-1 uppercase tracking-wider">Device Name</label>
                        <input wire:model="name" type="text" placeholder="e.g. Core OLT Dhaka" class="w-full bg-slate-950 border border-slate-800 focus:border-cyan-500 rounded-xl px-4 py-2.5 text-sm outline-none transition text-white">
                        @error('name') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="col-span-2">
                            <label class="block text-xs font-semibold text-slate-450 mb-1 uppercase tracking-wider">IP Address</label>
                            <input wire:model="ip_address" type="text" placeholder="e.g. 10.10.10.2" class="w-full bg-slate-950 border border-slate-800 focus:border-cyan-500 rounded-xl px-4 py-2.5 text-sm outline-none transition text-white">
                            @error('ip_address') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-450 mb-1 uppercase tracking-wider">Telnet Port</label>
                            <input wire:model="port" type="number" class="w-full bg-slate-950 border border-slate-800 focus:border-cyan-500 rounded-xl px-4 py-2.5 text-sm outline-none transition text-white">
                            @error('port') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-450 mb-1 uppercase tracking-wider">Username</label>
                            <input wire:model="username" type="text" placeholder="admin" class="w-full bg-slate-950 border border-slate-800 focus:border-cyan-500 rounded-xl px-4 py-2.5 text-sm outline-none transition text-white">
                            @error('username') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-450 mb-1 uppercase tracking-wider">Password</label>
                            <input wire:model="password" type="password" placeholder="password" class="w-full bg-slate-950 border border-slate-800 focus:border-cyan-500 rounded-xl px-4 py-2.5 text-sm outline-none transition text-white">
                            @error('password') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-450 mb-1 uppercase tracking-wider">Vendor</label>
                        <select wire:model="vendor" class="w-full bg-slate-950 border border-slate-800 focus:border-cyan-500 rounded-xl px-4 py-2.5 text-sm outline-none transition text-white">
                            <option value="zte">ZTE</option>
                            <option value="huawei">Huawei</option>
                        </select>
                        @error('vendor') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="pt-4 border-t border-slate-800 flex justify-end gap-3">
                        <button type="button" wire:click="toggleCreateModal" class="px-4 py-2 text-sm text-slate-400 hover:text-white font-medium transition">
                            Cancel
                        </button>
                        <button type="submit" class="bg-cyan-500 hover:bg-cyan-600 text-slate-950 font-bold px-4 py-2 rounded-xl text-sm transition">
                            Save Device
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
