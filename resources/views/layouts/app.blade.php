<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Sistema Docentes') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-slate-100 text-slate-800">
        <livewire:layout.sidebar />

        <div class="lg:pl-64 min-h-screen flex flex-col">
            <!-- Page Heading -->
            @if (isset($header))
                <header class="sticky top-0 z-20 bg-white/80 backdrop-blur border-b border-slate-200 mt-16 lg:mt-0">
                    <div class="px-4 sm:px-6 lg:px-8 py-5">
                        {{ $header }}
                    </div>
                </header>
            @else
                <div class="mt-16 lg:mt-0"></div>
            @endif

            <!-- Page Content -->
            <main class="flex-1">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
