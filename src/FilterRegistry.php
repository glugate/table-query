<?php

namespace Glugox\TableQuery\Support;

use Glugox\TableQuery\Contracts\Filter;
use Illuminate\Support\Collection;

class FilterRegistry
{
    /** @var array<string, class-string<Filter>> */
    protected static array $filters = [];

    /**
     * @param string $key
     * @param Filter $filter
     * @return void
     */
    public static function register(string $key, Filter $filter): void
    {
        static::$filters[$key] = $filter::class;
    }

    /**
     * Get the filter class by key.
     *
     * @param string $key
     * @return string
     */
    public static function for(string $key): string
    {
        if (! isset(static::$filters[$key])) {
            throw new \InvalidArgumentException("Unknown filter [$key]");
        }

        return static::$filters[$key];
    }

    /**
     * Get all registered filters.
     *
     * @return Collection<string, class-string<Filter>>
     */
    public static function all(): Collection
    {
        return collect(static::$filters);
    }
}
