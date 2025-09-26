<?php

namespace Glugox\TableQuery\Filters;

use Glugox\TableQuery\Contracts\Filter;
use Glugox\ModelMeta\Filter as FilterConfig;
use Illuminate\Database\Eloquent\Builder;

class EnumFilter implements Filter
{

    public function apply(Builder $query, FilterConfig $filterConfig, mixed $value): Builder
    {
        return $query->where($filterConfig->name, $value);
    }

    public function key(): string
    {
        return 'enum';
    }
}
