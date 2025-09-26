<?php

namespace Glugox\TableQuery\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Glugox\ModelMeta\Filter as FilterConfig;

interface Filter {

    /**
     * Apply the filter to the query.
     */
    public function apply(Builder $query, FilterConfig $filterConfig, mixed $value): Builder;

    /**
     * Return the unique key for this filter.
     */
    public function key(): string;

    /**
     * Human-readable label for UI.
     */
    //public function label(): string;

    /**
     * Optional: return available options (for select filters).
     */
    //public function options(): array;
}