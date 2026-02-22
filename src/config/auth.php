<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The Eloquent model class used for authentication. Core modules
    | reference this via config('auth.user_model') so they never import
    | App\ directly.
    |
    */
    'user_model' => App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'guard' => 'session',
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Reset
    |--------------------------------------------------------------------------
    */
    'passwords' => [
        'users' => [
            'table' => 'password_resets',
            'expire' => 60,     // minutes
            'throttle' => 60,   // seconds between reset requests
        ],
    ],
];
