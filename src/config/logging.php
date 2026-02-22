<?php

/**
 * Logging Configuration
 * 
 * This file configures the logging channels and levels for the application.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */
    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, supports a variety of log channels. You are free
    | to add additional channels as needed.
    |
    */
    'channels' => [
        // Stack channel - combines multiple channels
        'stack' => [
            'driver' => 'stack',
            'channels' => ['daily'],
            'ignore_exceptions' => true,
        ],

        // Daily rotating file channel
        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/app.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],

        // Single file channel
        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/app.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        // Error-only file channel
        'error' => [
            'driver' => 'daily',
            'path' => storage_path('logs/error.log'),
            'level' => 'error',
            'days' => 30,
        ],

        // Debug file channel
        'debug' => [
            'driver' => 'daily',
            'path' => storage_path('logs/debug.log'),
            'level' => 'debug',
            'days' => 7,
        ],

        // Query log channel
        'query' => [
            'driver' => 'daily',
            'path' => storage_path('logs/query.log'),
            'level' => 'debug',
            'days' => 7,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Levels
    |--------------------------------------------------------------------------
    |
    | Here you may define the default log levels. The log levels specify
    | which messages should be logged. Available log levels are:
    | emergency, alert, critical, error, warning, notice, info, debug.
    |
    */
    'level' => env('LOG_LEVEL', 'debug'),

    /*
    |--------------------------------------------------------------------------
    | Log Context
    |--------------------------------------------------------------------------
    |
    | Enable automatic context injection for all log messages.
    | This includes request ID, user ID, and other useful information.
    |
    */
    'context' => [
        'include_request_id' => true,
        'include_user_id' => true,
        'include_ip' => true,
        'include_url' => true,
    ],
];
