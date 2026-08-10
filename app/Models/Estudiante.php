<?php

namespace App\Models;

use App\Models\Concerns\BelongsToDocente;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['docente_id', 'codigo', 'nombres', 'apellidos', 'correo', 'celular'])]
class Estudiante extends Model
{
    use HasUuids, BelongsToDocente;

    public function getNombreCompletoAttribute(): string
    {
        return trim($this->nombres.' '.$this->apellidos);
    }

    public function cursos()
    {
        return $this->belongsToMany(Curso::class, 'matriculas')
            ->withPivot('id', 'estado')
            ->withTimestamps();
    }
}
