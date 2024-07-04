<?php

return [
    'hosts' => [
        [
            'host'   => env('ELASTICSEARCH_HOST', 'localhost'),
            'port'   => env('ELASTICSEARCH_PORT', 9200),
            'scheme' => env('ELASTICSEARCH_SCHEME', 'http'),
        ],
    ],
    'user'     => env('ELASTICSEARCH_USER', null),
    'password' => env('ELASTICSEARCH_PASSWORD', null),
    'retries'  => env('ELASTICSEARCH_RETRIES', 0), // Optional: Set retry attempts
    'timeout'  => env('ELASTICSEARCH_TIMEOUT', null), // Optional: Set connection timeout
    'headers'  => env('ELASTICSEARCH_HEADERS', []), // Optional: Add custom headers
];