@props([
    'wire',
    'title' => null,
    'subtitle' => null,
    'icon' => null,
    'iconGrad' => 'from-brand-500 to-brand-700',
    'maxWidth' => '2xl',
])

@php
    // Tamaños ampliados: cada nivel es más grande que antes para modales más cómodos.
    $max = [
        'sm' => 'max-w-md', 'md' => 'max-w-xl', 'lg' => 'max-w-2xl',
        'xl' => 'max-w-3xl', '2xl' => 'max-w-4xl', '3xl' => 'max-w-5xl', '4xl' => 'max-w-6xl',
    ][$maxWidth] ?? 'max-w-3xl';
@endphp

<div class="fixed inset-0 z-[60] overflow-y-auto" wire:key="modal-{{ $wire }}">
    <!-- Backdrop (click cierra) -->
    <div wire:click="$set('{{ $wire }}', false)" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>

    <!-- Contenedor centrado -->
    <div class="relative flex min-h-full items-center justify-center p-4 pointer-events-none">
        <!-- Panel -->
        <div class="modal-panel pointer-events-auto relative w-full {{ $max }} rounded-2xl bg-white shadow-2xl ring-1 ring-slate-200">
            <!-- Header -->
            @if ($title)
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-8 py-6">
                    <div class="flex items-center gap-3">
                        @if ($icon)
                            <span class="grid place-items-center h-11 w-11 rounded-xl bg-gradient-to-br {{ $iconGrad }} text-white shadow">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="{{ $icon }}"/></svg>
                            </span>
                        @endif
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">{{ $title }}</h3>
                            @if ($subtitle)
                                <p class="text-sm text-slate-500">{{ $subtitle }}</p>
                            @endif
                        </div>
                    </div>
                    <button type="button" wire:click="$set('{{ $wire }}', false)" class="grid place-items-center h-9 w-9 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            @endif

            <!-- Body -->
            <div class="px-8 py-7">
                {{ $slot }}
            </div>

            @isset($footer)
                <div class="flex items-center justify-end gap-3 border-t border-slate-100 px-8 py-5 bg-slate-50/60 rounded-b-2xl">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>
