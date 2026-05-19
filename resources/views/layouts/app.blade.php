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
        {{ $slot }}

        @livewireScripts
    </body>
</html>
