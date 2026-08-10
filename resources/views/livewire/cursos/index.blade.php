<?php

use App\Models\Curso;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public bool $showForm = false;
    public bool $showView = false;
    public bool $showInstitucionRapida = false;
    public ?string $editingId = null;
    public ?array $viewData = null;

    public string $nombre = '';
    public ?string $institucion_id = null;
    public string $regimen = 'semestral';
    public ?int $anio = null;
    public ?string $periodo = 'I';
    public int $semanas = 16;
    public ?int $tope_faltas = null;
    public int $peso_asistencia = 0;

    // Institución rápida
    public string $inst_nombre = '';
    public string $inst_lugar = '';

    public string $buscar = '';

    public function mount(): void
    {
        $this->anio = (int) date('Y');
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'institucion_id' => ['required', 'exists:instituciones,id'],
            'regimen' => ['required', 'in:anual,semestral,trimestral'],
            'anio' => ['required', 'integer', 'min:2000', 'max:2100'],
            'periodo' => ['nullable', 'in:I,II,III,IV,V,VI'],
            'semanas' => ['required', 'integer', 'min:1', 'max:52'],
            'tope_faltas' => ['nullable', 'integer', 'min:0'],
            'peso_asistencia' => ['required', 'integer', 'min:0', 'max:100'],
        ];
    }

    public function nuevo(): void
    {
        $this->reset('nombre', 'institucion_id', 'regimen', 'periodo', 'tope_faltas', 'peso_asistencia', 'editingId');
        $this->anio = (int) date('Y');
        $this->semanas = 16;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function editar(string $id): void
    {
        $curso = auth()->user()->cursos()->findOrFail($id);
        $this->editingId = $curso->id;
        $this->nombre = $curso->nombre;
        $this->institucion_id = $curso->institucion_id;
        $this->regimen = $curso->regimen;
        $this->anio = $curso->anio ?? (int) date('Y');
        $this->periodo = $curso->periodo ?? 'I';
        $this->semanas = $curso->semanas ?? 16;
        $this->tope_faltas = $curso->tope_faltas;
        $this->peso_asistencia = $curso->peso_asistencia;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function ver(string $id): void
    {
        $curso = auth()->user()->cursos()->with('institucion')->findOrFail($id);
        $this->viewData = [
            'nombre' => $curso->nombre,
            'institucion' => $curso->institucion?->nombre,
            'periodo_academico' => $curso->periodo_academico,
            'tope_faltas' => $curso->tope_faltas,
            'peso_asistencia' => $curso->peso_asistencia,
            'creado' => $curso->created_at?->format('d/m/Y'),
        ];
        $this->showView = true;
    }

    public function guardar(): void
    {
        $data = $this->validate();

        if ($this->regimen === 'anual') {
            $data['periodo'] = null;
        }

        if ($this->editingId) {
            $curso = auth()->user()->cursos()->findOrFail($this->editingId);
            $curso->update($data);
            $this->dispatch('notify', message: 'Curso actualizado correctamente.', type: 'success');
        } else {
            auth()->user()->cursos()->create($data);
            $this->dispatch('notify', message: 'Curso creado correctamente.', type: 'success');
        }

        $this->showForm = false;
        $this->reset('nombre', 'institucion_id', 'regimen', 'periodo', 'tope_faltas', 'peso_asistencia', 'editingId');
        $this->anio = (int) date('Y');
    }

    public function abrirInstitucionRapida(): void
    {
        $this->reset('inst_nombre', 'inst_lugar');
        $this->resetValidation();
        $this->showInstitucionRapida = true;
    }

    public function guardarInstitucionRapida(): void
    {
        $data = $this->validate([
            'inst_nombre' => ['required', 'string', 'max:255'],
            'inst_lugar' => ['nullable', 'string', 'max:255'],
        ]);

        $institucion = auth()->user()->instituciones()->create([
            'nombre' => $data['inst_nombre'],
            'lugar' => $data['inst_lugar'] ?: null,
        ]);

        $this->institucion_id = $institucion->id;
        $this->showInstitucionRapida = false;
        $this->reset('inst_nombre', 'inst_lugar');
        $this->dispatch('notify', message: 'Institución creada y seleccionada.', type: 'success');
    }

    public function eliminar(string $id): void
    {
        $curso = auth()->user()->cursos()->findOrFail($id);
        $this->authorize('delete', $curso);
        $curso->delete();
        $this->dispatch('notify', message: 'Curso eliminado.', type: 'success');
    }

    public function with(): array
    {
        $query = auth()->user()->cursos()->with('institucion')->latest();

        if ($this->buscar !== '') {
            $query->where('nombre', 'like', '%'.$this->buscar.'%');
        }

        return [
            'cursos' => $query->get(),
            'instituciones' => auth()->user()->instituciones()->orderBy('nombre')->get(),
            'totalCursos' => auth()->user()->cursos()->count(),
            'cursosConInstitucion' => auth()->user()->cursos()->whereNotNull('institucion_id')->count(),
            'aniosDistintos' => auth()->user()->cursos()->whereNotNull('anio')->distinct()->count('anio'),
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <span class="grid place-items-center h-11 w-11 rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            </span>
            <div>
                <h2 class="text-xl font-bold text-slate-900">{{ __('Cursos') }}</h2>
                <p class="text-sm text-slate-500">Administra tus cursos del semestre</p>
            </div>
        </div>
    </x-slot>

    <div class="w-full px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <!-- Indicadores -->
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-2xl bg-white p-6 ring-1 ring-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Total cursos</p>
                    <p class="mt-1 text-3xl font-bold text-slate-900">{{ $totalCursos }}</p>
                </div>
                <span class="grid place-items-center h-12 w-12 rounded-xl bg-brand-50 text-brand-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                </span>
            </div>
            <div class="rounded-2xl bg-white p-6 ring-1 ring-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Con institución</p>
                    <p class="mt-1 text-3xl font-bold text-slate-900">{{ $cursosConInstitucion }}</p>
                </div>
                <span class="grid place-items-center h-12 w-12 rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
                </span>
            </div>
            <div class="rounded-2xl bg-white p-6 ring-1 ring-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Años académicos</p>
                    <p class="mt-1 text-3xl font-bold text-slate-900">{{ $aniosDistintos }}</p>
                </div>
                <span class="grid place-items-center h-12 w-12 rounded-xl bg-amber-50 text-amber-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </span>
            </div>
        </div>

        <!-- Tabla -->
        <div class="rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 border-b border-slate-100">
                <h3 class="text-base font-semibold text-slate-900">Listado de cursos</h3>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input wire:model.live.debounce.300ms="buscar" type="text" placeholder="Buscar curso..." class="w-52 max-w-full rounded-xl border-slate-300 pl-9 text-sm focus:border-brand-500 focus:ring-brand-500">
                    </div>
                    <button wire:click="nuevo" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-600/20 hover:bg-brand-700 transition whitespace-nowrap">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Nuevo curso
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto scrollbar-thin">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/60 text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <th class="px-6 py-3">Curso</th>
                            <th class="px-6 py-3 hidden md:table-cell">Institución</th>
                            <th class="px-6 py-3 hidden sm:table-cell">Periodo académico</th>
                            <th class="px-6 py-3 text-center hidden lg:table-cell">Tope faltas</th>
                            <th class="px-6 py-3 text-center">Asistencia</th>
                            <th class="px-6 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($cursos as $curso)
                            <tr class="hover:bg-slate-50/70 transition" wire:key="curso-{{ $curso->id }}">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <span class="grid place-items-center h-10 w-10 shrink-0 rounded-xl bg-brand-100 text-brand-700 font-semibold">
                                            {{ strtoupper(substr($curso->nombre, 0, 1)) }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-slate-900 truncate">{{ $curso->nombre }}</p>
                                            <p class="text-xs text-slate-400 md:hidden">{{ $curso->institucion?->nombre ?? 'Sin institución' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-slate-600 hidden md:table-cell">{{ $curso->institucion?->nombre ?? '—' }}</td>
                                <td class="px-6 py-4 hidden sm:table-cell">
                                    <span class="inline-flex items-center rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">{{ $curso->periodo_academico ?: '—' }}</span>
                                </td>
                                <td class="px-6 py-4 text-center text-slate-600 hidden lg:table-cell">{{ $curso->tope_faltas ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center">
                                        <div class="w-24">
                                            <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                                                <span>{{ $curso->peso_asistencia }}%</span>
                                            </div>
                                            <div class="h-1.5 w-full rounded-full bg-slate-100 overflow-hidden">
                                                <div class="h-full rounded-full bg-gradient-to-r from-brand-400 to-brand-600" style="width: {{ $curso->peso_asistencia }}%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('cursos.gestionar', $curso->id) }}?tab=asistencia" wire:navigate class="hidden xl:inline-flex items-center gap-1 rounded-lg bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 transition" title="Ir a Asistencia">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            Asistencia
                                        </a>
                                        <a href="{{ route('cursos.gestionar', $curso->id) }}?tab=notafinal" wire:navigate class="hidden xl:inline-flex items-center gap-1 rounded-lg bg-amber-50 px-2.5 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-100 transition" title="Ir a Nota final">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            Notas
                                        </a>
                                        <a href="{{ route('cursos.gestionar', $curso->id) }}" wire:navigate class="inline-flex items-center gap-1 rounded-lg bg-brand-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-brand-700 transition mr-1" title="Gestionar curso">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            Gestionar
                                        </a>
                                        <button wire:click="ver('{{ $curso->id }}')" class="grid place-items-center h-9 w-9 rounded-lg text-slate-400 hover:bg-brand-50 hover:text-brand-600 transition" title="Ver">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>
                                        <button wire:click="editar('{{ $curso->id }}')" class="grid place-items-center h-9 w-9 rounded-lg text-slate-400 hover:bg-amber-50 hover:text-amber-600 transition" title="Editar">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <button
                                            x-data
                                            @click="window.confirmarEliminar('Se eliminará el curso «{{ addslashes($curso->nombre) }}».').then(r => { if (r.isConfirmed) $wire.eliminar('{{ $curso->id }}') })"
                                            class="grid place-items-center h-9 w-9 rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600 transition" title="Eliminar">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center">
                                    <span class="grid place-items-center h-16 w-16 mx-auto rounded-2xl bg-slate-100 text-slate-400">
                                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.4" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                    </span>
                                    <p class="mt-4 text-sm font-medium text-slate-600">Sin cursos</p>
                                    <p class="text-sm text-slate-400">Crea tu primer curso con el botón «Nuevo curso».</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal crear / editar -->
    @if ($showForm)
    <x-modal-panel
        wire="showForm"
        :title="$editingId ? 'Editar curso' : 'Nuevo curso'"
        subtitle="Completa la información del curso"
        icon="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
        icon-grad="from-brand-500 to-brand-700"
        max-width="2xl"
    >
        <form wire:submit="guardar" id="form-curso" class="space-y-4">
            <div>
                <x-input-label for="c_nombre" :value="__('Nombre del curso')" />
                <x-text-input wire:model="nombre" id="c_nombre" class="block mt-1 w-full" type="text" placeholder="Programación I" />
                <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="c_institucion" :value="__('Institución')" />
                <div class="flex gap-2 mt-1">
                    <select wire:model="institucion_id" id="c_institucion" class="flex-1 border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-xl shadow-sm text-slate-900">
                        <option value="">— Selecciona una institución —</option>
                        @foreach ($instituciones as $institucion)
                            <option value="{{ $institucion->id }}">{{ $institucion->nombre }}</option>
                        @endforeach
                    </select>
                    <button type="button" wire:click="abrirInstitucionRapida" class="inline-flex items-center gap-1 rounded-xl bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700 transition whitespace-nowrap" title="Crear institución">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Nueva
                    </button>
                </div>
                @if ($instituciones->isEmpty())
                    <p class="mt-2 text-xs text-amber-600">Aún no tienes instituciones. Crea una con el botón «Nueva».</p>
                @endif
                <x-input-error :messages="$errors->get('institucion_id')" class="mt-2" />
            </div>

            <!-- Periodo académico separado -->
            <div class="rounded-xl border border-slate-200 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-3">Periodo académico</p>
                <div class="grid sm:grid-cols-3 gap-4">
                    <div>
                        <x-input-label for="c_regimen" :value="__('Régimen')" />
                        <select wire:model.live="regimen" id="c_regimen" class="w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-xl shadow-sm text-slate-900 mt-1">
                            @foreach (\App\Models\Curso::REGIMENES as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('regimen')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="c_anio" :value="__('Año')" />
                        <x-text-input wire:model="anio" id="c_anio" class="block mt-1 w-full" type="number" min="2000" max="2100" placeholder="2026" />
                        <x-input-error :messages="$errors->get('anio')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="c_periodo" :value="__('Periodo (romano)')" />
                        <select wire:model="periodo" id="c_periodo" @disabled($regimen === 'anual') class="w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-xl shadow-sm text-slate-900 mt-1 disabled:bg-slate-100 disabled:text-slate-400">
                            @foreach (\App\Models\Curso::PERIODOS as $rom)
                                <option value="{{ $rom }}">{{ $rom }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('periodo')" class="mt-2" />
                    </div>
                </div>
                <div class="mt-4">
                    <x-input-label for="c_semanas" :value="__('Duración del ciclo (semanas)')" />
                    <x-text-input wire:model="semanas" id="c_semanas" class="block mt-1 w-full sm:w-40" type="number" min="1" max="52" placeholder="18" />
                    <p class="mt-1 text-xs text-slate-400">Ej. 16 o 18 semanas. Se usa para generar las clases del horario.</p>
                    <x-input-error :messages="$errors->get('semanas')" class="mt-2" />
                </div>
                @if ($regimen === 'anual')
                    <p class="mt-2 text-xs text-slate-400">El régimen anual no usa periodo.</p>
                @endif
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="c_tope" :value="__('Tope de faltas')" />
                    <x-text-input wire:model="tope_faltas" id="c_tope" class="block mt-1 w-full" type="number" min="0" placeholder="4" />
                    <x-input-error :messages="$errors->get('tope_faltas')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="c_peso" :value="__('Peso de asistencia (%)')" />
                    <x-text-input wire:model="peso_asistencia" id="c_peso" class="block mt-1 w-full" type="number" min="0" max="100" placeholder="20" />
                    <x-input-error :messages="$errors->get('peso_asistencia')" class="mt-2" />
                </div>
            </div>
        </form>

        <x-slot name="footer">
            <button type="button" wire:click="$set('showForm', false)" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100 transition">Cancelar</button>
            <button type="submit" form="form-curso" wire:loading.attr="disabled" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700 transition disabled:opacity-60">
                <svg wire:loading wire:target="guardar" class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                {{ $editingId ? 'Guardar cambios' : 'Crear curso' }}
            </button>
        </x-slot>
    </x-modal-panel>
    @endif

    <!-- Modal institución rápida (anidado) -->
    @if ($showInstitucionRapida)
    <x-modal-panel
        wire="showInstitucionRapida"
        title="Nueva institución"
        subtitle="Se seleccionará automáticamente en el curso"
        icon="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
        icon-grad="from-emerald-500 to-teal-600"
        max-width="md"
    >
        <form wire:submit="guardarInstitucionRapida" id="form-inst-rapida" class="space-y-4">
            <div>
                <x-input-label for="ir_nombre" :value="__('Nombre')" />
                <x-text-input wire:model="inst_nombre" id="ir_nombre" class="block mt-1 w-full" type="text" placeholder="Universidad Nacional..." />
                <x-input-error :messages="$errors->get('inst_nombre')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="ir_lugar" :value="__('Lugar')" />
                <x-text-input wire:model="inst_lugar" id="ir_lugar" class="block mt-1 w-full" type="text" placeholder="Ciudad o localidad" />
                <x-input-error :messages="$errors->get('inst_lugar')" class="mt-2" />
            </div>
        </form>
        <x-slot name="footer">
            <button type="button" wire:click="$set('showInstitucionRapida', false)" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100 transition">Cancelar</button>
            <button type="submit" form="form-inst-rapida" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 transition">
                Crear y seleccionar
            </button>
        </x-slot>
    </x-modal-panel>
    @endif

    <!-- Modal ver -->
    @if ($showView)
    <x-modal-panel
        wire="showView"
        title="Detalle del curso"
        icon="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
        icon-grad="from-brand-500 to-brand-700"
        max-width="lg"
    >
        @if ($viewData)
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <span class="grid place-items-center h-14 w-14 rounded-2xl bg-brand-100 text-brand-700 text-xl font-bold">
                        {{ strtoupper(substr($viewData['nombre'], 0, 1)) }}
                    </span>
                    <div>
                        <p class="text-lg font-bold text-slate-900">{{ $viewData['nombre'] }}</p>
                        <p class="text-sm text-slate-500">{{ $viewData['institucion'] ?? 'Sin institución' }} · Creado el {{ $viewData['creado'] }}</p>
                    </div>
                </div>
                <dl class="grid grid-cols-2 gap-4 pt-2">
                    <div class="col-span-2 rounded-xl bg-slate-50 p-4">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Periodo académico</dt>
                        <dd class="mt-1 font-semibold text-slate-800">{{ $viewData['periodo_academico'] ?: '—' }}</dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Tope de faltas</dt>
                        <dd class="mt-1 font-semibold text-slate-800">{{ $viewData['tope_faltas'] ?? '—' }}</dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Peso asistencia</dt>
                        <dd class="mt-1 font-semibold text-slate-800">{{ $viewData['peso_asistencia'] }}%</dd>
                    </div>
                </dl>
            </div>
        @endif
    </x-modal-panel>
    @endif
</div>
