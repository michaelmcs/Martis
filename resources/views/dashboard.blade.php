<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-900">Dashboard</h2>
                <p class="text-sm text-slate-500">Hola, {{ auth()->user()->nombre_completo }} 👋</p>
            </div>
        </div>
    </x-slot>

    @php
        $totalCursos = auth()->user()->cursos()->count();
        $totalInstituciones = auth()->user()->instituciones()->count();
        $totalEstudiantes = auth()->user()->estudiantes()->count();
        $misCursos = auth()->user()->cursos()->with('institucion')->latest()->take(6)->get();
        $cards = [
            ['label' => 'Cursos', 'value' => $totalCursos, 'route' => 'cursos.index', 'grad' => 'from-brand-500 to-brand-700', 'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
            ['label' => 'Instituciones', 'value' => $totalInstituciones, 'route' => 'instituciones.index', 'grad' => 'from-emerald-500 to-teal-600', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
            ['label' => 'Estudiantes', 'value' => $totalEstudiantes, 'route' => 'estudiantes.index', 'grad' => 'from-amber-500 to-orange-600', 'icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z'],
        ];
    @endphp

    <div class="px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        <!-- Stat cards -->
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($cards as $card)
                <a href="{{ route($card['route']) }}" wire:navigate class="group relative overflow-hidden rounded-2xl bg-white p-6 ring-1 ring-slate-200 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">{{ $card['label'] }}</p>
                            <p class="mt-2 text-4xl font-bold text-slate-900">{{ $card['value'] }}</p>
                        </div>
                        <span class="grid place-items-center h-14 w-14 rounded-2xl bg-gradient-to-br {{ $card['grad'] }} text-white shadow-lg">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="{{ $card['icon'] }}"/></svg>
                        </span>
                    </div>
                    <span class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-brand-600 group-hover:gap-2 transition-all">
                        Ver todo
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </span>
                </a>
            @endforeach
        </div>

        <!-- Mis cursos: accesos directos a asistencia y notas -->
        @if ($misCursos->isNotEmpty())
            <div class="rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Mis cursos</h3>
                        <p class="text-sm text-slate-500">Entra directo a la asistencia o las notas de cada curso</p>
                    </div>
                    <a href="{{ route('cursos.index') }}" wire:navigate class="text-sm font-medium text-brand-600 hover:text-brand-700">Ver todos</a>
                </div>
                <div class="divide-y divide-slate-100">
                    @foreach ($misCursos as $curso)
                        <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 hover:bg-slate-50/70 transition">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="grid place-items-center h-10 w-10 shrink-0 rounded-xl bg-brand-100 text-brand-700 font-semibold">{{ strtoupper(substr($curso->nombre, 0, 1)) }}</span>
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-900 truncate">{{ $curso->nombre }}</p>
                                    <p class="text-xs text-slate-400 truncate">{{ $curso->institucion?->nombre ?? 'Sin institución' }} · {{ $curso->periodo_academico }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('cursos.gestionar', $curso->id) }}?tab=asistencia" wire:navigate class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 transition">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Asistencia
                                </a>
                                <a href="{{ route('cursos.gestionar', $curso->id) }}?tab=notafinal" wire:navigate class="inline-flex items-center gap-1 rounded-lg bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-100 transition">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    Notas
                                </a>
                                <a href="{{ route('cursos.gestionar', $curso->id) }}" wire:navigate class="inline-flex items-center gap-1 rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-700 transition">
                                    Gestionar
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Welcome / quick actions -->
        <div class="rounded-2xl bg-gradient-to-br from-brand-700 to-brand-900 p-8 text-white shadow-lg">
            <div class="max-w-2xl">
                <h3 class="text-2xl font-bold">Comienza a organizar tu semestre</h3>
                <p class="mt-2 text-brand-100/80">Crea tus instituciones, registra tus cursos y lleva el control de asistencia y notas de tus estudiantes.</p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('cursos.index') }}" wire:navigate class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-brand-700 hover:bg-brand-50 transition">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Nuevo curso
                    </a>
                    <a href="{{ route('instituciones.index') }}" wire:navigate class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-4 py-2.5 text-sm font-semibold text-white ring-1 ring-white/20 hover:bg-white/20 transition">
                        Gestionar instituciones
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
