<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-white tracking-tight">Notification Templates</h2>
            <p class="text-sm text-slate-400 mt-1">Configure automated messages sent to customers</p>
        </div>
        <button wire:click="openModal" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded-xl transition duration-200 shadow-lg shadow-indigo-500/20">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Add Template
        </button>
    </div>

    @if (session()->has('message'))
        <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-xl flex items-center gap-3">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            {{ session('message') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($templates as $template)
            <div class="bg-slate-900/50 backdrop-blur-xl border {{ $template->is_active ? 'border-slate-800' : 'border-slate-800/50 opacity-60' }} rounded-2xl p-5 shadow-xl transition-all hover:border-slate-700 relative group">
                
                <div class="absolute top-4 right-4 flex items-center gap-2">
                    <button wire:click="openModal({{ $template->id }})" class="text-slate-400 hover:text-indigo-400 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    </button>
                </div>

                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-center">
                        @if($template->channel === 'email')
                            <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        @elseif($template->channel === 'sms')
                            <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                        @else
                            <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"></path></svg>
                        @endif
                    </div>
                    <div>
                        <h3 class="text-white font-bold">{{ ucwords(str_replace('_', ' ', $template->event_name)) }}</h3>
                        <p class="text-xs text-slate-400 uppercase tracking-wider">{{ $template->channel }}</p>
                    </div>
                </div>

                @if($template->channel === 'email' && $template->subject)
                    <div class="mb-2 text-sm text-slate-300 font-medium">
                        Sub: {{ $template->subject }}
                    </div>
                @endif
                
                <div class="text-xs text-slate-500 bg-slate-800/50 p-3 rounded-lg border border-slate-800 line-clamp-3">
                    {{ $template->body }}
                </div>
                
                <div class="mt-4 pt-4 border-t border-slate-800 flex justify-between items-center text-xs">
                    <span class="{{ $template->is_active ? 'text-emerald-400' : 'text-slate-500' }} font-semibold flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full {{ $template->is_active ? 'bg-emerald-400' : 'bg-slate-500' }}"></span>
                        {{ $template->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center text-slate-500 border border-dashed border-slate-800 rounded-2xl">
                <svg class="w-12 h-12 mx-auto text-slate-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                <p class="text-lg font-medium text-slate-400">No Templates Found</p>
                <p class="text-sm mt-1">Create templates to automate messaging.</p>
            </div>
        @endforelse
    </div>

    <!-- Modal -->
    @if($isModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-0">
        <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity"></div>
        <div class="relative bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl w-full max-w-2xl p-6 overflow-hidden">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-white">{{ $editingTemplateId ? 'Edit Template' : 'New Template' }}</h3>
                <button wire:click="closeModal" class="text-slate-400 hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form wire:submit="saveTemplate" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Event Trigger *</label>
                        <select wire:model="editEventName" class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500" required>
                            <option value="">Select Event...</option>
                            <option value="invoice_created">Invoice Created</option>
                            <option value="payment_received">Payment Received</option>
                            <option value="ticket_opened">Ticket Opened</option>
                            <option value="welcome_message">Welcome Message</option>
                            <option value="service_suspended">Service Suspended</option>
                        </select>
                        @error('editEventName') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Channel *</label>
                        <select wire:model="editChannel" class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500" required>
                            <option value="sms">SMS</option>
                            <option value="email">Email</option>
                            <option value="whatsapp">WhatsApp</option>
                        </select>
                        @error('editChannel') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                @if($editChannel === 'email')
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1">Email Subject *</label>
                    <input type="text" wire:model="editSubject" class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500">
                </div>
                @endif

                <div>
                    <div class="flex justify-between items-end mb-1">
                        <label class="block text-sm font-medium text-slate-300">Message Body *</label>
                        <span class="text-xs text-indigo-400">Available vars: {customer_name}, {amount}, {due_date}</span>
                    </div>
                    <textarea wire:model="editBody" rows="5" class="w-full bg-slate-800 border border-slate-700 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500" placeholder="Hello {customer_name}, your invoice for {amount} is due on {due_date}." required></textarea>
                    @error('editBody') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="flex items-center gap-2 mt-4">
                    <input type="checkbox" wire:model="editIsActive" id="editIsActive" class="w-5 h-5 rounded bg-slate-800 border-slate-700 text-indigo-500 focus:ring-indigo-500">
                    <label for="editIsActive" class="text-sm font-medium text-slate-300 cursor-pointer">Template is Active</label>
                </div>

                <div class="pt-4 flex justify-end gap-3">
                    <button type="button" wire:click="closeModal" class="px-4 py-2 text-sm font-medium text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 rounded-lg transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-500 rounded-lg transition-colors shadow-lg shadow-indigo-500/20">
                        Save Template
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
