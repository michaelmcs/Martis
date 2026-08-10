<?php

namespace App\Support;

use App\Models\Curso;
use App\Models\Horario;
use Carbon\Carbon;

/**
 * Genera un calendario en formato ICS (RFC 5545) para un curso:
 *  - Clases semanales recurrentes (según el horario), con recordatorio.
 *  - Fechas límite de evaluaciones / exámenes, con recordatorio el día previo.
 */
class IcsGenerator
{
    public static function paraCurso(Curso $curso, ?int $semanas = null): string
    {
        $semanas = $semanas ?: ($curso->semanas ?: 16);
        $curso->loadMissing('horarios', 'unidades.evaluaciones');

        $lineas = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Sistema Docentes//Calendario//ES',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:'.self::esc($curso->nombre),
        ];

        $stamp = Carbon::now()->utc()->format('Ymd\THis\Z');

        // Clases recurrentes
        foreach ($curso->horarios as $h) {
            $inicio = self::proximaFecha((int) $h->dia, $h->hora_inicio);
            $fin = self::proximaFecha((int) $h->dia, $h->hora_fin);
            // asegurar que fin sea el mismo día que inicio
            $fin->setDate($inicio->year, $inicio->month, $inicio->day);

            $byday = Horario::DIAS_ICS[(int) $h->dia] ?? 'MO';

            $lineas = array_merge($lineas, [
                'BEGIN:VEVENT',
                'UID:'.$h->id.'@sistema-docentes',
                'DTSTAMP:'.$stamp,
                'DTSTART:'.$inicio->format('Ymd\THis'),
                'DTEND:'.$fin->format('Ymd\THis'),
                'RRULE:FREQ=WEEKLY;BYDAY='.$byday.';COUNT='.$semanas,
                'SUMMARY:'.self::esc($curso->nombre.' (clase)'),
                'LOCATION:'.self::esc($h->aula ?: 'Aula por definir'),
                'DESCRIPTION:'.self::esc('Clase de '.$curso->nombre),
                'BEGIN:VALARM',
                'TRIGGER:-PT30M',
                'ACTION:DISPLAY',
                'DESCRIPTION:'.self::esc('Recordatorio de clase: '.$curso->nombre),
                'END:VALARM',
                'END:VEVENT',
            ]);
        }

        // Fechas límite de evaluaciones
        foreach ($curso->unidades as $unidad) {
            foreach ($unidad->evaluaciones as $eval) {
                if (! $eval->fecha_limite) {
                    continue;
                }
                $fecha = Carbon::parse($eval->fecha_limite);
                $esExamen = $eval->tipo === 'examen';
                $etiqueta = $esExamen ? 'Examen' : 'Entrega';

                $lineas = array_merge($lineas, [
                    'BEGIN:VEVENT',
                    'UID:'.$eval->id.'@sistema-docentes',
                    'DTSTAMP:'.$stamp,
                    'DTSTART;VALUE=DATE:'.$fecha->format('Ymd'),
                    'DTEND;VALUE=DATE:'.$fecha->copy()->addDay()->format('Ymd'),
                    'SUMMARY:'.self::esc($etiqueta.': '.$eval->titulo.' — '.$curso->nombre),
                    'DESCRIPTION:'.self::esc($eval->descripcion ?: ($etiqueta.' de '.$eval->titulo)),
                    'BEGIN:VALARM',
                    'TRIGGER:-P1D',
                    'ACTION:DISPLAY',
                    'DESCRIPTION:'.self::esc('Recordatorio: '.$eval->titulo),
                    'END:VALARM',
                    'END:VEVENT',
                ]);
            }
        }

        $lineas[] = 'END:VCALENDAR';

        return implode("\r\n", $lineas)."\r\n";
    }

    /** Próxima fecha (>= hoy) que caiga en el día de semana dado, con la hora indicada. */
    private static function proximaFecha(int $dia, $hora): Carbon
    {
        // Carbon: 0=domingo..6=sábado; nuestro dia: 1=lunes..7=domingo
        $isoTarget = $dia; // ISO: 1=lunes..7=domingo
        $fecha = Carbon::today();
        while ($fecha->dayOfWeekIso !== $isoTarget) {
            $fecha->addDay();
        }
        [$h, $m] = array_pad(explode(':', (string) $hora), 2, '00');

        return $fecha->setTime((int) $h, (int) $m, 0);
    }

    private static function esc(string $texto): string
    {
        return str_replace(
            ["\\", ';', ',', "\n", "\r"],
            ['\\\\', '\\;', '\\,', '\\n', ''],
            $texto
        );
    }
}
