<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['curso_id', 'fecha', 'tipo', 'tema', 'enlace'])]
class Sesion extends Model
{
    use HasUuids;

    protected $table = 'sesiones';

    public const TIPOS = [
        'clase' => 'Clase (presencial)',
        'virtual' => 'Clase virtual',
        'feriado' => 'Feriado',
        'toma_local' => 'Toma de local',
        'parada' => 'Parada universitaria',
        'actividad' => 'Actividad',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function getTipoLabelAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? 'Clase';
    }

    /** ¿Es un día que cuenta para la asistencia? (hay clase, presencial o virtual con enlace) */
    public function getEsLectivaAttribute(): bool
    {
        return in_array($this->tipo, ['clase', 'virtual'], true) || filled($this->enlace);
    }

    /** Scope: solo sesiones que cuentan para la asistencia. */
    public function scopeLectivas($query)
    {
        return $query->where(function ($q) {
            $q->whereIn('tipo', ['clase', 'virtual'])->orWhereNotNull('enlace');
        });
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class);
    }

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class);
    }
}
