<?php

namespace App\Middleware;

use Core\Auth\Contracts\AuthManagerInterface;
use Core\Http\Message\Request;
use Core\Http\Message\Response;
use Closure;

class Authenticate
{
    public function __construct(private AuthManagerInterface $auth) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->auth->check()) {
            return new Response('', 302, ['Location' => '/login']);
        }

        return $next($request);
    }
}
