<?php

namespace Core\Http\Resources;

use Psr\Http\Message\ResponseInterface;
use Core\Http\Message\Response;

/**
 * JSON API Resource.
 *
 * Usage in controllers:
 *   return new UserResource($user);
 *   return UserResource::collection($users);
 */
class JsonResource
{
    /**
     * The underlying resource data.
     *
     * @var array<string, mixed>|object
     */
    public readonly array|object $resource;

    /**
     * Additional data to merge with the resource response.
     *
     * @var array<string, mixed>
     */
    protected array $additional = [];

    /**
     * The HTTP status code for the response.
     */
    protected int $statusCode = 200;

    /**
     * @param array<string, mixed>|object $resource
     */
    public function __construct(array|object $resource)
    {
        $this->resource = $resource;
    }

    /**
     * Transform the resource into an array.
     * Override this in subclasses to customize the output.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        if (is_array($this->resource)) {
            return $this->resource;
        }

        if (method_exists($this->resource, 'toArray')) {
            return $this->resource->toArray();
        }

        return (array) $this->resource;
    }

    /**
     * Set additional meta data on the resource response.
     *
     * @param array<string, mixed> $data
     * @return $this
     */
    public function additional(array $data): static
    {
        $this->additional = $data;
        return $this;
    }

    /**
     * Set the HTTP response status code.
     *
     * @return $this
     */
    public function status(int $code): static
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Create a collection of resources.
     *
     * @param iterable<mixed> $collection
     * @return array<int, array<string, mixed>>
     */
    public static function collection(iterable $collection): array
    {
        $result = [];
        foreach ($collection as $item) {
            $resource = new static($item);
            $result[] = $resource->toArray();
        }
        return $result;
    }

    /**
     * Convert the resource to a PSR-7 JSON response.
     */
    public function toResponse(): Response
    {
        $data = $this->toArray();

        if (!empty($this->additional)) {
            $data = array_merge($data, $this->additional);
        }

        return Response::json($data, $this->statusCode);
    }

    /**
     * Resolve the resource to an array (for serialization).
     *
     * @return array<string, mixed>
     */
    public function resolve(): array
    {
        $data = $this->toArray();

        if (!empty($this->additional)) {
            $data = array_merge($data, $this->additional);
        }

        return $data;
    }
}
