<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['curso_id', 'dia', 'hora_inicio', 'hora_fin', 'aula'])]
class Horario extends Model
{
    use HasUuids;

    public const DIAS = [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
        7 => 'Domingo',
    ];

    // Código ICS por día (BYDAY)
    public const DIAS_ICS = [1 => 'MO', 2 => 'TU', 3 => 'WE', 4 => 'TH', 5 => 'FR', 6 => 'SA', 7 => 'SU'];

    protected $casts = [
        'dia' => 'integer',
    ];

    public function getDiaNombreAttribute(): string
    {
        return self::DIAS[$this->dia] ?? '—';
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class);
    }
}
