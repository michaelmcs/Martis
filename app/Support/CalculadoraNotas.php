<?php

namespace App\Support;

use App\Models\Asistencia;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\Nota;

/**
 * Calcula las notas consolidadas de un curso siguiendo el modelo del documento:
 *  - Nota de evaluación = suma de puntajes de criterios, normalizada a base 20.
 *  - Nota de unidad = promedio ponderado de sus evaluaciones (según peso).
 *  - Nota final = promedio ponderado de unidades + componente de asistencia (si aplica).
 *
 * Escala vigesimal (0 - 20). Aprobado cuando la nota final es >= 10.5.
 */
class CalculadoraNotas
{
    public const APROBATORIA = 10.5;

    /**
     * Cuaderno tipo Excel: notas por CADA evaluación (base 20) + promedio de unidad,
     * promedio académico y nota final por estudiante, además del promedio de la clase.
     */
    public static function cuaderno(Curso $curso): array
    {
        $unidades = $curso->unidades()->with('evaluaciones.criterios')->get();

        $estudiantes = Estudiante::whereIn('id',
            Matricula::where('curso_id', $curso->id)->pluck('estudiante_id')
        )->orderBy('apellidos')->orderBy('nombres')->get();

        $criterioIds = $unidades->flatMap->evaluaciones->flatMap->criterios->pluck('id');
        $notasIdx = [];
        foreach (Nota::whereIn('criterio_id', $criterioIds)->get() as $n) {
            $notasIdx[$n->estudiante_id][$n->criterio_id] = (float) $n->valor;
        }

        // Asistencia
        $sesionIds = $curso->sesiones()->lectivas()->pluck("id");
        $totalSesiones = $sesionIds->count();
        $ausencias = Asistencia::whereIn('sesion_id', $sesionIds)
            ->where('estado', 'ausente')
            ->selectRaw('estudiante_id, count(*) as total')
            ->groupBy('estudiante_id')->pluck('total', 'estudiante_id');

        $usaAsistencia = (int) $curso->peso_asistencia > 0;
        $p = (int) $curso->peso_asistencia / 100;

        // Nota (base 20) de una evaluación para un estudiante, o null si no tiene notas.
        $notaEval = function ($eval, $estId) use ($notasIdx): ?float {
            $max = (float) $eval->criterios->sum('puntaje_max');
            if ($max <= 0) {
                return null;
            }
            $tiene = false;
            $obt = 0.0;
            foreach ($eval->criterios as $c) {
                if (isset($notasIdx[$estId][$c->id])) {
                    $tiene = true;
                    $obt += $notasIdx[$estId][$c->id];
                }
            }

            return $tiene ? round(($obt / $max) * 20, 2) : null;
        };

        $filas = [];
        $sumaEval = []; // acumulador para promedio de clase por evaluación
        $cuentaEval = [];

        foreach ($estudiantes as $est) {
            $evals = [];
            $notasUnidad = [];
            $pesosUni = [];

            foreach ($unidades as $u) {
                $gradesU = [];
                $pesosE = [];
                foreach ($u->evaluaciones as $e) {
                    $n = $notaEval($e, $est->id);
                    $evals[$e->id] = $n;
                    if ($n !== null) {
                        $gradesU[] = $n;
                        $pesosE[] = (int) $e->peso;
                        $sumaEval[$e->id] = ($sumaEval[$e->id] ?? 0) + $n;
                        $cuentaEval[$e->id] = ($cuentaEval[$e->id] ?? 0) + 1;
                    }
                }
                if (empty($gradesU)) {
                    $notasUnidad[$u->id] = null;
                } else {
                    $sp = array_sum($pesosE);
                    if ($sp > 0) {
                        $acc = 0.0;
                        foreach ($gradesU as $i => $g) {
                            $acc += $g * $pesosE[$i];
                        }
                        $notasUnidad[$u->id] = round($acc / $sp, 2);
                    } else {
                        $notasUnidad[$u->id] = round(array_sum($gradesU) / count($gradesU), 2);
                    }
                    $pesosUni[$u->id] = (int) $u->peso;
                }
            }

            // Académica: promedio ponderado de las unidades con nota
            $conNota = array_filter($notasUnidad, fn ($v) => $v !== null);
            $sumaPesos = array_sum($pesosUni);
            if (empty($conNota)) {
                $academica = 0.0;
            } elseif ($sumaPesos > 0) {
                $acc = 0.0;
                foreach ($pesosUni as $uid => $peso) {
                    $acc += $notasUnidad[$uid] * $peso;
                }
                $academica = $acc / $sumaPesos;
            } else {
                $academica = array_sum($conNota) / count($conNota);
            }

            $faltas = (int) ($ausencias[$est->id] ?? 0);
            $asistPct = $totalSesiones > 0 ? ($totalSesiones - $faltas) / $totalSesiones : 1.0;
            $final = $usaAsistencia ? $academica * (1 - $p) + ($asistPct * 20) * $p : $academica;

            $filas[] = [
                'estudiante_id' => $est->id,
                'nombre' => $est->nombre_completo,
                'codigo' => $est->codigo,
                'evals' => $evals,
                'unidades' => $notasUnidad,
                'academica' => round($academica, 2),
                'asistencia_pct' => round($asistPct * 100, 1),
                'faltas' => $faltas,
                'final' => round($final, 2),
                'aprobado' => $final >= self::APROBATORIA,
            ];
        }

        $promEval = [];
        foreach ($sumaEval as $eid => $suma) {
            $promEval[$eid] = round($suma / $cuentaEval[$eid], 2);
        }
        $finales = array_column($filas, 'final');

        return [
            'unidades' => $unidades->map(fn ($u) => [
                'id' => $u->id, 'numero' => $u->numero, 'nombre' => $u->nombre, 'peso' => $u->peso,
                'evaluaciones' => $u->evaluaciones->map(fn ($e) => [
                    'id' => $e->id, 'titulo' => $e->titulo, 'peso' => $e->peso,
                ])->all(),
            ])->all(),
            'usaAsistencia' => $usaAsistencia,
            'pesoAsistencia' => (int) $curso->peso_asistencia,
            'totalSesiones' => $totalSesiones,
            'filas' => $filas,
            'promEval' => $promEval,
            'promedioCurso' => count($finales) ? round(array_sum($finales) / count($finales), 2) : 0,
        ];
    }

    public static function paraCurso(Curso $curso): array
    {
        $unidades = $curso->unidades()->with('evaluaciones.criterios')->get();

        // Estudiantes matriculados
        $estudiantes = Estudiante::whereIn('id',
            Matricula::where('curso_id', $curso->id)->pluck('estudiante_id')
        )->orderBy('apellidos')->orderBy('nombres')->get();

        // Índice de notas: [estudiante_id][criterio_id] => valor
        $criterioIds = $unidades->flatMap->evaluaciones->flatMap->criterios->pluck('id');
        $notasIdx = [];
        foreach (Nota::whereIn('criterio_id', $criterioIds)->get() as $n) {
            $notasIdx[$n->estudiante_id][$n->criterio_id] = (float) $n->valor;
        }

        // Asistencia
        $sesionIds = $curso->sesiones()->lectivas()->pluck("id");
        $totalSesiones = $sesionIds->count();
        $ausencias = Asistencia::whereIn('sesion_id', $sesionIds)
            ->where('estado', 'ausente')
            ->selectRaw('estudiante_id, count(*) as total')
            ->groupBy('estudiante_id')->pluck('total', 'estudiante_id');

        $usaAsistencia = (int) $curso->peso_asistencia > 0;
        $p = (int) $curso->peso_asistencia / 100;

        $filas = [];
        foreach ($estudiantes as $est) {
            $notasUnidad = [];
            $pesosConNota = [];
            $acumAcademica = 0.0;

            foreach ($unidades as $u) {
                $notaU = self::notaUnidad($u, $est->id, $notasIdx);
                $notasUnidad[$u->id] = $notaU;
                if ($notaU !== null) {
                    $pesosConNota[$u->id] = (int) $u->peso;
                }
            }

            $conNota = array_filter($notasUnidad, fn ($v) => $v !== null);
            $sumaPesos = array_sum($pesosConNota);
            if (empty($conNota)) {
                $academica = 0.0;
            } elseif ($sumaPesos > 0) {
                foreach ($pesosConNota as $uid => $peso) {
                    $acumAcademica += $notasUnidad[$uid] * $peso;
                }
                $academica = $acumAcademica / $sumaPesos;
            } else {
                // pesos en 0 pero hay notas: promedio simple
                $academica = array_sum($conNota) / count($conNota);
            }

            // Asistencia
            $faltas = (int) ($ausencias[$est->id] ?? 0);
            $asistenciaPct = $totalSesiones > 0 ? ($totalSesiones - $faltas) / $totalSesiones : 1.0;
            $notaAsistencia = $asistenciaPct * 20;

            $final = $usaAsistencia
                ? $academica * (1 - $p) + $notaAsistencia * $p
                : $academica;

            $filas[] = [
                'estudiante_id' => $est->id,
                'nombre' => $est->nombre_completo,
                'codigo' => $est->codigo,
                'unidades' => array_map(fn ($v) => $v === null ? null : round($v, 2), $notasUnidad),
                'academica' => round($academica, 2),
                'faltas' => $faltas,
                'asistencia_pct' => round($asistenciaPct * 100, 1),
                'nota_asistencia' => round($notaAsistencia, 2),
                'final' => round($final, 2),
                'aprobado' => $final >= self::APROBATORIA,
            ];
        }

        $finales = array_column($filas, 'final');
        $aprobados = count(array_filter($filas, fn ($f) => $f['aprobado']));

        return [
            'unidades' => $unidades->map(fn ($u) => [
                'id' => $u->id, 'numero' => $u->numero, 'nombre' => $u->nombre, 'peso' => $u->peso,
            ])->all(),
            'usaAsistencia' => $usaAsistencia,
            'pesoAsistencia' => (int) $curso->peso_asistencia,
            'totalSesiones' => $totalSesiones,
            'filas' => $filas,
            'promedioCurso' => count($finales) ? round(array_sum($finales) / count($finales), 2) : 0,
            'aprobados' => $aprobados,
            'desaprobados' => count($filas) - $aprobados,
        ];
    }

    /** Nota de una unidad (base 20) para un estudiante, o null si la unidad no tiene evaluaciones calificables. */
    private static function notaUnidad($unidad, string $estudianteId, array $notasIdx): ?float
    {
        $grades = [];
        $pesos = [];

        foreach ($unidad->evaluaciones as $eval) {
            $maxTotal = (float) $eval->criterios->sum('puntaje_max');
            if ($maxTotal <= 0) {
                continue; // evaluación sin criterios: no califica
            }
            $obtenido = 0.0;
            foreach ($eval->criterios as $crit) {
                $obtenido += $notasIdx[$estudianteId][$crit->id] ?? 0;
            }
            $grades[] = ($obtenido / $maxTotal) * 20;
            $pesos[] = (int) $eval->peso;
        }

        if (empty($grades)) {
            return null;
        }

        $sumaPesos = array_sum($pesos);
        if ($sumaPesos > 0) {
            $acum = 0.0;
            foreach ($grades as $i => $g) {
                $acum += $g * $pesos[$i];
            }

            return $acum / $sumaPesos;
        }

        return array_sum($grades) / count($grades); // pesos en 0: promedio simple
    }
}
