<?php

return [
    'port' => env('WEBSOCKET_PORT', 6001),
    'host' => env('WEBSOCKET_HOST', '127.0.0.1'),
    'count' => 4,
    'name' => 'AnivalWebSocket',
    'redis' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'port' => env('REDIS_PORT', 6379),
        'username' => env('REDIS_USERNAME', ''),
        'password' => env('REDIS_PASSWORD', ''),
    ],
];