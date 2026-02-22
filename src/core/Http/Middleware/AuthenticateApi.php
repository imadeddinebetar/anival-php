<?php

namespace Core\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Nyholm\Psr7\Response;
use Core\Database\Internal\DatabaseManager;

/**
 * @internal
 */
class AuthenticateApi implements MiddlewareInterface
{
    /** @var array<int, string> */
    protected array $except = [];
    protected DatabaseManager $db;

    public function __construct(DatabaseManager $db)
    {
        $this->db = $db;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->shouldSkip($request)) {
            return $handler->handle($request);
        }

        $token = $this->extractBearerToken($request);

        if ($token === null) {
            return new Response(401, ['Content-Type' => 'application/json'], json_encode([
                'error' => 'Authentication required',
            ]) ?: '');
        }

        $userId = $this->validateToken($token);

        if ($userId === false) {
            return new Response(401, ['Content-Type' => 'application/json'], json_encode([
                'error' => 'Invalid or expired token',
            ]) ?: '');
        }

        $request = $request->withAttribute('auth_user_id', $userId);

        return $handler->handle($request);
    }

    protected function shouldSkip(ServerRequestInterface $request): bool
    {
        $path = $request->getUri()->getPath();
        foreach ($this->except as $except) {
            if ($path === $except || fnmatch($except, $path)) {
                return true;
            }
        }
        return false;
    }

    protected function extractBearerToken(ServerRequestInterface $request): ?string
    {
        $header = $request->getHeaderLine('Authorization');
        if ($header !== '' && str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }
        return null;
    }

    /**
     * Validate a token against the database.
     *
     * @return int|false User ID on success, false on failure
     */
    protected function validateToken(string $token): int|false
    {
        $hashedToken = hash('sha256', $token);
        $accessToken = $this->db->table('personal_access_tokens')
            ->where('token', $hashedToken)
            ->first();

        if (!$accessToken) {
            return false;
        }

        if ($accessToken->expires_at && \Carbon\Carbon::parse($accessToken->expires_at)->isPast()) {
            return false;
        }

        $this->db->table('personal_access_tokens')
            ->where('id', $accessToken->id)
            ->update(['last_used_at' => now()->toDateTimeString()]);

        return (int) $accessToken->user_id;
    }
}
