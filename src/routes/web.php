<?php

use App\Controllers\UserController;
use App\Middleware\Authenticate;

$router->get('/', function () {
    return view('welcome');
});

$router->get('/utilisateurs', [UserController::class, 'index']);
$router->get('/utilisateurs/{id}', [UserController::class, 'show']);

$router->group(['middleware' => Authenticate::class], function () use ($router) {

});
