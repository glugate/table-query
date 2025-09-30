<?php

namespace Glugox\TableQuery\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SearchService
{
    /**
     * Apply search to a query builder.
     *
     * @param Builder<Model> $query The Eloquent query builder instance.
     * @param string|null $searchTerm The search term to apply.
     * @param string[]|null $searchableFields The fields to search in.
     * @return Builder<Model> The modified query builder with search applied.
     */
    public function apply(
        Builder $query,
        ?string $searchTerm,
        ?array $searchableFields = [],
    ): Builder {

        // Apply search
        if ($searchTerm && ($searchableFields !== null && $searchableFields !== [])) {
            $query->where(function ($q) use ($searchTerm, $searchableFields): void {
                foreach ($searchableFields as $field) {
                    $q->orWhere($field, 'like', "%{$searchTerm}%");
                }
            });
        }

        return $query;
    }
}
