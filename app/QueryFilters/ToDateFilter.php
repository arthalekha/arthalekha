<?php

namespace App\QueryFilters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class ToDateFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property): void
    {
        $query->whereDate('transacted_at', '<=', $value);
    }
}
