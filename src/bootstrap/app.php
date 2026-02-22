<?php

use Core\Container\Internal\Application;
use Dotenv\Dotenv;

// Load environment variables
if (file_exists(dirname(__DIR__) . '/.env')) {
    $dotenv = Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->load();
}

$app = new \Core\Container\Internal\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

// Validate critical environment variables
$app->validateEnvironment(['APP_KEY', 'APP_ENV']);

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

return $app;
