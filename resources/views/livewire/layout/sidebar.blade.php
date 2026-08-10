<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

@php
    $esAdmin = auth()->user()->esAdmin();
    if ($esAdmin) {
        $links = [
            ['route' => 'admin.dashboard', 'label' => 'Panel', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
            ['route' => 'admin.docentes', 'label' => 'Docentes', 'icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z'],
        ];
        $rolLabel = 'Administrador';
    } else {
        $links = [
            ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
            ['route' => 'cursos.index', 'label' => 'Cursos', 'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
            ['route' => 'estudiantes.index', 'label' => 'Estudiantes', 'icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z'],
            ['route' => 'instituciones.index', 'label' => 'Instituciones', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
            ['route' => 'asistente.index', 'label' => 'Asistente IA', 'icon' => 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z'],
        ];
        $rolLabel = 'Docente';
    }
@endphp

<div
    x-data="{ open: false }"
    class="lg:contents"
>
    <!-- Mobile top bar -->
    <div class="lg:hidden fixed top-0 inset-x-0 z-30 flex items-center justify-between h-16 px-4 bg-brand-900 text-white shadow-lg">
        <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2">
            <x-application-logo class="h-8 w-8 text-brand-300" />
            <span class="font-semibold tracking-tight">Sistema Docentes</span>
        </a>
        <button @click="open = true" class="p-2 rounded-lg hover:bg-white/10">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
        </button>
    </div>

    <!-- Mobile backdrop -->
    <div x-show="open" x-transition.opacity @click="open = false" class="lg:hidden fixed inset-0 z-40 bg-black/50" style="display:none"></div>

    <!-- Sidebar -->
    <aside
        class="fixed inset-y-0 left-0 z-50 w-64 flex flex-col bg-gradient-to-b from-brand-900 via-brand-800 to-brand-950 text-brand-100 transform transition-transform duration-200 lg:translate-x-0"
        :class="open ? 'translate-x-0' : '-translate-x-full'"
    >
        <!-- Brand -->
        <div class="flex items-center justify-between gap-2 h-16 px-5 border-b border-white/10">
            <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2.5">
                <span class="grid place-items-center h-9 w-9 rounded-xl bg-white/10 ring-1 ring-white/15">
                    <x-application-logo class="h-6 w-6 text-brand-200" />
                </span>
                <span class="font-semibold text-white tracking-tight leading-tight">Sistema<br class="hidden">Docentes</span>
            </a>
            <button @click="open = false" class="lg:hidden p-1.5 rounded-lg hover:bg-white/10 text-white">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <!-- Nav -->
        <nav class="flex-1 px-3 py-5 space-y-1 overflow-y-auto">
            <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-brand-300/70">Menú · {{ $rolLabel }}</p>
            @foreach ($links as $link)
                @php $active = request()->routeIs($link['route']); @endphp
                <a href="{{ route($link['route']) }}" wire:navigate
                   @class([
                       'group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition',
                       'bg-white/15 text-white shadow-sm ring-1 ring-white/10' => $active,
                       'text-brand-100/80 hover:bg-white/10 hover:text-white' => ! $active,
                   ])>
                    <svg class="h-5 w-5 shrink-0 {{ $active ? 'text-brand-200' : 'text-brand-300/70 group-hover:text-brand-200' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $link['icon'] }}" />
                    </svg>
                    {{ $link['label'] }}
                </a>
            @endforeach
        </nav>

        <!-- User card -->
        <div class="p-3 border-t border-white/10">
            <div class="flex items-center gap-3 px-2 py-2 rounded-xl bg-white/5">
                <span class="grid place-items-center h-9 w-9 rounded-full bg-brand-500 text-white font-semibold text-sm">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-white truncate">{{ auth()->user()->nombre_completo }}</p>
                    <p class="text-xs text-brand-300/80 truncate">{{ auth()->user()->email }}</p>
                </div>
            </div>
            <div class="mt-2 flex items-center gap-2">
                <a href="{{ route('profile') }}" wire:navigate class="flex-1 text-center text-xs font-medium px-3 py-2 rounded-lg bg-white/5 text-brand-100 hover:bg-white/10 transition">
                    Perfil
                </a>
                <button wire:click="logout" class="flex-1 text-center text-xs font-medium px-3 py-2 rounded-lg bg-white/5 text-brand-100 hover:bg-red-500/80 hover:text-white transition">
                    Salir
                </button>
            </div>
        </div>
    </aside>
</div>
