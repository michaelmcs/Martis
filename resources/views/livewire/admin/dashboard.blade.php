<?php

use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Institucion;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public function with(): array
    {
        return [
            'totalDocentes' => User::where('rol', User::ROL_DOCENTE)->count(),
            'totalAdmins' => User::where('rol', User::ROL_ADMIN)->count(),
            'totalCursos' => Curso::withoutGlobalScopes()->count(),
            'totalInstituciones' => Institucion::withoutGlobalScopes()->count(),
            'totalEstudiantes' => Estudiante::withoutGlobalScopes()->count(),
            'ultimosDocentes' => User::where('rol', User::ROL_DOCENTE)
                ->withCount([
                    'cursos' => fn ($q) => $q->withoutGlobalScopes(),
                ])
                ->latest()
                ->take(5)
                ->get(),
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <span class="grid place-items-center h-11 w-11 rounded-xl bg-gradient-to-br from-brand-600 to-brand-900 text-white shadow">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </span>
            <div>
                <h2 class="text-xl font-bold text-slate-900">Panel de administración</h2>
                <p class="text-sm text-slate-500">Vista general de toda la plataforma</p>
            </div>
        </div>
    </x-slot>

    @php
        $cards = [
            ['label' => 'Docentes', 'value' => $totalDocentes, 'grad' => 'from-brand-500 to-brand-700', 'icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z'],
            ['label' => 'Cursos', 'value' => $totalCursos, 'grad' => 'from-violet-500 to-purple-700', 'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13'],
            ['label' => 'Instituciones', 'value' => $totalInstituciones, 'grad' => 'from-emerald-500 to-teal-600', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
            ['label' => 'Estudiantes', 'value' => $totalEstudiantes, 'grad' => 'from-amber-500 to-orange-600', 'icon' => 'M12 14l9-5-9-5-9 5 9 5z M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z'],
        ];
    @endphp

    <div class="w-full px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <!-- Indicadores globales -->
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($cards as $card)
                <div class="rounded-2xl bg-white p-6 ring-1 ring-slate-200 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">{{ $card['label'] }}</p>
                            <p class="mt-2 text-4xl font-bold text-slate-900">{{ $card['value'] }}</p>
                        </div>
                        <span class="grid place-items-center h-14 w-14 rounded-2xl bg-gradient-to-br {{ $card['grad'] }} text-white shadow-lg">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="{{ $card['icon'] }}"/></svg>
                        </span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Últimos docentes -->
            <div class="lg:col-span-2 rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                    <h3 class="text-base font-semibold text-slate-900">Últimos docentes registrados</h3>
                    <a href="{{ route('admin.docentes') }}" wire:navigate class="text-sm font-medium text-brand-600 hover:text-brand-700">Ver todos</a>
                </div>
                <div class="overflow-x-auto scrollbar-thin">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/60 text-xs font-semibold uppercase tracking-wider text-slate-500">
                                <th class="px-6 py-3">Docente</th>
                                <th class="px-6 py-3 hidden sm:table-cell">Correo</th>
                                <th class="px-6 py-3 text-center">Cursos</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($ultimosDocentes as $docente)
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <span class="grid place-items-center h-9 w-9 rounded-full bg-brand-500 text-white text-sm font-semibold">{{ strtoupper(substr($docente->name, 0, 1)) }}</span>
                                            <div>
                                                <p class="font-semibold text-slate-900">{{ $docente->nombre_completo }}</p>
                                                <p class="text-xs text-slate-400 sm:hidden">{{ $docente->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-slate-600 hidden sm:table-cell">{{ $docente->email }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-700">{{ $docente->cursos_count }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-6 py-12 text-center text-sm text-slate-400">Aún no hay docentes registrados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Resumen -->
            <div class="rounded-2xl bg-gradient-to-br from-brand-700 to-brand-900 p-6 text-white shadow-lg">
                <h3 class="text-lg font-bold">Resumen de roles</h3>
                <p class="mt-1 text-sm text-brand-100/80">Distribución de cuentas en la plataforma.</p>
                <div class="mt-6 space-y-4">
                    <div class="rounded-xl bg-white/10 p-4 ring-1 ring-white/10">
                        <div class="flex items-center justify-between">
                            <span class="text-brand-100">Docentes</span>
                            <span class="text-2xl font-bold">{{ $totalDocentes }}</span>
                        </div>
                    </div>
                    <div class="rounded-xl bg-white/10 p-4 ring-1 ring-white/10">
                        <div class="flex items-center justify-between">
                            <span class="text-brand-100">Administradores</span>
                            <span class="text-2xl font-bold">{{ $totalAdmins }}</span>
                        </div>
                    </div>
                </div>
                <a href="{{ route('admin.docentes') }}" wire:navigate class="mt-6 inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-brand-700 hover:bg-brand-50 transition">
                    Gestionar docentes
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        </div>
    </div>
</div>
