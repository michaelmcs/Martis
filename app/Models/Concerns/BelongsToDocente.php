<?php

namespace App\Models\Concerns;

use App\Models\Scopes\DocenteScope;
use App\Models\User;

trait BelongsToDocente
{
    public static function bootBelongsToDocente(): void
    {
        static::addGlobalScope(new DocenteScope);

        static::creating(function ($model) {
            if (! $model->docente_id && auth()->check()) {
                $model->docente_id = auth()->id();
            }
        });
    }

    public function docente()
    {
        return $this->belongsTo(User::class, 'docente_id');
    }
}
