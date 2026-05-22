<div class="p-6 lg:p-8 min-h-screen bg-slate-950 text-slate-100 font-sans antialiased">
    <!-- Header -->
    <header class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-slate-800 pb-6 mb-8 gap-4">
        <div>
            <div class="flex items-center gap-3">
                <span class="px-2.5 py-1 text-xs font-semibold tracking-wider text-cyan-400 bg-cyan-950 border border-cyan-800 rounded-full uppercase">
                    Network
                </span>
                <span class="text-xs text-slate-400">ONU / ONT Control Center</span>
            </div>
            <h1 class="text-3xl font-extrabold text-white mt-1 tracking-tight">
                ONT <span class="bg-gradient-to-r from-cyan-400 to-indigo-500 bg-clip-text text-transparent">Auto-Provisioner</span>
            </h1>
            <p class="text-slate-400 text-sm mt-1">Discover, register, and monitor optical line signal quality for customer modems.</p>
        </div>
        
        <!-- OLT Selector -->
        <div class="flex items-center gap-3 bg-slate-900 border border-slate-800 rounded-2xl px-4 py-2">
            <span class="text-xs text-slate-450 uppercase font-bold tracking-wider">Active OLT:</span>
            <select wire:model.live="selectedOltId" class="bg-slate-950 border border-slate-850 focus:border-cyan-500 rounded-xl px-3 py-1.5 text-sm outline-none transition text-white">
                <option value="">-- Select OLT Device --</option>
                @foreach($olts as $o)
                    <option value="{{ $o->id }}">{{ $o->name }} ({{ $o->ip_address }})</option>
                @endforeach
            </select>
        </div>
    </header>

    @if (session()->has('message'))
        <div class="mb-6 p-4 rounded-2xl bg-emerald-950 border border-emerald-800 text-emerald-300 text-sm flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            {{ session('message') }}
        </div>
    @endif

    <!-- Terminal logs / Provision logs output -->
    @if(!empty($cliLogs))
        <div class="mb-8 bg-black border border-slate-800 rounded-3xl p-6 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3 mb-4">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-rose-500"></span>
                    <span class="w-3 h-3 rounded-full bg-yellow-500"></span>
                    <span class="w-3 h-3 rounded-full bg-green-500"></span>
                    <span class="text-xs text-slate-450 font-mono ml-2 uppercase">OLT Command Execution Terminal</span>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="text-xs text-slate-450 hover:text-white transition">Close</button>
            </div>
            <pre class="text-xs text-green-450 font-mono leading-relaxed overflow-x-auto max-h-60 whitespace-pre-wrap">{{ $cliLogs }}</pre>
        </div>
    @endif

    <!-- Tab Navigation -->
    <div class="flex border-b border-slate-800 mb-6 gap-6">
        <button wire:click="$set('activeTab', 'discovery')" class="pb-3 text-sm font-bold tracking-wide relative {{ $activeTab === 'discovery' ? 'text-cyan-400 border-b-2 border-cyan-500' : 'text-slate-400 hover:text-slate-200' }}">
            Discovered ONUs (Autofind)
            @if(count($discoveredOnus) > 0)
                <span class="ml-2 bg-rose-600 text-white rounded-full px-2 py-0.5 text-[10px] font-extrabold">{{ count($discoveredOnus) }}</span>
            @endif
        </button>
        <button wire:click="$set('activeTab', 'active_onus')" class="pb-3 text-sm font-bold tracking-wide relative {{ $activeTab === 'active_onus' ? 'text-cyan-400 border-b-2 border-cyan-500' : 'text-slate-400 hover:text-slate-200' }}">
            Configured ONUs ({{ count($activeOnus) }})
        </button>
    </div>

    <!-- Tab Contents -->
    @if($activeTab === 'discovery')
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-800">
                            <th class="py-3 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">ONT Serial Number</th>
                            <th class="py-3 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">OLT Port</th>
                            <th class="py-3 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Vendor/Model</th>
                            <th class="py-3 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Distance</th>
                            <th class="py-3 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse($discoveredOnus as $discovered)
                            <tr class="hover:bg-slate-850/40 transition">
                                <td class="py-4 px-4 align-middle font-mono font-bold text-white">{{ $discovered['serial_number'] }}</td>
                                <td class="py-4 px-4 align-middle text-sm text-slate-300 font-mono">{{ $discovered['gpon_port'] }}</td>
                                <td class="py-4 px-4 align-middle text-sm text-slate-350">{{ $discovered['vendor'] }} ({{ $discovered['model'] }})</td>
                                <td class="py-4 px-4 align-middle text-sm text-slate-400">{{ $discovered['distance_meters'] }} m</td>
                                <td class="py-4 px-4 align-middle text-right">
                                    <button wire:click="startProvision('{{ $discovered['serial_number'] }}', '{{ $discovered['gpon_port'] }}')" class="bg-cyan-500 hover:bg-cyan-600 text-slate-950 font-bold px-3 py-1.5 rounded-xl text-xs transition">
                                        Authorize / Add
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-500 text-sm">
                                    No new unconfigured ONUs detected. Try changing OLT or refresh page.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <!-- Configured Active ONUs -->
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-800">
                            <th class="py-3 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Customer / Subscription</th>
                            <th class="py-3 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Port & ID</th>
                            <th class="py-3 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Serial Number</th>
                            <th class="py-3 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Signal Strength (RX)</th>
                            <th class="py-3 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Status</th>
                            <th class="py-3 px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse($activeOnus as $active)
                            <tr class="hover:bg-slate-850/40 transition">
                                <td class="py-4 px-4 align-middle">
                                    <p class="text-sm font-bold text-white">{{ $active->subscription->customer->full_name ?? 'N/A' }}</p>
                                    <p class="text-xs text-slate-450 font-mono">{{ $active->subscription->subscription_code ?? 'Static ONU' }}</p>
                                </td>
                                <td class="py-4 px-4 align-middle font-mono text-sm text-slate-300">
                                    {{ $active->gpon_port }} : {{ $active->onu_id ?? '?' }}
                                </td>
                                <td class="py-4 px-4 align-middle font-mono text-xs text-slate-400">{{ $active->serial_number }}</td>
                                <td class="py-4 px-4 align-middle">
                                    @if(isset($opticalReadings[$active->id]))
                                        <div class="flex flex-col">
                                            <span class="text-xs font-mono font-bold {{ $opticalReadings[$active->id]['status'] === 'Good' ? 'text-emerald-450' : ($opticalReadings[$active->id]['status'] === 'Warning' ? 'text-amber-500' : 'text-rose-500') }}">
                                                {{ $opticalReadings[$active->id]['onu_rx_power_dbm'] }}
                                            </span>
                                            <span class="text-[9px] text-slate-500">TX: {{ $opticalReadings[$active->id]['onu_tx_power_dbm'] }}</span>
                                        </div>
                                    @else
                                        <button wire:click="readSignal({{ $active->id }})" class="text-xs text-cyan-400 hover:underline">
                                            Fetch Optical Signal
                                        </button>
                                    @endif
                                </td>
                                <td class="py-4 px-4 align-middle">
                                    <span class="px-2 py-0.5 text-[10px] font-extrabold uppercase rounded-full {{ $active->status === 'active' ? 'bg-emerald-950 border border-emerald-800 text-emerald-400' : ($active->status === 'suspended' ? 'bg-rose-950 border border-rose-800 text-rose-450' : 'bg-slate-950 border border-slate-800 text-slate-400') }}">
                                        {{ $active->status }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 align-middle text-right">
                                    <div class="flex justify-end gap-2">
                                        @if($active->status === 'active')
                                            <button wire:click="suspendOnu({{ $active->id }})" class="bg-rose-950 hover:bg-rose-900 border border-rose-800 text-rose-350 text-xs px-2.5 py-1 rounded-xl transition">
                                                Block
                                            </button>
                                        @elseif($active->status === 'suspended')
                                            <button wire:click="reactivateOnu({{ $active->id }})" class="bg-emerald-950 hover:bg-emerald-900 border border-emerald-800 text-emerald-450 text-xs px-2.5 py-1 rounded-xl transition">
                                                Restore
                                            </button>
                                        @endif
                                        <button onclick="confirm('deprovision ONU config from OLT?') || event.stopImmediatePropagation()" wire:click="deleteOnu({{ $active->id }})" class="bg-slate-950 hover:bg-slate-850 border border-slate-800 hover:border-rose-900 text-slate-400 hover:text-rose-400 text-xs px-2.5 py-1 rounded-xl transition">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-500 text-sm">
                                    No active ONUs authorized on this OLT.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Provisioning Dialog Modal -->
    @if($showProvisionModal)
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-lg shadow-2xl relative">
                <button wire:click="$set('showProvisionModal', false)" class="absolute top-4 right-4 text-slate-400 hover:text-white transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>

                <h2 class="text-xl font-bold text-white mb-6">ONT Provisioning Profile Configuration</h2>

                <form wire:submit.prevent="provision" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-450 mb-1 uppercase">Serial Number</label>
                            <input type="text" readonly wire:model="selectedSerialNumber" class="w-full bg-slate-950/50 border border-slate-850 rounded-xl px-4 py-2.5 text-sm text-slate-500 font-mono outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-450 mb-1 uppercase">GPON Interface</label>
                            <input type="text" readonly wire:model="selectedGponPort" class="w-full bg-slate-950/50 border border-slate-850 rounded-xl px-4 py-2.5 text-sm text-slate-500 font-mono outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-450 mb-1 uppercase">Assign Customer Subscription</label>
                        <select wire:model="subscriptionId" class="w-full bg-slate-950 border border-slate-850 focus:border-cyan-500 rounded-xl px-4 py-2.5 text-sm outline-none text-white">
                            <option value="">-- Select Subscription --</option>
                            @foreach($subscriptions as $sub)
                                <option value="{{ $sub->id }}">
                                    {{ $sub->customer->full_name }} ({{ $sub->subscription_code }} - {{ $sub->servicePackage->name ?? 'Broadband' }})
                                </option>
                            @endforeach
                        </select>
                        @error('subscriptionId') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-450 mb-1 uppercase">ONT Line Profile</label>
                            <input type="text" wire:model="lineProfile" class="w-full bg-slate-950 border border-slate-850 focus:border-cyan-500 rounded-xl px-4 py-2.5 text-sm text-white font-mono">
                            @error('lineProfile') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-450 mb-1 uppercase">ONT Service Profile</label>
                            <input type="text" wire:model="serviceProfile" class="w-full bg-slate-950 border border-slate-850 focus:border-cyan-500 rounded-xl px-4 py-2.5 text-sm text-white font-mono">
                            @error('serviceProfile') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-800 flex justify-end gap-3">
                        <button type="button" wire:click="$set('showProvisionModal', false)" class="px-4 py-2 text-sm text-slate-400 hover:text-white font-medium transition">
                            Cancel
                        </button>
                        <button type="submit" class="bg-cyan-500 hover:bg-cyan-600 text-slate-950 font-bold px-4 py-2 rounded-xl text-sm transition">
                            Authorize ONT
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
