<?php

namespace PerformingDigital\QueryTool;

use Illuminate\Support\ServiceProvider;

final class QueryToolServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/query-tool.php', 'query-tool');

        $this->app->singleton(StatsQueryExecutor::class, function ($app) {
            return new StatsQueryExecutor(
                config('query-tool.datasets', []),
                config('query-tool.default_connection'),
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/query-tool.php' => config_path('query-tool.php'),
        ], 'query-tool-config');
    }
}
