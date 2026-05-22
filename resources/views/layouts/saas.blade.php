<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? 'SaaS Admin — Interboard ISP' }}</title>

        @fonts

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <!-- Fallback Tailwind CSS CDN -->
            <script src="https://cdn.tailwindcss.com"></script>
        @endif

        @livewireStyles

        <script>
            (function() {
                const theme = localStorage.getItem('theme') || 'system';
                const isDark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                if (isDark) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            })();
        </script>
    </head>
    <body class="bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100 font-sans antialiased min-h-screen transition-colors duration-200">
        @php
            $user = auth()->user();
            $userName = $user ? $user->name : 'Super Admin';
            $userEmail = $user ? $user->email : 'admin@platform.com';
        @endphp

        <div class="flex h-screen overflow-hidden">
            <!-- Sidebar (Purple Theme) -->
            <aside class="hidden lg:flex lg:flex-col lg:w-64 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-purple-900/30 flex-shrink-0 transition-colors duration-200">
                <!-- Brand / Logo Area -->
                <div class="h-16 flex items-center justify-between px-6 border-b border-slate-200 dark:border-purple-900/30 bg-slate-50 dark:bg-slate-900/50">
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 bg-gradient-to-tr from-purple-500 to-fuchsia-500 rounded-lg text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                        </span>
                        <span class="font-extrabold text-lg text-slate-800 dark:text-white tracking-tight">SaaS Admin</span>
                    </div>
                </div>

                <!-- Navigation Links -->
                <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto">
                    <a href="/saas" class="flex items-center gap-3 px-4 py-3 text-sm font-semibold rounded-xl transition duration-200 {{ request()->is('saas') ? 'bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 border border-purple-100 dark:border-purple-900/50' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-850 hover:text-slate-900 dark:hover:text-slate-200' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4zM14 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2v-4z"></path></svg>
                        Platform Overview
                    </a>

                    <a href="/saas/tenants" class="flex items-center gap-3 px-4 py-3 text-sm font-semibold rounded-xl transition duration-200 {{ request()->is('saas/tenants*') ? 'bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 border border-purple-100 dark:border-purple-900/50' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-850 hover:text-slate-900 dark:hover:text-slate-200' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        Tenant Manager
                    </a>

                    <div class="h-px bg-slate-200 dark:bg-slate-800/60 my-3 mx-4"></div>

                    <a href="/dashboard" class="flex items-center gap-3 px-4 py-3 text-sm font-semibold rounded-xl transition duration-200 text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-850 hover:text-slate-800 dark:hover:text-slate-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                        Back to ISP Panel
                    </a>
                </nav>

                <!-- Footer -->
                <div class="p-4 border-t border-slate-200 dark:border-purple-900/30 bg-slate-50 dark:bg-slate-900/30">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-purple-600 to-fuchsia-600 flex items-center justify-center font-bold text-white">
                            SA
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold text-purple-650 dark:text-purple-400 tracking-wider uppercase">Super Admin</p>
                            <p class="text-sm font-bold text-slate-850 dark:text-white truncate">Platform Owner</p>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main Layout Window -->
            <div class="flex flex-col flex-1 min-w-0 overflow-hidden">
                <!-- Topbar -->
                <header class="h-16 flex items-center justify-between px-6 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-purple-900/30 flex-shrink-0 z-10 transition-colors duration-200">
                    <div class="flex items-center gap-4">
                        <span class="flex items-center gap-1.5 px-3 py-1 text-xs font-bold tracking-wide text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-950/65 border border-purple-200 dark:border-purple-800/80 rounded-full uppercase">
                            <span class="w-1.5 h-1.5 rounded-full bg-purple-500 dark:bg-purple-400 animate-pulse"></span>
                            SaaS Governance
                        </span>
                    </div>

                    <!-- User controls & Theme Switcher -->
                    <div class="flex items-center gap-4">
                        <!-- Theme Switcher -->
                        <div x-data="{
                            open: false,
                            theme: localStorage.getItem('theme') || 'system',
                            setTheme(val) {
                                this.theme = val;
                                localStorage.setItem('theme', val);
                                if (val === 'dark' || (val === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                                    document.documentElement.classList.add('dark');
                                } else {
                                    document.documentElement.classList.remove('dark');
                                }
                                this.open = false;
                            }
                        }" class="relative">
                            <button @click="open = !open" class="w-10 h-10 rounded-xl bg-slate-55 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center font-bold text-slate-600 dark:text-slate-350 hover:bg-slate-100 dark:hover:bg-slate-750 transition duration-200" title="Switch Theme">
                                <!-- Sun (Light Mode) -->
                                <svg x-show="theme === 'light'" class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.364l-.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z"></path></svg>
                                <!-- Moon (Dark Mode) -->
                                <svg x-show="theme === 'dark'" style="display: none;" class="w-5 h-5 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                                <!-- Desktop (System Mode) -->
                                <svg x-show="theme === 'system'" style="display: none;" class="w-5 h-5 text-cyan-500 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            </button>
                            <div x-show="open" @click.away="open = false" style="display: none;" class="absolute right-0 mt-2 w-40 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-2xl py-2 z-50">
                                <button @click="setTheme('light')" class="w-full flex items-center gap-2.5 px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-850 hover:text-slate-900 dark:hover:text-white transition duration-150">
                                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.364l-.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z"></path></svg>
                                    Light Mode
                                </button>
                                <button @click="setTheme('dark')" class="w-full flex items-center gap-2.5 px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-850 hover:text-slate-900 dark:hover:text-white transition duration-150">
                                    <svg class="w-4 h-4 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                                    Dark Mode
                                </button>
                                <button @click="setTheme('system')" class="w-full flex items-center gap-2.5 px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-850 hover:text-slate-900 dark:hover:text-white transition duration-150">
                                    <svg class="w-4 h-4 text-cyan-500 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                    System Default
                                </button>
                            </div>
                        </div>

                        <!-- User Profile Info -->
                        <div class="flex items-center gap-3">
                            <div class="text-right hidden sm:block">
                                <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $userName }}</p>
                                <p class="text-xs text-slate-500">{{ $userEmail }}</p>
                            </div>
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-purple-600 to-fuchsia-600 flex items-center justify-center font-bold text-white">
                                {{ substr($userName, 0, 1) }}
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Page view contents slot -->
                <main class="flex-1 overflow-y-auto bg-slate-50 dark:bg-slate-950 p-6 transition-colors duration-200">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
