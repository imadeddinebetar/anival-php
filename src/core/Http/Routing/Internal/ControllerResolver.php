<?php

namespace Core\Http\Routing\Internal;

use Core\Container\Contracts\ContainerInterface;
use Core\Config\Contracts\ConfigRepositoryInterface;
use Core\Database\Contracts\ModelBinderInterface;
use Core\Http\Message\Request;
use Core\Http\Message\Response;
use Core\Http\Routing\Contracts\ControllerResolverInterface;
use Psr\Http\Message\ResponseInterface;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionException;
use ReflectionNamedType;
use RuntimeException;

/**
 * @internal
 */
class ControllerResolver implements ControllerResolverInterface
{
    public function __construct(
        protected ContainerInterface $container,
        protected ConfigRepositoryInterface $config,
        protected ?ModelBinderInterface $modelBinder = null
    ) {}

    public function resolve(Request $request, string|array|callable $action): ResponseInterface
    {
        if (is_string($action)) {
            return $this->resolveControllerAction($request, $action);
        }

        if (is_array($action)) {
            return $this->resolveArrayControllerAction($request, $action);
        }

        // It's a callable
        $result = $this->resolveCallable($request, $action);
        return $result instanceof Response ? $result : new Response($result);
    }

    protected function resolveCallable(Request $request, callable $action): mixed
    {
        $parameters = $this->resolveActionParameters($request, $action);

        $defaultParameters = [
            'request' => $request,
            Request::class => $request
        ];

        return $this->container->call($action, array_merge($defaultParameters, $parameters));
    }

    protected function resolveControllerAction(Request $request, string $action): Response
    {
        if (!str_contains($action, '@')) {
            throw new RuntimeException("Invalid action format '{$action}'. Expected 'Controller@method'.");
        }

        [$controller, $method] = explode('@', $action);
        if (!str_contains($controller, '\\')) {
            $namespace = $this->config->get('app.controller_namespace', 'App\\Controllers');
            $controller = $namespace . '\\' . $controller;
        }

        $instance = $this->container->get($controller);
        $parameters = $this->resolveActionParameters($request, [$instance, $method]);

        $defaultParameters = [
            'request' => $request,
            Request::class => $request
        ];

        $result = $this->container->call([$instance, $method], array_merge($defaultParameters, $parameters));

        return $result instanceof Response ? $result : new Response($result);
    }

    protected function resolveArrayControllerAction(Request $request, array $action): Response
    {
        [$controllerClass, $method] = $action;

        if (is_string($controllerClass)) {
            $instance = $this->container->get($controllerClass);
        } else {
            $instance = $controllerClass;
        }

        $parameters = $this->resolveActionParameters($request, [$instance, $method]);

        $defaultParameters = [
            'request' => $request,
            Request::class => $request
        ];

        $result = $this->container->call([$instance, $method], array_merge($defaultParameters, $parameters));

        return $result instanceof Response ? $result : new Response($result);
    }

    /**
     * @param Request $request
     * @param callable $action
     * @return array<string, mixed>
     */
    protected function resolveActionParameters(Request $request, callable $action): array
    {
        $parameters = [];

        try {
            $reflection = is_array($action)
                ? new ReflectionMethod($action[0], $action[1])
                : new ReflectionFunction($action);
        } catch (ReflectionException $e) { // @codeCoverageIgnoreStart
            throw new RuntimeException('Cannot reflect on route action: ' . $e->getMessage(), 0, $e);
            // @codeCoverageIgnoreEnd
        }

        foreach ($reflection->getParameters() as $parameter) {
            $name = $parameter->getName();
            $type = $parameter->getType();

            // For builtin types (int, string, float, bool), resolve from route parameters (request attributes)
            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                $routeValue = $request->getAttribute($name);
                if ($routeValue !== null) {
                    // Cast to the declared type
                    if ($type instanceof ReflectionNamedType) {
                        $parameters[$name] = match ($type->getName()) {
                            'int' => (int) $routeValue,
                            'float' => (float) $routeValue,
                            'bool' => (bool) $routeValue,
                            'string' => (string) $routeValue,
                            default => $routeValue,
                        };
                    } else {
                        $parameters[$name] = $routeValue;
                    }
                }
                continue;
            }

            $className = $type->getName();

            // Try model binding first via interface
            if ($this->modelBinder) {
                $value = $request->getAttribute($name);
                if ($value) {
                    $bound = $this->modelBinder->bind($className, $value);
                    if ($bound) {
                        $parameters[$name] = $bound;
                        continue;
                    }
                }
            }

            if ($className === Request::class || is_subclass_of($className, Request::class)) {
                if ($className === Request::class) {
                    $parameters[$name] = $request;
                } else {
                    // Custom Request (FormRequest)
                    /** @var Request $customRequest */
                    $customRequest = $this->container->make($className, ['request' => $request->getPsrRequest()]);

                    // Copy attributes to new request
                    foreach ($request->getPsrRequest()->getAttributes() as $key => $val) {
                        $customRequest = $customRequest->withAttribute($key, $val);
                    }

                    // Auto-validate if method exists
                    if (method_exists($customRequest, 'validateResolved')) {
                        $customRequest->validateResolved();
                    }

                    $parameters[$name] = $customRequest;
                }
            }
        }

        return $parameters;
    }
}
