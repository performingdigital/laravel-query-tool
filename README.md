# Laravel Query Tool

Safe query DSL for Laravel chatbots.

The chatbot emits JSON, never SQL. This package validates datasets, fields, metrics, dimensions, filters, sorting and limits, then builds a Laravel query builder query.

## Install

```bash
composer require performingdigital/laravel-query-tool
php artisan vendor:publish --tag=query-tool-config
```

## Example payload

```json
{
  "dataset": "orders",
  "metrics": ["revenue", "orders"],
  "dimensions": ["country"],
  "filters": [
    { "field": "created_at", "op": "gte", "value": "2026-01-01" }
  ],
  "sort": { "field": "revenue", "dir": "desc" },
  "limit": 10
}
```

## Usage

```php
use PerformingDigital\QueryTool\StatsQueryExecutor;

$rows = app(StatsQueryExecutor::class)->run($payload);
```

## Safety

- no raw SQL from the LLM
- dataset whitelist
- field whitelist
- metric whitelist
- dimension whitelist
- filter/operator whitelist
- max limit
- read-only DB user recommended
- read replica recommended

## Config

Publish and edit `config/query-tool.php`.
