<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class DocenteScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (auth()->check()) {
            $builder->where($model->getTable().'.docente_id', auth()->id());
        }
    }
}
