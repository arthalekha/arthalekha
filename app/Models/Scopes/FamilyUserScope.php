<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class FamilyUserScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (auth()->guest()) {
            return;
        }

        if (request()->routeIs('family.*')) {
            return;
        }

        $builder->where('user_id', auth()->id());
    }
}
