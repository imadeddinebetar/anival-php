<?php

namespace Core\Http\Routing\Contracts;

use Core\Http\Message\Request;
use Psr\Http\Message\ResponseInterface;

interface ControllerResolverInterface
{
    /**
     * Resolve the controller or action and return the response.
     *
     * @param Request $request
     * @param string|array|callable $action
     * @return ResponseInterface
     */
    public function resolve(Request $request, string|array|callable $action): ResponseInterface;
}
