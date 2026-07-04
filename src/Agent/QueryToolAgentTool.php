<?php

namespace PerformingDigital\QueryTool\Agent;

use PerformingDigital\QueryTool\StatsQueryExecutor;

final class QueryToolAgentTool
{
    public function __construct(
        private readonly StatsQueryExecutor $executor,
    ) {}

    public function name(): string
    {
        return 'query_dataset_statistics';
    }

    public function description(): string
    {
        return 'Run a safe aggregate/statistics query on a whitelisted dataset. Use this for totals, counts, averages, rankings and grouped statistics. Never use it for arbitrary SQL.';
    }

    /**
     * OpenAI/Anthropic-style JSON schema for function/tool calling.
     *
     * @return array<string, mixed>
     */
    public function schema(): array
    {
        return [
            'name' => $this->name(),
            'description' => $this->description(),
            'parameters' => [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['dataset', 'metrics'],
                'properties' => [
                    'dataset' => [
                        'type' => 'string',
                        'description' => 'Whitelisted dataset name, for example orders.',
                    ],
                    'metrics' => [
                        'type' => 'array',
                        'minItems' => 1,
                        'items' => ['type' => 'string'],
                        'description' => 'Metric names configured for the dataset, for example revenue or orders.',
                    ],
                    'dimensions' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                        'description' => 'Optional group-by dimensions configured for the dataset.',
                    ],
                    'filters' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'additionalProperties' => false,
                            'required' => ['field', 'op', 'value'],
                            'properties' => [
                                'field' => ['type' => 'string'],
                                'op' => [
                                    'type' => 'string',
                                    'enum' => ['eq', 'neq', 'gt', 'gte', 'lt', 'lte', 'in', 'contains'],
                                ],
                                'value' => [
                                    'description' => 'Scalar value or array for in operator.',
                                ],
                            ],
                        ],
                    ],
                    'sort' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['field', 'dir'],
                        'properties' => [
                            'field' => ['type' => 'string'],
                            'dir' => ['type' => 'string', 'enum' => ['asc', 'desc']],
                        ],
                    ],
                    'limit' => [
                        'type' => 'integer',
                        'minimum' => 1,
                        'maximum' => 100,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array{tool:string, rows:array<int, array<string, mixed>>}
     */
    public function handle(array $arguments): array
    {
        return [
            'tool' => $this->name(),
            'rows' => $this->executor->run($arguments),
        ];
    }

    /**
     * Alias useful for invokable agent integrations.
     *
     * @return array{tool:string, rows:array<int, array<string, mixed>>}
     */
    public function __invoke(array $arguments): array
    {
        return $this->handle($arguments);
    }
}
