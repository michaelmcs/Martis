<?php

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public bool $showView = false;
    public ?array $viewData = null;
    public string $buscar = '';

    public function ver(int $id): void
    {
        $docente = User::withCount([
            'cursos' => fn ($q) => $q->withoutGlobalScopes(),
            'instituciones' => fn ($q) => $q->withoutGlobalScopes(),
            'estudiantes' => fn ($q) => $q->withoutGlobalScopes(),
        ])->findOrFail($id);

        $this->viewData = [
            'name' => $docente->nombre_completo,
            'dni' => $docente->dni,
            'celular' => $docente->celular,
            'email' => $docente->email,
            'rol' => $docente->rol,
            'cursos_count' => $docente->cursos_count,
            'instituciones_count' => $docente->instituciones_count,
            'estudiantes_count' => $docente->estudiantes_count,
            'creado' => $docente->created_at?->format('d/m/Y'),
        ];
        $this->showView = true;
    }

    public function cambiarRol(int $id): void
    {
        $usuario = User::findOrFail($id);

        if ($usuario->id === auth()->id()) {
            $this->dispatch('notify', message: 'No puedes cambiar tu propio rol.', type: 'error');
            return;
        }

        $usuario->rol = $usuario->esAdmin() ? User::ROL_DOCENTE : User::ROL_ADMIN;
        $usuario->save();

        $this->dispatch('notify', message: 'Rol actualizado a '.$usuario->rol.'.', type: 'success');
    }

    public function eliminar(int $id): void
    {
        if ($id === auth()->id()) {
            $this->dispatch('notify', message: 'No puedes eliminar tu propia cuenta.', type: 'error');
            return;
        }

        User::findOrFail($id)->delete();
        $this->dispatch('notify', message: 'Cuenta eliminada.', type: 'success');
    }

    public function with(): array
    {
        $query = User::withCount([
            'cursos' => fn ($q) => $q->withoutGlobalScopes(),
            'estudiantes' => fn ($q) => $q->withoutGlobalScopes(),
        ])->latest();

        if ($this->buscar !== '') {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->buscar.'%')
                  ->orWhere('email', 'like', '%'.$this->buscar.'%')
                  ->orWhere('dni', 'like', '%'.$this->buscar.'%');
            });
        }

        return [
            'usuarios' => $query->get(),
            'totalDocentes' => User::where('rol', User::ROL_DOCENTE)->count(),
            'totalAdmins' => User::where('rol', User::ROL_ADMIN)->count(),
            'totalUsuarios' => User::count(),
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <span class="grid place-items-center h-11 w-11 rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z"/></svg>
            </span>
            <div>
                <h2 class="text-xl font-bold text-slate-900">Gestión de docentes</h2>
                <p class="text-sm text-slate-500">Administra las cuentas de la plataforma</p>
            </div>
        </div>
    </x-slot>

    <div class="w-full px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <!-- Indicadores -->
        <div class="grid gap-5 sm:grid-cols-3">
            <div class="rounded-2xl bg-white p-6 ring-1 ring-slate-200 shadow-sm flex items-center justify-between">
                <div><p class="text-sm font-medium text-slate-500">Total cuentas</p><p class="mt-1 text-3xl font-bold text-slate-900">{{ $totalUsuarios }}</p></div>
                <span class="grid place-items-center h-12 w-12 rounded-xl bg-slate-100 text-slate-600"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z"/></svg></span>
            </div>
            <div class="rounded-2xl bg-white p-6 ring-1 ring-slate-200 shadow-sm flex items-center justify-between">
                <div><p class="text-sm font-medium text-slate-500">Docentes</p><p class="mt-1 text-3xl font-bold text-slate-900">{{ $totalDocentes }}</p></div>
                <span class="grid place-items-center h-12 w-12 rounded-xl bg-brand-50 text-brand-600"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M12 14l9-5-9-5-9 5 9 5z"/></svg></span>
            </div>
            <div class="rounded-2xl bg-white p-6 ring-1 ring-slate-200 shadow-sm flex items-center justify-between">
                <div><p class="text-sm font-medium text-slate-500">Administradores</p><p class="mt-1 text-3xl font-bold text-slate-900">{{ $totalAdmins }}</p></div>
                <span class="grid place-items-center h-12 w-12 rounded-xl bg-amber-50 text-amber-600"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg></span>
            </div>
        </div>

        <!-- Tabla -->
        <div class="rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 border-b border-slate-100">
                <h3 class="text-base font-semibold text-slate-900">Cuentas</h3>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input wire:model.live.debounce.300ms="buscar" type="text" placeholder="Buscar docente..." class="w-64 max-w-full rounded-xl border-slate-300 pl-9 text-sm focus:border-brand-500 focus:ring-brand-500">
                </div>
            </div>

            <div class="overflow-x-auto scrollbar-thin">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/60 text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <th class="px-6 py-3">Usuario</th>
                            <th class="px-6 py-3 hidden md:table-cell">DNI</th>
                            <th class="px-6 py-3 hidden lg:table-cell">Correo</th>
                            <th class="px-6 py-3 text-center">Rol</th>
                            <th class="px-6 py-3 text-center hidden sm:table-cell">Cursos</th>
                            <th class="px-6 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($usuarios as $usuario)
                            <tr class="hover:bg-slate-50/70 transition" wire:key="user-{{ $usuario->id }}">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <span class="grid place-items-center h-10 w-10 shrink-0 rounded-full {{ $usuario->esAdmin() ? 'bg-amber-500' : 'bg-brand-500' }} text-white font-semibold">{{ strtoupper(substr($usuario->name, 0, 1)) }}</span>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-slate-900 truncate">{{ $usuario->nombre_completo }}
                                                @if ($usuario->id === auth()->id())
                                                    <span class="ml-1 text-xs font-medium text-slate-400">(tú)</span>
                                                @endif
                                            </p>
                                            <p class="text-xs text-slate-400 lg:hidden">{{ $usuario->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-slate-600 hidden md:table-cell">{{ $usuario->dni ?: '—' }}</td>
                                <td class="px-6 py-4 text-slate-600 hidden lg:table-cell">{{ $usuario->email }}</td>
                                <td class="px-6 py-4 text-center">
                                    @if ($usuario->esAdmin())
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-amber-200">Admin</span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-700 ring-1 ring-brand-200">Docente</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center text-slate-600 hidden sm:table-cell">{{ $usuario->cursos_count }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <button wire:click="ver({{ $usuario->id }})" class="grid place-items-center h-9 w-9 rounded-lg text-slate-400 hover:bg-brand-50 hover:text-brand-600 transition" title="Ver">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>
                                        @if ($usuario->id !== auth()->id())
                                            <button
                                                x-data
                                                @click="Swal.fire({title:'Cambiar rol', text:'¿Cambiar el rol de «{{ addslashes($usuario->name) }}»?', icon:'question', showCancelButton:true, confirmButtonColor:'#4f46e5', cancelButtonColor:'#64748b', confirmButtonText:'Sí, cambiar', cancelButtonText:'Cancelar', reverseButtons:true}).then(r => { if (r.isConfirmed) $wire.cambiarRol({{ $usuario->id }}) })"
                                                class="grid place-items-center h-9 w-9 rounded-lg text-slate-400 hover:bg-amber-50 hover:text-amber-600 transition" title="Cambiar rol">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                            </button>
                                            <button
                                                x-data
                                                @click="window.confirmarEliminar('Se eliminará la cuenta de «{{ addslashes($usuario->name) }}» y todos sus datos.').then(r => { if (r.isConfirmed) $wire.eliminar({{ $usuario->id }}) })"
                                                class="grid place-items-center h-9 w-9 rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600 transition" title="Eliminar">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-16 text-center text-sm text-slate-400">No se encontraron cuentas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal ver -->
    @if ($showView)
    <x-modal-panel
        wire="showView"
        title="Detalle del docente"
        icon="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
        icon-grad="from-brand-500 to-brand-700"
        max-width="lg"
    >
        @if ($viewData)
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <span class="grid place-items-center h-14 w-14 rounded-2xl {{ ($viewData['rol'] ?? '') === 'administrador' ? 'bg-amber-500' : 'bg-brand-500' }} text-white text-xl font-bold">
                        {{ strtoupper(substr($viewData['name'], 0, 1)) }}
                    </span>
                    <div>
                        <p class="text-lg font-bold text-slate-900">{{ $viewData['name'] }}</p>
                        <p class="text-sm text-slate-500">{{ ucfirst($viewData['rol']) }} · Desde {{ $viewData['creado'] }}</p>
                    </div>
                </div>
                <dl class="grid grid-cols-2 gap-4 pt-2">
                    <div class="rounded-xl bg-slate-50 p-4"><dt class="text-xs font-medium uppercase tracking-wide text-slate-400">DNI</dt><dd class="mt-1 font-semibold text-slate-800">{{ $viewData['dni'] ?: '—' }}</dd></div>
                    <div class="rounded-xl bg-slate-50 p-4"><dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Celular</dt><dd class="mt-1 font-semibold text-slate-800">{{ $viewData['celular'] ?: '—' }}</dd></div>
                    <div class="col-span-2 rounded-xl bg-slate-50 p-4"><dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Correo</dt><dd class="mt-1 font-semibold text-slate-800 truncate">{{ $viewData['email'] }}</dd></div>
                    <div class="rounded-xl bg-slate-50 p-4"><dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Cursos</dt><dd class="mt-1 font-semibold text-slate-800">{{ $viewData['cursos_count'] }}</dd></div>
                    <div class="rounded-xl bg-slate-50 p-4"><dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Instituciones</dt><dd class="mt-1 font-semibold text-slate-800">{{ $viewData['instituciones_count'] }}</dd></div>
                    <div class="col-span-2 rounded-xl bg-slate-50 p-4"><dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Estudiantes</dt><dd class="mt-1 font-semibold text-slate-800">{{ $viewData['estudiantes_count'] }}</dd></div>
                </dl>
            </div>
        @endif
    </x-modal-panel>
    @endif
</div>
