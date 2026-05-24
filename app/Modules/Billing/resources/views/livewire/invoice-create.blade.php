<div class="p-6 lg:p-8">
    <!-- Header -->
    <div class="flex items-center justify-between border-b border-slate-800 pb-6 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-white tracking-tight">
                Create <span class="bg-gradient-to-r from-emerald-400 to-cyan-500 bg-clip-text text-transparent">Manual Invoice</span>
            </h1>
            <p class="text-slate-400 text-sm mt-1">Generate custom billing documents for non-standard or ad-hoc charges.</p>
        </div>
        <a href="{{ route('tenant.invoices.index') }}" class="inline-flex items-center justify-center px-4 py-2 bg-slate-800 hover:bg-slate-700 border border-slate-700 hover:border-slate-600 text-slate-300 text-sm font-bold rounded-xl transition duration-200">
            <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Back to Invoices
        </a>
    </div>

    @if (session()->has('error'))
        <div class="mb-6 p-4 bg-rose-950/50 border border-rose-900 rounded-xl text-rose-400 text-sm flex items-center">
            <svg class="w-5 h-5 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            {{ session('error') }}
        </div>
    @endif

    <form wire:submit.prevent="save">
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
            <!-- Left Column: Main Details & Line Items -->
            <div class="xl:col-span-2 space-y-8">
                <!-- Customer Details -->
                <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl">
                    <h2 class="text-lg font-bold text-white mb-6 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        Invoice Details
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-slate-400 mb-2">Customer <span class="text-rose-500">*</span></label>
                            <select wire:model="customerId" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-200 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition duration-200">
                                <option value="">Select a Customer</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->full_name }} ({{ $customer->phone }})</option>
                                @endforeach
                            </select>
                            @error('customerId') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-400 mb-2">Issue Date <span class="text-rose-500">*</span></label>
                            <input type="date" wire:model="issueDate" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-200 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition duration-200">
                            @error('issueDate') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-400 mb-2">Due Date <span class="text-rose-500">*</span></label>
                            <input type="date" wire:model="dueDate" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-200 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition duration-200">
                            @error('dueDate') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <!-- Line Items -->
                <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-bold text-white flex items-center">
                            <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                            Line Items
                        </h2>
                        <button type="button" wire:click="addLineItem" class="text-xs font-bold px-3 py-1.5 bg-cyan-950/50 text-cyan-400 border border-cyan-900/50 hover:bg-cyan-900/50 hover:text-cyan-300 rounded-lg transition duration-200">
                            + Add Item
                        </button>
                    </div>

                    <div class="space-y-4">
                        @foreach($lineItems as $index => $item)
                            <div class="flex flex-col md:flex-row gap-4 items-start p-4 bg-slate-950/50 border border-slate-850 rounded-2xl relative group">
                                <div class="flex-grow w-full md:w-auto">
                                    <label class="block text-xs font-semibold text-slate-500 mb-1">Description</label>
                                    <input type="text" wire:model="lineItems.{{ $index }}.description" placeholder="Item description" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-cyan-500 transition duration-200">
                                    @error('lineItems.'.$index.'.description') <span class="text-rose-500 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div class="w-full md:w-32">
                                    <label class="block text-xs font-semibold text-slate-500 mb-1">Quantity</label>
                                    <input type="number" step="0.01" wire:model.live="lineItems.{{ $index }}.quantity" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-cyan-500 transition duration-200 text-center">
                                    @error('lineItems.'.$index.'.quantity') <span class="text-rose-500 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div class="w-full md:w-32">
                                    <label class="block text-xs font-semibold text-slate-500 mb-1">Unit Price</label>
                                    <input type="number" step="0.01" wire:model.live="lineItems.{{ $index }}.unit_price" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-cyan-500 transition duration-200 text-right">
                                    @error('lineItems.'.$index.'.unit_price') <span class="text-rose-500 text-[10px] mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div class="w-full md:w-32 text-right">
                                    <label class="block text-xs font-semibold text-slate-500 mb-1">Total</label>
                                    <div class="h-[38px] flex items-center justify-end text-sm font-bold text-emerald-400">
                                        {{ number_format(((float)($item['quantity'] ?? 0)) * ((float)($item['unit_price'] ?? 0)), 2) }} BDT
                                    </div>
                                </div>
                                
                                <button type="button" wire:click="removeLineItem({{ $index }})" class="absolute -top-3 -right-3 md:top-auto md:right-auto md:relative md:self-center md:mt-6 p-2 bg-slate-900 border border-slate-800 text-rose-500 hover:bg-rose-950/50 hover:text-rose-400 rounded-full md:rounded-xl shadow-lg transition duration-200" title="Remove Item">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        @endforeach
                        @error('lineItems') <span class="text-rose-500 text-xs mt-2 block font-semibold">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Notes -->
                <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl">
                    <label class="block text-sm font-semibold text-slate-400 mb-2 flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        Additional Notes (Optional)
                    </label>
                    <textarea wire:model="notes" rows="3" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-200 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition duration-200" placeholder="Notes visible on the invoice..."></textarea>
                    @error('notes') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Right Column: Summary & Actions -->
            <div class="space-y-6">
                <!-- Summary Card -->
                <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl sticky top-6">
                    <h2 class="text-lg font-bold text-white mb-6 border-b border-slate-800 pb-4">Invoice Summary</h2>
                    
                    <div class="space-y-4 mb-6">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-slate-400 font-semibold">Subtotal</span>
                            <span class="font-mono text-slate-200">{{ $this->subtotal }} BDT</span>
                        </div>
                        
                        <div class="flex justify-between items-center text-sm border-t border-slate-800/50 pt-4">
                            <span class="text-slate-400 font-semibold">Tax Total</span>
                            <div class="flex items-center">
                                <span class="text-slate-500 mr-2 text-xs">+</span>
                                <input type="number" step="0.01" wire:model.live="taxTotal" class="w-24 bg-slate-950 border border-slate-800 rounded-lg px-2 py-1 text-right text-sm text-slate-200 focus:outline-none focus:border-cyan-500">
                            </div>
                        </div>
                        @error('taxTotal') <div class="text-right"><span class="text-rose-500 text-xs">{{ $message }}</span></div> @enderror
                        
                        <div class="flex justify-between items-center text-sm border-t border-slate-800/50 pt-4">
                            <span class="text-slate-400 font-semibold">Discount Total</span>
                            <div class="flex items-center">
                                <span class="text-slate-500 mr-2 text-xs">-</span>
                                <input type="number" step="0.01" wire:model.live="discountTotal" class="w-24 bg-slate-950 border border-slate-800 rounded-lg px-2 py-1 text-right text-sm text-slate-200 focus:outline-none focus:border-cyan-500">
                            </div>
                        </div>
                        @error('discountTotal') <div class="text-right"><span class="text-rose-500 text-xs">{{ $message }}</span></div> @enderror

                        <div class="flex justify-between items-center bg-slate-950 border border-slate-800 p-4 rounded-xl mt-4">
                            <span class="text-white font-bold">Grand Total</span>
                            <span class="font-mono text-lg font-black text-cyan-400">{{ $this->grandTotal }} BDT</span>
                        </div>
                        @error('general') <div class="p-3 bg-rose-950/40 border border-rose-900 rounded-xl mt-2"><span class="text-rose-500 text-xs block font-bold text-center">{{ $message }}</span></div> @enderror
                    </div>

                    <button type="submit" class="w-full flex items-center justify-center px-6 py-4 bg-gradient-to-r from-cyan-600 to-indigo-600 hover:from-cyan-500 hover:to-indigo-500 text-white font-bold rounded-xl shadow-lg shadow-cyan-900/30 transition-all duration-200 focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2 focus:ring-offset-slate-900">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                        Generate Invoice
                    </button>
                    <p class="text-center text-[10px] text-slate-500 mt-4 px-4 leading-relaxed">
                        By generating, a draft invoice will be created securely using high-precision calculation mechanisms.
                    </p>
                </div>
            </div>
        </div>
    </form>
</div>
