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
    <body class="font-sans text-slate-900 antialiased">
        <div class="min-h-screen grid lg:grid-cols-2">
            <!-- Brand panel -->
            <div class="relative hidden lg:flex flex-col justify-between p-12 text-white overflow-hidden bg-gradient-to-br from-brand-800 via-brand-700 to-brand-950">
                <div class="absolute -top-24 -right-24 h-96 w-96 rounded-full bg-white/10 blur-3xl"></div>
                <div class="absolute -bottom-32 -left-16 h-96 w-96 rounded-full bg-brand-400/20 blur-3xl"></div>

                <a href="/" wire:navigate class="relative flex items-center gap-3">
                    <span class="grid place-items-center h-11 w-11 rounded-2xl bg-white/10 ring-1 ring-white/20">
                        <x-application-logo class="h-7 w-7 text-brand-100" />
                    </span>
                    <span class="text-lg font-semibold tracking-tight">Sistema Docentes</span>
                </a>

                <div class="relative max-w-md">
                    <h1 class="text-4xl font-bold leading-tight">Tu cuaderno digital de asistencia y notas.</h1>
                    <p class="mt-4 text-brand-100/80 text-lg">Controla tus cursos, asistencia y calificaciones en un solo lugar, con apoyo de inteligencia artificial.</p>

                    <div class="mt-10 space-y-4">
                        @foreach (['Registra asistencia en segundos', 'Notas flexibles con tus propios criterios', 'Reportes y calendario para tus estudiantes'] as $item)
                            <div class="flex items-center gap-3">
                                <span class="grid place-items-center h-7 w-7 rounded-full bg-white/15">
                                    <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                </span>
                                <span class="text-brand-50/90">{{ $item }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <p class="relative text-sm text-brand-200/70">&copy; {{ date('Y') }} Sistema Docentes</p>
            </div>

            <!-- Form panel -->
            <div class="flex flex-col justify-center items-center px-6 py-12 bg-slate-50">
                <div class="w-full max-w-md">
                    <div class="lg:hidden flex items-center gap-3 mb-8">
                        <span class="grid place-items-center h-10 w-10 rounded-2xl bg-brand-600 text-white">
                            <x-application-logo class="h-6 w-6" />
                        </span>
                        <span class="text-lg font-semibold tracking-tight text-slate-900">Sistema Docentes</span>
                    </div>

                    <div class="bg-white shadow-xl shadow-slate-200/60 ring-1 ring-slate-200 rounded-2xl p-8">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
