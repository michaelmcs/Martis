<?php

use App\Imports\EstudiantesImport;
use App\Models\Estudiante;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use Livewire\Volt\Component;
use Maatwebsite\Excel\Facades\Excel;

new #[Layout('layouts.app')] class extends Component
{
    use WithFileUploads;

    public bool $showForm = false;
    public bool $showView = false;
    public bool $showImport = false;
    public ?string $editingId = null;
    public ?array $viewData = null;

    public string $codigo = '';
    public string $nombres = '';
    public string $apellidos = '';
    public string $correo = '';
    public string $celular = '';

    public $archivo = null;
    public array $importResumen = [];
    public string $importMetodo = 'archivo'; // archivo | texto | imagen
    public string $textoPegar = '';

    // Modo Excel (captura masiva)
    public bool $modoExcel = false;
    public array $filas = [];

    public string $buscar = '';

    public function rules(): array
    {
        return [
            'codigo' => ['nullable', 'string', 'max:50'],
            'nombres' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'correo' => ['nullable', 'email', 'max:255'],
            'celular' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function nuevo(): void
    {
        $this->reset('codigo', 'nombres', 'apellidos', 'correo', 'celular', 'editingId');
        $this->resetValidation();
        $this->showForm = true;
    }

    public function editar(string $id): void
    {
        $estudiante = auth()->user()->estudiantes()->findOrFail($id);
        $this->editingId = $estudiante->id;
        $this->codigo = $estudiante->codigo ?? '';
        $this->nombres = $estudiante->nombres;
        $this->apellidos = $estudiante->apellidos ?? '';
        $this->correo = $estudiante->correo ?? '';
        $this->celular = $estudiante->celular ?? '';
        $this->resetValidation();
        $this->showForm = true;
    }

    public function ver(string $id): void
    {
        $estudiante = auth()->user()->estudiantes()->withCount('cursos')->findOrFail($id);
        $this->viewData = [
            'nombre_completo' => $estudiante->nombre_completo,
            'codigo' => $estudiante->codigo,
            'correo' => $estudiante->correo,
            'celular' => $estudiante->celular,
            'cursos_count' => $estudiante->cursos_count,
            'creado' => $estudiante->created_at?->format('d/m/Y'),
        ];
        $this->showView = true;
    }

    public function guardar(): void
    {
        $data = $this->validate();

        if ($this->editingId) {
            $estudiante = auth()->user()->estudiantes()->findOrFail($this->editingId);
            $estudiante->update($data);
            $this->dispatch('notify', message: 'Estudiante actualizado correctamente.', type: 'success');
        } else {
            auth()->user()->estudiantes()->create($data);
            $this->dispatch('notify', message: 'Estudiante registrado correctamente.', type: 'success');
        }

        $this->showForm = false;
        $this->reset('codigo', 'nombres', 'apellidos', 'correo', 'celular', 'editingId');
    }

    public function eliminar(string $id): void
    {
        $estudiante = auth()->user()->estudiantes()->findOrFail($id);
        $this->authorize('delete', $estudiante);
        $estudiante->delete();
        $this->dispatch('notify', message: 'Estudiante eliminado.', type: 'success');
    }

    /* ---------------- Importación ---------------- */
    public function abrirImport(): void
    {
        $this->reset('archivo', 'importResumen', 'textoPegar');
        $this->importMetodo = 'archivo';
        $this->resetValidation();
        $this->showImport = true;
    }

    public function descargarPlantilla()
    {
        return Excel::download(new \App\Exports\PlantillaEstudiantesExport, 'plantilla_estudiantes.xlsx');
    }

    public function importar(): void
    {
        $this->validate([
            'archivo' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:5120'],
        ]);

        $import = new EstudiantesImport;
        Excel::import($import, $this->archivo->getRealPath());

        $this->guardarFilas($import->rows);
        $this->reset('archivo');
    }

    public function importarTexto(): void
    {
        $this->validate(['textoPegar' => ['required', 'string']]);

        // Cada línea = un estudiante. Columnas separadas por tab, punto y coma o coma.
        // Orden: nombres, apellidos, codigo, correo, celular
        $filas = [];
        foreach (preg_split('/\r\n|\r|\n/', $this->textoPegar) as $linea) {
            $linea = trim($linea);
            if ($linea === '') {
                continue;
            }
            $partes = preg_split('/\t|;|,/', $linea);
            $partes = array_map('trim', $partes);

            // Saltar una posible fila de encabezados
            if (isset($partes[0]) && in_array(mb_strtolower($partes[0]), ['nombres', 'nombre'])) {
                continue;
            }

            $filas[] = [
                'nombres' => $partes[0] ?? '',
                'apellidos' => $partes[1] ?? '',
                'codigo' => $partes[2] ?? '',
                'correo' => $partes[3] ?? '',
                'celular' => $partes[4] ?? '',
            ];
        }

        $this->guardarFilas($filas);
        $this->reset('textoPegar');
    }

    private function guardarFilas(array $filas): void
    {
        $creados = 0;
        $actualizados = 0;
        $omitidos = 0;

        foreach ($filas as $fila) {
            $nombres = trim((string) ($fila['nombres'] ?? ''));
            $apellidos = trim((string) ($fila['apellidos'] ?? ''));

            if ($nombres === '' && $apellidos === '') {
                $omitidos++;
                continue;
            }

            $datos = [
                'nombres' => $nombres ?: 'Sin nombre',
                'apellidos' => $apellidos ?: null,
                'correo' => trim((string) ($fila['correo'] ?? '')) ?: null,
                'celular' => trim((string) ($fila['celular'] ?? '')) ?: null,
            ];
            $codigo = trim((string) ($fila['codigo'] ?? ''));

            if ($codigo !== '') {
                $existente = auth()->user()->estudiantes()->where('codigo', $codigo)->first();
                if ($existente) {
                    $existente->update($datos);
                    $actualizados++;
                    continue;
                }
                $datos['codigo'] = $codigo;
            }

            auth()->user()->estudiantes()->create($datos);
            $creados++;
        }

        $this->importResumen = compact('creados', 'actualizados', 'omitidos');
        $this->dispatch('notify', message: "Importación: {$creados} nuevos, {$actualizados} actualizados.", type: 'success');
    }

    /* ---------------- Modo Excel (captura masiva) ---------------- */
    private function filaVacia(): array
    {
        return ['nombres' => '', 'apellidos' => '', 'codigo' => '', 'correo' => '', 'celular' => ''];
    }

    public function abrirExcel(): void
    {
        $this->filas = array_fill(0, 8, $this->filaVacia());
        $this->modoExcel = true;
    }

    public function cerrarExcel(): void
    {
        $this->modoExcel = false;
        $this->filas = [];
    }

    public function agregarFila(int $cantidad = 1): void
    {
        for ($i = 0; $i < $cantidad; $i++) {
            $this->filas[] = $this->filaVacia();
        }
    }

    public function quitarFila(int $indice): void
    {
        unset($this->filas[$indice]);
        $this->filas = array_values($this->filas);
        if (empty($this->filas)) {
            $this->filas[] = $this->filaVacia();
        }
    }

    public function guardarExcel(): void
    {
        $this->guardarFilas($this->filas);
        $this->modoExcel = false;
        $this->filas = [];
    }

    public function with(): array
    {
        $query = auth()->user()->estudiantes()->latest();

        if ($this->buscar !== '') {
            $query->where(function ($q) {
                $q->where('nombres', 'like', '%'.$this->buscar.'%')
                  ->orWhere('apellidos', 'like', '%'.$this->buscar.'%')
                  ->orWhere('codigo', 'like', '%'.$this->buscar.'%')
                  ->orWhere('correo', 'like', '%'.$this->buscar.'%');
            });
        }

        return [
            'estudiantes' => $query->get(),
            'totalEstudiantes' => auth()->user()->estudiantes()->count(),
            'conCorreo' => auth()->user()->estudiantes()->whereNotNull('correo')->where('correo', '!=', '')->count(),
            'conCelular' => auth()->user()->estudiantes()->whereNotNull('celular')->where('celular', '!=', '')->count(),
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <span class="grid place-items-center h-11 w-11 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 text-white shadow">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M12 14l9-5-9-5-9 5 9 5z M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
            </span>
            <div>
                <h2 class="text-xl font-bold text-slate-900">{{ __('Estudiantes') }}</h2>
                <p class="text-sm text-slate-500">Tu registro de estudiantes</p>
            </div>
        </div>
    </x-slot>

    <div class="w-full px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <!-- Indicadores -->
        <div class="grid gap-5 sm:grid-cols-3">
            <div class="rounded-2xl bg-white p-6 ring-1 ring-slate-200 shadow-sm flex items-center justify-between">
                <div><p class="text-sm font-medium text-slate-500">Total estudiantes</p><p class="mt-1 text-3xl font-bold text-slate-900">{{ $totalEstudiantes }}</p></div>
                <span class="grid place-items-center h-12 w-12 rounded-xl bg-amber-50 text-amber-600"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z"/></svg></span>
            </div>
            <div class="rounded-2xl bg-white p-6 ring-1 ring-slate-200 shadow-sm flex items-center justify-between">
                <div><p class="text-sm font-medium text-slate-500">Con correo</p><p class="mt-1 text-3xl font-bold text-slate-900">{{ $conCorreo }}</p></div>
                <span class="grid place-items-center h-12 w-12 rounded-xl bg-brand-50 text-brand-600"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg></span>
            </div>
            <div class="rounded-2xl bg-white p-6 ring-1 ring-slate-200 shadow-sm flex items-center justify-between">
                <div><p class="text-sm font-medium text-slate-500">Con celular</p><p class="mt-1 text-3xl font-bold text-slate-900">{{ $conCelular }}</p></div>
                <span class="grid place-items-center h-12 w-12 rounded-xl bg-emerald-50 text-emerald-600"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg></span>
            </div>
        </div>

        <!-- Tabla -->
        <div class="rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 border-b border-slate-100">
                <h3 class="text-base font-semibold text-slate-900">Listado de estudiantes</h3>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input wire:model.live.debounce.300ms="buscar" type="text" placeholder="Buscar estudiante..." class="w-52 max-w-full rounded-xl border-slate-300 pl-9 text-sm focus:border-brand-500 focus:ring-brand-500">
                    </div>
                    <button wire:click="abrirExcel" class="inline-flex items-center gap-2 rounded-xl bg-green-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-green-800 transition whitespace-nowrap">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M3 14h18m-9-8v16M5 4h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z"/></svg>
                        Captura tipo Excel
                    </button>
                    <button wire:click="abrirImport" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 transition whitespace-nowrap">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Importar
                    </button>
                    <button wire:click="nuevo" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-brand-600/20 hover:bg-brand-700 transition whitespace-nowrap">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Nuevo
                    </button>
                </div>
            </div>

            {{-- ===== MODO EXCEL: captura masiva editable ===== --}}
            @if ($modoExcel)
                <div class="border-b border-slate-100 bg-green-50/40 px-6 py-3 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2 text-sm text-green-800">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M3 14h18m-9-8v16M5 4h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z"/></svg>
                        <span class="font-semibold">Modo Excel</span> — escribe directamente en las celdas. {{ count($filas) }} fila(s).
                    </div>
                    <div class="flex items-center gap-2">
                        <button wire:click="agregarFila(1)" class="rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50">+ 1 fila</button>
                        <button wire:click="agregarFila(5)" class="rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50">+ 5 filas</button>
                        <button wire:click="cerrarExcel" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-500 hover:bg-slate-100">Cancelar</button>
                        <button wire:click="guardarExcel" class="inline-flex items-center gap-1.5 rounded-lg bg-green-700 px-4 py-1.5 text-xs font-semibold text-white hover:bg-green-800 transition">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Guardar todos
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto scrollbar-thin">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr class="bg-slate-100 text-xs font-bold uppercase tracking-wide text-slate-500">
                                <th class="px-2 py-2 w-10 text-center border border-slate-200">#</th>
                                <th class="px-3 py-2 border border-slate-200">Nombres *</th>
                                <th class="px-3 py-2 border border-slate-200">Apellidos *</th>
                                <th class="px-3 py-2 border border-slate-200">Código</th>
                                <th class="px-3 py-2 border border-slate-200">Correo</th>
                                <th class="px-3 py-2 border border-slate-200">Celular</th>
                                <th class="px-2 py-2 w-10 border border-slate-200"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($filas as $i => $fila)
                                <tr wire:key="fila-{{ $i }}" class="hover:bg-green-50/30">
                                    <td class="px-2 py-1 text-center text-xs text-slate-400 border border-slate-200 bg-slate-50">{{ $i + 1 }}</td>
                                    <td class="p-0 border border-slate-200"><input wire:model.blur="filas.{{ $i }}.nombres" type="text" class="w-full border-0 bg-transparent px-3 py-2 text-sm focus:ring-2 focus:ring-inset focus:ring-green-500" placeholder="Ej. Juan Carlos"></td>
                                    <td class="p-0 border border-slate-200"><input wire:model.blur="filas.{{ $i }}.apellidos" type="text" class="w-full border-0 bg-transparent px-3 py-2 text-sm focus:ring-2 focus:ring-inset focus:ring-green-500" placeholder="Ej. Pérez Gómez"></td>
                                    <td class="p-0 border border-slate-200"><input wire:model.blur="filas.{{ $i }}.codigo" type="text" class="w-full border-0 bg-transparent px-3 py-2 text-sm focus:ring-2 focus:ring-inset focus:ring-green-500" placeholder="20211234"></td>
                                    <td class="p-0 border border-slate-200"><input wire:model.blur="filas.{{ $i }}.correo" type="email" class="w-full border-0 bg-transparent px-3 py-2 text-sm focus:ring-2 focus:ring-inset focus:ring-green-500" placeholder="correo@ejemplo.com"></td>
                                    <td class="p-0 border border-slate-200"><input wire:model.blur="filas.{{ $i }}.celular" type="text" class="w-full border-0 bg-transparent px-3 py-2 text-sm focus:ring-2 focus:ring-inset focus:ring-green-500" placeholder="987654321"></td>
                                    <td class="px-2 py-1 text-center border border-slate-200">
                                        <button wire:click="quitarFila({{ $i }})" class="grid place-items-center h-7 w-7 mx-auto rounded text-slate-400 hover:bg-red-50 hover:text-red-600 transition" title="Quitar fila">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-3 bg-slate-50/40 text-xs text-slate-500">
                    Las filas vacías se ignoran. Si el código ya existe, ese estudiante se actualiza. Pulsa <strong>«Guardar todos»</strong> para crearlos.
                </div>
            @else

            <div class="overflow-x-auto scrollbar-thin">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/60 text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <th class="px-6 py-3">Estudiante</th>
                            <th class="px-6 py-3 hidden sm:table-cell">Código</th>
                            <th class="px-6 py-3 hidden lg:table-cell">Correo</th>
                            <th class="px-6 py-3 hidden md:table-cell">Celular</th>
                            <th class="px-6 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($estudiantes as $estudiante)
                            <tr class="hover:bg-slate-50/70 transition" wire:key="est-{{ $estudiante->id }}">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <span class="grid place-items-center h-10 w-10 shrink-0 rounded-full bg-amber-100 text-amber-700 font-semibold">
                                            {{ strtoupper(substr($estudiante->nombres, 0, 1)).strtoupper(substr($estudiante->apellidos ?? '', 0, 1)) }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-slate-900 truncate">{{ $estudiante->nombre_completo }}</p>
                                            <p class="text-xs text-slate-400 sm:hidden">{{ $estudiante->codigo ?: '—' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 hidden sm:table-cell">
                                    @if ($estudiante->codigo)
                                        <span class="inline-flex items-center rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">{{ $estudiante->codigo }}</span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-slate-600 hidden lg:table-cell">{{ $estudiante->correo ?: '—' }}</td>
                                <td class="px-6 py-4 text-slate-600 hidden md:table-cell">{{ $estudiante->celular ?: '—' }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <button wire:click="ver('{{ $estudiante->id }}')" class="grid place-items-center h-9 w-9 rounded-lg text-slate-400 hover:bg-brand-50 hover:text-brand-600 transition" title="Ver">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>
                                        <button wire:click="editar('{{ $estudiante->id }}')" class="grid place-items-center h-9 w-9 rounded-lg text-slate-400 hover:bg-amber-50 hover:text-amber-600 transition" title="Editar">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <button
                                            x-data
                                            @click="window.confirmarEliminar('Se eliminará al estudiante «{{ addslashes($estudiante->nombre_completo) }}».').then(r => { if (r.isConfirmed) $wire.eliminar('{{ $estudiante->id }}') })"
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
                                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.4" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z"/></svg>
                                    </span>
                                    <p class="mt-4 text-sm font-medium text-slate-600">Sin estudiantes</p>
                                    <p class="text-sm text-slate-400">Registra tu primer estudiante con el botón «Nuevo estudiante».</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

    <!-- Modal crear / editar -->
    @if ($showForm)
    <x-modal-panel
        wire="showForm"
        :title="$editingId ? 'Editar estudiante' : 'Nuevo estudiante'"
        subtitle="Completa los datos del estudiante"
        icon="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z"
        icon-grad="from-amber-500 to-orange-600"
        max-width="xl"
    >
        <form wire:submit="guardar" id="form-estudiante" class="space-y-4">
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="e_nombres" :value="__('Nombres')" />
                    <x-text-input wire:model="nombres" id="e_nombres" class="block mt-1 w-full" type="text" placeholder="Ej. Juan Carlos" />
                    <x-input-error :messages="$errors->get('nombres')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="e_apellidos" :value="__('Apellidos')" />
                    <x-text-input wire:model="apellidos" id="e_apellidos" class="block mt-1 w-full" type="text" placeholder="Ej. Pérez Gómez" />
                    <x-input-error :messages="$errors->get('apellidos')" class="mt-2" />
                </div>
            </div>
            <div>
                <x-input-label for="e_codigo" :value="__('Código universitario')" />
                <x-text-input wire:model="codigo" id="e_codigo" class="block mt-1 w-full" type="text" placeholder="Ej. 20211234" />
                <x-input-error :messages="$errors->get('codigo')" class="mt-2" />
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="e_correo" :value="__('Correo')" />
                    <x-text-input wire:model="correo" id="e_correo" class="block mt-1 w-full" type="email" placeholder="estudiante@correo.com" />
                    <x-input-error :messages="$errors->get('correo')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="e_celular" :value="__('Celular')" />
                    <x-text-input wire:model="celular" id="e_celular" class="block mt-1 w-full" type="text" placeholder="Ej. 987 654 321" />
                    <x-input-error :messages="$errors->get('celular')" class="mt-2" />
                </div>
            </div>
        </form>

        <x-slot name="footer">
            <button type="button" wire:click="$set('showForm', false)" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100 transition">Cancelar</button>
            <button type="submit" form="form-estudiante" wire:loading.attr="disabled" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-700 transition disabled:opacity-60">
                <svg wire:loading wire:target="guardar" class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                {{ $editingId ? 'Guardar cambios' : 'Registrar estudiante' }}
            </button>
        </x-slot>
    </x-modal-panel>
    @endif

    <!-- Modal ver -->
    @if ($showView)
    <x-modal-panel
        wire="showView"
        title="Detalle del estudiante"
        icon="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
        icon-grad="from-amber-500 to-orange-600"
        max-width="lg"
    >
        @if ($viewData)
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <span class="grid place-items-center h-14 w-14 rounded-2xl bg-amber-100 text-amber-700 text-xl font-bold">
                        {{ strtoupper(substr($viewData['nombre_completo'], 0, 1)) }}
                    </span>
                    <div>
                        <p class="text-lg font-bold text-slate-900">{{ $viewData['nombre_completo'] }}</p>
                        <p class="text-sm text-slate-500">Registrado el {{ $viewData['creado'] }}</p>
                    </div>
                </div>
                <dl class="grid grid-cols-2 gap-4 pt-2">
                    <div class="rounded-xl bg-slate-50 p-4"><dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Código</dt><dd class="mt-1 font-semibold text-slate-800">{{ $viewData['codigo'] ?: '—' }}</dd></div>
                    <div class="rounded-xl bg-slate-50 p-4"><dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Cursos</dt><dd class="mt-1 font-semibold text-slate-800">{{ $viewData['cursos_count'] }}</dd></div>
                    <div class="rounded-xl bg-slate-50 p-4"><dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Correo</dt><dd class="mt-1 font-semibold text-slate-800 truncate">{{ $viewData['correo'] ?: '—' }}</dd></div>
                    <div class="rounded-xl bg-slate-50 p-4"><dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Celular</dt><dd class="mt-1 font-semibold text-slate-800">{{ $viewData['celular'] ?: '—' }}</dd></div>
                </dl>
            </div>
        @endif
    </x-modal-panel>
    @endif

    <!-- Modal importar estudiantes -->
    @if ($showImport)
    <x-modal-panel
        wire="showImport"
        title="Importar estudiantes"
        subtitle="Desde Excel, texto pegado o una imagen"
        icon="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
        icon-grad="from-emerald-500 to-teal-600"
        max-width="2xl"
    >
        <div class="space-y-4">
            <!-- Selector de método -->
            <div class="grid grid-cols-3 gap-2 rounded-xl bg-slate-100 p-1">
                @php
                    $metodos = [
                        'archivo' => ['Excel/CSV', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                        'texto' => ['Pegar texto', 'M8 5H6a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2v-1m-6-8h6m-6 4h6m2-9v6m3-3h-6'],
                        'imagen' => ['Desde imagen', 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
                    ];
                @endphp
                @foreach ($metodos as $key => [$label, $icon])
                    <button type="button" wire:click="$set('importMetodo', '{{ $key }}')"
                        @class([
                            'flex items-center justify-center gap-1.5 rounded-lg px-2 py-2 text-sm font-semibold transition',
                            'bg-white text-brand-700 shadow-sm' => $importMetodo === $key,
                            'text-slate-500 hover:text-slate-700' => $importMetodo !== $key,
                        ])>
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="{{ $icon }}"/></svg>
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div class="rounded-xl bg-emerald-50 border border-emerald-100 p-3 text-xs text-emerald-800 flex items-center justify-between gap-2">
                <span>Columnas: <span class="font-mono bg-white px-1.5 py-0.5 rounded">nombres · apellidos · codigo · correo · celular</span></span>
                <button type="button" wire:click="descargarPlantilla" class="inline-flex items-center gap-1 font-semibold text-emerald-700 hover:text-emerald-900 whitespace-nowrap">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Plantilla Excel
                </button>
            </div>

            {{-- Método: Archivo --}}
            @if ($importMetodo === 'archivo')
                <div>
                    <x-input-label :value="__('Archivo (.xlsx, .xls o .csv)')" />
                    <input wire:model="archivo" type="file" accept=".xlsx,.xls,.csv" class="mt-1 block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-emerald-700 hover:file:bg-emerald-100">
                    <div wire:loading wire:target="archivo" class="mt-1 text-xs text-emerald-600">Subiendo archivo...</div>
                    <x-input-error :messages="$errors->get('archivo')" class="mt-2" />
                </div>
            @endif

            {{-- Método: Pegar texto --}}
            @if ($importMetodo === 'texto')
                <div>
                    <x-input-label :value="__('Pega la lista (una fila por estudiante)')" />
                    <p class="text-xs text-slate-400 mb-1">Copia desde Excel o escribe. Separa columnas con tabulación, coma o punto y coma.</p>
                    <textarea wire:model="textoPegar" rows="7" class="w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-xl shadow-sm text-slate-900 text-sm font-mono" placeholder="Juan Carlos, Pérez Gómez, 20211234, juan@correo.com, 987654321
Ana María, Quispe Flores, 20211235, ana@correo.com, 987111222"></textarea>
                    <x-input-error :messages="$errors->get('textoPegar')" class="mt-2" />
                </div>
            @endif

            {{-- Método: Desde imagen (OCR) --}}
            @if ($importMetodo === 'imagen')
                <div x-data="ocrImportador()">
                    <x-input-label :value="__('Imagen de la lista (foto o captura)')" />
                    <input type="file" accept="image/*" @change="procesar($event)" class="mt-1 block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-emerald-700 hover:file:bg-emerald-100">
                    <div x-show="cargando" class="mt-2 flex items-center gap-2 text-sm text-emerald-600">
                        <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                        Extrayendo texto de la imagen... <span x-text="progreso"></span>
                    </div>
                    <p class="mt-2 text-xs text-slate-400">Se extrae el texto y aparece abajo para que lo revises y corrijas antes de importar. Funciona mejor con listas escritas a máquina y buena iluminación.</p>
                    <div class="mt-3">
                        <x-input-label :value="__('Texto extraído (revísalo)')" />
                        <textarea wire:model="textoPegar" rows="6" class="w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-xl shadow-sm text-slate-900 text-sm font-mono" placeholder="El texto extraído aparecerá aquí..."></textarea>
                        <x-input-error :messages="$errors->get('textoPegar')" class="mt-2" />
                    </div>
                </div>
            @endif

            @if (!empty($importResumen))
                <div class="rounded-xl bg-slate-50 p-4 text-sm">
                    <p class="font-semibold text-slate-800 mb-1">Resultado de la importación</p>
                    <ul class="text-slate-600 space-y-0.5">
                        <li>✅ {{ $importResumen['creados'] }} nuevos</li>
                        <li>🔄 {{ $importResumen['actualizados'] }} actualizados</li>
                        <li>⏭️ {{ $importResumen['omitidos'] }} omitidos (filas vacías)</li>
                    </ul>
                </div>
            @endif
        </div>

        <x-slot name="footer">
            <button type="button" wire:click="$set('showImport', false)" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100 transition">Cerrar</button>
            @if ($importMetodo === 'archivo')
                <button type="button" wire:click="importar" wire:loading.attr="disabled" wire:target="importar,archivo" @disabled(!$archivo) class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 transition disabled:opacity-50">
                    <svg wire:loading wire:target="importar" class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                    Importar archivo
                </button>
            @else
                <button type="button" wire:click="importarTexto" wire:loading.attr="disabled" wire:target="importarTexto" @disabled(blank($textoPegar)) class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 transition disabled:opacity-50">
                    <svg wire:loading wire:target="importarTexto" class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                    Importar lista
                </button>
            @endif
        </x-slot>
    </x-modal-panel>
    @endif
</div>

@script
<script>
    window.ocrImportador = () => ({
        cargando: false,
        progreso: '',
        async procesar(e) {
            const file = e.target.files[0];
            if (!file) return;
            this.cargando = true;
            this.progreso = '';
            try {
                const texto = await window.ocrDesdeImagen(file, (p) => { this.progreso = p; });
                // volcar el texto reconocido al campo de Livewire
                $wire.set('textoPegar', texto);
                window.toast && window.toast({ icon: 'success', title: 'Texto extraído. Revísalo antes de importar.' });
            } catch (err) {
                window.toast && window.toast({ icon: 'error', title: 'No se pudo procesar la imagen.' });
            } finally {
                this.cargando = false;
            }
        },
    });
</script>
@endscript
