<?php

namespace Glugox\TableQuery;

use Glugox\TableQuery\Filters\EnumFilter;
use Glugox\TableQuery\Support\FilterRegistry;
use Illuminate\Support\ServiceProvider;

class TableQueryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        FilterRegistry::register('enum', new EnumFilter());
    }

    public function boot(): void
    {
        //
    }
}
