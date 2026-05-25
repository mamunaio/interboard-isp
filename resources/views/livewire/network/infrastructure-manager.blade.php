<div class="space-y-8 min-h-screen bg-slate-950 text-slate-100 font-sans p-6 lg:p-8" x-data="{ 
    showAddPopModal: false, 
    newPopName: '',
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
    <header class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-slate-800 pb-6">
        <div>
            <div class="flex items-center gap-3">
                <span class="px-2.5 py-1 text-xs font-semibold tracking-wider text-cyan-400 bg-cyan-950/80 border border-cyan-800 rounded-full uppercase">
                    Network Module
                </span>
            </div>
            <h1 class="text-3xl font-extrabold text-white mt-2 tracking-tight">
                Physical <span class="bg-gradient-to-r from-cyan-400 to-indigo-500 bg-clip-text text-transparent">Infrastructure</span>
            </h1>
            <p class="text-slate-400 text-sm mt-1">Manage Points of Presence (POPs) and Distribution Boxes / Splitters.</p>
        </div>
    </header>

    <!-- Section 1: POPs Grid -->
    <section class="space-y-4">
        <div class="flex justify-between items-center">
            <h2 class="text-lg font-bold text-white flex items-center gap-2">
                <span class="w-2.5 h-6 bg-cyan-500 rounded-full"></span>
                Points of Presence (POPs)
            </h2>
            <button @click="showAddPopModal = true" class="flex items-center gap-1.5 px-4 py-2 text-xs font-bold bg-slate-900 border border-slate-800 hover:border-cyan-500 text-cyan-400 hover:text-white rounded-xl shadow-lg hover:shadow-cyan-500/10 transition-all duration-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>Add POP</span>
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @forelse($pops as $pop)
                <div wire:click="selectPop({{ $pop->id }})" 
                     class="relative cursor-pointer group border rounded-3xl p-5 shadow-xl transition-all duration-300 transform hover:-translate-y-1 hover:shadow-2xl"
                     :class="{
                        'bg-gradient-to-br from-slate-900 to-slate-950 border-cyan-500 shadow-cyan-500/5': {{ $selectedPopId === $pop->id ? 'true' : 'false' }},
                        'bg-slate-900/50 border-slate-800 hover:border-slate-700': {{ $selectedPopId === $pop->id ? 'false' : 'true' }}
                     }">
                    
                    <!-- Selected glow effect -->
                    @if($selectedPopId === $pop->id)
                        <div class="absolute -top-1.5 -right-1.5 w-3 h-3 bg-cyan-500 rounded-full animate-ping"></div>
                        <div class="absolute -top-1.5 -right-1.5 w-3 h-3 bg-cyan-500 rounded-full"></div>
                    @endif

                    <div class="flex flex-col justify-between h-full space-y-4">
                        <div>
                            <span class="text-xs uppercase tracking-widest font-semibold text-slate-500 group-hover:text-cyan-400/80 transition-colors">Zone / POP</span>
                            <h3 class="text-lg font-bold text-white mt-1 group-hover:text-cyan-300 transition-colors">{{ $pop->name }}</h3>
                        </div>
                        <div class="flex justify-between items-end border-t border-slate-800/80 pt-4">
                            <span class="text-xs text-slate-400 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                {{ $pop->location ?: 'No location' }}
                            </span>
                            <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-slate-400 bg-slate-950 border border-slate-800 rounded-md">
                                {{ $pop->distribution_boxes_count }} Boxes
                            </span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="sm:col-span-2 md:col-span-3 lg:col-span-4 bg-slate-900/30 border border-dashed border-slate-800 rounded-3xl p-12 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-900 border border-slate-800 mb-4 text-slate-600">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    </div>
                    <h3 class="text-sm font-semibold text-slate-300">No POPs Configured</h3>
                    <p class="text-xs text-slate-500 mt-1">Please add a POP first to map distribution boxes under it.</p>
                </div>
            @endforelse
        </div>
    </section>

    @if($selectedPop)
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Section 2: Form to Add New Box (Left Column) -->
            <div class="lg:col-span-1 bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl space-y-6">
                <div>
                    <h2 class="text-lg font-bold text-white flex items-center gap-2">
                        <span class="w-2.5 h-6 bg-cyan-500 rounded-full"></span>
                        Add Box inside {{ $selectedPop->name }}
                    </h2>
                    <p class="text-slate-400 text-xs mt-1">Create a new Splitter or Switch inside this POP zone.</p>
                </div>

                <form wire:submit="addBox" class="space-y-5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Box Name / Code <span class="text-rose-500">*</span></label>
                        <input wire:model="boxName" type="text" placeholder="e.g. Box-1, Splitter-F4" class="w-full bg-slate-950 border border-slate-800 focus:border-cyan-500 rounded-xl px-4 py-3 text-sm text-slate-100 placeholder-slate-600 outline-none transition duration-200">
                        @error('boxName') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Location / Specific Details</label>
                        <input wire:model="boxLocation" type="text" placeholder="e.g. Electric Pole #12, Roof-top" class="w-full bg-slate-950 border border-slate-800 focus:border-cyan-500 rounded-xl px-4 py-3 text-sm text-slate-100 placeholder-slate-600 outline-none transition duration-200">
                        @error('boxLocation') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Port Capacity <span class="text-rose-500">*</span></label>
                        <select wire:model="boxPortCapacity" class="w-full bg-slate-950 border border-slate-800 focus:border-cyan-500 rounded-xl px-4 py-3 text-sm text-slate-100 outline-none transition duration-200">
                            <option value="4">4 Ports</option>
                            <option value="8">8 Ports</option>
                            <option value="16">16 Ports</option>
                            <option value="32">32 Ports</option>
                            <option value="64">64 Ports</option>
                        </select>
                        @error('boxPortCapacity') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <button type="submit" class="w-full flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-cyan-500 to-indigo-600 hover:from-cyan-400 hover:to-indigo-500 text-white text-sm font-bold rounded-xl shadow-lg hover:shadow-cyan-500/25 transition-all duration-200" wire:loading.attr="disabled">
                        <svg wire:loading.remove wire:target="addBox" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        <svg wire:loading wire:target="addBox" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span>Add Box</span>
                    </button>
                </form>
            </div>

            <!-- Section 3: Table of Boxes (Right 2 Columns) -->
            <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl flex flex-col">
                <div class="p-6 border-b border-slate-800">
                    <h2 class="text-lg font-bold text-white flex items-center gap-2">
                        <span class="w-2.5 h-6 bg-indigo-500 rounded-full"></span>
                        Boxes mapped under {{ $selectedPop->name }}
                    </h2>
                </div>

                <div class="flex-grow overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-300">
                        <thead class="text-xs uppercase bg-slate-950/50 text-slate-400 font-semibold tracking-wider">
                            <tr>
                                <th class="px-6 py-4">Box / Splitter</th>
                                <th class="px-6 py-4">Location Details</th>
                                <th class="px-6 py-4 text-center">Port Capacity</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/80">
                            @forelse($selectedPop->distributionBoxes as $box)
                                <tr class="hover:bg-slate-800/40 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="p-2 bg-indigo-950/50 border border-indigo-800/60 rounded-xl text-indigo-400">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                            </div>
                                            <span class="font-bold text-slate-200">{{ $box->name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-slate-400 text-xs">{{ $box->location_details ?: 'Not specified' }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-cyan-950 text-cyan-400 border border-cyan-800/50">
                                            {{ $box->port_capacity }} Ports
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <button wire:click="deleteBox({{ $box->id }})" 
                                                wire:confirm="Are you sure you want to delete this distribution box?"
                                                class="p-2 rounded-xl bg-slate-950 border border-slate-800 hover:border-rose-500/50 hover:bg-rose-950/30 text-slate-400 hover:text-rose-400 transition-all duration-200">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center">
                                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-950 border border-slate-800 mb-3 text-slate-600">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2H5"></path></svg>
                                        </div>
                                        <h3 class="text-xs font-semibold text-slate-300">No Distribution Boxes</h3>
                                        <p class="text-[11px] text-slate-500 mt-1">There are no splitters/boxes in this POP. Create one using the form on the left.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- Simple POP Creation Modal / Prompt Panel -->
    <div x-show="showAddPopModal" 
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         style="display: none;">
        
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl max-w-md w-full mx-4 space-y-6"
             @click.away="showAddPopModal = false; newPopName = ''">
            <div>
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                    <span class="w-2.5 h-6 bg-cyan-500 rounded-full"></span>
                    Create Point of Presence (POP)
                </h3>
                <p class="text-slate-400 text-xs mt-1">Enter details for the new Point of Presence/Zone.</p>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">POP Name <span class="text-rose-500">*</span></label>
                    <input x-model="newPopName" type="text" placeholder="e.g. Uttara Sector 3, Dhanmondi Hub" class="w-full bg-slate-950 border border-slate-800 focus:border-cyan-500 rounded-xl px-4 py-3 text-sm text-slate-100 placeholder-slate-600 outline-none transition duration-200">
                    @error('new_pop_name') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-800">
                <button @click="showAddPopModal = false; newPopName = ''" class="px-4 py-2.5 text-xs font-semibold bg-slate-950 border border-slate-800 hover:border-slate-700 text-slate-400 hover:text-slate-200 rounded-xl transition duration-200">
                    Cancel
                </button>
                <button @click="if(newPopName.trim()) { $wire.addPop(newPopName); showAddPopModal = false; newPopName = ''; }" class="px-5 py-2.5 text-xs font-bold bg-cyan-500 hover:bg-cyan-400 text-white rounded-xl shadow-lg hover:shadow-cyan-500/25 transition duration-200">
                    Create POP
                </button>
            </div>
        </div>
    </div>
</div>
