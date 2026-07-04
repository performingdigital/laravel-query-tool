<?php

namespace PerformingDigital\QueryTool;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use PerformingDigital\QueryTool\Exceptions\InvalidQueryToolPayload;

final class StatsQueryExecutor
{
    private const OPS = [
        'count' => 'COUNT',
        'sum' => 'SUM',
        'avg' => 'AVG',
        'min' => 'MIN',
        'max' => 'MAX',
    ];

    public function __construct(
        private readonly array $datasets,
        private readonly ?string $defaultConnection = null,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function run(array $payload): array
    {
        $dataset = $this->requireString($payload, 'dataset');
        $config = $this->datasets[$dataset] ?? null;

        if ($config === null) {
            throw InvalidQueryToolPayload::because("Invalid dataset [{$dataset}].");
        }

        $metrics = $payload['metrics'] ?? [];

        if (! is_array($metrics) || $metrics === []) {
            throw InvalidQueryToolPayload::because('At least one metric is required.');
        }

        $query = $this->connection($config)->table($config['table']);
        $selects = [];

        foreach ($payload['dimensions'] ?? [] as $dimension) {
            $this->ensureAllowed($dimension, $config['dimensions'] ?? [], 'dimension');
            $column = $this->fieldColumn($config, $dimension);

            $query->groupBy($column);
            $selects[] = DB::raw($this->quoteIdentifier($column) . ' as ' . $this->quoteIdentifier($dimension));
        }

        foreach ($metrics as $metricName) {
            if (! is_string($metricName) || ! isset($config['metrics'][$metricName])) {
                throw InvalidQueryToolPayload::because('Invalid metric.');
            }

            $metric = $config['metrics'][$metricName];
            $op = strtolower($metric['op'] ?? '');

            if (! isset(self::OPS[$op])) {
                throw InvalidQueryToolPayload::because("Invalid metric operator [{$op}].");
            }

            $field = $metric['field'] ?? null;
            $column = $field === null ? '*' : $this->quoteIdentifier($this->fieldColumn($config, $field));

            $selects[] = DB::raw(self::OPS[$op] . '(' . $column . ') as ' . $this->quoteIdentifier($metricName));
        }

        $query->select($selects);

        foreach ($payload['filters'] ?? [] as $filter) {
            $this->applyFilter($query, $config, $filter);
        }

        if (isset($payload['sort'])) {
            $sort = $payload['sort'];

            if (! is_array($sort)) {
                throw InvalidQueryToolPayload::because('Invalid sort.');
            }

            $field = $this->requireString($sort, 'field');
            $this->ensureAllowed($field, $config['sort'] ?? [], 'sort');

            $dir = strtolower((string) ($sort['dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';
            $query->orderBy($field, $dir);
        }

        $defaultLimit = (int) ($config['default_limit'] ?? 50);
        $maxLimit = (int) ($config['max_limit'] ?? 100);
        $limit = min(max((int) ($payload['limit'] ?? $defaultLimit), 1), $maxLimit);

        return $query->limit($limit)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    private function connection(array $config): ConnectionInterface
    {
        return DB::connection($config['connection'] ?? $this->defaultConnection);
    }

    private function applyFilter($query, array $config, mixed $filter): void
    {
        if (! is_array($filter)) {
            throw InvalidQueryToolPayload::because('Invalid filter.');
        }

        $field = $this->requireString($filter, 'field');
        $op = $this->requireString($filter, 'op');
        $value = $filter['value'] ?? null;

        $allowedOps = $config['filters'][$field] ?? null;

        if (! is_array($allowedOps) || ! in_array($op, $allowedOps, true)) {
            throw InvalidQueryToolPayload::because("Invalid filter [{$field}:{$op}].");
        }

        $column = $this->fieldColumn($config, $field);

        match ($op) {
            'eq' => $query->where($column, '=', $value),
            'neq' => $query->where($column, '!=', $value),
            'gte' => $query->where($column, '>=', $value),
            'lte' => $query->where($column, '<=', $value),
            'gt' => $query->where($column, '>', $value),
            'lt' => $query->where($column, '<', $value),
            'in' => $query->whereIn($column, is_array($value) ? $value : [$value]),
            'contains' => $query->where($column, 'like', '%' . addcslashes((string) $value, '%_\\') . '%'),
            default => throw InvalidQueryToolPayload::because("Unsupported filter operator [{$op}]."),
        };
    }

    private function fieldColumn(array $config, string $field): string
    {
        $column = $config['fields'][$field] ?? null;

        if (! is_string($column) || $column === '') {
            throw InvalidQueryToolPayload::because("Invalid field [{$field}].");
        }

        return $column;
    }

    private function ensureAllowed(string $value, array $allowed, string $type): void
    {
        if (! in_array($value, $allowed, true)) {
            throw InvalidQueryToolPayload::because("Invalid {$type} [{$value}].");
        }
    }

    private function requireString(array $payload, string $key): string
    {
        $value = $payload[$key] ?? null;

        if (! is_string($value) || $value === '') {
            throw InvalidQueryToolPayload::because("Missing or invalid [{$key}].");
        }

        return $value;
    }

    private function quoteIdentifier(string $identifier): string
    {
        if (! preg_match('/^[A-Za-z_][A-Za-z0-9_\.]*$/', $identifier)) {
            throw InvalidQueryToolPayload::because("Invalid SQL identifier [{$identifier}].");
        }

        return collect(explode('.', $identifier))
            ->map(fn (string $part) => '`' . str_replace('`', '``', $part) . '`')
            ->implode('.');
    }
}
