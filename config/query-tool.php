<?php

return [
    'default_connection' => null,

    'datasets' => [
        'orders' => [
            'table' => 'orders',
            'connection' => null,
            'max_limit' => 100,
            'default_limit' => 50,
            'fields' => [
                'id' => 'id',
                'total' => 'total',
                'country' => 'country',
                'created_at' => 'created_at',
            ],
            'metrics' => [
                'orders' => ['op' => 'count', 'field' => 'id'],
                'revenue' => ['op' => 'sum', 'field' => 'total'],
                'avg_order' => ['op' => 'avg', 'field' => 'total'],
            ],
            'dimensions' => ['country', 'created_at'],
            'filters' => [
                'country' => ['eq', 'neq', 'in'],
                'created_at' => ['gte', 'lte'],
                'total' => ['gte', 'lte'],
            ],
            'sort' => ['orders', 'revenue', 'avg_order', 'country', 'created_at'],
        ],
    ],
];
