<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['evaluacion_id', 'nombre', 'puntaje_max', 'orden'])]
class Criterio extends Model
{
    use HasUuids;

    protected $casts = [
        'puntaje_max' => 'decimal:2',
        'orden' => 'integer',
    ];

    public function evaluacion()
    {
        return $this->belongsTo(Evaluacion::class);
    }

    public function notas()
    {
        return $this->hasMany(Nota::class);
    }
}
