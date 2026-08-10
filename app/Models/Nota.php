<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['evaluacion_id', 'criterio_id', 'estudiante_id', 'valor', 'comentario'])]
class Nota extends Model
{
    use HasUuids;

    protected $casts = [
        'valor' => 'decimal:2',
    ];

    public function evaluacion()
    {
        return $this->belongsTo(Evaluacion::class);
    }

    public function criterio()
    {
        return $this->belongsTo(Criterio::class);
    }

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class);
    }
}
