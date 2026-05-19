<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'Interboard ISP') }}</title>

        @fonts

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <!-- Fallback Tailwind CSS CDN for visual completeness if assets aren't pre-built -->
            <script src="https://cdn.tailwindcss.com"></script>
        @endif

        @livewireStyles
    </head>
    <body class="bg-slate-950 text-slate-100 font-sans antialiased min-h-screen">
        @php
            $tenantManager = app(\App\Core\Tenant\TenantManager::class);
            $tenant = $tenantManager->getTenant();
            $user = auth()->user() ?? auth('staff')->user();
            $userName = $user ? $user->name : 'System Admin';
            $userEmail = $user ? $user->email : 'admin@isp.net';
        @endphp

        <div class="flex h-screen overflow-hidden">
            <!-- Sidebar -->
            <aside class="hidden lg:flex lg:flex-col lg:w-64 bg-slate-900 border-r border-slate-800 flex-shrink-0">
                <!-- Brand / Logo Area -->
                <div class="h-16 flex items-center justify-between px-6 border-b border-slate-800 bg-slate-900/50">
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 bg-gradient-to-tr from-cyan-500 to-indigo-500 rounded-lg text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </span>
                        <span class="font-extrabold text-lg text-white tracking-tight bg-gradient-to-r from-white to-slate-350 bg-clip-text text-transparent">Mamun ISP</span>
                    </div>
                </div>

                <!-- Navigation Links -->
                <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto">
                    <a href="/dashboard" class="flex items-center gap-3 px-4 py-3 text-sm font-semibold rounded-xl transition duration-200 {{ request()->is('dashboard') ? 'bg-cyan-950/50 text-cyan-400 border border-cyan-900/50' : 'text-slate-400 hover:bg-slate-850 hover:text-slate-200' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4zM14 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2v-4z"></path></svg>
                        Operations & Monitoring
                    </a>

                    <a href="/audit" class="flex items-center gap-3 px-4 py-3 text-sm font-semibold rounded-xl transition duration-200 {{ request()->is('audit*') ? 'bg-cyan-950/50 text-cyan-400 border border-cyan-900/50' : 'text-slate-400 hover:bg-slate-850 hover:text-slate-200' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                        Audit Activity Log
                    </a>

                    <a href="/search" class="flex items-center gap-3 px-4 py-3 text-sm font-semibold rounded-xl transition duration-200 {{ request()->is('search*') ? 'bg-cyan-950/50 text-cyan-400 border border-cyan-900/50' : 'text-slate-400 hover:bg-slate-850 hover:text-slate-200' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        Global Search
                    </a>

                    <a href="/customers" class="flex items-center gap-3 px-4 py-3 text-sm font-semibold rounded-xl transition duration-200 {{ request()->is('customers*') ? 'bg-cyan-950/50 text-cyan-400 border border-cyan-900/50' : 'text-slate-400 hover:bg-slate-850 hover:text-slate-200' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        Customers List
                    </a>

                    <a href="/subscriptions" class="flex items-center gap-3 px-4 py-3 text-sm font-semibold rounded-xl transition duration-200 {{ request()->is('subscriptions') ? 'bg-cyan-950/50 text-cyan-400 border border-cyan-900/50' : 'text-slate-400 hover:bg-slate-850 hover:text-slate-200' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                        Subscriptions
                    </a>

                    <a href="/subscriptions/actions" class="flex items-center gap-3 px-4 py-3 text-sm font-semibold rounded-xl transition duration-200 {{ request()->is('subscriptions/actions*') ? 'bg-cyan-950/50 text-cyan-400 border border-cyan-900/50' : 'text-slate-400 hover:bg-slate-850 hover:text-slate-200' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        Subscription Actions
                    </a>

                    <a href="/plans" class="flex items-center gap-3 px-4 py-3 text-sm font-semibold rounded-xl transition duration-200 {{ request()->is('plans*') ? 'bg-cyan-950/50 text-cyan-400 border border-cyan-900/50' : 'text-slate-400 hover:bg-slate-850 hover:text-slate-200' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                        Service Catalog
                    </a>

                    <a href="/invoices" class="flex items-center gap-3 px-4 py-3 text-sm font-semibold rounded-xl transition duration-200 {{ request()->is('invoices*') ? 'bg-cyan-950/50 text-cyan-400 border border-cyan-900/50' : 'text-slate-400 hover:bg-slate-850 hover:text-slate-200' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Invoices Engine
                    </a>

                    <div class="h-px bg-slate-800/60 my-2 mx-4"></div>

                    <a href="/payments/intake" class="flex items-center gap-3 px-4 py-3 text-sm font-semibold rounded-xl transition duration-200 {{ request()->is('payments*') ? 'bg-cyan-950/50 text-cyan-400 border border-cyan-900/50' : 'text-slate-400 hover:bg-slate-850 hover:text-slate-200' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        Payments Intake
                    </a>

                    <a href="/wallet" class="flex items-center gap-3 px-4 py-3 text-sm font-semibold rounded-xl transition duration-200 {{ request()->is('wallet*') ? 'bg-cyan-950/50 text-cyan-400 border border-cyan-900/50' : 'text-slate-400 hover:bg-slate-850 hover:text-slate-200' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                        Customer Wallets
                    </a>

                    <a href="/allocations" class="flex items-center gap-3 px-4 py-3 text-sm font-semibold rounded-xl transition duration-200 {{ request()->is('allocations*') ? 'bg-cyan-950/50 text-cyan-400 border border-cyan-900/50' : 'text-slate-400 hover:bg-slate-850 hover:text-slate-200' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                        Allocations Audit
                    </a>

                    <a href="/gateway-attempts" class="flex items-center gap-3 px-4 py-3 text-sm font-semibold rounded-xl transition duration-200 {{ request()->is('gateway-attempts*') ? 'bg-cyan-950/50 text-cyan-400 border border-cyan-900/50' : 'text-slate-400 hover:bg-slate-850 hover:text-slate-200' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Gateway Logs
                    </a>

                    <a href="/reversals" class="flex items-center gap-3 px-4 py-3 text-sm font-semibold rounded-xl transition duration-200 {{ request()->is('reversals*') ? 'bg-cyan-950/50 text-cyan-400 border border-cyan-900/50' : 'text-slate-400 hover:bg-slate-850 hover:text-slate-200' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0019 16V8a1 1 0 00-1.6-.8l-5.334 4zM4.066 11.2a1 1 0 000 1.6l5.334 4A1 1 0 0011 16V8a1 1 0 00-1.6-.8l-5.334 4z"></path></svg>
                        Reversals & Adjusts
                    </a>

                    <a href="/provisioning" class="flex items-center gap-3 px-4 py-3 text-sm font-semibold rounded-xl transition duration-200 {{ request()->is('provisioning*') ? 'bg-cyan-950/50 text-cyan-400 border border-cyan-900/50' : 'text-slate-400 hover:bg-slate-850 hover:text-slate-200' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        Provisioning Sync
                    </a>

                    <div class="h-px bg-slate-800/60 my-2 mx-4"></div>

                    <a href="/online-users" class="flex items-center gap-3 px-4 py-3 text-sm font-semibold rounded-xl transition duration-200 {{ request()->is('online-users*') ? 'bg-cyan-950/50 text-cyan-400 border border-cyan-900/50' : 'text-slate-400 hover:bg-slate-850 hover:text-slate-200' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        Online Users
                    </a>

                    <a href="/network-sessions" class="flex items-center gap-3 px-4 py-3 text-sm font-semibold rounded-xl transition duration-200 {{ request()->is('network-sessions*') ? 'bg-cyan-950/50 text-cyan-400 border border-cyan-900/50' : 'text-slate-400 hover:bg-slate-850 hover:text-slate-200' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                        Network Sessions
                    </a>

                    <a href="/radius" class="flex items-center gap-3 px-4 py-3 text-sm font-semibold rounded-xl transition duration-200 {{ request()->is('radius*') ? 'bg-cyan-950/50 text-cyan-400 border border-cyan-900/50' : 'text-slate-400 hover:bg-slate-850 hover:text-slate-200' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path></svg>
                        RADIUS Config
                    </a>
                </nav>

                <!-- Footer / Tenant Area -->
                <div class="p-4 border-t border-slate-800 bg-slate-900/30">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-center font-bold text-slate-200">
                            {{ $tenant ? substr($tenant->name, 0, 2) : 'SY' }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold text-slate-400 tracking-wider uppercase">Active Tenant</p>
                            <p class="text-sm font-bold text-white truncate">{{ $tenant ? $tenant->name : 'System Hub' }}</p>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main Layout Window -->
            <div class="flex flex-col flex-1 min-w-0 overflow-hidden">
                <!-- Topbar -->
                <header class="h-16 flex items-center justify-between px-6 bg-slate-900 border-b border-slate-800 flex-shrink-0 z-10">
                    <!-- Right Sidebar Mobile Toggle -->
                    <div class="flex items-center gap-4">
                        <button class="lg:hidden p-2 text-slate-400 hover:text-white" onclick="alert('Use desktop version for best experience')">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                        </button>
                        
                        <!-- Tenant Badge -->
                        @if($tenant)
                            <span class="flex items-center gap-1.5 px-3 py-1 text-xs font-bold tracking-wide text-cyan-400 bg-cyan-950/65 border border-cyan-800/80 rounded-full uppercase">
                                <span class="w-1.5 h-1.5 rounded-full bg-cyan-400"></span>
                                {{ $tenant->slug }}
                            </span>
                        @else
                            <span class="flex items-center gap-1.5 px-3 py-1 text-xs font-bold tracking-wide text-amber-400 bg-amber-950/65 border border-amber-800/80 rounded-full uppercase">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                Central Mode
                            </span>
                        @endif

                        <!-- Search Box Link in Topbar -->
                        <div class="hidden md:block ml-4">
                            <a href="/search" class="flex items-center gap-2 px-4 py-1.5 bg-slate-950 border border-slate-800 hover:border-slate-700 rounded-xl text-xs text-slate-400 hover:text-slate-200 transition duration-150">
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                Search...
                            </a>
                        </div>
                    </div>

                    <!-- User controls -->
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-3">
                            <div class="text-right hidden sm:block">
                                <p class="text-sm font-semibold text-slate-200">{{ $userName }}</p>
                                <p class="text-xs text-slate-500">{{ $userEmail }}</p>
                            </div>
                            <!-- User Dropdown Menu -->
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" class="w-10 h-10 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-center font-bold text-slate-300 hover:bg-slate-750 transition duration-200">
                                    {{ substr($userName, 0, 1) }}
                                </button>
                                <div x-show="open" @click.away="open = false" style="display: none;" class="absolute right-0 mt-2 w-48 bg-slate-900 border border-slate-800 rounded-xl shadow-2xl py-2 z-50">
                                    <div class="px-4 py-2 border-b border-slate-800 sm:hidden">
                                        <p class="text-sm font-semibold text-slate-200">{{ $userName }}</p>
                                        <p class="text-xs text-slate-500">{{ $userEmail }}</p>
                                    </div>
                                    <a href="#" class="block px-4 py-2 text-sm text-slate-300 hover:bg-slate-850 hover:text-white">Profile Settings</a>
                                    <a href="#" class="block px-4 py-2 text-sm text-rose-400 hover:bg-slate-850 hover:text-rose-300">Sign Out</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Page view contents slot -->
                <main class="flex-1 overflow-y-auto bg-slate-950">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
