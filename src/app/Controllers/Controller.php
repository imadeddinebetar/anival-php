<?php

namespace App\Controllers;

use Core\Http\Message\Response;
use Core\Validation\Traits\ValidatesRequests;

abstract class Controller
{
    use ValidatesRequests;

    protected function json(array $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    protected function redirect(string $url, int $status = 302): Response
    {
        return new Response('', $status, ['Location' => $url]);
    }
}
