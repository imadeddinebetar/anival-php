<?php

return [
    'default' => env('CACHE_DRIVER', 'file'),

    'prefix' => env('CACHE_PREFIX', 'anival_cache_'),

    'stores' => [
        'file' => [
            'driver' => 'file',
            'path' => storage_path('cache'),
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_URL', 'redis://localhost'),
        ],

        'apcu' => [
            'driver' => 'apcu',
        ],

        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],
    ],
];
