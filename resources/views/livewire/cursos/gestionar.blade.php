<?php

use App\Models\Criterio;
use App\Models\Curso;
use App\Models\Evaluacion;
use App\Models\Matricula;
use App\Models\Nota;
use App\Models\Unidad;
use App\Support\CalculadoraNotas;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public Curso $curso;

    #[Url]
    public string $tab = 'estudiantes';

    // Matrícula
    public bool $showMatricular = false;
    public array $seleccion = [];

    // Unidad
    public bool $showUnidad = false;
    public ?string $unidadEditId = null;
    public int $u_numero = 1;
    public string $u_nombre = '';
    public int $u_peso = 0;

    // Evaluación
    public bool $showEvaluacion = false;
    public ?string $evalEditId = null;
    public ?string $eval_unidad_id = null;
    public string $e_titulo = '';
    public string $e_tipo = 'trabajo';
    public string $e_descripcion = '';
    public int $e_peso = 0;
    public ?string $e_fecha_limite = null;

    // Criterio
    public bool $showCriterio = false;
    public ?string $critEditId = null;
    public ?string $crit_evaluacion_id = null;
    public string $cr_nombre = '';
    public $cr_puntaje_max = 0;

    // Notas
    public ?string $notasEvaluacionId = null;
    public array $notas = [];

    // Asistencia
    public bool $showSesion = false;
    public ?string $sesionEditId = null;
    public ?string $s_fecha = null;
    public string $s_tipo = 'clase';
    public string $s_tema = '';
    public string $s_enlace = '';
    public ?string $asistenciaSesionId = null;
    public array $asistencias = [];
    public string $buscarAsistencia = '';

    // Ficha del estudiante
    public bool $showFicha = false;
    public ?array $ficha = null;

    // Horario
    public bool $showHorario = false;
    public ?string $horarioEditId = null;
    public int $h_dia = 1;
    public ?string $h_hora_inicio = '08:00';
    public ?string $h_hora_fin = '10:00';
    public string $h_aula = '';

    // Generar clases del ciclo
    public bool $showGenerar = false;
    public ?string $gen_fecha_inicio = null;
    public int $gen_semanas = 16;

    public function mount(string $cursoId): void
    {
        $this->curso = auth()->user()->cursos()->findOrFail($cursoId);
    }

    /* ---------------- Matrícula ---------------- */
    public function abrirMatricular(): void
    {
        $this->seleccion = [];
        $this->showMatricular = true;
    }

    private function idsDisponibles(): array
    {
        $inscritos = Matricula::where('curso_id', $this->curso->id)->pluck('estudiante_id')->all();

        return auth()->user()->estudiantes()->whereNotIn('id', $inscritos)->pluck('id')->all();
    }

    public function seleccionarTodos(): void
    {
        $this->seleccion = $this->idsDisponibles();
    }

    public function limpiarSeleccion(): void
    {
        $this->seleccion = [];
    }

    public function matricular(): void
    {
        foreach ($this->seleccion as $estudianteId) {
            Matricula::firstOrCreate([
                'curso_id' => $this->curso->id,
                'estudiante_id' => $estudianteId,
            ], ['estado' => 'activo']);
        }
        $this->showMatricular = false;
        $this->seleccion = [];
        $this->dispatch('notify', message: 'Estudiantes matriculados.', type: 'success');
    }

    public function desmatricular(string $matriculaId): void
    {
        Matricula::where('curso_id', $this->curso->id)->where('id', $matriculaId)->delete();
        $this->dispatch('notify', message: 'Estudiante retirado del curso.', type: 'success');
    }

    /* ---------------- Unidades ---------------- */
    public function nuevaUnidad(): void
    {
        $this->reset('unidadEditId', 'u_nombre', 'u_peso');
        $this->u_numero = ($this->curso->unidades()->max('numero') ?? 0) + 1;
        $this->resetValidation();
        $this->showUnidad = true;
    }

    public function editarUnidad(string $id): void
    {
        $u = $this->curso->unidades()->findOrFail($id);
        $this->unidadEditId = $u->id;
        $this->u_numero = $u->numero;
        $this->u_nombre = $u->nombre;
        $this->u_peso = $u->peso;
        $this->resetValidation();
        $this->showUnidad = true;
    }

    public function guardarUnidad(): void
    {
        $data = $this->validate([
            'u_numero' => ['required', 'integer', 'min:1'],
            'u_nombre' => ['required', 'string', 'max:255'],
            'u_peso' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        if ($this->unidadEditId) {
            $this->curso->unidades()->findOrFail($this->unidadEditId)->update([
                'numero' => $data['u_numero'], 'nombre' => $data['u_nombre'], 'peso' => $data['u_peso'],
            ]);
            $this->dispatch('notify', message: 'Unidad actualizada.', type: 'success');
        } else {
            $this->curso->unidades()->create([
                'numero' => $data['u_numero'], 'nombre' => $data['u_nombre'], 'peso' => $data['u_peso'],
            ]);
            $this->dispatch('notify', message: 'Unidad creada.', type: 'success');
        }
        $this->showUnidad = false;
    }

    public function eliminarUnidad(string $id): void
    {
        $this->curso->unidades()->findOrFail($id)->delete();
        $this->dispatch('notify', message: 'Unidad eliminada.', type: 'success');
    }

    /* ---------------- Evaluaciones ---------------- */
    public function nuevaEvaluacion(string $unidadId): void
    {
        $this->reset('evalEditId', 'e_titulo', 'e_descripcion', 'e_peso', 'e_fecha_limite');
        $this->e_tipo = 'trabajo';
        $this->eval_unidad_id = $unidadId;
        $this->resetValidation();
        $this->showEvaluacion = true;
    }

    public function editarEvaluacion(string $id): void
    {
        $e = $this->evaluacionDelCurso($id);
        $this->evalEditId = $e->id;
        $this->eval_unidad_id = $e->unidad_id;
        $this->e_titulo = $e->titulo;
        $this->e_tipo = $e->tipo;
        $this->e_descripcion = $e->descripcion ?? '';
        $this->e_peso = $e->peso;
        $this->e_fecha_limite = $e->fecha_limite?->format('Y-m-d');
        $this->resetValidation();
        $this->showEvaluacion = true;
    }

    public function guardarEvaluacion(): void
    {
        $data = $this->validate([
            'e_titulo' => ['required', 'string', 'max:255'],
            'e_tipo' => ['required', 'in:'.implode(',', array_keys(Evaluacion::TIPOS))],
            'e_descripcion' => ['nullable', 'string'],
            'e_peso' => ['required', 'integer', 'min:0', 'max:100'],
            'e_fecha_limite' => ['nullable', 'date'],
        ]);

        $payload = [
            'titulo' => $data['e_titulo'], 'tipo' => $data['e_tipo'],
            'descripcion' => $data['e_descripcion'] ?: null, 'peso' => $data['e_peso'],
            'fecha_limite' => $data['e_fecha_limite'] ?: null,
        ];

        if ($this->evalEditId) {
            $this->evaluacionDelCurso($this->evalEditId)->update($payload);
            $this->dispatch('notify', message: 'Evaluación actualizada.', type: 'success');
        } else {
            Unidad::where('curso_id', $this->curso->id)->findOrFail($this->eval_unidad_id)
                ->evaluaciones()->create($payload);
            $this->dispatch('notify', message: 'Evaluación creada.', type: 'success');
        }
        $this->showEvaluacion = false;
    }

    public function eliminarEvaluacion(string $id): void
    {
        $this->evaluacionDelCurso($id)->delete();
        $this->dispatch('notify', message: 'Evaluación eliminada.', type: 'success');
    }

    /* ---------------- Criterios ---------------- */
    public function nuevoCriterio(string $evaluacionId): void
    {
        $this->reset('critEditId', 'cr_nombre', 'cr_puntaje_max');
        $this->crit_evaluacion_id = $evaluacionId;
        $this->resetValidation();
        $this->showCriterio = true;
    }

    public function editarCriterio(string $id): void
    {
        $c = $this->criterioDelCurso($id);
        $this->critEditId = $c->id;
        $this->crit_evaluacion_id = $c->evaluacion_id;
        $this->cr_nombre = $c->nombre;
        $this->cr_puntaje_max = $c->puntaje_max;
        $this->resetValidation();
        $this->showCriterio = true;
    }

    public function guardarCriterio(): void
    {
        $data = $this->validate([
            'cr_nombre' => ['required', 'string', 'max:255'],
            'cr_puntaje_max' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        if ($this->critEditId) {
            $this->criterioDelCurso($this->critEditId)->update([
                'nombre' => $data['cr_nombre'], 'puntaje_max' => $data['cr_puntaje_max'],
            ]);
            $this->dispatch('notify', message: 'Criterio actualizado.', type: 'success');
        } else {
            $eval = $this->evaluacionDelCurso($this->crit_evaluacion_id);
            $eval->criterios()->create([
                'nombre' => $data['cr_nombre'],
                'puntaje_max' => $data['cr_puntaje_max'],
                'orden' => ($eval->criterios()->max('orden') ?? 0) + 1,
            ]);
            $this->dispatch('notify', message: 'Criterio agregado.', type: 'success');
        }
        $this->showCriterio = false;
    }

    public function eliminarCriterio(string $id): void
    {
        $this->criterioDelCurso($id)->delete();
        $this->dispatch('notify', message: 'Criterio eliminado.', type: 'success');
    }

    /* ---------------- Notas ---------------- */
    public function updatedNotasEvaluacionId($value): void
    {
        if ($value) {
            $this->cargarNotas($value);
        } else {
            $this->notas = [];
        }
    }

    public function cargarNotas(string $evaluacionId): void
    {
        $this->notasEvaluacionId = $evaluacionId;
        $eval = $this->evaluacionDelCurso($evaluacionId)->load('criterios', 'notas');
        $this->notas = [];
        foreach ($this->estudiantesMatriculados() as $est) {
            foreach ($eval->criterios as $crit) {
                $nota = $eval->notas->where('criterio_id', $crit->id)->where('estudiante_id', $est->id)->first();
                $this->notas[$est->id][$crit->id] = $nota?->valor;
            }
        }
    }

    public function guardarNotas(): void
    {
        $eval = $this->evaluacionDelCurso($this->notasEvaluacionId)->load('criterios');
        $maxPorCriterio = $eval->criterios->pluck('puntaje_max', 'id');

        foreach ($this->notas as $estId => $criterios) {
            foreach ($criterios as $critId => $valor) {
                if ($valor === '' || $valor === null) {
                    Nota::where('criterio_id', $critId)->where('estudiante_id', $estId)->delete();
                    continue;
                }
                $valor = min((float) $valor, (float) ($maxPorCriterio[$critId] ?? 100));
                Nota::updateOrCreate(
                    ['criterio_id' => $critId, 'estudiante_id' => $estId],
                    ['evaluacion_id' => $eval->id, 'valor' => $valor],
                );
            }
        }
        $this->dispatch('notify', message: 'Notas guardadas.', type: 'success');
    }

    /* ---------------- Asistencia ---------------- */
    public function nuevaSesion(): void
    {
        $this->reset('sesionEditId', 's_tema', 's_enlace');
        $this->s_tipo = 'clase';
        $this->s_fecha = now()->format('Y-m-d');
        $this->resetValidation();
        $this->showSesion = true;
    }

    public function editarSesion(string $id): void
    {
        $s = $this->curso->sesiones()->findOrFail($id);
        $this->sesionEditId = $s->id;
        $this->s_fecha = $s->fecha->format('Y-m-d');
        $this->s_tipo = $s->tipo ?? 'clase';
        $this->s_tema = $s->tema ?? '';
        $this->s_enlace = $s->enlace ?? '';
        $this->resetValidation();
        $this->showSesion = true;
    }

    public function guardarSesion(): void
    {
        $data = $this->validate([
            's_fecha' => ['required', 'date'],
            's_tipo' => ['required', 'in:'.implode(',', array_keys(\App\Models\Sesion::TIPOS))],
            's_tema' => ['nullable', 'string', 'max:255'],
            's_enlace' => ['nullable', 'url', 'max:500'],
        ], [], ['s_enlace' => 'enlace']);

        $payload = [
            'fecha' => $data['s_fecha'],
            'tipo' => $data['s_tipo'],
            'tema' => $data['s_tema'] ?: null,
            'enlace' => $data['s_enlace'] ?: null,
        ];

        // ¿la sesión cuenta para asistencia? (clase/virtual o con enlace)
        $lectiva = in_array($data['s_tipo'], ['clase', 'virtual'], true) || filled($data['s_enlace']);

        if ($this->sesionEditId) {
            $this->curso->sesiones()->findOrFail($this->sesionEditId)->update($payload);
            $this->dispatch('notify', message: 'Sesión actualizada.', type: 'success');
        } else {
            $sesion = $this->curso->sesiones()->create($payload);

            if ($lectiva) {
                // Todos inician como AUSENTES: el docente marca a los presentes.
                foreach ($this->estudiantesMatriculados() as $est) {
                    \App\Models\Asistencia::firstOrCreate(
                        ['sesion_id' => $sesion->id, 'estudiante_id' => $est->id],
                        ['estado' => 'ausente'],
                    );
                }
                $this->cargarAsistencia($sesion->id);
                $this->dispatch('notify', message: 'Sesión creada. Todos inician como ausentes: marca a los presentes.', type: 'success');
            } else {
                $this->dispatch('notify', message: 'Día marcado como '.\App\Models\Sesion::TIPOS[$data['s_tipo']].' (no cuenta para asistencia).', type: 'success');
            }
        }
        $this->showSesion = false;
    }

    public function eliminarSesion(string $id): void
    {
        $this->curso->sesiones()->findOrFail($id)->delete();
        if ($this->asistenciaSesionId === $id) {
            $this->asistenciaSesionId = null;
            $this->asistencias = [];
        }
        $this->dispatch('notify', message: 'Sesión eliminada.', type: 'success');
    }

    public function updatedAsistenciaSesionId($value): void
    {
        if ($value) {
            $this->cargarAsistencia($value);
        } else {
            $this->asistencias = [];
        }
    }

    public function cargarAsistencia(string $sesionId): void
    {
        $this->asistenciaSesionId = $sesionId;
        $sesion = $this->curso->sesiones()->with('asistencias')->findOrFail($sesionId);
        $this->asistencias = [];
        foreach ($this->estudiantesMatriculados() as $est) {
            $a = $sesion->asistencias->firstWhere('estudiante_id', $est->id);
            $this->asistencias[$est->id] = $a?->estado ?? 'ausente';
        }
    }

    public function marcar(string $estudianteId, string $estado): void
    {
        if (! $this->asistenciaSesionId || ! array_key_exists($estado, \App\Models\Asistencia::ESTADOS)) {
            return;
        }
        \App\Models\Asistencia::updateOrCreate(
            ['sesion_id' => $this->asistenciaSesionId, 'estudiante_id' => $estudianteId],
            ['estado' => $estado],
        );
        $this->asistencias[$estudianteId] = $estado;
    }

    public function marcarTodos(string $estado): void
    {
        foreach ($this->estudiantesMatriculados() as $est) {
            $this->marcar($est->id, $estado);
        }
        $this->dispatch('notify', message: 'Todos marcados como '.\App\Models\Asistencia::ESTADOS[$estado].'.', type: 'success');
    }

    // Marca asistencia para una sesión específica (usado desde el Cuaderno).
    public function marcarEn(string $sesionId, string $estudianteId, string $estado): void
    {
        if (! array_key_exists($estado, \App\Models\Asistencia::ESTADOS)) {
            return;
        }
        // la sesión debe pertenecer al curso
        if (! $this->curso->sesiones()->whereKey($sesionId)->exists()) {
            return;
        }
        \App\Models\Asistencia::updateOrCreate(
            ['sesion_id' => $sesionId, 'estudiante_id' => $estudianteId],
            ['estado' => $estado],
        );
    }

    /* ---------------- Ficha del estudiante ---------------- */
    public function verFicha(string $estudianteId): void
    {
        $est = \App\Models\Estudiante::findOrFail($estudianteId);

        // Asistencia: fechas faltadas y justificadas (solo días lectivos)
        $sesiones = $this->curso->sesiones()->lectivas()
            ->with(['asistencias' => fn ($q) => $q->where('estudiante_id', $estudianteId)])
            ->get();

        $faltas = [];
        $justificadas = [];
        foreach ($sesiones as $s) {
            $estado = $s->asistencias->first()?->estado;
            if ($estado === 'ausente') {
                $faltas[] = $s->fecha->format('d/m/Y');
            } elseif ($estado === 'justificado') {
                $justificadas[] = $s->fecha->format('d/m/Y');
            }
        }

        // Notas por evaluación (base 20) y pendientes de calificar
        $unidades = $this->curso->unidades()->with('evaluaciones.criterios')->get();
        $criterioIds = $unidades->flatMap->evaluaciones->flatMap->criterios->pluck('id');
        $notas = Nota::whereIn('criterio_id', $criterioIds)
            ->where('estudiante_id', $estudianteId)
            ->get()->keyBy('criterio_id');

        $evaluaciones = [];
        foreach ($unidades as $u) {
            foreach ($u->evaluaciones as $e) {
                $max = (float) $e->criterios->sum('puntaje_max');
                if ($max <= 0) {
                    continue;
                }
                $tiene = false;
                $obtenido = 0.0;
                foreach ($e->criterios as $c) {
                    if (isset($notas[$c->id])) {
                        $tiene = true;
                        $obtenido += (float) $notas[$c->id]->valor;
                    }
                }
                $evaluaciones[] = [
                    'unidad' => 'U'.$u->numero,
                    'titulo' => $e->titulo,
                    'calificada' => $tiene,
                    'nota20' => $tiene ? round(($obtenido / $max) * 20, 2) : null,
                ];
            }
        }

        $totalSes = $sesiones->count();
        $this->ficha = [
            'nombre' => $est->nombre_completo,
            'codigo' => $est->codigo,
            'total_sesiones' => $totalSes,
            'asistidas' => max(0, $totalSes - count($faltas)),
            'faltas' => $faltas,
            'justificadas' => $justificadas,
            'evaluaciones' => $evaluaciones,
            'pendientes' => count(array_filter($evaluaciones, fn ($e) => ! $e['calificada'])),
        ];
        $this->showFicha = true;
    }

    /* ---------------- Horario ---------------- */
    public function nuevoHorario(): void
    {
        $this->reset('horarioEditId', 'h_aula');
        $this->h_dia = 1;
        $this->h_hora_inicio = '08:00';
        $this->h_hora_fin = '10:00';
        $this->resetValidation();
        $this->showHorario = true;
    }

    public function editarHorario(string $id): void
    {
        $h = $this->curso->horarios()->findOrFail($id);
        $this->horarioEditId = $h->id;
        $this->h_dia = $h->dia;
        $this->h_hora_inicio = \Illuminate\Support\Carbon::parse($h->hora_inicio)->format('H:i');
        $this->h_hora_fin = \Illuminate\Support\Carbon::parse($h->hora_fin)->format('H:i');
        $this->h_aula = $h->aula ?? '';
        $this->resetValidation();
        $this->showHorario = true;
    }

    public function guardarHorario(): void
    {
        $data = $this->validate([
            'h_dia' => ['required', 'integer', 'between:1,7'],
            'h_hora_inicio' => ['required', 'date_format:H:i'],
            'h_hora_fin' => ['required', 'date_format:H:i', 'after:h_hora_inicio'],
            'h_aula' => ['nullable', 'string', 'max:100'],
        ]);

        $payload = [
            'dia' => $data['h_dia'], 'hora_inicio' => $data['h_hora_inicio'],
            'hora_fin' => $data['h_hora_fin'], 'aula' => $data['h_aula'] ?: null,
        ];

        if ($this->horarioEditId) {
            $this->curso->horarios()->findOrFail($this->horarioEditId)->update($payload);
            $this->dispatch('notify', message: 'Horario actualizado.', type: 'success');
        } else {
            $this->curso->horarios()->create($payload);
            $this->dispatch('notify', message: 'Bloque de horario agregado.', type: 'success');
        }
        $this->showHorario = false;
    }

    public function eliminarHorario(string $id): void
    {
        $this->curso->horarios()->findOrFail($id)->delete();
        $this->dispatch('notify', message: 'Bloque de horario eliminado.', type: 'success');
    }

    public function abrirGenerar(): void
    {
        if ($this->curso->horarios()->count() === 0) {
            $this->dispatch('notify', message: 'Primero agrega al menos un bloque de horario (día y hora).', type: 'error');

            return;
        }
        $this->gen_semanas = $this->curso->semanas ?: 16;
        $this->gen_fecha_inicio = now()->format('Y-m-d');
        $this->showGenerar = true;
    }

    // Genera las sesiones de clase automáticamente según el horario semanal.
    public function generarSesiones(): void
    {
        $data = $this->validate([
            'gen_fecha_inicio' => ['required', 'date'],
            'gen_semanas' => ['required', 'integer', 'min:1', 'max:52'],
        ], [], ['gen_fecha_inicio' => 'fecha de inicio', 'gen_semanas' => 'semanas']);

        $horarios = $this->curso->horarios()->get();
        if ($horarios->isEmpty()) {
            $this->dispatch('notify', message: 'No hay horario definido.', type: 'error');

            return;
        }

        // guardar las semanas en el curso
        $this->curso->update(['semanas' => $data['gen_semanas']]);

        $matriculados = $this->estudiantesMatriculados();
        $creadas = 0;
        $feriados = 0;

        foreach ($horarios as $h) {
            // primera fecha (>= inicio) que caiga en el día de la semana del bloque
            $fecha = \Illuminate\Support\Carbon::parse($data['gen_fecha_inicio']);
            while ($fecha->dayOfWeekIso !== (int) $h->dia) {
                $fecha->addDay();
            }

            for ($i = 0; $i < $data['gen_semanas']; $i++) {
                $dia = $fecha->copy()->addWeeks($i);

                // evitar duplicados: una sesión por fecha
                if ($this->curso->sesiones()->whereDate('fecha', $dia->toDateString())->exists()) {
                    continue;
                }

                // feriado automático (Perú)
                $feriado = \App\Support\Feriados::nombre($dia);

                $sesion = $this->curso->sesiones()->create([
                    'fecha' => $dia->toDateString(),
                    'tipo' => $feriado ? 'feriado' : 'clase',
                    'tema' => $feriado ?: 'Semana '.($i + 1),
                ]);

                if ($feriado) {
                    $feriados++;
                    continue; // feriado: no se registra asistencia
                }

                foreach ($matriculados as $est) {
                    \App\Models\Asistencia::firstOrCreate(
                        ['sesion_id' => $sesion->id, 'estudiante_id' => $est->id],
                        ['estado' => 'ausente'],
                    );
                }
                $creadas++;
            }
        }

        $this->showGenerar = false;
        $msg = "Se generaron {$creadas} clases";
        $msg .= $feriados > 0 ? " y se marcaron {$feriados} feriado(s) automáticamente." : '.';
        $this->dispatch('notify', message: $msg, type: 'success');
    }

    public function enviarCalendario(): void
    {
        $destinatarios = $this->estudiantesMatriculados()
            ->filter(fn ($e) => filled($e->correo));

        if ($destinatarios->isEmpty()) {
            $this->dispatch('notify', message: 'Ningún estudiante matriculado tiene correo registrado.', type: 'error');

            return;
        }

        foreach ($destinatarios as $est) {
            \Illuminate\Support\Facades\Mail::to($est->correo)
                ->send(new \App\Mail\CalendarioCurso($this->curso, $est->nombre_completo));
        }

        $this->dispatch('notify', message: 'Calendario enviado a '.$destinatarios->count().' estudiante(s).', type: 'success');
    }

    /* ---------------- Helpers ---------------- */
    private function evaluacionDelCurso(string $id): Evaluacion
    {
        return Evaluacion::whereHas('unidad', fn ($q) => $q->where('curso_id', $this->curso->id))->findOrFail($id);
    }

    private function criterioDelCurso(string $id): Criterio
    {
        return Criterio::whereHas('evaluacion.unidad', fn ($q) => $q->where('curso_id', $this->curso->id))->findOrFail($id);
    }

    private function estudiantesMatriculados()
    {
        return \App\Models\Estudiante::whereIn('id',
            Matricula::where('curso_id', $this->curso->id)->pluck('estudiante_id')
        )->orderBy('apellidos')->orderBy('nombres')->get();
    }

    public function with(): array
    {
        $matriculas = Matricula::where('curso_id', $this->curso->id)
            ->with('estudiante')->get()
            ->sortBy(fn ($m) => $m->estudiante->apellidos.' '.$m->estudiante->nombres)->values();

        $inscritos = $matriculas->pluck('estudiante_id')->all();

        $unidades = $this->curso->unidades()->with(['evaluaciones' => fn ($q) => $q->orderBy('created_at'), 'evaluaciones.criterios'])->get();

        $evaluacionSel = $this->notasEvaluacionId
            ? $unidades->flatMap->evaluaciones->firstWhere('id', $this->notasEvaluacionId)
            : null;

        $sesiones = $this->curso->sesiones()->withCount([
            'asistencias as ausentes_count' => fn ($q) => $q->where('estado', 'ausente'),
        ])->get();

        // Estadísticas de asistencia por estudiante (solo días lectivos)
        $idsLectivas = $sesiones->filter(fn ($s) => $s->es_lectiva)->pluck('id');
        $totalSesiones = $idsLectivas->count();
        $ausenciasPorEstudiante = \App\Models\Asistencia::whereIn('sesion_id', $idsLectivas)
            ->where('estado', 'ausente')
            ->selectRaw('estudiante_id, count(*) as total')
            ->groupBy('estudiante_id')->pluck('total', 'estudiante_id');

        return [
            'matriculas' => $matriculas,
            'disponibles' => auth()->user()->estudiantes()->whereNotIn('id', $inscritos)->orderBy('apellidos')->get(),
            'unidades' => $unidades,
            'pesoUnidades' => $unidades->sum('peso'),
            'estudiantesMatriculados' => $this->estudiantesMatriculados(),
            'evaluacionSel' => $evaluacionSel,
            'sesiones' => $sesiones,
            'totalSesiones' => $totalSesiones,
            'ausenciasPorEstudiante' => $ausenciasPorEstudiante,
            'estudiantesAsistencia' => $this->estudiantesMatriculados()->filter(
                fn ($e) => $this->buscarAsistencia === ''
                    || str_contains(mb_strtolower($e->nombre_completo), mb_strtolower($this->buscarAsistencia))
                    || str_contains((string) $e->codigo, $this->buscarAsistencia)
            )->values(),
            'acta' => $this->tab === 'notafinal' ? CalculadoraNotas::paraCurso($this->curso) : null,
            'cuaderno' => $this->tab === 'cuaderno' ? CalculadoraNotas::cuaderno($this->curso) : null,
            'sesionesCuaderno' => $this->tab === 'cuaderno'
                ? $this->curso->sesiones()->orderBy('fecha')->get(['id', 'fecha', 'tema', 'tipo', 'enlace'])
                : collect(),
            'asistenciaCuaderno' => $this->tab === 'cuaderno'
                ? \App\Models\Asistencia::whereIn('sesion_id', $this->curso->sesiones()->pluck('id'))
                    ->get(['sesion_id', 'estudiante_id', 'estado'])
                    ->groupBy('estudiante_id')
                    ->map(fn ($g) => $g->pluck('estado', 'sesion_id'))
                : collect(),
            'horarios' => $this->curso->horarios()->get(),
            'eventos' => $this->eventosCalendario(),
            'conCorreo' => $this->estudiantesMatriculados()->filter(fn ($e) => filled($e->correo))->count(),
        ];
    }

    private function eventosCalendario(): array
    {
        $eventos = [];
        $evals = \App\Models\Evaluacion::whereHas('unidad', fn ($q) => $q->where('curso_id', $this->curso->id))
            ->whereNotNull('fecha_limite')->orderBy('fecha_limite')->get();

        foreach ($evals as $e) {
            $eventos[] = [
                'fecha' => \Illuminate\Support\Carbon::parse($e->fecha_limite),
                'tipo' => $e->tipo === 'examen' ? 'Examen' : 'Entrega',
                'titulo' => $e->titulo,
                'esExamen' => $e->tipo === 'examen',
            ];
        }

        usort($eventos, fn ($a, $b) => $a['fecha'] <=> $b['fecha']);

        return $eventos;
    }

    public function exportarActa()
    {
        $acta = CalculadoraNotas::paraCurso($this->curso);
        $nombre = 'acta_'.str($this->curso->nombre)->slug().'_'.$this->curso->anio.'.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ActaExport($acta, $this->curso),
            $nombre
        );
    }

    public function exportarCuaderno()
    {
        $cuaderno = CalculadoraNotas::cuaderno($this->curso);
        $nombre = 'cuaderno_'.str($this->curso->nombre)->slug().'_'.$this->curso->anio.'.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\CuadernoExport($cuaderno, $this->curso),
            $nombre
        );
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('cursos.index') }}" wire:navigate class="grid place-items-center h-9 w-9 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition" title="Volver">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <span class="grid place-items-center h-11 w-11 rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow font-bold">
                    {{ strtoupper(substr($curso->nombre, 0, 1)) }}
                </span>
                <div>
                    <h2 class="text-xl font-bold text-slate-900">{{ $curso->nombre }}</h2>
                    <p class="text-sm text-slate-500">{{ $curso->institucion?->nombre }} · {{ $curso->periodo_academico }}</p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="w-full px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <!-- Tabs -->
        <div class="flex flex-wrap gap-2 border-b border-slate-200">
            @php
                $tabs = [
                    'estudiantes' => ['Estudiantes', 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z'],
                    'asistencia' => ['Asistencia', 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                    'academico' => ['Unidades y evaluaciones', 'M4 6h16M4 10h16M4 14h10M4 18h10'],
                    'notas' => ['Notas', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                    'cuaderno' => ['Cuaderno (Excel)', 'M3 10h18M3 14h18m-9-8v16M5 4h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z'],
                    'notafinal' => ['Nota final', 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                    'horario' => ['Horario y calendario', 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                ];
            @endphp
            @foreach ($tabs as $key => [$label, $icon])
                <button wire:click="$set('tab', '{{ $key }}')"
                    @class([
                        'inline-flex items-center gap-2 px-4 py-3 text-sm font-semibold border-b-2 -mb-px transition',
                        'border-brand-600 text-brand-700' => $tab === $key,
                        'border-transparent text-slate-500 hover:text-slate-700' => $tab !== $key,
                    ])>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="{{ $icon }}"/></svg>
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- ============ TAB ESTUDIANTES ============ --}}
        @if ($tab === 'estudiantes')
            <div class="rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Estudiantes matriculados</h3>
                        <p class="text-sm text-slate-500">{{ $matriculas->count() }} matriculado(s)</p>
                    </div>
                    <button wire:click="abrirMatricular" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700 transition">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Matricular estudiante
                    </button>
                </div>
                <div class="overflow-x-auto scrollbar-thin">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/60 text-xs font-semibold uppercase tracking-wider text-slate-500">
                                <th class="px-6 py-3">Estudiante</th>
                                <th class="px-6 py-3 hidden sm:table-cell">Código</th>
                                <th class="px-6 py-3 hidden md:table-cell">Correo</th>
                                <th class="px-6 py-3 text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($matriculas as $m)
                                <tr class="hover:bg-slate-50/70 transition" wire:key="mat-{{ $m->id }}">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <span class="grid place-items-center h-9 w-9 rounded-full bg-amber-100 text-amber-700 text-sm font-semibold">{{ strtoupper(substr($m->estudiante->nombres,0,1)).strtoupper(substr($m->estudiante->apellidos ?? '',0,1)) }}</span>
                                            <p class="font-semibold text-slate-900">{{ $m->estudiante->nombre_completo }}</p>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-slate-600 hidden sm:table-cell">{{ $m->estudiante->codigo ?: '—' }}</td>
                                    <td class="px-6 py-4 text-slate-600 hidden md:table-cell">{{ $m->estudiante->correo ?: '—' }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <button x-data @click="window.confirmarEliminar('Se retirará a «{{ addslashes($m->estudiante->nombre_completo) }}» del curso.').then(r => { if (r.isConfirmed) $wire.desmatricular('{{ $m->id }}') })"
                                            class="inline-flex items-center gap-1 text-sm font-medium text-red-600 hover:text-red-800">Retirar</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-6 py-14 text-center text-sm text-slate-400">Aún no hay estudiantes matriculados. Usa «Matricular estudiante».</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- ============ TAB ASISTENCIA ============ --}}
        @if ($tab === 'asistencia')
            <!-- Barra compacta de sesiones (arriba) -->
            <div class="rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm px-4 py-3">
                <div class="flex items-center gap-3">
                    <span class="text-sm font-semibold text-slate-700 shrink-0">Sesiones</span>
                    <div class="flex-1 flex items-center gap-2 overflow-x-auto scrollbar-thin py-1">
                        @forelse ($sesiones as $sesion)
                            @php $sLect = $sesion->es_lectiva; @endphp
                            <button wire:click="cargarAsistencia('{{ $sesion->id }}')" wire:key="ses-{{ $sesion->id }}"
                                @class([
                                    'shrink-0 inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-semibold transition ring-1',
                                    'bg-brand-600 text-white ring-brand-600 shadow-sm' => $asistenciaSesionId === $sesion->id,
                                    'bg-red-50 text-red-600 ring-red-200 hover:bg-red-100' => $asistenciaSesionId !== $sesion->id && ! $sLect,
                                    'bg-slate-50 text-slate-600 ring-slate-200 hover:bg-brand-50 hover:text-brand-700' => $asistenciaSesionId !== $sesion->id && $sLect,
                                ])
                                title="{{ $sesion->tipo_label }}{{ $sesion->tema ? ' · '.$sesion->tema : '' }}">
                                {{ $sesion->fecha->format('d/m') }}
                                @if (! $sLect)
                                    <span class="rounded-full px-1.5 text-[10px] {{ $asistenciaSesionId === $sesion->id ? 'bg-white/20 text-white' : 'bg-red-100 text-red-600' }}">{{ \Illuminate\Support\Str::limit($sesion->tipo_label, 7, '') }}</span>
                                @elseif ($sesion->ausentes_count > 0)
                                    <span class="rounded-full px-1.5 text-[10px] {{ $asistenciaSesionId === $sesion->id ? 'bg-white/20 text-white' : 'bg-red-100 text-red-600' }}">{{ $sesion->ausentes_count }}F</span>
                                @endif
                            </button>
                        @empty
                            <span class="text-sm text-slate-400">Sin sesiones aún — crea la primera.</span>
                        @endforelse
                    </div>
                    <button wire:click="nuevaSesion" class="shrink-0 inline-flex items-center gap-1 rounded-xl bg-brand-600 px-3 py-2 text-xs font-semibold text-white hover:bg-brand-700 transition">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Nueva sesión
                    </button>
                </div>
            </div>

            <!-- Panel principal: tabla de asistencia grande -->
            <div class="rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm overflow-hidden">
                @php $sesionActual = $sesiones->firstWhere('id', $asistenciaSesionId); @endphp
                @if ($sesionActual)
                    <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 border-b border-slate-100">
                        <div class="flex items-center gap-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-base font-semibold text-slate-900">{{ $sesionActual->fecha->translatedFormat('l d \d\e F') }}</h3>
                                    @unless ($sesionActual->es_lectiva)
                                        <span class="inline-flex items-center rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-semibold text-red-700 ring-1 ring-red-200">{{ $sesionActual->tipo_label }}</span>
                                    @elseif ($sesionActual->tipo === 'virtual' || $sesionActual->enlace)
                                        <span class="inline-flex items-center rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-700 ring-1 ring-brand-200">Virtual</span>
                                    @endunless
                                </div>
                                <p class="text-sm text-slate-500">
                                    {{ $sesionActual->tema ?: 'Sin tema' }}
                                    @if ($sesionActual->enlace)
                                        · <a href="{{ $sesionActual->enlace }}" target="_blank" rel="noopener" class="text-brand-600 hover:text-brand-700 font-medium">Enlace de clase ↗</a>
                                    @endif
                                </p>
                            </div>
                            <button wire:click="editarSesion('{{ $sesionActual->id }}')" class="grid place-items-center h-8 w-8 rounded-lg text-slate-400 hover:bg-amber-50 hover:text-amber-600 transition" title="Editar sesión">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <button x-data @click="window.confirmarEliminar('Eliminar la sesión del {{ $sesionActual->fecha->format('d/m/Y') }}?').then(r => { if (r.isConfirmed) $wire.eliminarSesion('{{ $sesionActual->id }}') })" class="grid place-items-center h-8 w-8 rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600 transition" title="Eliminar sesión">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                <input wire:model.live.debounce.300ms="buscarAsistencia" type="text" placeholder="Buscar estudiante..." class="w-48 rounded-xl border-slate-300 pl-9 text-sm focus:border-brand-500 focus:ring-brand-500">
                            </div>
                            <span class="text-xs text-slate-400">Marcar todos:</span>
                            <button wire:click="marcarTodos('presente')" class="rounded-lg bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">Presente</button>
                            <button wire:click="marcarTodos('ausente')" class="rounded-lg bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100">Ausente</button>
                        </div>
                    </div>
                    <div class="overflow-x-auto scrollbar-thin">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-slate-100 bg-slate-50/60 text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    <th class="px-6 py-3">Estudiante</th>
                                    <th class="px-4 py-3 text-center">Asistencias</th>
                                    <th class="px-4 py-3 text-center">Faltas</th>
                                    <th class="px-4 py-3 text-center">Hoy</th>
                                    <th class="px-4 py-3 text-right">Ficha</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($estudiantesAsistencia as $est)
                                    @php
                                        $estado = $asistencias[$est->id] ?? 'ausente';
                                        $faltasN = $ausenciasPorEstudiante[$est->id] ?? 0;
                                        $asistidas = max(0, $totalSesiones - $faltasN);
                                        $pct = $totalSesiones > 0 ? round(($asistidas / $totalSesiones) * 100) : 100;
                                        $superaTope = $curso->tope_faltas && $faltasN > $curso->tope_faltas;
                                        $estilos = [
                                            'presente' => ['bg-emerald-600 text-white', 'bg-slate-100 text-slate-500 hover:bg-emerald-50 hover:text-emerald-700'],
                                            'ausente' => ['bg-red-600 text-white', 'bg-slate-100 text-slate-500 hover:bg-red-50 hover:text-red-700'],
                                            'justificado' => ['bg-amber-500 text-white', 'bg-slate-100 text-slate-500 hover:bg-amber-50 hover:text-amber-700'],
                                        ];
                                    @endphp
                                    <tr class="hover:bg-slate-50/70 transition" wire:key="asis-{{ $est->id }}">
                                        <td class="px-6 py-3">
                                            <div class="flex items-center gap-3">
                                                <span class="grid place-items-center h-9 w-9 shrink-0 rounded-full bg-amber-100 text-amber-700 text-xs font-semibold">{{ strtoupper(substr($est->nombres,0,1)).strtoupper(substr($est->apellidos ?? '',0,1)) }}</span>
                                                <div class="min-w-0">
                                                    <p class="font-semibold text-slate-800 text-sm truncate">{{ $est->nombre_completo }}</p>
                                                    <p class="text-xs text-slate-400">{{ $est->codigo ?: '—' }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <p class="text-sm font-bold {{ $pct >= 70 ? 'text-emerald-700' : 'text-amber-600' }}">{{ $asistidas }} de {{ $totalSesiones }}</p>
                                            <div class="mx-auto mt-1 h-1.5 w-20 rounded-full bg-slate-100 overflow-hidden">
                                                <div class="h-full rounded-full {{ $pct >= 70 ? 'bg-emerald-500' : 'bg-amber-500' }}" style="width: {{ $pct }}%"></div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold {{ $faltasN > 0 ? ($superaTope ? 'bg-red-600 text-white' : 'bg-red-50 text-red-600') : 'bg-slate-100 text-slate-500' }}">{{ $faltasN }}</span>
                                            @if ($superaTope)
                                                <p class="text-[10px] font-semibold text-red-600 mt-0.5">¡supera el tope!</p>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center justify-center gap-1">
                                                @foreach (\App\Models\Asistencia::ESTADOS as $val => $label)
                                                    <button wire:click="marcar('{{ $est->id }}', '{{ $val }}')"
                                                        class="rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ $estado === $val ? $estilos[$val][0] : $estilos[$val][1] }}">{{ $label }}</button>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <button wire:click="verFicha('{{ $est->id }}')" class="inline-flex items-center gap-1 rounded-lg bg-brand-50 px-2.5 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-100 transition" title="Ver ficha del estudiante">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                                Ficha
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-6 py-10 text-center text-sm text-slate-400">{{ $buscarAsistencia !== '' ? 'Ningún estudiante coincide con la búsqueda.' : 'No hay estudiantes matriculados.' }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="grid place-items-center min-h-[16rem] p-10 text-center">
                        <div>
                            <span class="grid place-items-center h-14 w-14 mx-auto rounded-2xl bg-slate-100 text-slate-400">
                                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.4" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </span>
                            <p class="mt-3 text-sm text-slate-500">Elige una sesión arriba, o crea una nueva. Al crearla, todos inician como <strong>ausentes</strong> y tú marcas a los presentes.</p>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- ============ TAB ACADEMICO ============ --}}
        @if ($tab === 'academico')
            <div class="flex items-center justify-between rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm px-6 py-4">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Estructura del curso</h3>
                    <p class="text-sm text-slate-500">Suma de pesos de unidades:
                        <span class="font-semibold {{ $pesoUnidades === 100 ? 'text-emerald-600' : 'text-amber-600' }}">{{ $pesoUnidades }}%</span>
                        @if ($pesoUnidades !== 100) <span class="text-slate-400">(ideal 100%)</span> @endif
                    </p>
                </div>
                <button wire:click="nuevaUnidad" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700 transition">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Nueva unidad
                </button>
            </div>

            @forelse ($unidades as $unidad)
                <div class="rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm overflow-hidden" wire:key="uni-{{ $unidad->id }}">
                    <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 bg-slate-50/60 border-b border-slate-100">
                        <div class="flex items-center gap-3">
                            <span class="grid place-items-center h-9 w-9 rounded-lg bg-brand-100 text-brand-700 font-bold text-sm">U{{ $unidad->numero }}</span>
                            <div>
                                <p class="font-semibold text-slate-900">{{ $unidad->nombre }}</p>
                                <p class="text-xs text-slate-500">Peso: {{ $unidad->peso }}% · {{ $unidad->evaluaciones->count() }} evaluación(es)</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            <button wire:click="nuevaEvaluacion('{{ $unidad->id }}')" class="inline-flex items-center gap-1 rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-brand-700 ring-1 ring-brand-200 hover:bg-brand-50 transition">+ Evaluación</button>
                            <button wire:click="editarUnidad('{{ $unidad->id }}')" class="grid place-items-center h-8 w-8 rounded-lg text-slate-400 hover:bg-amber-50 hover:text-amber-600 transition" title="Editar unidad">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <button x-data @click="window.confirmarEliminar('Se eliminará la unidad «{{ addslashes($unidad->nombre) }}» y sus evaluaciones.').then(r => { if (r.isConfirmed) $wire.eliminarUnidad('{{ $unidad->id }}') })" class="grid place-items-center h-8 w-8 rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600 transition" title="Eliminar unidad">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="divide-y divide-slate-100">
                        @forelse ($unidad->evaluaciones as $eval)
                            <div class="px-6 py-4" wire:key="eval-{{ $eval->id }}">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center rounded-lg bg-brand-50 px-2 py-0.5 text-xs font-semibold text-brand-700 capitalize">{{ \App\Models\Evaluacion::TIPOS[$eval->tipo] ?? $eval->tipo }}</span>
                                        <p class="font-semibold text-slate-900">{{ $eval->titulo }}</p>
                                        <span class="text-xs text-slate-400">Peso {{ $eval->peso }}% · Total {{ rtrim(rtrim(number_format($eval->puntaje_total,2),'0'),'.') }} pts</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <button wire:click="nuevoCriterio('{{ $eval->id }}')" class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-200 transition">+ Criterio</button>
                                        <button wire:click="editarEvaluacion('{{ $eval->id }}')" class="grid place-items-center h-8 w-8 rounded-lg text-slate-400 hover:bg-amber-50 hover:text-amber-600 transition" title="Editar">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <button x-data @click="window.confirmarEliminar('Se eliminará la evaluación «{{ addslashes($eval->titulo) }}».').then(r => { if (r.isConfirmed) $wire.eliminarEvaluacion('{{ $eval->id }}') })" class="grid place-items-center h-8 w-8 rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600 transition" title="Eliminar">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </div>
                                @if ($eval->criterios->isNotEmpty())
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @foreach ($eval->criterios as $crit)
                                            <span class="group inline-flex items-center gap-2 rounded-lg bg-slate-50 px-3 py-1.5 text-xs ring-1 ring-slate-200">
                                                <span class="font-medium text-slate-700">{{ $crit->nombre }}</span>
                                                <span class="text-slate-400">{{ rtrim(rtrim(number_format($crit->puntaje_max,2),'0'),'.') }} pts</span>
                                                <button wire:click="editarCriterio('{{ $crit->id }}')" class="text-slate-300 hover:text-amber-600" title="Editar">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                </button>
                                                <button x-data @click="window.confirmarEliminar('Eliminar criterio «{{ addslashes($crit->nombre) }}»?').then(r => { if (r.isConfirmed) $wire.eliminarCriterio('{{ $crit->id }}') })" class="text-slate-300 hover:text-red-600" title="Eliminar">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="mt-2 text-xs text-slate-400">Sin criterios. Agrega con «+ Criterio».</p>
                                @endif
                            </div>
                        @empty
                            <div class="px-6 py-6 text-center text-sm text-slate-400">Sin evaluaciones en esta unidad.</div>
                        @endforelse
                    </div>
                </div>
            @empty
                <div class="rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm p-12 text-center">
                    <p class="text-sm font-medium text-slate-600">Sin unidades</p>
                    <p class="text-sm text-slate-400">Crea la primera unidad con «Nueva unidad».</p>
                </div>
            @endforelse
        @endif

        {{-- ============ TAB NOTAS ============ --}}
        @if ($tab === 'notas')
            <div class="rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm p-6">
                <label class="block text-sm font-medium text-slate-700 mb-1">Selecciona una evaluación</label>
                <select wire:model.live="notasEvaluacionId" class="w-full sm:w-96 border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-xl shadow-sm text-slate-900">
                    <option value="">— Elige una evaluación —</option>
                    @foreach ($unidades as $unidad)
                        <optgroup label="U{{ $unidad->numero }} · {{ $unidad->nombre }}">
                            @foreach ($unidad->evaluaciones as $eval)
                                <option value="{{ $eval->id }}">{{ $eval->titulo }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>

            @if ($evaluacionSel && $evaluacionSel->criterios->isNotEmpty())
                <div class="rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm overflow-hidden">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                        <h3 class="text-base font-semibold text-slate-900">{{ $evaluacionSel->titulo }} — registro de notas</h3>
                        <button wire:click="guardarNotas" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 transition">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Guardar notas
                        </button>
                    </div>
                    <div class="overflow-x-auto scrollbar-thin">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-slate-100 bg-slate-50/60 text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    <th class="px-6 py-3 sticky left-0 bg-slate-50">Estudiante</th>
                                    @foreach ($evaluacionSel->criterios as $crit)
                                        <th class="px-4 py-3 text-center">{{ $crit->nombre }}<br><span class="text-[10px] normal-case text-slate-400">máx {{ rtrim(rtrim(number_format($crit->puntaje_max,2),'0'),'.') }}</span></th>
                                    @endforeach
                                    <th class="px-4 py-3 text-center">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($estudiantesMatriculados as $est)
                                    @php $total = 0; @endphp
                                    <tr wire:key="nota-row-{{ $est->id }}">
                                        <td class="px-6 py-3 sticky left-0 bg-white font-medium text-slate-800 whitespace-nowrap">{{ $est->nombre_completo }}</td>
                                        @foreach ($evaluacionSel->criterios as $crit)
                                            @php $total += (float) ($notas[$est->id][$crit->id] ?? 0); @endphp
                                            <td class="px-4 py-3 text-center">
                                                <input type="number" step="0.01" min="0" max="{{ $crit->puntaje_max }}"
                                                    wire:model="notas.{{ $est->id }}.{{ $crit->id }}"
                                                    class="w-20 text-center border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-lg text-sm">
                                            </td>
                                        @endforeach
                                        <td class="px-4 py-3 text-center font-bold text-brand-700">{{ rtrim(rtrim(number_format($total,2),'0'),'.') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="{{ $evaluacionSel->criterios->count() + 2 }}" class="px-6 py-10 text-center text-sm text-slate-400">No hay estudiantes matriculados. Ve a la pestaña «Estudiantes».</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @elseif ($evaluacionSel)
                <div class="rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm p-10 text-center text-sm text-slate-400">
                    Esta evaluación no tiene criterios. Agrégalos en la pestaña «Unidades y evaluaciones».
                </div>
            @endif
        @endif

        {{-- ============ TAB CUADERNO (EXCEL) ============ --}}
        @if ($tab === 'cuaderno' && $cuaderno)
            <div class="rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Cuaderno de notas</h3>
                        <p class="text-sm text-slate-500">Todas las evaluaciones de un vistazo · escala 0–20 · desliza horizontalmente →</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-slate-500">Promedio del curso: <span class="font-bold text-slate-900">{{ number_format($cuaderno['promedioCurso'], 2) }}</span></span>
                        <button wire:click="exportarCuaderno" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 transition">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Descargar Excel
                        </button>
                    </div>
                </div>

                @if (empty($cuaderno['filas']))
                    <p class="px-6 py-12 text-center text-sm text-slate-400">No hay estudiantes matriculados. Ve a la pestaña «Estudiantes».</p>
                @else
                    <div class="overflow-x-auto scrollbar-thin">
                        <table class="text-left text-sm border-collapse">
                            <thead>
                                <!-- Fila 1: agrupación por unidad -->
                                <tr class="bg-slate-100 text-xs font-bold uppercase tracking-wide text-slate-500">
                                    <th rowspan="2" class="px-4 py-2 sticky left-0 z-20 bg-slate-100 border-r border-slate-200 min-w-[200px] align-middle">Estudiante</th>
                                    @if ($sesionesCuaderno->isNotEmpty())
                                        <th colspan="{{ $sesionesCuaderno->count() }}" class="px-3 py-2 text-center border-l-2 border-emerald-300 bg-emerald-50 text-emerald-700">Asistencia por día</th>
                                    @endif
                                    @foreach ($cuaderno['unidades'] as $u)
                                        <th colspan="{{ count($u['evaluaciones']) + 1 }}" class="px-3 py-2 text-center border-l-2 border-slate-300" title="{{ $u['nombre'] }}">U{{ $u['numero'] }} · {{ $u['peso'] }}%</th>
                                    @endforeach
                                    <th rowspan="2" class="px-3 py-2 text-center border-l-2 border-slate-300 align-middle bg-slate-200/70">Acad.</th>
                                    @if ($cuaderno['usaAsistencia'])
                                        <th rowspan="2" class="px-3 py-2 text-center align-middle bg-slate-200/70">Asist.</th>
                                    @endif
                                    <th rowspan="2" class="px-3 py-2 text-center align-middle bg-brand-600 text-white">FINAL</th>
                                </tr>
                                <!-- Fila 2: días de asistencia + cada evaluación + prom. unidad -->
                                <tr class="bg-slate-50 text-[11px] font-semibold text-slate-500">
                                    @foreach ($sesionesCuaderno as $idx => $s)
                                        @php $lect = in_array($s->tipo, ['clase','virtual']) || filled($s->enlace); @endphp
                                        <th class="px-2 py-2 text-center whitespace-nowrap {{ $idx === 0 ? 'border-l-2 border-emerald-300' : 'border-l border-slate-100' }} {{ $lect ? 'bg-emerald-50/60' : 'bg-red-50/60 text-red-500' }}" title="{{ $s->tipo_label }}{{ $s->tema ? ' · '.$s->tema : '' }}">
                                            {{ $s->fecha->format('d/m') }}
                                            @unless ($lect)<span class="block text-[9px] normal-case">{{ \Illuminate\Support\Str::limit($s->tipo_label, 8, '') }}</span>@endunless
                                        </th>
                                    @endforeach
                                    @foreach ($cuaderno['unidades'] as $u)
                                        @foreach ($u['evaluaciones'] as $ei => $e)
                                            <th class="px-3 py-2 text-center {{ $ei === 0 ? 'border-l-2 border-slate-300' : 'border-l border-slate-100' }} whitespace-nowrap max-w-[120px] truncate" title="{{ $e['titulo'] }} ({{ $e['peso'] }}%)">{{ \Illuminate\Support\Str::limit($e['titulo'], 12) }}</th>
                                        @endforeach
                                        <th class="px-3 py-2 text-center bg-slate-100 border-l border-slate-200">Prom.</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($cuaderno['filas'] as $f)
                                    <tr class="hover:bg-brand-50/40 transition" wire:key="cua-{{ $f['estudiante_id'] }}">
                                        <td class="px-4 py-2.5 sticky left-0 z-10 bg-white border-r border-slate-200">
                                            <p class="font-semibold text-slate-800 whitespace-nowrap">{{ $f['nombre'] }}</p>
                                            <p class="text-[11px] text-slate-400">{{ $f['codigo'] ?: '—' }}</p>
                                        </td>
                                        {{-- Asistencia por día: desplegable editable (o etiqueta si no es día lectivo) --}}
                                        @foreach ($sesionesCuaderno as $idx => $s)
                                            @php $lectiva = in_array($s->tipo, ['clase','virtual']) || filled($s->enlace); @endphp
                                            <td class="p-1 text-center {{ $idx === 0 ? 'border-l-2 border-emerald-200' : 'border-l border-slate-50' }}">
                                                @if ($lectiva)
                                                    @php
                                                        $estAsis = $asistenciaCuaderno[$f['estudiante_id']][$s->id] ?? 'ausente';
                                                        $bgAsis = ['presente' => 'bg-emerald-50 text-emerald-700', 'ausente' => 'bg-red-50 text-red-700', 'justificado' => 'bg-amber-50 text-amber-700'][$estAsis];
                                                    @endphp
                                                    <select wire:change="marcarEn('{{ $s->id }}', '{{ $f['estudiante_id'] }}', $event.target.value)"
                                                        class="w-full rounded-md border-0 text-xs font-semibold py-1 pl-2 pr-6 focus:ring-2 focus:ring-inset focus:ring-emerald-500 {{ $bgAsis }}">
                                                        <option value="presente" @selected($estAsis==='presente')>P</option>
                                                        <option value="ausente" @selected($estAsis==='ausente')>A</option>
                                                        <option value="justificado" @selected($estAsis==='justificado')>J</option>
                                                    </select>
                                                @else
                                                    <span class="block text-[10px] font-semibold text-slate-400" title="{{ $s->tipo_label }}">—</span>
                                                @endif
                                            </td>
                                        @endforeach
                                        @foreach ($cuaderno['unidades'] as $u)
                                            @foreach ($u['evaluaciones'] as $e)
                                                @php $n = $f['evals'][$e['id']] ?? null; @endphp
                                                <td class="px-3 py-2.5 text-center border-l border-slate-50 {{ $n === null ? 'text-slate-300' : ($n >= 10.5 ? 'text-emerald-700 font-medium' : 'text-red-600 font-medium') }}">
                                                    {{ $n === null ? '—' : number_format($n, 1) }}
                                                </td>
                                            @endforeach
                                            @php $nu = $f['unidades'][$u['id']] ?? null; @endphp
                                            <td class="px-3 py-2.5 text-center bg-slate-50/70 border-l border-slate-200 font-semibold {{ $nu === null ? 'text-slate-300' : ($nu >= 10.5 ? 'text-emerald-700' : 'text-red-600') }}">
                                                {{ $nu === null ? '—' : number_format($nu, 1) }}
                                            </td>
                                        @endforeach
                                        <td class="px-3 py-2.5 text-center bg-slate-50/70 border-l border-slate-200 font-semibold text-slate-700">{{ number_format($f['academica'], 1) }}</td>
                                        @if ($cuaderno['usaAsistencia'])
                                            <td class="px-3 py-2.5 text-center bg-slate-50/70 text-slate-600">{{ $f['asistencia_pct'] }}%</td>
                                        @endif
                                        <td class="px-3 py-2.5 text-center">
                                            <span class="inline-flex items-center justify-center min-w-[2.75rem] rounded-lg px-2 py-1 font-bold {{ $f['aprobado'] ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">{{ number_format($f['final'], 1) }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-slate-50 text-xs font-semibold text-slate-500 border-t-2 border-slate-200">
                                    <td class="px-4 py-2.5 sticky left-0 z-10 bg-slate-50 border-r border-slate-200">Promedio de la clase</td>
                                    @if ($sesionesCuaderno->isNotEmpty())
                                        <td colspan="{{ $sesionesCuaderno->count() }}" class="border-l-2 border-emerald-200 bg-emerald-50/40 text-center text-[11px] text-emerald-600">↑ marca la asistencia de cada día</td>
                                    @endif
                                    @foreach ($cuaderno['unidades'] as $u)
                                        @foreach ($u['evaluaciones'] as $e)
                                            @php $pe = $cuaderno['promEval'][$e['id']] ?? null; @endphp
                                            <td class="px-3 py-2.5 text-center border-l border-slate-100 text-slate-600">{{ $pe === null ? '—' : number_format($pe, 1) }}</td>
                                        @endforeach
                                        <td class="px-3 py-2.5 border-l border-slate-200 bg-slate-100"></td>
                                    @endforeach
                                    <td class="px-3 py-2.5 border-l border-slate-200 bg-slate-100"></td>
                                    @if ($cuaderno['usaAsistencia'])
                                        <td class="bg-slate-100"></td>
                                    @endif
                                    <td class="px-3 py-2.5 text-center text-slate-700">{{ number_format($cuaderno['promedioCurso'], 1) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="px-6 py-3 border-t border-slate-100 bg-slate-50/40 text-xs text-slate-500">
                        Verde = aprobado (≥ 10.5) · Rojo = desaprobado · «—» = sin nota todavía. La columna «FINAL» combina unidades @if($cuaderno['usaAsistencia']) y asistencia ({{ $cuaderno['pesoAsistencia'] }}%) @endif.
                    </div>
                @endif
            </div>
        @endif

        {{-- ============ TAB NOTA FINAL ============ --}}
        @if ($tab === 'notafinal' && $acta)
            <!-- Resumen -->
            <div class="grid gap-5 sm:grid-cols-4">
                <div class="rounded-2xl bg-white p-5 ring-1 ring-slate-200 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Promedio del curso</p>
                    <p class="mt-1 text-3xl font-bold text-slate-900">{{ number_format($acta['promedioCurso'], 2) }}</p>
                </div>
                <div class="rounded-2xl bg-white p-5 ring-1 ring-slate-200 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Aprobados</p>
                    <p class="mt-1 text-3xl font-bold text-emerald-600">{{ $acta['aprobados'] }}</p>
                </div>
                <div class="rounded-2xl bg-white p-5 ring-1 ring-slate-200 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Desaprobados</p>
                    <p class="mt-1 text-3xl font-bold text-red-600">{{ $acta['desaprobados'] }}</p>
                </div>
                <div class="rounded-2xl bg-white p-5 ring-1 ring-slate-200 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Peso asistencia</p>
                    <p class="mt-1 text-3xl font-bold text-slate-900">{{ $acta['pesoAsistencia'] }}%</p>
                </div>
            </div>

            <div class="rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Acta de notas — {{ $curso->nombre }}</h3>
                        <p class="text-sm text-slate-500">Escala vigesimal (0–20) · Aprobado ≥ 10.5</p>
                    </div>
                    <button wire:click="exportarActa" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 transition">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Descargar acta (Excel)
                    </button>
                </div>
                <div class="overflow-x-auto scrollbar-thin">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/60 text-xs font-semibold uppercase tracking-wider text-slate-500">
                                <th class="px-4 py-3 sticky left-0 bg-slate-50">Estudiante</th>
                                @foreach ($acta['unidades'] as $u)
                                    <th class="px-3 py-3 text-center" title="{{ $u['nombre'] }}">U{{ $u['numero'] }}<br><span class="text-[10px] normal-case text-slate-400">{{ $u['peso'] }}%</span></th>
                                @endforeach
                                <th class="px-3 py-3 text-center">Académico</th>
                                @if ($acta['usaAsistencia'])
                                    <th class="px-3 py-3 text-center">Asist.</th>
                                @endif
                                <th class="px-3 py-3 text-center">Final</th>
                                <th class="px-4 py-3 text-center">Condición</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($acta['filas'] as $f)
                                <tr class="hover:bg-slate-50/70 transition" wire:key="acta-{{ $f['estudiante_id'] }}">
                                    <td class="px-4 py-3 sticky left-0 bg-white whitespace-nowrap">
                                        <p class="font-semibold text-slate-800">{{ $f['nombre'] }}</p>
                                        <p class="text-xs text-slate-400">{{ $f['codigo'] ?: '—' }}</p>
                                    </td>
                                    @foreach ($acta['unidades'] as $u)
                                        <td class="px-3 py-3 text-center text-slate-600">{{ $f['unidades'][$u['id']] !== null ? number_format($f['unidades'][$u['id']], 2) : '—' }}</td>
                                    @endforeach
                                    <td class="px-3 py-3 text-center font-medium text-slate-700">{{ number_format($f['academica'], 2) }}</td>
                                    @if ($acta['usaAsistencia'])
                                        <td class="px-3 py-3 text-center text-slate-600">
                                            {{ $f['asistencia_pct'] }}%
                                            @if ($f['faltas'] > 0)<span class="block text-[10px] text-red-500">{{ $f['faltas'] }} falta(s)</span>@endif
                                        </td>
                                    @endif
                                    <td class="px-3 py-3 text-center">
                                        <span class="inline-flex items-center justify-center min-w-[2.5rem] rounded-lg px-2 py-1 font-bold {{ $f['aprobado'] ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">{{ number_format($f['final'], 2) }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if ($f['aprobado'])
                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">Aprobado</span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-semibold text-red-700 ring-1 ring-red-200">Desaprobado</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="99" class="px-6 py-14 text-center text-sm text-slate-400">No hay estudiantes matriculados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-3 border-t border-slate-100 bg-slate-50/40 text-xs text-slate-500">
                    La nota final combina el promedio ponderado de unidades
                    @if ($acta['usaAsistencia']) y la asistencia ({{ $acta['pesoAsistencia'] }}%) @endif.
                    Cada evaluación se normaliza a base 20 según sus criterios.
                </div>
            </div>
        @endif

        {{-- ============ TAB HORARIO Y CALENDARIO ============ --}}
        @if ($tab === 'horario')
            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Horario semanal -->
                <div class="rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm overflow-hidden">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900">Horario semanal</h3>
                            <p class="text-sm text-slate-500">Los bloques de clase del curso</p>
                        </div>
                        <button wire:click="nuevoHorario" class="inline-flex items-center gap-1 rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-700 transition">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Bloque
                        </button>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @forelse ($horarios as $h)
                            <div class="flex items-center justify-between gap-3 px-6 py-3" wire:key="hor-{{ $h->id }}">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="grid place-items-center h-11 w-11 shrink-0 rounded-xl bg-brand-100 text-brand-700 text-xs font-bold text-center leading-tight">{{ mb_substr(\App\Models\Horario::DIAS[$h->dia], 0, 3) }}</span>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-800">{{ \App\Models\Horario::DIAS[$h->dia] }}</p>
                                        <p class="text-sm text-slate-500">
                                            {{ \Illuminate\Support\Carbon::parse($h->hora_inicio)->format('H:i') }} – {{ \Illuminate\Support\Carbon::parse($h->hora_fin)->format('H:i') }}
                                            @if ($h->aula) · {{ $h->aula }} @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    <button wire:click="editarHorario('{{ $h->id }}')" class="grid place-items-center h-8 w-8 rounded-lg text-slate-400 hover:bg-amber-50 hover:text-amber-600 transition" title="Editar">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <button x-data @click="window.confirmarEliminar('Eliminar este bloque de horario?').then(r => { if (r.isConfirmed) $wire.eliminarHorario('{{ $h->id }}') })" class="grid place-items-center h-8 w-8 rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600 transition" title="Eliminar">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </div>
                        @empty
                            <p class="px-6 py-10 text-center text-sm text-slate-400">Sin horario. Agrega bloques con «Bloque».</p>
                        @endforelse
                    </div>
                    @if ($horarios->isNotEmpty())
                        <div class="px-6 py-4 border-t border-slate-100 bg-emerald-50/40 flex flex-wrap items-center justify-between gap-3">
                            <p class="text-sm text-emerald-800">Genera las clases del ciclo ({{ $curso->semanas ?: 16 }} semanas) según estos días.</p>
                            <button wire:click="abrirGenerar" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 transition">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                Generar clases del ciclo
                            </button>
                        </div>
                    @endif
                </div>

                <!-- Calendario del curso -->
                <div class="rounded-2xl bg-white ring-1 ring-slate-200 shadow-sm overflow-hidden">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900">Calendario del curso</h3>
                            <p class="text-sm text-slate-500">Fechas de entregas y exámenes</p>
                        </div>
                    </div>
                    <div class="divide-y divide-slate-100 max-h-80 overflow-y-auto scrollbar-thin">
                        @forelse ($eventos as $ev)
                            <div class="flex items-center gap-3 px-6 py-3">
                                <span class="grid place-items-center h-11 w-11 shrink-0 rounded-xl text-center leading-none {{ $ev['esExamen'] ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-600' }}">
                                    <span class="text-base font-bold">{{ $ev['fecha']->format('d') }}</span>
                                    <span class="text-[10px] uppercase">{{ $ev['fecha']->translatedFormat('M') }}</span>
                                </span>
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-800">{{ $ev['titulo'] }}</p>
                                    <p class="text-xs {{ $ev['esExamen'] ? 'text-red-500' : 'text-amber-600' }}">{{ $ev['tipo'] }} · {{ $ev['fecha']->translatedFormat('l d \d\e F') }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="px-6 py-10 text-center text-sm text-slate-400">Sin fechas. Asigna fechas límite a las evaluaciones (pestaña «Unidades y evaluaciones»).</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Acciones del calendario -->
            <div class="rounded-2xl bg-gradient-to-br from-brand-700 to-brand-900 p-6 text-white shadow-lg">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="max-w-lg">
                        <h3 class="text-lg font-bold">Comparte el calendario con tus estudiantes</h3>
                        <p class="mt-1 text-sm text-brand-100/80">Descarga el archivo <span class="font-mono">.ics</span> (clases + entregas + exámenes con recordatorios) o envíalo por correo. Se agrega con un toque a Google Calendar, Outlook o el celular.</p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('cursos.calendario', $curso->id) }}"
                           class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-brand-700 hover:bg-brand-50 transition">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 10v6m0 0l-3-3m3 3l3-3M4 6h16M4 6V4a2 2 0 012-2h12a2 2 0 012 2v2M4 6v12a2 2 0 002 2h12a2 2 0 002-2V6"/></svg>
                            Descargar .ics
                        </a>
                        <button x-data @click="window.Swal.fire({title:'Enviar calendario', text:'Se enviará el calendario por correo a {{ $conCorreo }} estudiante(s) con correo registrado.', icon:'question', showCancelButton:true, confirmButtonColor:'#4f46e5', cancelButtonColor:'#64748b', confirmButtonText:'Sí, enviar', cancelButtonText:'Cancelar', reverseButtons:true}).then(r => { if (r.isConfirmed) $wire.enviarCalendario() })"
                           @disabled($conCorreo === 0)
                           class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-4 py-2.5 text-sm font-semibold text-white ring-1 ring-white/20 hover:bg-white/20 transition disabled:opacity-40">
                            <svg wire:loading.remove wire:target="enviarCalendario" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <svg wire:loading wire:target="enviarCalendario" class="animate-spin h-5 w-5" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                            Enviar por correo ({{ $conCorreo }})
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- ============ MODALES ============ --}}
    @if ($showMatricular)
    <x-modal-panel wire="showMatricular" title="Matricular estudiantes" subtitle="Selecciona a quiénes agregar al curso" icon="M12 4v16m8-8H4" icon-grad="from-brand-500 to-brand-700" max-width="2xl">
        @if ($disponibles->isNotEmpty())
            <div class="flex flex-wrap items-center justify-between gap-3 mb-3 pb-3 border-b border-slate-100">
                <p class="text-sm text-slate-500"><span class="font-semibold text-slate-800">{{ count($seleccion) }}</span> de {{ $disponibles->count() }} seleccionados</p>
                <div class="flex items-center gap-2">
                    <button type="button" wire:click="seleccionarTodos" class="inline-flex items-center gap-1 rounded-lg bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-100 transition">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Seleccionar todos
                    </button>
                    <button type="button" wire:click="limpiarSeleccion" @disabled(count($seleccion) === 0) class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-200 transition disabled:opacity-50">Limpiar</button>
                </div>
            </div>
        @endif
        <div class="grid sm:grid-cols-2 gap-2 max-h-96 overflow-y-auto scrollbar-thin">
            @forelse ($disponibles as $est)
                <label class="flex items-center gap-3 rounded-xl border border-slate-100 p-3 hover:bg-slate-50 cursor-pointer" wire:key="disp-{{ $est->id }}">
                    <input type="checkbox" wire:model.live="seleccion" value="{{ $est->id }}" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    <span class="grid place-items-center h-8 w-8 shrink-0 rounded-full bg-amber-100 text-amber-700 text-xs font-semibold">{{ strtoupper(substr($est->nombres,0,1)).strtoupper(substr($est->apellidos ?? '',0,1)) }}</span>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-800 truncate">{{ $est->nombre_completo }}</p>
                        <p class="text-xs text-slate-400">{{ $est->codigo ?: 'Sin código' }}</p>
                    </div>
                </label>
            @empty
                <p class="sm:col-span-2 text-sm text-slate-400 text-center py-6">Todos tus estudiantes ya están matriculados, o no tienes estudiantes. Créalos en el módulo «Estudiantes».</p>
            @endforelse
        </div>
        <x-slot name="footer">
            <button type="button" wire:click="$set('showMatricular', false)" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100 transition">Cancelar</button>
            <button type="button" wire:click="matricular" @disabled(count($seleccion) === 0) class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700 transition disabled:opacity-50">Matricular ({{ count($seleccion) }})</button>
        </x-slot>
    </x-modal-panel>
    @endif

    @if ($showGenerar)
    <x-modal-panel wire="showGenerar" title="Generar clases del ciclo" subtitle="Crea las sesiones automáticamente desde el horario" icon="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" icon-grad="from-emerald-500 to-teal-600" max-width="lg">
        <div class="space-y-4">
            <div class="rounded-xl bg-emerald-50 border border-emerald-100 p-3 text-sm text-emerald-800">
                Se crearán clases para cada día del horario, repitiéndose cada semana. Los días que ya tengan sesión no se duplican.
            </div>
            <div class="rounded-xl bg-slate-50 p-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">Días del horario</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($horarios as $h)
                        <span class="inline-flex items-center gap-1 rounded-lg bg-white px-2.5 py-1 text-xs font-medium text-slate-700 ring-1 ring-slate-200">
                            {{ \App\Models\Horario::DIAS[$h->dia] }} {{ \Illuminate\Support\Carbon::parse($h->hora_inicio)->format('H:i') }}–{{ \Illuminate\Support\Carbon::parse($h->hora_fin)->format('H:i') }}
                        </span>
                    @endforeach
                </div>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="gen_fecha" :value="__('Fecha de inicio del ciclo')" />
                    <x-text-input wire:model="gen_fecha_inicio" id="gen_fecha" class="block mt-1 w-full" type="date" />
                    <x-input-error :messages="$errors->get('gen_fecha_inicio')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="gen_semanas" :value="__('Número de semanas')" />
                    <x-text-input wire:model="gen_semanas" id="gen_semanas" class="block mt-1 w-full" type="number" min="1" max="52" placeholder="18" />
                    <x-input-error :messages="$errors->get('gen_semanas')" class="mt-2" />
                </div>
            </div>
            <p class="text-xs text-slate-400">Total aproximado: {{ $horarios->count() }} día(s) × {{ $gen_semanas }} semanas = <strong>{{ $horarios->count() * $gen_semanas }} clases</strong>.</p>
        </div>
        <x-slot name="footer">
            <button type="button" wire:click="$set('showGenerar', false)" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100 transition">Cancelar</button>
            <button type="button" wire:click="generarSesiones" wire:loading.attr="disabled" wire:target="generarSesiones" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 transition disabled:opacity-60">
                <svg wire:loading wire:target="generarSesiones" class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                Generar clases
            </button>
        </x-slot>
    </x-modal-panel>
    @endif

    @if ($showHorario)
    <x-modal-panel wire="showHorario" :title="$horarioEditId ? 'Editar bloque' : 'Nuevo bloque de horario'" subtitle="Un día y hora de clase semanal" icon="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" icon-grad="from-brand-500 to-brand-700" max-width="md">
        <form wire:submit="guardarHorario" id="form-horario" class="space-y-4">
            <div>
                <x-input-label for="h_dia" :value="__('Día de la semana')" />
                <select wire:model="h_dia" id="h_dia" class="w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-xl shadow-sm text-slate-900 mt-1">
                    @foreach (\App\Models\Horario::DIAS as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('h_dia')" class="mt-2" />
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="h_inicio" :value="__('Hora inicio')" />
                    <x-text-input wire:model="h_hora_inicio" id="h_inicio" class="block mt-1 w-full" type="time" />
                    <x-input-error :messages="$errors->get('h_hora_inicio')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="h_fin" :value="__('Hora fin')" />
                    <x-text-input wire:model="h_hora_fin" id="h_fin" class="block mt-1 w-full" type="time" />
                    <x-input-error :messages="$errors->get('h_hora_fin')" class="mt-2" />
                </div>
            </div>
            <div>
                <x-input-label for="h_aula" :value="__('Aula (opcional)')" />
                <x-text-input wire:model="h_aula" id="h_aula" class="block mt-1 w-full" type="text" placeholder="Ej. Lab 302" />
                <x-input-error :messages="$errors->get('h_aula')" class="mt-2" />
            </div>
        </form>
        <x-slot name="footer">
            <button type="button" wire:click="$set('showHorario', false)" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100 transition">Cancelar</button>
            <button type="submit" form="form-horario" class="rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700 transition">{{ $horarioEditId ? 'Guardar' : 'Agregar bloque' }}</button>
        </x-slot>
    </x-modal-panel>
    @endif

    {{-- Modal Ficha del estudiante --}}
    @if ($showFicha)
    <x-modal-panel wire="showFicha" title="Ficha del estudiante" subtitle="Resumen de asistencia y notas en este curso" icon="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" icon-grad="from-brand-500 to-brand-700" max-width="2xl">
        @if ($ficha)
            <div class="space-y-5">
                <!-- Encabezado -->
                <div class="flex items-center gap-3">
                    <span class="grid place-items-center h-14 w-14 rounded-2xl bg-amber-100 text-amber-700 text-xl font-bold">{{ strtoupper(substr($ficha['nombre'], 0, 1)) }}</span>
                    <div>
                        <p class="text-lg font-bold text-slate-900">{{ $ficha['nombre'] }}</p>
                        <p class="text-sm text-slate-500">{{ $ficha['codigo'] ?: 'Sin código' }} · {{ $curso->nombre }}</p>
                    </div>
                </div>

                <!-- Indicadores rápidos -->
                <div class="grid grid-cols-3 gap-3">
                    @php $pctF = $ficha['total_sesiones'] > 0 ? round($ficha['asistidas'] / $ficha['total_sesiones'] * 100) : 100; @endphp
                    <div class="rounded-xl bg-emerald-50 p-3 text-center">
                        <p class="text-2xl font-bold text-emerald-700">{{ $ficha['asistidas'] }} de {{ $ficha['total_sesiones'] }}</p>
                        <p class="text-xs text-emerald-600">Asistencias ({{ $pctF }}%)</p>
                    </div>
                    <div class="rounded-xl {{ count($ficha['faltas']) > 0 ? 'bg-red-50' : 'bg-slate-50' }} p-3 text-center">
                        <p class="text-2xl font-bold {{ count($ficha['faltas']) > 0 ? 'text-red-700' : 'text-slate-500' }}">{{ count($ficha['faltas']) }}</p>
                        <p class="text-xs {{ count($ficha['faltas']) > 0 ? 'text-red-600' : 'text-slate-400' }}">Faltas</p>
                    </div>
                    <div class="rounded-xl {{ $ficha['pendientes'] > 0 ? 'bg-amber-50' : 'bg-slate-50' }} p-3 text-center">
                        <p class="text-2xl font-bold {{ $ficha['pendientes'] > 0 ? 'text-amber-700' : 'text-slate-500' }}">{{ $ficha['pendientes'] }}</p>
                        <p class="text-xs {{ $ficha['pendientes'] > 0 ? 'text-amber-600' : 'text-slate-400' }}">Notas pendientes</p>
                    </div>
                </div>

                <!-- Días que faltó -->
                @if (count($ficha['faltas']) > 0 || count($ficha['justificadas']) > 0)
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">Días que faltó</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($ficha['faltas'] as $f)
                                <span class="inline-flex items-center gap-1 rounded-lg bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700 ring-1 ring-red-200">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    {{ $f }}
                                </span>
                            @endforeach
                            @foreach ($ficha['justificadas'] as $j)
                                <span class="inline-flex items-center gap-1 rounded-lg bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-200" title="Justificado">
                                    {{ $j }} (J)
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Evolución de notas -->
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">Evolución de notas (base 20)</p>
                    @if (count($ficha['evaluaciones']) > 0)
                        <div class="space-y-2">
                            @foreach ($ficha['evaluaciones'] as $ev)
                                <div class="flex items-center gap-3">
                                    <span class="w-8 shrink-0 text-[11px] font-bold text-slate-400">{{ $ev['unidad'] }}</span>
                                    <span class="w-40 shrink-0 truncate text-sm font-medium text-slate-700">{{ $ev['titulo'] }}</span>
                                    @if ($ev['calificada'])
                                        <div class="flex-1 h-2.5 rounded-full bg-slate-100 overflow-hidden">
                                            <div class="h-full rounded-full {{ $ev['nota20'] >= 10.5 ? 'bg-gradient-to-r from-emerald-400 to-emerald-600' : 'bg-gradient-to-r from-red-400 to-red-600' }}" style="width: {{ min(100, ($ev['nota20'] / 20) * 100) }}%"></div>
                                        </div>
                                        <span class="w-14 shrink-0 text-right text-sm font-bold {{ $ev['nota20'] >= 10.5 ? 'text-emerald-700' : 'text-red-600' }}">{{ number_format($ev['nota20'], 2) }}</span>
                                    @else
                                        <div class="flex-1"></div>
                                        <span class="shrink-0 inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-semibold text-amber-700 ring-1 ring-amber-200">Falta calificar</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-slate-400">Este curso aún no tiene evaluaciones con criterios.</p>
                    @endif
                </div>
            </div>
        @endif
    </x-modal-panel>
    @endif

    @if ($showSesion)
    <x-modal-panel wire="showSesion" :title="$sesionEditId ? 'Editar sesión' : 'Nueva sesión'" subtitle="Un día de clase para registrar asistencia" icon="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" icon-grad="from-brand-500 to-brand-700" max-width="md">
        <form wire:submit="guardarSesion" id="form-sesion" class="space-y-4">
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="s_fecha" :value="__('Fecha')" />
                    <x-text-input wire:model="s_fecha" id="s_fecha" class="block mt-1 w-full" type="date" />
                    <x-input-error :messages="$errors->get('s_fecha')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="s_tipo" :value="__('Tipo de día')" />
                    <select wire:model.live="s_tipo" id="s_tipo" class="w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-xl shadow-sm text-slate-900 mt-1">
                        @foreach (\App\Models\Sesion::TIPOS as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('s_tipo')" class="mt-2" />
                </div>
            </div>
            @if (in_array($s_tipo, ['feriado', 'toma_local', 'parada', 'actividad']))
                <div class="rounded-xl bg-amber-50 border border-amber-100 p-3 text-xs text-amber-800">
                    ⚠️ Este día <strong>no cuenta para la asistencia</strong>. Si igual dictarás clase en línea, pega abajo el enlace de Meet/Zoom y sí contará.
                </div>
            @endif
            <div>
                <x-input-label for="s_tema" :value="__('Tema / motivo (opcional)')" />
                <x-text-input wire:model="s_tema" id="s_tema" class="block mt-1 w-full" type="text" placeholder="Ej. Introducción a... / Feriado por Fiestas Patrias" />
                <x-input-error :messages="$errors->get('s_tema')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="s_enlace" :value="__('Enlace de clase virtual (Meet / Zoom) — opcional')" />
                <x-text-input wire:model="s_enlace" id="s_enlace" class="block mt-1 w-full" type="url" placeholder="https://meet.google.com/... o https://zoom.us/j/..." />
                <p class="mt-1 text-xs text-slate-400">Si pones un enlace, el día cuenta como clase (virtual) y los estudiantes lo verán.</p>
                <x-input-error :messages="$errors->get('s_enlace')" class="mt-2" />
            </div>
        </form>
        <x-slot name="footer">
            <button type="button" wire:click="$set('showSesion', false)" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100 transition">Cancelar</button>
            <button type="submit" form="form-sesion" class="rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700 transition">{{ $sesionEditId ? 'Guardar' : 'Crear sesión' }}</button>
        </x-slot>
    </x-modal-panel>
    @endif

    @if ($showUnidad)
    <x-modal-panel wire="showUnidad" :title="$unidadEditId ? 'Editar unidad' : 'Nueva unidad'" subtitle="Cada unidad aporta un peso a la nota final" icon="M4 6h16M4 10h16M4 14h10M4 18h10" icon-grad="from-brand-500 to-brand-700" max-width="lg">
        <form wire:submit="guardarUnidad" id="form-unidad" class="space-y-4">
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="u_numero" :value="__('Número de unidad')" />
                    <x-text-input wire:model="u_numero" id="u_numero" class="block mt-1 w-full" type="number" min="1" />
                    <x-input-error :messages="$errors->get('u_numero')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="u_peso" :value="__('Peso en la nota final (%)')" />
                    <x-text-input wire:model="u_peso" id="u_peso" class="block mt-1 w-full" type="number" min="0" max="100" />
                    <x-input-error :messages="$errors->get('u_peso')" class="mt-2" />
                </div>
            </div>
            <div>
                <x-input-label for="u_nombre" :value="__('Nombre de la unidad')" />
                <x-text-input wire:model="u_nombre" id="u_nombre" class="block mt-1 w-full" type="text" placeholder="Ej. Fundamentos" />
                <x-input-error :messages="$errors->get('u_nombre')" class="mt-2" />
            </div>
        </form>
        <x-slot name="footer">
            <button type="button" wire:click="$set('showUnidad', false)" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100 transition">Cancelar</button>
            <button type="submit" form="form-unidad" class="rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700 transition">{{ $unidadEditId ? 'Guardar' : 'Crear unidad' }}</button>
        </x-slot>
    </x-modal-panel>
    @endif

    @if ($showEvaluacion)
    <x-modal-panel wire="showEvaluacion" :title="$evalEditId ? 'Editar evaluación' : 'Nueva evaluación'" subtitle="Una actividad calificable dentro de la unidad" icon="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" icon-grad="from-brand-500 to-brand-700" max-width="xl">
        <form wire:submit="guardarEvaluacion" id="form-eval" class="space-y-4">
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="e_titulo" :value="__('Título')" />
                    <x-text-input wire:model="e_titulo" id="e_titulo" class="block mt-1 w-full" type="text" placeholder="Ej. Trabajo 1" />
                    <x-input-error :messages="$errors->get('e_titulo')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="e_tipo" :value="__('Tipo')" />
                    <select wire:model="e_tipo" id="e_tipo" class="w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-xl shadow-sm text-slate-900 mt-1">
                        @foreach (\App\Models\Evaluacion::TIPOS as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="e_peso" :value="__('Peso en la unidad (%)')" />
                    <x-text-input wire:model="e_peso" id="e_peso" class="block mt-1 w-full" type="number" min="0" max="100" />
                    <x-input-error :messages="$errors->get('e_peso')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="e_fecha" :value="__('Fecha límite (opcional)')" />
                    <x-text-input wire:model="e_fecha_limite" id="e_fecha" class="block mt-1 w-full" type="date" />
                    <x-input-error :messages="$errors->get('e_fecha_limite')" class="mt-2" />
                </div>
            </div>
            <div>
                <x-input-label for="e_desc" :value="__('Descripción (opcional)')" />
                <textarea wire:model="e_descripcion" id="e_desc" rows="2" class="w-full border-slate-300 focus:border-brand-500 focus:ring-brand-500 rounded-xl shadow-sm text-slate-900 mt-1" placeholder="Instrucciones o entregable esperado"></textarea>
            </div>
        </form>
        <x-slot name="footer">
            <button type="button" wire:click="$set('showEvaluacion', false)" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100 transition">Cancelar</button>
            <button type="submit" form="form-eval" class="rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700 transition">{{ $evalEditId ? 'Guardar' : 'Crear evaluación' }}</button>
        </x-slot>
    </x-modal-panel>
    @endif

    @if ($showCriterio)
    <x-modal-panel wire="showCriterio" :title="$critEditId ? 'Editar criterio' : 'Nuevo criterio'" subtitle="Un aspecto a calificar con su puntaje" icon="M9 12l2 2 4-4" icon-grad="from-brand-500 to-brand-700" max-width="md">
        <form wire:submit="guardarCriterio" id="form-crit" class="space-y-4">
            <div>
                <x-input-label for="cr_nombre" :value="__('Nombre del criterio')" />
                <x-text-input wire:model="cr_nombre" id="cr_nombre" class="block mt-1 w-full" type="text" placeholder="Ej. Carátula, Contenido..." />
                <x-input-error :messages="$errors->get('cr_nombre')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="cr_puntaje" :value="__('Puntaje máximo')" />
                <x-text-input wire:model="cr_puntaje_max" id="cr_puntaje" class="block mt-1 w-full" type="number" step="0.01" min="0" placeholder="Ej. 5" />
                <x-input-error :messages="$errors->get('cr_puntaje_max')" class="mt-2" />
            </div>
        </form>
        <x-slot name="footer">
            <button type="button" wire:click="$set('showCriterio', false)" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100 transition">Cancelar</button>
            <button type="submit" form="form-crit" class="rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700 transition">{{ $critEditId ? 'Guardar' : 'Agregar criterio' }}</button>
        </x-slot>
    </x-modal-panel>
    @endif
</div>
