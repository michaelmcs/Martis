<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['curso_id', 'estudiante_id', 'estado'])]
class Matricula extends Model
{
    use HasUuids;

    public function curso()
    {
        return $this->belongsTo(Curso::class);
    }

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class);
    }
}
