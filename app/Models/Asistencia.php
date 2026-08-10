<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['sesion_id', 'estudiante_id', 'estado'])]
class Asistencia extends Model
{
    use HasUuids;

    protected $table = 'asistencias';

    public const ESTADOS = [
        'presente' => 'Presente',
        'ausente' => 'Ausente',
        'justificado' => 'Justificado',
    ];

    public function sesion()
    {
        return $this->belongsTo(Sesion::class);
    }

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class);
    }
}
