<div class="p-6 lg:p-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-slate-800 pb-6 mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-white tracking-tight">
                Customers <span class="bg-gradient-to-r from-cyan-400 to-indigo-500 bg-clip-text text-transparent">Directory</span>
            </h1>
            <p class="text-slate-400 text-sm mt-1">Manage active ISP subscribers, profile records, and subscription states.</p>
        </div>
        <div>
            <button wire:click="openCreateModal" class="px-5 py-3 bg-gradient-to-r from-cyan-500 to-indigo-500 hover:from-cyan-600 hover:to-indigo-650 text-white text-sm font-bold rounded-xl shadow-lg transition duration-200 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Register Customer
            </button>
        </div>
    </div>

    <!-- Alert Message -->
    @if (session()->has('message'))
        <div class="mb-6 p-4 bg-emerald-950/60 border border-emerald-800 text-emerald-400 rounded-2xl flex items-center gap-3">
            <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span class="text-sm font-semibold">{{ session('message') }}</span>
        </div>
    @endif

    <!-- Search & Filter Controls -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 mb-8 shadow-2xl">
        <div class="relative max-w-md">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </span>
            <input wire:model.live="search" type="text" placeholder="Search by name, email, code or phone..." class="w-full bg-slate-950 text-slate-100 placeholder-slate-500 pl-10 pr-4 py-3 rounded-xl border border-slate-800 focus:outline-none focus:border-cyan-500 transition duration-200 text-sm">
        </div>
    </div>

    <!-- Customers Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-400 text-xs font-bold uppercase tracking-wider bg-slate-900/50">
                        <th class="py-4 px-6">Customer Code</th>
                        <th class="py-4 px-6">Full Name</th>
                        <th class="py-4 px-6">Contact Info</th>
                        <th class="py-4 px-6">Billing Type</th>
                        <th class="py-4 px-6">Status</th>
                        <th class="py-4 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-sm">
                    @forelse($customers as $cust)
                        <tr class="hover:bg-slate-850/40 transition duration-200">
                            <td class="py-4 px-6 font-mono font-bold text-cyan-400">
                                {{ $cust->customer_code }}
                            </td>
                            <td class="py-4 px-6 font-semibold text-white">
                                {{ $cust->full_name }}
                                @if($cust->company_name)
                                    <span class="block text-xs font-normal text-slate-400 mt-0.5">{{ $cust->company_name }}</span>
                                @endif
                            </td>
                            <td class="py-4 px-6">
                                <p class="text-slate-200">{{ $cust->phone }}</p>
                                <p class="text-xs text-slate-400 mt-0.5">{{ $cust->email }}</p>
                            </td>
                            <td class="py-4 px-6 capitalize">
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-slate-800 text-slate-300 border border-slate-700">
                                    {{ $cust->type->value }}
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                @php
                                    $statusColor = match($cust->status->value) {
                                        'active' => 'text-emerald-400 bg-emerald-950/50 border-emerald-900',
                                        'suspended' => 'text-rose-400 bg-rose-950/50 border-rose-900',
                                        'disconnected' => 'text-amber-400 bg-amber-950/50 border-amber-900',
                                        'terminated' => 'text-slate-400 bg-slate-800 border-slate-700',
                                        default => 'text-slate-300 bg-slate-800 border-slate-700'
                                    };
                                @endphp
                                <span class="px-2.5 py-1 text-xs font-bold tracking-wide rounded-full border {{ $statusColor }}">
                                    {{ $cust->status->value }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-right space-x-2">
                                <a href="/customers/{{ $cust->id }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-slate-800 border border-slate-700 hover:border-slate-650 hover:bg-slate-750 text-slate-200 rounded-lg text-xs font-bold transition duration-200">
                                    Manage
                                </a>
                                <button wire:click="editCustomer({{ $cust->id }})" class="px-3.5 py-1.5 bg-cyan-950 border border-cyan-800 text-cyan-400 hover:bg-cyan-900 rounded-lg text-xs font-bold transition duration-200">
                                    Edit
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-500">
                                <svg class="w-12 h-12 mx-auto text-slate-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                <p class="text-sm font-semibold">No customers found.</p>
                                <p class="text-xs text-slate-650 mt-1">Try refining your search keyword or register a new customer above.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        @if($customers->hasPages())
            <div class="px-6 py-4 border-t border-slate-800 bg-slate-900/30">
                {{ $customers->links() }}
            </div>
        @endif
    </div>

    <!-- Create Customer Modal -->
    @if($isCreateModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl w-full max-w-lg shadow-2xl overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-800 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-white">Register New Customer</h3>
                    <button wire:click="closeCreateModal" class="p-1 text-slate-400 hover:text-white rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <form wire:submit.prevent="createCustomer" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Customer Code</label>
                            <input wire:model="customer_code" type="text" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500">
                            @error('customer_code') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Billing Type</label>
                            <select wire:model="type" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500">
                                <option value="residential">Residential</option>
                                <option value="business">Business</option>
                                <option value="enterprise">Enterprise</option>
                            </select>
                            @error('type') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Full Name</label>
                        <input wire:model="full_name" type="text" placeholder="John Doe" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500">
                        @error('full_name') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Company Name (Optional)</label>
                        <input wire:model="company_name" type="text" placeholder="Doe Tech Ltd" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500">
                        @error('company_name') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Phone Number</label>
                            <input wire:model="phone" type="text" placeholder="+88017..." class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500">
                            @error('phone') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Email Address</label>
                            <input wire:model="email" type="email" placeholder="john@example.com" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500">
                            @error('email') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Installation / Billing Address</label>
                        <textarea wire:model="address" placeholder="Road 4, House 12, Sector 3, Uttara, Dhaka" rows="3" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500"></textarea>
                        @error('address') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">National ID / NID</label>
                            <input wire:model="national_id" type="text" placeholder="1992..." class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500">
                            @error('national_id') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Initial Status</label>
                            <select wire:model="status" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500">
                                <option value="active">Active</option>
                                <option value="suspended">Suspended</option>
                                <option value="lead">Lead</option>
                                <option value="prospect">Prospect</option>
                            </select>
                            @error('status') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-800 flex justify-end gap-3">
                        <button type="button" wire:click="closeCreateModal" class="px-5 py-2.5 bg-slate-850 hover:bg-slate-800 border border-slate-800 hover:border-slate-700 rounded-xl text-sm font-semibold text-slate-350 transition duration-150">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-cyan-500 to-indigo-500 hover:from-cyan-600 hover:to-indigo-650 text-white rounded-xl text-sm font-semibold transition duration-155">
                            Save Customer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Edit Customer Modal -->
    @if($isEditModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl w-full max-w-lg shadow-2xl overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-800 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-white">Edit Customer Profile</h3>
                    <button wire:click="resetForm; $set('isEditModalOpen', false)" class="p-1 text-slate-400 hover:text-white rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <form wire:submit.prevent="updateCustomer" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Customer Code</label>
                            <input wire:model="customer_code" type="text" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500">
                            @error('customer_code') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Billing Type</label>
                            <select wire:model="type" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500">
                                <option value="residential">Residential</option>
                                <option value="business">Business</option>
                                <option value="enterprise">Enterprise</option>
                            </select>
                            @error('type') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Full Name</label>
                        <input wire:model="full_name" type="text" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500">
                        @error('full_name') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Company Name (Optional)</label>
                        <input wire:model="company_name" type="text" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500">
                        @error('company_name') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Phone Number</label>
                            <input wire:model="phone" type="text" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500">
                            @error('phone') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Email Address</label>
                            <input wire:model="email" type="email" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500">
                            @error('email') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Primary Installation Address</label>
                        <textarea wire:model="address" rows="3" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500"></textarea>
                        @error('address') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">National ID</label>
                            <input wire:model="national_id" type="text" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500">
                            @error('national_id') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-450 uppercase mb-1.5">Customer Status</label>
                            <select wire:model="status" class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500">
                                <option value="active">Active</option>
                                <option value="suspended">Suspended</option>
                                <option value="disconnected">Disconnected</option>
                                <option value="terminated">Terminated</option>
                            </select>
                            @error('status') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-800 flex justify-end gap-3">
                        <button type="button" wire:click="resetForm; $set('isEditModalOpen', false)" class="px-5 py-2.5 bg-slate-850 hover:bg-slate-800 border border-slate-800 hover:border-slate-700 rounded-xl text-sm font-semibold text-slate-350 transition duration-150">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-cyan-500 to-indigo-500 hover:from-cyan-600 hover:to-indigo-650 text-white rounded-xl text-sm font-semibold transition duration-155">
                            Update Customer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
