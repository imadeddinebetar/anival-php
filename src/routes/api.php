<?php

use App\Controllers\Api\TokenController;
use Core\Http\Middleware\AuthenticateApi;

$router->group(['middleware' => AuthenticateApi::class], function ($router) {
    $router->post('/token/refresh', [TokenController::class, 'refresh']);
    $router->post('/logout', [TokenController::class, 'logout']);
    $router->post('/logout-all', [TokenController::class, 'logoutAll']);
});
