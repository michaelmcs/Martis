<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['unidad_id', 'titulo', 'tipo', 'descripcion', 'peso', 'fecha_limite'])]
class Evaluacion extends Model
{
    use HasUuids;

    protected $table = 'evaluaciones';

    public const TIPOS = [
        'examen' => 'Examen',
        'trabajo' => 'Trabajo',
        'participacion' => 'Participación',
        'practica' => 'Práctica',
        'proyecto' => 'Proyecto',
        'otro' => 'Otro',
    ];

    protected $casts = [
        'peso' => 'integer',
        'fecha_limite' => 'date',
    ];

    public function unidad()
    {
        return $this->belongsTo(Unidad::class);
    }

    public function criterios()
    {
        return $this->hasMany(Criterio::class)->orderBy('orden');
    }

    public function notas()
    {
        return $this->hasMany(Nota::class);
    }

    public function getPuntajeTotalAttribute(): float
    {
        return (float) $this->criterios->sum('puntaje_max');
    }
}
