<?php

use App\Support\AsistenteIA;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public string $tab = 'alternativas';

    // Calificar alternativas
    public string $clave = '';
    public string $respuestas = '';
    public ?array $resultado = null;

    // Generar preguntas
    public string $tema = '';
    public int $cantidad = 5;
    public string $tipoPregunta = 'alternativas';
    public string $preguntasGeneradas = '';

    public function calificar(): void
    {
        $this->validate([
            'clave' => ['required', 'string'],
            'respuestas' => ['required', 'string'],
        ], [], ['clave' => 'clave de respuestas', 'respuestas' => 'respuestas del estudiante']);

        $this->resultado = app(AsistenteIA::class)->calificarAlternativas($this->clave, $this->respuestas);
        $this->dispatch('notify', message: 'Nota sugerida: '.$this->resultado['nota_sugerida'].' (tentativa).', type: 'success');
    }

    public function generar(): void
    {
        $this->validate([
            'tema' => ['required', 'string', 'min:5'],
            'cantidad' => ['required', 'integer', 'min:1', 'max:20'],
            'tipoPregunta' => ['required', 'in:alternativas,desarrollo,vf'],
        ], [], ['tema' => 'tema o sílabo']);

        $asistente = app(AsistenteIA::class);
        if (! $asistente->iaDisponible()) {
            $this->dispatch('notify', message: 'Configura IA_API_KEY en el archivo .env para usar la IA.', type: 'error');

            return;
        }

        try {
            $this->preguntasGeneradas = $asistente->generarPreguntas($this->tema, $this->cantidad, $this->tipoPregunta);
            $this->dispatch('notify', message: 'Preguntas generadas.', type: 'success');
        } catch (\Throwable $e) {
            $this->dispatch('notify', message: 'No se pudo generar: '.$e->getMessage(), type: 'error');
        }
    }

    public function with(): array
    {
        return [
            'iaDisponible' => app(AsistenteIA::class)->iaDisponible(),
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <span class="grid place-items-center h-11 w-11 rounded-xl bg-gradient-to-br from-violet-500 to-purple-700 text-white shadow">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
            </span>
            <div>
                <h2 class="text-xl font-bold text-slate-900">Asistente de revisión (IA)</h2>
                <p class="text-sm text-slate-500">La herramienta asiste; el docente decide la nota final</p>
            </div>
        </div>
    </x-slot>

    <div class="w-full px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <!-- Tabs -->
        <div class="flex flex-wrap gap-2 border-b border-slate-200">
            @php
                $tabs = [
                    'alternativas' => ['Calificar alternativas', 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                    'preguntas' => ['Generar preguntas', 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                ];
            @endphp
            @foreach ($tabs as $key => [$label, $icon])
                <button wire:click="$set('tab', '{{ $key }}')"
                    @class([
                        'inline-flex items-center gap-2 px-4 py-3 text-sm font-semibold border-b-2 -mb-px transition',
                        'border-violet-600 text-violet-700' => $tab === $key,
                        'border-transparent text-slate-500 hover:text-slate-700' => $tab !== $key,
                    ])>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="{{ $icon }}"/></svg>
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- ============ CALIFICAR ALTERNATIVAS ============ --}}
        @if ($tab === 'alternativas')
            <div class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm p-6 space-y-4">
                    <div class="rounded-xl bg-violet-50 border border-violet-100 p-3 text-xs text-violet-800">
                        Compara la clave de respuestas con las del estudiante y calcula una <strong>nota tentativa</strong> (base 20). Funciona sin conexión. Acepta formatos como <span class="font-mono">1a 2c 3b</span>, <span class="font-mono">a,c,b</span> o <span class="font-mono">acb</span>.
                    </div>

                    <div>
                        <x-input-label for="clave" :value="__('Clave de respuestas (correctas)')" />
                        <textarea wire:model="clave" id="clave" rows="3" class="w-full border-slate-300 focus:border-violet-500 focus:ring-violet-500 rounded-xl shadow-sm text-slate-900 text-sm font-mono mt-1" placeholder="1) A  2) C  3) B  4) D  5) A"></textarea>
                        <x-input-error :messages="$errors->get('clave')" class="mt-2" />
                    </div>

                    <div x-data="ocrAsistente()">
                        <div class="flex items-center justify-between">
                            <x-input-label for="respuestas" :value="__('Respuestas del estudiante')" />
                            <label class="text-xs font-semibold text-violet-700 hover:text-violet-900 cursor-pointer">
                                <input type="file" accept="image/*" class="hidden" @change="procesar($event)">
                                📷 Extraer de imagen (OCR)
                            </label>
                        </div>
                        <textarea wire:model="respuestas" id="respuestas" rows="3" class="w-full border-slate-300 focus:border-violet-500 focus:ring-violet-500 rounded-xl shadow-sm text-slate-900 text-sm font-mono mt-1" placeholder="1) A  2) B  3) B  4) D  5) C"></textarea>
                        <div x-show="cargando" class="mt-1 flex items-center gap-2 text-xs text-violet-600">
                            <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                            Extrayendo texto... <span x-text="progreso"></span>
                        </div>
                        <x-input-error :messages="$errors->get('respuestas')" class="mt-2" />
                    </div>

                    <button wire:click="calificar" class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-violet-700 transition">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/></svg>
                        Calificar
                    </button>
                </div>

                <div class="rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm p-6">
                    @if ($resultado)
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-base font-semibold text-slate-900">Resultado</h3>
                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-amber-200">Nota tentativa</span>
                        </div>
                        <div class="grid grid-cols-3 gap-3 mb-4">
                            <div class="rounded-xl bg-emerald-50 p-3 text-center"><p class="text-2xl font-bold text-emerald-700">{{ $resultado['correctas'] }}</p><p class="text-xs text-emerald-600">Correctas</p></div>
                            <div class="rounded-xl bg-red-50 p-3 text-center"><p class="text-2xl font-bold text-red-700">{{ $resultado['incorrectas'] }}</p><p class="text-xs text-red-600">Incorrectas</p></div>
                            <div class="rounded-xl bg-violet-50 p-3 text-center"><p class="text-2xl font-bold text-violet-700">{{ number_format($resultado['nota_sugerida'], 2) }}</p><p class="text-xs text-violet-600">Nota / 20</p></div>
                        </div>
                        <div class="overflow-hidden rounded-xl ring-1 ring-slate-200">
                            <table class="w-full text-sm">
                                <thead><tr class="bg-slate-50 text-xs font-semibold uppercase text-slate-500"><th class="px-3 py-2 text-left">N°</th><th class="px-3 py-2 text-center">Correcta</th><th class="px-3 py-2 text-center">Marcada</th><th class="px-3 py-2 text-center">Estado</th></tr></thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($resultado['detalle'] as $d)
                                        <tr>
                                            <td class="px-3 py-2 text-slate-600">{{ $d['numero'] }}</td>
                                            <td class="px-3 py-2 text-center font-semibold text-slate-800">{{ $d['correcta'] }}</td>
                                            <td class="px-3 py-2 text-center {{ $d['acierto'] ? 'text-slate-800' : 'text-red-600 font-semibold' }}">{{ $d['marcada'] }}</td>
                                            <td class="px-3 py-2 text-center">
                                                @if ($d['acierto'])
                                                    <span class="text-emerald-600">✓</span>
                                                @else
                                                    <span class="text-red-500">✗</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="mt-3 text-xs text-slate-400">Esta nota es una sugerencia. El docente revisa y decide la nota final.</p>
                    @else
                        <div class="grid place-items-center h-full min-h-[16rem] text-center">
                            <div>
                                <span class="grid place-items-center h-14 w-14 mx-auto rounded-2xl bg-slate-100 text-slate-400"><svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.4" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>
                                <p class="mt-3 text-sm text-slate-500">Ingresa la clave y las respuestas, luego «Calificar».</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- ============ GENERAR PREGUNTAS ============ --}}
        @if ($tab === 'preguntas')
            <div class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm p-6 space-y-4">
                    @unless ($iaDisponible)
                        <div class="rounded-xl bg-amber-50 border border-amber-200 p-3 text-xs text-amber-800">
                            ⚠️ Las funciones con IA requieren una clave. Agrega <span class="font-mono">IA_API_KEY=tu-clave</span> en el archivo <span class="font-mono">.env</span> y reinicia el servidor. La llamada al modelo se hace siempre desde el servidor.
                        </div>
                    @endunless
                    <div>
                        <x-input-label for="tema" :value="__('Tema o contenido del sílabo')" />
                        <textarea wire:model="tema" id="tema" rows="5" class="w-full border-slate-300 focus:border-violet-500 focus:ring-violet-500 rounded-xl shadow-sm text-slate-900 text-sm mt-1" placeholder="Ej. Estructuras de datos: pilas, colas y listas enlazadas. Complejidad algorítmica..."></textarea>
                        <x-input-error :messages="$errors->get('tema')" class="mt-2" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="cantidad" :value="__('Cantidad')" />
                            <x-text-input wire:model="cantidad" id="cantidad" class="block mt-1 w-full" type="number" min="1" max="20" />
                        </div>
                        <div>
                            <x-input-label for="tipoPregunta" :value="__('Tipo')" />
                            <select wire:model="tipoPregunta" id="tipoPregunta" class="w-full border-slate-300 focus:border-violet-500 focus:ring-violet-500 rounded-xl shadow-sm text-slate-900 mt-1">
                                <option value="alternativas">Opción múltiple</option>
                                <option value="desarrollo">Desarrollo</option>
                                <option value="vf">Verdadero / Falso</option>
                            </select>
                        </div>
                    </div>
                    <button wire:click="generar" wire:loading.attr="disabled" wire:target="generar" @disabled(!$iaDisponible) class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-violet-700 transition disabled:opacity-50">
                        <svg wire:loading.remove wire:target="generar" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                        <svg wire:loading wire:target="generar" class="animate-spin h-5 w-5" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                        <span wire:loading.remove wire:target="generar">Generar preguntas</span>
                        <span wire:loading wire:target="generar">Generando...</span>
                    </button>
                </div>

                <div class="rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm p-6">
                    <h3 class="text-base font-semibold text-slate-900 mb-3">Preguntas generadas</h3>
                    @if ($preguntasGeneradas !== '')
                        <div class="prose prose-sm max-w-none whitespace-pre-wrap text-sm text-slate-700 leading-relaxed">{{ $preguntasGeneradas }}</div>
                    @else
                        <div class="grid place-items-center h-full min-h-[16rem] text-center">
                            <div>
                                <span class="grid place-items-center h-14 w-14 mx-auto rounded-2xl bg-slate-100 text-slate-400"><svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.4" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01"/></svg></span>
                                <p class="mt-3 text-sm text-slate-500">Escribe el tema y genera preguntas para tu examen.</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

@script
<script>
    window.ocrAsistente = () => ({
        cargando: false,
        progreso: '',
        async procesar(e) {
            const file = e.target.files[0];
            if (!file) return;
            this.cargando = true;
            this.progreso = '';
            try {
                const texto = await window.ocrDesdeImagen(file, (p) => { this.progreso = p; });
                $wire.set('respuestas', texto);
                window.toast && window.toast({ icon: 'success', title: 'Texto extraído. Revísalo antes de calificar.' });
            } catch (err) {
                window.toast && window.toast({ icon: 'error', title: 'No se pudo procesar la imagen.' });
            } finally {
                this.cargando = false;
            }
        },
    });
</script>
@endscript
