<?php

namespace Glugox\TableQuery\Services;

use Glugox\ModelMeta\ModelMeta;
use Glugox\ModelMeta\ModelMetaResolver;
use Glugox\TableQuery\Contracts\Filter;
use Glugox\TableQuery\Support\FilterRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

/**
 * Service to handle table queries: search, sort, pagination.
 */
class TableQueryService
{
    /**
     * @var ModelMeta
     */
    protected ModelMeta $modelMeta;

    /**
     * Constructor
     */
    public function __construct(
        protected SearchService $searchService,
    ) {}

    /**
     * Build instance for specific model meta.
     */
    public static function forModel(string $modelClass): TableQueryService
    {
        return app(static::class)
            ->setModelMeta(ModelMetaResolver::make($modelClass));
    }

    /**
     * @param ModelMeta
     */
    public function setModelMeta(ModelMeta $modelMeta): self
    {
        $this->modelMeta = $modelMeta;
        return $this;
    }

    /**
     * Apply all query modifications: search, sort, pagination.
     *
     * Usage:
     * ```php
     * $query = Product::query();
     * $query = $tableQueryService->applyAll(
     *     $query,
     *     searchableFields: ['name', 'description'],
     *     selectedIds: $selectedProductIds,
     *     defaultSortField: 'name',
     *     defaultSortDir: 'asc',
     *     relations: ['category', 'tags'],
     *     queryFields: ['id', 'name', 'price', 'category_id']
     * );
     * $items = $query->paginate(12);
     * ```
     *
     * @param  Builder  $query  The Eloquent query builder instance.
     * @param  array  $searchableFields  Fields to search in.
     * @param  string[]  $relations  Relations to eager load.
     * @param  array  $selectFields  Fields to select in the query.
     * @param  string|null  $searchString  The search string from request.
     * @param  string|null  $defaultSortField  Default field to sort by if none specified in request.
     * @param  string|null  $defaultSortDir  Default sort direction ('asc' or 'desc').
     * @return Builder The modified query builder instance.
     */
    public function applyAll(
        Builder $query,
        ?string $searchString = '',
        ?string $defaultSortField = null,
        ?string $defaultSortDir = null,
    ): Builder {

        /**
         * array $searchableFields = [],
         * ?array $relations = [],
         * array $selectFields = [],
         */
        $selectFields = $this->modelMeta->tableFields();
        $searchableFields = $this->modelMeta->searchableFields();
        $relations = $this->modelMeta->relationsNames();

        // Eager load relations
        if ($relations !== null && $relations !== []) {
            $query->with($relations);
        }

        // Select specific fields if any
        if ($selectFields !== []) {
            $query->select($selectFields);
        }

        // Apply search
        if ($searchString !== null && $searchString !== '' && $searchString !== '0') {
            $query = $this->searchService->apply(
                $query,
                $searchString,
                $searchableFields
            );
        }

        // Filters can be applied here as well if needed
        // e.g., $query = $this->filterService->apply($query, request()->all());
        $query = $this->applyFilters($query, request('filters', []));

        // Apply sorting
        return $this->applySort(
            $query,
            $defaultSortField,
            $defaultSortDir
        );
    }

    /**
     * Apply registered filters to a query based on request parameters.
     *
     * @param Builder $query The Eloquent query builder instance.
     * @param array $filters
     * @return Builder The modified query builder instance.
     */
    public function applyFilters(Builder $query, array $filters): Builder
    {
        foreach ($filters as $key => $value) {
            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            try {
                $filterClass = FilterRegistry::for($key);
                if(!$filterClass) {
                    Log::warning("Filter class not found for key: {$key}");
                    continue;
                }

                /** @var Filter $filter */
                $filter = app($filterClass);
                $query = $filter->apply($query, $filterConfig, $value);
            } catch (\Throwable $e) {
                // log or ignore
            }
        }

        return $query;
    }

    /**
     * Apply sorting to a query.
     *
     * If `sortKey` exists in request, uses it.
     * Otherwise, can optionally order `selectedIds` first, then by default field and direction.
     *
     * @param  Builder  $query  The Eloquent query builder instance.
     * @param  string  $defaultField  Default field to sort by if none specified in request.
     * @param  string  $defaultDir  Default sort direction ('asc' or 'desc').
     * @return Builder The modified query builder instance.
     */
    public function applySort(
        Builder $query,
        ?string $defaultField = null,
        ?string $defaultDir = null
    ): Builder {
        $request = request();

        $defaultField = $defaultField ?? 'id';
        $defaultDir = $defaultDir ?? 'desc';

        if ($request->has('sortKey')) {
            $query->orderBy($defaultField, $defaultDir);
        }/* elseif (!empty($selectedIds)) {
            // Selected first
            $ids = implode(',', $selectedIds);
            $query->orderByRaw("CASE WHEN id IN ({$ids}) THEN 0 ELSE 1 END")
                ->orderBy($defaultField, $defaultDir);
        }*/ else {
            $query->orderBy($defaultField, $defaultDir);
        }

        return $query;
    }

    /**
     * Apply pagination to a query.
     */
    public function paginate(Builder $query, int $page = 1, int $perPage = 12): LengthAwarePaginator
    {
        // Paginate
        return $query->paginate(
            $perPage,
            ['*'],
            'page',
            $page
        );
    }

    /**
     * Prepare filters from request data.
     *
     * This can be extended to handle more complex filter logic.
     *
     * @param  array  $requestData  The request data (e.g. $request->all()).
     * @param  array  $selectFields  The fields available for selection.
     * @return array The prepared filters.
     */
    public function prepareFilters(array $requestData): array
    {
        $filters = $requestData;

        // Exclude *_id fields
        $filteredFields = array_filter($this->modelMeta->tableFields(), function ($field) {
            return ! str_ends_with($field, '_id');
        });

        $filters['allColumns'] = array_values(array_map(function ($item) {
            return [
                'name' => $item,
                'label' => ucfirst(str_replace('_', ' ', $item)),
            ];
        }, $filteredFields));

        $filters['visibleColumns'] = array_values($filteredFields); // reset array keys

        return $filters;
    }
}
