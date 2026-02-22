<?php

namespace Core\Http\Foundation;

use Psr\Http\Message\ResponseInterface;

/**
 * Emits a PSR-7 response to the client.
 * Extracted from index.php so it can be reused and tested.
 *
 * @internal
 */
class ResponseEmitter
{
    /**
     * Emit the given response (status code, headers, body).
     */
    public function emit(ResponseInterface $response): void
    {
        if (!headers_sent()) {
            http_response_code($response->getStatusCode());

            foreach ($response->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header(sprintf('%s: %s', $name, $value), false);
                }
            }
        }

        $body = $response->getBody();

        if ($body->isSeekable()) {
            $body->rewind();
        }

        echo $body;
    }
}
