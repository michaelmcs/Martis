<?php

namespace App\Models;

use App\Models\Concerns\BelongsToDocente;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['docente_id', 'institucion_id', 'nombre', 'regimen', 'anio', 'periodo', 'semanas', 'tope_faltas', 'peso_asistencia'])]
class Curso extends Model
{
    use HasUuids, BelongsToDocente;

    public const REGIMENES = [
        'anual' => 'Anual',
        'semestral' => 'Semestral',
        'trimestral' => 'Trimestral',
    ];

    public const PERIODOS = ['I', 'II', 'III', 'IV', 'V', 'VI'];

    protected $casts = [
        'anio' => 'integer',
        'semanas' => 'integer',
    ];

    public function getPeriodoAcademicoAttribute(): string
    {
        $regimen = self::REGIMENES[$this->regimen] ?? ucfirst((string) $this->regimen);
        $partes = array_filter([
            $this->anio,
            $regimen,
            $this->regimen === 'anual' ? null : $this->periodo,
        ]);

        return implode(' · ', $partes);
    }

    public function institucion()
    {
        return $this->belongsTo(Institucion::class);
    }

    public function estudiantes()
    {
        return $this->belongsToMany(Estudiante::class, 'matriculas')
            ->withPivot('id', 'estado')
            ->withTimestamps();
    }

    public function matriculas()
    {
        return $this->hasMany(Matricula::class);
    }

    public function unidades()
    {
        return $this->hasMany(Unidad::class)->orderBy('numero');
    }

    public function sesiones()
    {
        return $this->hasMany(Sesion::class)->orderBy('fecha');
    }

    public function horarios()
    {
        return $this->hasMany(Horario::class)->orderBy('dia')->orderBy('hora_inicio');
    }
}
