<?php

namespace Core\Http\Contracts;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * Core-owned request contract.
 *
 * Wraps a PSR-7 ServerRequestInterface and provides convenience methods
 * for accessing request data. Application code should type-hint this
 * interface rather than the concrete Request class.
 */
interface RequestInterface
{
    /**
     * Get the HTTP method (GET, POST, PUT, etc.).
     */
    public function getMethod(): string;

    /**
     * Get the request URI.
     */
    public function getUri(): UriInterface;

    /**
     * Get the request path, relative to the application base path.
     */
    public function getPath(): string;

    /**
     * Get the application base path (subdirectory).
     */
    public function getBasePath(): string;

    /**
     * Get a request attribute.
     */
    public function getAttribute(string $name, mixed $default = null): mixed;

    /**
     * Return a new instance with the given attribute set.
     */
    public function withAttribute(string $name, mixed $value): static;

    /**
     * Get all query parameters.
     *
     * @return array<string, mixed>
     */
    public function getQueryParams(): array;

    /**
     * Get the parsed body (POST data, JSON, etc.).
     */
    public function getParsedBody(): mixed;

    /**
     * Get all request headers.
     *
     * @return array<string, string[]>
     */
    public function getHeaders(): array;

    /**
     * Get the underlying PSR-7 server request.
     */
    public function getPsrRequest(): ServerRequestInterface;

    /**
     * Get an uploaded file.
     */
    public function file(string $key): ?\Psr\Http\Message\UploadedFileInterface;

    /**
     * Get all uploaded files.
     *
     * @return array<string, \Psr\Http\Message\UploadedFileInterface>
     */
    public function files(): array;

    /**
     * Check if a file was uploaded.
     */
    public function hasFile(string $key): bool;

    /**
     * Get the bearer token from the Authorization header.
     */
    public function bearerToken(): ?string;

    /**
     * Get all input from the request (query and body merged).
     *
     * @return array<string, mixed>
     */
    public function all(): array;

    /**
     * Get an input value from the request.
     */
    public function input(string $key, mixed $default = null): mixed;

    /**
     * Get a subset of the input data.
     *
     * @param array<int, string> $keys
     * @return array<string, mixed>
     */
    public function only(array $keys): array;

    /**
     * Get the client IP address.
     */
    public function getIp(): string;

    /**
     * Determine if the request is the result of an AJAX call.
     */
    public function isAjax(): bool;
}
