<div class="min-h-screen bg-slate-950 text-slate-100 font-sans antialiased p-6 lg:p-8">
    <!-- Header -->
    <header class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-slate-800 pb-6 mb-8 gap-4">
        <div>
            <div class="flex items-center gap-3">
                <span class="px-2.5 py-1 text-xs font-semibold tracking-wider text-indigo-400 bg-indigo-950 border border-indigo-800 rounded-full uppercase">
                    Settings
                </span>
            </div>
            <h1 class="text-3xl font-extrabold text-white mt-1 tracking-tight">
                Profile <span class="bg-gradient-to-r from-cyan-400 to-indigo-500 bg-clip-text text-transparent">Management</span>
            </h1>
            <p class="text-slate-400 text-sm mt-1">Manage your account settings and set e-mail preferences.</p>
        </div>
    </header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Sidebar / Navigation (Optional) -->
        <div class="space-y-4">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl">
                <div class="flex flex-col items-center text-center">
                    <div class="relative w-32 h-32 mb-4">
                        @if($avatar)
                            <img src="{{ $avatar->temporaryUrl() }}" class="w-full h-full rounded-full object-cover border-4 border-slate-800 shadow-xl">
                        @elseif(Auth::user() && Auth::user()->avatar)
                            <img src="{{ asset('storage/' . Auth::user()->avatar) }}" class="w-full h-full rounded-full object-cover border-4 border-slate-800 shadow-xl">
                        @else
                            <div class="w-full h-full rounded-full bg-slate-800 flex items-center justify-center border-4 border-slate-800 shadow-xl">
                                <span class="text-4xl font-bold text-slate-500">{{ substr($name, 0, 1) }}</span>
                            </div>
                        @endif

                        <label for="avatar-upload" class="absolute bottom-0 right-0 p-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-full cursor-pointer shadow-lg transition transform hover:scale-110">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16V8a2 2 0 012-2h3l1-2h6l1 2h3a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        </label>
                        <input id="avatar-upload" type="file" wire:model="avatar" class="hidden" accept="image/*">
                    </div>
                    <h3 class="text-xl font-bold text-white">{{ $name }}</h3>
                    <p class="text-sm text-slate-400">{{ $email }}</p>
                    <span class="mt-3 px-3 py-1 bg-emerald-950/50 text-emerald-400 border border-emerald-900 rounded-full text-xs font-semibold">Active Session</span>
                </div>
                <div wire:loading wire:target="avatar" class="mt-4 text-center text-sm text-indigo-400 animate-pulse">
                    Uploading image...
                </div>
                @error('avatar') <span class="text-rose-500 text-xs mt-2 block text-center">{{ $message }}</span> @enderror
            </div>
        </div>

        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Profile Information -->
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 lg:p-8 shadow-2xl relative overflow-hidden group">
                <div class="absolute -right-4 -top-4 w-32 h-32 bg-cyan-500/5 rounded-full blur-2xl pointer-events-none"></div>
                
                <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                    <span class="w-2 h-5 bg-cyan-500 rounded-full"></span>
                    Personal Information
                </h3>

                @if(session()->has('profile_message'))
                    <div class="mb-6 p-4 bg-emerald-950 border border-emerald-900 text-emerald-400 rounded-2xl text-sm flex items-center gap-3">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        {{ session('profile_message') }}
                    </div>
                @endif

                <form wire:submit.prevent="updateProfile" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-slate-400">Full Name</label>
                            <input type="text" wire:model="name" class="w-full bg-slate-950 border border-slate-800 focus:border-cyan-500 focus:ring focus:ring-cyan-500/20 text-slate-200 rounded-xl px-4 py-3 outline-none transition duration-200">
                            @error('name') <span class="text-rose-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium text-slate-400">Email Address</label>
                            <input type="email" wire:model="email" class="w-full bg-slate-950 border border-slate-800 focus:border-cyan-500 focus:ring focus:ring-cyan-500/20 text-slate-200 rounded-xl px-4 py-3 outline-none transition duration-200">
                            @error('email') <span class="text-rose-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <label class="text-sm font-medium text-slate-400">Phone Number</label>
                            <input type="text" wire:model="phone" class="w-full bg-slate-950 border border-slate-800 focus:border-cyan-500 focus:ring focus:ring-cyan-500/20 text-slate-200 rounded-xl px-4 py-3 outline-none transition duration-200" placeholder="+880 1XXX-XXXXXX">
                            @error('phone') <span class="text-rose-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="pt-4 flex justify-end">
                        <button type="submit" class="px-6 py-3 bg-gradient-to-r from-cyan-600 to-indigo-600 hover:from-cyan-500 hover:to-indigo-500 text-white rounded-xl font-bold text-sm transition duration-200 shadow-lg shadow-indigo-900/50 flex items-center gap-2">
                            <span wire:loading.remove wire:target="updateProfile">Save Changes</span>
                            <span wire:loading wire:target="updateProfile">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Security & Password -->
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 lg:p-8 shadow-2xl relative overflow-hidden group">
                <div class="absolute -right-4 -top-4 w-32 h-32 bg-rose-500/5 rounded-full blur-2xl pointer-events-none"></div>

                <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                    <span class="w-2 h-5 bg-rose-500 rounded-full"></span>
                    Security & Password
                </h3>

                @if(session()->has('password_message'))
                    <div class="mb-6 p-4 bg-emerald-950 border border-emerald-900 text-emerald-400 rounded-2xl text-sm flex items-center gap-3">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        {{ session('password_message') }}
                    </div>
                @endif

                <form wire:submit.prevent="updatePassword" class="space-y-6">
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-slate-400">Current Password</label>
                        <input type="password" wire:model="current_password" class="w-full bg-slate-950 border border-slate-800 focus:border-rose-500 focus:ring focus:ring-rose-500/20 text-slate-200 rounded-xl px-4 py-3 outline-none transition duration-200">
                        @error('current_password') <span class="text-rose-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-slate-400">New Password</label>
                            <input type="password" wire:model="new_password" class="w-full bg-slate-950 border border-slate-800 focus:border-rose-500 focus:ring focus:ring-rose-500/20 text-slate-200 rounded-xl px-4 py-3 outline-none transition duration-200">
                            @error('new_password') <span class="text-rose-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium text-slate-400">Confirm New Password</label>
                            <input type="password" wire:model="new_password_confirmation" class="w-full bg-slate-950 border border-slate-800 focus:border-rose-500 focus:ring focus:ring-rose-500/20 text-slate-200 rounded-xl px-4 py-3 outline-none transition duration-200">
                        </div>
                    </div>

                    <div class="pt-4 flex justify-end">
                        <button type="submit" class="px-6 py-3 bg-slate-800 hover:bg-slate-700 text-white rounded-xl font-bold text-sm transition duration-200 border border-slate-700 flex items-center gap-2">
                            <span wire:loading.remove wire:target="updatePassword">Update Password</span>
                            <span wire:loading wire:target="updatePassword">Updating...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
