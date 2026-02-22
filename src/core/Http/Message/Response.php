<?php

namespace Core\Http\Message;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @phpstan-consistent-constructor
 * @internal
 */
class Response implements ResponseInterface
{
    protected ResponseInterface $response;

    public function __construct(ResponseInterface|string $response = '', int $status = 200, array $headers = [])
    {
        if (is_string($response)) {
            $this->response = new \Nyholm\Psr7\Response($status, $headers, $response);
        } else {
            $this->response = $response;
        }
    }

    public function getProtocolVersion(): string
    {
        return $this->response->getProtocolVersion();
    }

    public function withProtocolVersion(string $version): static
    {
        $new = clone $this;
        $new->response = $this->response->withProtocolVersion($version);
        return $new;
    }

    public function getHeaders(): array
    {
        return $this->response->getHeaders();
    }

    public function hasHeader(string $name): bool
    {
        return $this->response->hasHeader($name);
    }

    public function getHeader(string $name): array
    {
        return $this->response->getHeader($name);
    }

    public function getHeaderLine(string $name): string
    {
        return $this->response->getHeaderLine($name);
    }

    public function withHeader(string $name, $value): static
    {
        $new = clone $this;
        $new->response = $this->response->withHeader($name, $value);
        return $new;
    }

    public function withAddedHeader(string $name, $value): static
    {
        $new = clone $this;
        $new->response = $this->response->withAddedHeader($name, $value);
        return $new;
    }

    public function withoutHeader(string $name): static
    {
        $new = clone $this;
        $new->response = $this->response->withoutHeader($name);
        return $new;
    }

    public function getBody(): StreamInterface
    {
        return $this->response->getBody();
    }

    public function withBody(StreamInterface $body): static
    {
        $new = clone $this;
        $new->response = $this->response->withBody($body);
        return $new;
    }

    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    public function withStatus(int $code, string $reasonPhrase = ''): static
    {
        $new = clone $this;
        $new->response = $this->response->withStatus($code, $reasonPhrase);
        return $new;
    }

    public function getReasonPhrase(): string
    {
        return $this->response->getReasonPhrase();
    }

    public function getPsrResponse(): ResponseInterface
    {
        return $this->response;
    }

    public static function json(mixed $data, int $status = 200, array $headers = []): static
    {
        $headers['Content-Type'] = 'application/json';
        return new static(json_encode($data) ?: '', $status, $headers);
    }

    public static function html(string $html, int $status = 200, array $headers = []): static
    {
        $headers['Content-Type'] = 'text/html; charset=UTF-8';
        return new static($html, $status, $headers);
    }

    public static function download(string $file, ?string $name = null, array $headers = []): static
    {
        if (!file_exists($file)) {
            throw new \RuntimeException("File [{$file}] not found.");
        }

        $name = $name ?: basename($file);
        $headers['Content-Disposition'] = "attachment; filename=\"{$name}\"";
        $headers['Content-Type'] = mime_content_type($file) ?: 'application/octet-stream';

        return new static(file_get_contents($file) ?: '', 200, $headers);
    }
}
