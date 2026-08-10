<?php

use App\Models\Institucion;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    use WithFileUploads;

    public bool $showForm = false;
    public bool $showView = false;
    public ?string $editingId = null;
    public ?array $viewData = null;

    public string $nombre = '';
    public string $lugar = '';
    public string $direccion = '';
    public $logo = null;            // archivo temporal subido
    public ?string $logoActual = null; // ruta del logo ya guardado

    public string $buscar = '';

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'lugar' => ['nullable', 'string', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function nuevo(): void
    {
        $this->reset('nombre', 'lugar', 'direccion', 'logo', 'logoActual', 'editingId');
        $this->resetValidation();
        $this->showForm = true;
    }

    public function editar(string $id): void
    {
        $institucion = auth()->user()->instituciones()->findOrFail($id);
        $this->editingId = $institucion->id;
        $this->nombre = $institucion->nombre;
        $this->lugar = $institucion->lugar ?? '';
        $this->direccion = $institucion->direccion ?? '';
        $this->logo = null;
        $this->logoActual = $institucion->logo;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function ver(string $id): void
    {
        $institucion = auth()->user()->instituciones()->withCount('cursos')->findOrFail($id);
        $this->viewData = [
            'nombre' => $institucion->nombre,
            'lugar' => $institucion->lugar,
            'direccion' => $institucion->direccion,
            'logo_url' => $institucion->logo_url,
            'cursos_count' => $institucion->cursos_count,
            'creado' => $institucion->created_at?->format('d/m/Y'),
        ];
        $this->showView = true;
    }

    public function guardar(): void
    {
        $data = $this->validate();
        unset($data['logo']);

        if ($this->logo) {
            if ($this->logoActual) {
                Storage::disk('public')->delete($this->logoActual);
            }
            $data['logo'] = $this->logo->store('logos', 'public');
        }

        if ($this->editingId) {
            $institucion = auth()->user()->instituciones()->findOrFail($this->editingId);
            $institucion->update($data);
            $this->dispatch('notify', message: 'Institución actualizada correctamente.', type: 'success');
        } else {
            auth()->user()->instituciones()->create($data);
            $this->dispatch('notify', message: 'Institución creada correctamente.', type: 'success');
        }

        $this->showForm = false;
        $this->reset('nombre', 'lugar', 'direccion', 'logo', 'logoActual', 'editingId');
    }

    public function eliminar(string $id): void
    {
        $institucion = auth()->user()->instituciones()->findOrFail($id);
        $this->authorize('delete', $institucion);
        if ($institucion->logo) {
            Storage::disk('public')->delete($institucion->logo);
        }
        $institucion->delete();
        $this->dispatch('notify', message: 'Institución eliminada.', type: 'success');
    }

    public function with(): array
    {
        $query = auth()->user()->instituciones()->withCount('cursos')->latest();

        if ($this->buscar !== '') {
            $query->where(function ($q) {
                $q->where('nombre', 'like', '%'.$this->buscar.'%')
                  ->orWhere('lugar', 'like', '%'.$this->buscar.'%');
            });
        }

        $instituciones = $query->get();

        return [
            'instituciones' => $instituciones,
            'totalInstituciones' => auth()->user()->instituciones()->count(),
            'totalCursosVinculados' => auth()->user()->cursos()->whereNotNull('institucion_id')->count(),
            'totalCiudades' => auth()->user()->instituciones()->whereNotNull('lugar')->distinct()->count('lugar'),
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <span class="grid place-items-center h-11 w-11 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </span>
            <div>
                <h2 class="text-xl font-bold text-slate-900">{{ __('Instituciones') }}</h2>
                <p class="text-sm text-slate-500">Gestiona las universidades donde dictas clases</p>
            </div>
        </div>
    </x-slot>

    <div class="w-full px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <!-- Indicadores -->
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-2xl bg-white p-6 ring-1 ring-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Total instituciones</p>
                    <p class="mt-1 text-3xl font-bold text-slate-900">{{ $totalInstituciones }}</p>
                </div>
                <span class="grid place-items-center h-12 w-12 rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </span>
            </div>
            <div class="rounded-2xl bg-white p-6 ring-1 ring-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Cursos vinculados</p>
                    <p class="mt-1 text-3xl font-bold text-slate-900">{{ $totalCursosVinculados }}</p>
                </div>
                <span class="grid place-items-center h-12 w-12 rounded-xl bg-brand-50 text-brand-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                </span>
            </div>
            <div class="rounded-2xl bg-white p-6 ring-1 ring-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Ciudades</p>
                    <p class="mt-1 text-3xl font-bold text-slate-900">{{ $totalCiudades }}</p>
                </div>
                <span class="grid place-items-center h-12 w-12 rounded-xl bg-amber-50 text-amber-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </span>
            </div>
        </div>

        <!-- Tabla -->
        <div class="rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 border-b border-slate-100">
                <h3 class="text-base font-semibold text-slate-900">Listado de instituciones</h3>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input wire:model.live.debounce.300ms="buscar" type="text" placeholder="Buscar..." class="w-52 max-w-full rounded-xl border-slate-300 pl-9 text-sm focus:border-brand-500 focus:ring-brand-500">
                    </div>
                    <button wire:click="nuevo" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-600/20 hover:bg-brand-700 transition whitespace-nowrap">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Nueva institución
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto scrollbar-thin">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/60 text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <th class="px-6 py-3">Institución</th>
                            <th class="px-6 py-3 hidden md:table-cell">Lugar</th>
                            <th class="px-6 py-3 hidden lg:table-cell">Dirección</th>
                            <th class="px-6 py-3 text-center">Cursos</th>
                            <th class="px-6 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($instituciones as $institucion)
                            <tr class="hover:bg-slate-50/70 transition" wire:key="inst-{{ $institucion->id }}">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        @if ($institucion->logo)
                                            <img src="{{ $institucion->logo_url }}" alt="Logo" class="h-10 w-10 shrink-0 rounded-xl object-cover ring-1 ring-slate-200">
                                        @else
                                            <span class="grid place-items-center h-10 w-10 shrink-0 rounded-xl bg-emerald-100 text-emerald-700 font-semibold">
                                                {{ strtoupper(substr($institucion->nombre, 0, 1)) }}
                                            </span>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="font-semibold text-slate-900 truncate">{{ $institucion->nombre }}</p>
                                            <p class="text-xs text-slate-400 md:hidden">{{ $institucion->lugar ?: '—' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-slate-600 hidden md:table-cell">{{ $institucion->lugar ?: '—' }}</td>
                                <td class="px-6 py-4 text-slate-600 hidden lg:table-cell">{{ $institucion->direccion ?: '—' }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-700">{{ $institucion->cursos_count }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <button wire:click="ver('{{ $institucion->id }}')" class="grid place-items-center h-9 w-9 rounded-lg text-slate-400 hover:bg-brand-50 hover:text-brand-600 transition" title="Ver">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>
                                        <button wire:click="editar('{{ $institucion->id }}')" class="grid place-items-center h-9 w-9 rounded-lg text-slate-400 hover:bg-amber-50 hover:text-amber-600 transition" title="Editar">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <button
                                            x-data
                                            @click="window.confirmarEliminar('Se eliminará la institución «{{ addslashes($institucion->nombre) }}».').then(r => { if (r.isConfirmed) $wire.eliminar('{{ $institucion->id }}') })"
                                            class="grid place-items-center h-9 w-9 rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600 transition" title="Eliminar">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center">
                                    <span class="grid place-items-center h-16 w-16 mx-auto rounded-2xl bg-slate-100 text-slate-400">
                                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.4" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    </span>
                                    <p class="mt-4 text-sm font-medium text-slate-600">Sin instituciones</p>
                                    <p class="text-sm text-slate-400">Crea tu primera institución con el botón «Nueva institución».</p>
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
        :title="$editingId ? 'Editar institución' : 'Nueva institución'"
        subtitle="Completa los datos de la universidad"
        icon="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
        icon-grad="from-emerald-500 to-teal-600"
        max-width="lg"
    >
        <form wire:submit="guardar" id="form-institucion" class="space-y-4">
            <!-- Logo -->
            <div>
                <x-input-label :value="__('Logo (imagen)')" />
                <div class="mt-1 flex items-center gap-4">
                    <div class="grid place-items-center h-16 w-16 shrink-0 rounded-xl bg-slate-100 ring-1 ring-slate-200 overflow-hidden">
                        @if ($logo)
                            <img src="{{ $logo->temporaryUrl() }}" alt="Vista previa" class="h-full w-full object-cover">
                        @elseif ($logoActual)
                            <img src="{{ Storage::url($logoActual) }}" alt="Logo actual" class="h-full w-full object-cover">
                        @else
                            <svg class="h-7 w-7 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.4" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        @endif
                    </div>
                    <div class="flex-1">
                        <input wire:model="logo" type="file" accept="image/*" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                        <p class="mt-1 text-xs text-slate-400">PNG o JPG, máx. 2 MB.</p>
                        <div wire:loading wire:target="logo" class="mt-1 text-xs text-brand-600">Subiendo imagen...</div>
                        <x-input-error :messages="$errors->get('logo')" class="mt-1" />
                    </div>
                </div>
            </div>
            <div>
                <x-input-label for="m_nombre" :value="__('Nombre')" />
                <x-text-input wire:model="nombre" id="m_nombre" class="block mt-1 w-full" type="text" placeholder="Universidad Nacional..." />
                <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="m_lugar" :value="__('Lugar')" />
                <x-text-input wire:model="lugar" id="m_lugar" class="block mt-1 w-full" type="text" placeholder="Ciudad o localidad" />
                <x-input-error :messages="$errors->get('lugar')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="m_direccion" :value="__('Dirección')" />
                <x-text-input wire:model="direccion" id="m_direccion" class="block mt-1 w-full" type="text" placeholder="Av. ..." />
                <x-input-error :messages="$errors->get('direccion')" class="mt-2" />
            </div>
        </form>

        <x-slot name="footer">
            <button type="button" wire:click="$set('showForm', false)" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100 transition">Cancelar</button>
            <button type="submit" form="form-institucion" wire:loading.attr="disabled" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700 transition disabled:opacity-60">
                <svg wire:loading wire:target="guardar" class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                {{ $editingId ? 'Guardar cambios' : 'Crear institución' }}
            </button>
        </x-slot>
    </x-modal-panel>
    @endif

    <!-- Modal ver -->
    @if ($showView)
    <x-modal-panel
        wire="showView"
        title="Detalle de institución"
        icon="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
        icon-grad="from-brand-500 to-brand-700"
        max-width="lg"
    >
        @if ($viewData)
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    @if (!empty($viewData['logo_url']))
                        <img src="{{ $viewData['logo_url'] }}" alt="Logo" class="h-14 w-14 rounded-2xl object-cover ring-1 ring-slate-200">
                    @else
                        <span class="grid place-items-center h-14 w-14 rounded-2xl bg-emerald-100 text-emerald-700 text-xl font-bold">
                            {{ strtoupper(substr($viewData['nombre'], 0, 1)) }}
                        </span>
                    @endif
                    <div>
                        <p class="text-lg font-bold text-slate-900">{{ $viewData['nombre'] }}</p>
                        <p class="text-sm text-slate-500">Registrada el {{ $viewData['creado'] }}</p>
                    </div>
                </div>
                <dl class="grid grid-cols-2 gap-4 pt-2">
                    <div class="rounded-xl bg-slate-50 p-4">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Lugar</dt>
                        <dd class="mt-1 font-semibold text-slate-800">{{ $viewData['lugar'] ?: '—' }}</dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Cursos</dt>
                        <dd class="mt-1 font-semibold text-slate-800">{{ $viewData['cursos_count'] }}</dd>
                    </div>
                    <div class="col-span-2 rounded-xl bg-slate-50 p-4">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Dirección</dt>
                        <dd class="mt-1 font-semibold text-slate-800">{{ $viewData['direccion'] ?: '—' }}</dd>
                    </div>
                </dl>
            </div>
        @endif
    </x-modal-panel>
    @endif
</div>
