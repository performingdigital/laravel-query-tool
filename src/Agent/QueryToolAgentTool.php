<?php

namespace PerformingDigital\QueryTool\Agent;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use PerformingDigital\QueryTool\StatsQueryExecutor;
use Stringable;

final class QueryToolAgentTool implements Tool
{
    public function __construct(
        private readonly StatsQueryExecutor $executor,
    ) {}

    public function description(): Stringable|string
    {
        return 'Run a safe aggregate/statistics query on a whitelisted dataset. Use this for totals, counts, averages, rankings and grouped statistics. Never use it for arbitrary SQL.';
    }

    public function handle(Request $request): Stringable|string
    {
        return json_encode([
            'rows' => $this->executor->run($request->all()),
        ], JSON_THROW_ON_ERROR);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'dataset' => $schema->string()
                ->description('Whitelisted dataset name, for example orders.')
                ->required(),

            'metrics' => $schema->array()
                ->items($schema->string())
                ->description('Metric names configured for the dataset, for example revenue or orders.')
                ->required(),

            'dimensions' => $schema->array()
                ->items($schema->string())
                ->description('Optional group-by dimensions configured for the dataset.'),

            'filters' => $schema->array()
                ->items($schema->object([
                    'field' => $schema->string()->required(),
                    'op' => $schema->string()
                        ->enum(['eq', 'neq', 'gt', 'gte', 'lt', 'lte', 'in', 'contains'])
                        ->required(),
                    'value' => $schema->string()->description('Filter value. Use JSON encoded arrays for the in operator.')->required(),
                ]))
                ->description('Optional filters.'),

            'sort' => $schema->object([
                'field' => $schema->string()->required(),
                'dir' => $schema->string()->enum(['asc', 'desc'])->required(),
            ])->description('Optional sort.'),

            'limit' => $schema->integer()
                ->min(1)
                ->max(100)
                ->description('Maximum number of rows to return.'),
        ];
    }
}
