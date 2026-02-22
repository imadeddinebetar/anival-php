<?php

namespace Core\Session\Internal;

use Psr\Log\LoggerInterface;
use Core\Database\Internal\DatabaseManager;
use Core\Http\Contracts\CookieJarInterface;
use Core\Session\Contracts\SessionInterface;

/**
 * @internal
 */
class SessionManager implements SessionInterface
{
    protected ?LoggerInterface $logger;
    protected ?DatabaseManager $db;
    protected ?CookieJarInterface $cookies;

    public function __construct(?LoggerInterface $logger = null, ?DatabaseManager $db = null, ?CookieJarInterface $cookies = null)
    {
        $this->logger = $logger;
        $this->db = $db;
        $this->cookies = $cookies;
    }

    public function start(): bool
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return true;
        }

        if (!headers_sent()) {
            // Config lifetime is in minutes; session_set_cookie_params expects seconds
            $lifetimeMinutes = config('session.lifetime', 120);

            session_set_cookie_params([
                'lifetime' => $lifetimeMinutes * 60,
                'path' => config('session.path', '/'),
                'domain' => config('session.domain'),
                'secure' => config('session.secure', true),
                'httponly' => config('session.httponly', true),
                'samesite' => config('session.samesite', 'Lax'),
            ]);

            $this->configureHandler();

            return session_start();
        }

        // @codeCoverageIgnoreStart
        if ($this->logger) {
            $this->logger->warning('Session could not start: headers already sent.');
        }

        return false;
        // @codeCoverageIgnoreEnd
    }

    protected function configureHandler(): void
    {
        $driver = config('session.driver', 'file');

        $handler = match ($driver) {
            'file' => null, // Default PHP behavior
            'redis' => $this->createRedisHandler(), // @codeCoverageIgnore
            'database' => $this->createDatabaseHandler(), // @codeCoverageIgnore
            default => null,
        };

        if ($handler) {
            session_set_save_handler($handler, true); // @codeCoverageIgnore
        }
    }

    /**
     * @codeCoverageIgnore Requires live Redis server
     */
    protected function createRedisHandler(): \SessionHandlerInterface
    {
        $connection = config('session.connection', 'default');
        // Retrieve Redis connection from Cache/Redis manager if available, or create new
        // For simplicity, we'll create a new connection using the same config as cache
        $server = config("cache.stores.redis.connection", 'redis://localhost');

        $client = \Symfony\Component\Cache\Adapter\RedisAdapter::createConnection($server);
        return new \Symfony\Component\HttpFoundation\Session\Storage\Handler\RedisSessionHandler($client);
    }

    /**
     * @codeCoverageIgnore Requires live database connection
     */
    protected function createDatabaseHandler(): \SessionHandlerInterface
    {
        $connectionName = config('session.connection');
        $table = config('session.table', 'sessions');

        $pdo = $this->db->connection($connectionName)->getPdo();

        return new \Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler(
            $pdo,
            ['db_table' => $table]
        );
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (isset($_SESSION[$key])) {
            return $_SESSION[$key];
        }

        if (isset($_SESSION['_flash']['new'][$key])) {
            return $_SESSION['_flash']['new'][$key];
        }

        if (isset($_SESSION['_flash']['old'][$key])) {
            return $_SESSION['_flash']['old'][$key];
        }

        return $default;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]) ||
            isset($_SESSION['_flash']['new'][$key]) ||
            isset($_SESSION['_flash']['old'][$key]);
    }

    public function remove(string $key): mixed
    {
        $value = $_SESSION[$key] ?? null;
        unset($_SESSION[$key]);
        return $value;
    }

    public function all(): array
    {
        return $_SESSION ?? [];
    }

    public function put(string $key, mixed $value): void
    {
        $this->set($key, $value);
    }

    public function push(string $key, mixed $value): void
    {
        $array = $this->get($key, []);
        $array[] = $value;
        $this->set($key, $array);
    }

    public function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function flush(): void
    {
        $this->clear();
    }

    public function regenerate(bool $destroy = false): bool
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return session_regenerate_id($destroy);
        }
        return false;
    }

    public function clear(): void
    {
        $_SESSION = [];
    }

    public function destroy(): bool
    {
        $this->clear();
        if (ini_get("session.use_cookies") && $this->cookies) {
            $params = session_get_cookie_params();
            $this->cookies->expire(
                session_name(),
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            return session_destroy();
        }

        return true;
    }

    public function flash(string $key, mixed $value = true): void
    {
        $_SESSION['_flash']['new'][$key] = $value;
    }

    public function reflash(): void
    {
        $_SESSION['_flash']['new'] = array_merge(
            $_SESSION['_flash']['new'] ?? [],
            $_SESSION['_flash']['old'] ?? []
        );
    }

    public function ageFlashData(): void
    {
        $_SESSION['_flash']['old'] = $_SESSION['_flash']['new'] ?? [];
        $_SESSION['_flash']['new'] = [];
    }
}
