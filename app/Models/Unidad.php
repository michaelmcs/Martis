<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['curso_id', 'numero', 'nombre', 'peso'])]
class Unidad extends Model
{
    use HasUuids;

    protected $table = 'unidades';

    protected $casts = [
        'numero' => 'integer',
        'peso' => 'integer',
    ];

    public function curso()
    {
        return $this->belongsTo(Curso::class);
    }

    public function evaluaciones()
    {
        return $this->hasMany(Evaluacion::class);
    }
}
