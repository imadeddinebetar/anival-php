<?php

namespace Core\Http\Message;

use Core\Http\Contracts\RequestInterface;
use Illuminate\Support\Str;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * @internal
 */
class Request implements RequestInterface
{
    protected ServerRequestInterface $request;

    public function __construct(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    public function getMethod(): string
    {
        return $this->request->getMethod();
    }

    public function getUri(): UriInterface
    {
        return $this->request->getUri();
    }

    public function getPath(): string
    {
        $path = $this->request->getUri()->getPath();
        $basePath = $this->getBasePath();

        if ($basePath && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }

        return $path ?: '/';
    }

    /**
     * Get the application base path (subdirectory).
     */
    public function getBasePath(): string
    {
        $serverParams = $this->request->getServerParams();
        $scriptName = $serverParams['SCRIPT_NAME'] ?? '';
        $baseDir = dirname(dirname($scriptName));
        return ($baseDir === DIRECTORY_SEPARATOR || $baseDir === '.') ? '' : $baseDir;
    }

    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $this->request->getAttribute($name, $default);
    }

    public function withAttribute(string $name, mixed $value): static
    {
        $new = clone $this;
        $new->request = $this->request->withAttribute($name, $value);
        return $new;
    }

    public function getQueryParams(): array
    {
        return $this->request->getQueryParams();
    }

    public function getParsedBody(): mixed
    {
        return $this->request->getParsedBody();
    }

    public function getHeaders(): array
    {
        return $this->request->getHeaders();
    }

    /**
     * Proxies calls to the underlying PSR-7 request.
     */
    public function __call(string $method, array $args): mixed
    {
        return $this->request->$method(...$args);
    }

    public function getPsrRequest(): ServerRequestInterface
    {
        return $this->request;
    }



    /**
     * Get an uploaded file.
     *
     * @param string $key
     * @return \Psr\Http\Message\UploadedFileInterface|null
     */
    public function file(string $key): ?\Psr\Http\Message\UploadedFileInterface
    {
        return $this->files()[$key] ?? null;
    }

    /**
     * Get all uploaded files.
     *
     * @return array<string, \Psr\Http\Message\UploadedFileInterface>
     */
    public function files(): array
    {
        return $this->request->getUploadedFiles();
    }

    /**
     * Check if a file was uploaded.
     *
     * @param string $key
     * @return bool
     */
    public function hasFile(string $key): bool
    {
        return isset($this->files()[$key]);
    }

    /**
     * Get the bearer token from the request.
     *
     * @return string|null
     */
    public function bearerToken(): ?string
    {
        $header = $this->request->getHeaderLine('Authorization');

        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }

        return null;
    }
    /**
     * Get all input from the request (query and body).
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return array_merge($this->getQueryParams(), (array)$this->getParsedBody());
    }

    /**
     * Get an input value from the request.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    /**
     * Alias for input().
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->input($key, $default);
    }

    /**
     * Get a subset of the input data.
     *
     * @param array<int, string> $keys
     * @return array<string, mixed>
     */
    public function only(array $keys): array
    {
        $all = $this->all();
        $result = [];

        foreach ($keys as $key) {
            if (array_key_exists($key, $all)) {
                $result[$key] = $all[$key];
            }
        }

        return $result;
    }

    /**
     * Get the client IP address.
     *
     * @return string
     */
    public function getIp(): string
    {
        $params = $this->request->getServerParams();
        return $params['HTTP_X_FORWARDED_FOR'] ?? $params['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Get the matched route for this request.
     *
     * @return Route|null
     */
    public function route(): ?Route
    {
        return $this->getAttribute('__route__');
    }

    /**
     * Determine if the current route name matches a given pattern.
     * Supports wildcards using '*'.
     *
     * @param string ...$patterns
     * @return bool
     */
    public function routeIs(string ...$patterns): bool
    {
        $route = $this->route();
        if ($route === null) {
            return false;
        }

        $name = $route->getName();
        if ($name === null) {
            return false;
        }

        foreach ($patterns as $pattern) {
            if (Str::is($pattern, $name)) {
                return true;
            }
        }

        return false;
    }
    /**
     * Determine if the request is the result of an AJAX call.
     *
     * @return bool
     */
    public function isAjax(): bool
    {
        return $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
    }
}
