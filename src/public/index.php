<?php

use Core\Http\Foundation\HttpKernel;
use Core\Http\Foundation\ResponseEmitter;
use Core\Exceptions\Internal\ErrorHandleService;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;

require __DIR__ . '/../vendor/autoload.php';

$kernel  = null;
$request = null;

try {
    $psr17Factory = new Psr17Factory();
    $request = (new ServerRequestCreator(
        $psr17Factory,
        $psr17Factory,
        $psr17Factory,
        $psr17Factory
    ))->fromGlobals();

    $app = require __DIR__ . '/../bootstrap/app.php';
    $app->bootstrap();

    /** @var \Core\Config\Contracts\ConfigRepositoryInterface $config */
    $config = $app->get(\Core\Config\Contracts\ConfigRepositoryInterface::class);

    $kernel = new HttpKernel($app, $config, null, $config->get('app.middleware.global', []));
    $kernel->bootstrap();

    $response = $kernel->handle($request);
} catch (\Throwable $e) {
    $response = ErrorHandleService::handle($e, $kernel, $request);
}

if ($response !== null) {
    (new ResponseEmitter())->emit($response);
}

// Perform post-response cleanup
if ($kernel !== null && $request !== null && $response !== null) {
    $kernel->terminate($request, $response);
}
