<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Feriados nacionales del Perú (fijos + Semana Santa, que es móvil).
 */
class Feriados
{
    /** Feriados de fecha fija: 'd-m' => nombre */
    private const FIJOS = [
        '1-1' => 'Año Nuevo',
        '1-5' => 'Día del Trabajo',
        '29-6' => 'San Pedro y San Pablo',
        '23-7' => 'Día de la Fuerza Aérea',
        '28-7' => 'Fiestas Patrias',
        '29-7' => 'Fiestas Patrias',
        '6-8' => 'Batalla de Junín',
        '30-8' => 'Santa Rosa de Lima',
        '8-10' => 'Combate de Angamos',
        '1-11' => 'Todos los Santos',
        '8-12' => 'Inmaculada Concepción',
        '9-12' => 'Batalla de Ayacucho',
        '25-12' => 'Navidad',
    ];

    /** Devuelve el nombre del feriado si la fecha lo es, o null. */
    public static function nombre(Carbon $fecha): ?string
    {
        $clave = $fecha->day.'-'.$fecha->month;
        if (isset(self::FIJOS[$clave])) {
            return self::FIJOS[$clave];
        }

        // Semana Santa (Jueves y Viernes Santo) — basados en la Pascua
        $pascua = Carbon::createFromTimestamp(easter_date($fecha->year))->startOfDay();
        if ($fecha->isSameDay($pascua->copy()->subDays(3))) {
            return 'Jueves Santo';
        }
        if ($fecha->isSameDay($pascua->copy()->subDays(2))) {
            return 'Viernes Santo';
        }

        return null;
    }

    public static function esFeriado(Carbon $fecha): bool
    {
        return self::nombre($fecha) !== null;
    }
}
