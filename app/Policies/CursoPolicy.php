<?php

namespace App\Policies;

use App\Models\Curso;
use App\Models\User;

class CursoPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Curso $curso): bool
    {
        return $user->id === $curso->docente_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Curso $curso): bool
    {
        return $user->id === $curso->docente_id;
    }

    public function delete(User $user, Curso $curso): bool
    {
        return $user->id === $curso->docente_id;
    }

    public function restore(User $user, Curso $curso): bool
    {
        return $user->id === $curso->docente_id;
    }

    public function forceDelete(User $user, Curso $curso): bool
    {
        return $user->id === $curso->docente_id;
    }
}
