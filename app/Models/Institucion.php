<?php

namespace App\Models;

use App\Models\Concerns\BelongsToDocente;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['docente_id', 'nombre', 'logo', 'lugar', 'direccion'])]
class Institucion extends Model
{
    use HasUuids, BelongsToDocente;

    protected $table = 'instituciones';

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? \Illuminate\Support\Facades\Storage::url($this->logo) : null;
    }

    public function cursos()
    {
        return $this->hasMany(Curso::class);
    }
}
