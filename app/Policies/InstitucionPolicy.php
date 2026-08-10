<?php

namespace App\Policies;

use App\Models\Institucion;
use App\Models\User;

class InstitucionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Institucion $institucion): bool
    {
        return $user->id === $institucion->docente_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Institucion $institucion): bool
    {
        return $user->id === $institucion->docente_id;
    }

    public function delete(User $user, Institucion $institucion): bool
    {
        return $user->id === $institucion->docente_id;
    }

    public function restore(User $user, Institucion $institucion): bool
    {
        return $user->id === $institucion->docente_id;
    }

    public function forceDelete(User $user, Institucion $institucion): bool
    {
        return $user->id === $institucion->docente_id;
    }
}
