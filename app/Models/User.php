<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'apellidos', 'dni', 'celular', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROL_DOCENTE = 'docente';
    public const ROL_ADMIN = 'administrador';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim($this->name.' '.$this->apellidos);
    }

    public function esAdmin(): bool
    {
        return $this->rol === self::ROL_ADMIN;
    }

    public function esDocente(): bool
    {
        return $this->rol === self::ROL_DOCENTE;
    }

    public function instituciones()
    {
        return $this->hasMany(Institucion::class, 'docente_id');
    }

    public function cursos()
    {
        return $this->hasMany(Curso::class, 'docente_id');
    }

    public function estudiantes()
    {
        return $this->hasMany(Estudiante::class, 'docente_id');
    }
}
