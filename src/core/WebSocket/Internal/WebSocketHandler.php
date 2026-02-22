<?php

namespace Core\WebSocket\Internal;

use Workerman\Worker;
use Workerman\Connection\TcpConnection;
use Core\Container\Internal\Application;
use Throwable;

/**
 * @internal
 */
class WebSocketHandler
{
    protected Application $app;
    protected ?RedisInterface $redis = null;
    protected ?RedisInterface $subRedis = null;
    protected array $clients = []; // connection_id => connection
    protected array $rooms = [];   // room_name => [connection_id => connection]

    // Config
    protected string $redisHost;
    protected int $redisPort;
    protected string $redisUser;
    protected string $redisPass;
    protected int $maxConnectionsPerUser;
    protected int $historyLimit;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->redisHost = config('websocket.redis.host', 'redis');
        $this->redisPort = (int) config('websocket.redis.port', 6379);
        $this->redisUser = config('websocket.redis.username', '');
        $this->redisPass = config('websocket.redis.password', '');
        $this->maxConnectionsPerUser = (int) config('websocket.max_connections_per_user', 5);
        $this->historyLimit = (int) config('websocket.history_limit', 50);
    }

    public function onWorkerStart(Worker $worker)
    {
        try {
            // Main Redis connection for operations
            $this->redis = $this->createRedisConnection();
            if ($this->redis) {
                echo "Worker {$worker->id} connected to Redis\n";
            } else {
                 echo "Worker {$worker->id}: Redis not available.\n";
            }

            // Subscriber Redis connection for cross-process messaging
            $this->subRedis = $this->createRedisConnection();

            \Workerman\Timer::add(0.1, function () {
                $this->processBroadcastQueue();
            });
        } catch (Throwable $e) {
            echo "Worker {$worker->id} failed to connect to Redis: " . $e->getMessage() . "\n";
        }
    }

    protected function createRedisConnection(): ?RedisInterface
    {
        $adapter = new RedisAdapter();
        if ($adapter->connect($this->redisHost, $this->redisPort)) {
            if ($this->redisPass) {
                $credentials = $this->redisUser ? [$this->redisUser, $this->redisPass] : $this->redisPass;
                $adapter->auth($credentials);
            }
            return $adapter;
        }
        return null;
    }

    public function onConnect(TcpConnection $connection)
    {
        $connection->rooms = [];
        $connection->userId = null;
        $connection->authenticated = false;
        $this->clients[$connection->id] = $connection;

        echo "New connection! ({$connection->id})\n";

        // Initial tracking (anonymous)
        $this->trackConnection($connection);
    }

    public function onMessage(TcpConnection $connection, $msg)
    {
        try {
            // Binary message support (if not JSON)
            if (!is_string($msg) || (json_decode($msg) === null && json_last_error() !== JSON_ERROR_NONE)) {
                 $this->handleBinaryMessage($connection, $msg);
                 return;
            }

            $data = json_decode($msg, true);
            if (!$data) {
                $connection->send(json_encode(['error' => 'Invalid JSON']));
                return;
            }

            $action = $data['action'] ?? null;

            switch ($action) {
                case 'authenticate':
                    $this->handleAuthentication($connection, $data);
                    break;
                case 'ping':
                    $connection->send(json_encode(['action' => 'pong']));
                    break;
                case 'join':
                case 'leave':
                case 'message':
                case 'broadcast':
                    if (!$connection->authenticated) {
                        $connection->send(json_encode(['error' => 'Authentication required']));
                        return;
                    }
                    match ($action) {
                        'join' => $this->handleJoinRoom($connection, $data),
                        'leave' => $this->handleLeaveRoom($connection, $data),
                        'message' => $this->handleMessage($connection, $data),
                        'broadcast' => $this->handleBroadcast($connection, $data),
                    };
                    break;
                default:
                    $connection->send(json_encode(['error' => 'Unknown action']));
            }
        } catch (Throwable $e) {
            echo "Error processing message: {$e->getMessage()}\n";
            $connection->send(json_encode(['error' => 'Server error']));
        }
    }

    public function onClose(TcpConnection $connection)
    {
        // Remove from local rooms
        foreach ($connection->rooms as $room) {
            $this->removeFromRoom($connection, $room);
        }

        // Untrack from Redis
        $this->untrackConnection($connection);

        unset($this->clients[$connection->id]);
        echo "Connection {$connection->id} disconnected\n";
    }

    // --- Feature Implementations ---

    protected function handleBinaryMessage(TcpConnection $connection, $data)
    {
        echo "Received binary message of size " . strlen($data) . " from {$connection->id}\n";
    }

    protected function handleAuthentication(TcpConnection $conn, array $data)
    {
        $token = $data['token'] ?? null;
        if (!$token) {
            $conn->send(json_encode(['error' => 'Token required']));
            return;
        }

        $userId = $this->validateToken($token);

        if ($userId) {
            // Check connection limit
            if ($this->isConnectionLimitReached($userId)) {
                $conn->send(json_encode(['error' => 'Connection limit reached']));
                $conn->close();
                return;
            }

            $conn->userId = $userId;
            $conn->authenticated = true;
            $conn->send(json_encode(['action' => 'authenticated', 'userId' => $userId]));

            $this->trackConnection($conn);
            echo "User {$userId} authenticated\n";
        } else {
            $conn->send(json_encode(['error' => 'Invalid token']));
        }
    }

    protected function handleJoinRoom(TcpConnection $conn, array $data)
    {
        $room = $data['room'] ?? null;
        if (!$room) {
            $conn->send(json_encode(['error' => 'Room required']));
            return;
        }

        // Private channel check (starts with 'private-')
        if (str_starts_with($room, 'private-')) {
            if (!$this->authorizePrivateChannel($conn->userId, $room)) {
                 $conn->send(json_encode(['error' => 'Unauthorized for this private channel']));
                 return;
            }
        }

        // Add to local room
        if (!isset($this->rooms[$room])) {
            $this->rooms[$room] = [];
        }
        $this->rooms[$room][$conn->id] = $conn;
        if (!in_array($room, $conn->rooms)) {
            $conn->rooms[] = $room;
        }

        // Add to Redis set for presence
        if ($this->redis) {
            $this->redis->sAdd("room:{$room}:members", $conn->userId);
        }

        $conn->send(json_encode([
            'action' => 'joined',
            'room' => $room,
            // Send history
            'history' => $this->getRoomHistory($room)
        ]));

        $this->trackConnection($conn);

        // Notify others locally
        $this->broadcastToRoom($room, [
            'action' => 'user_joined',
            'room' => $room,
            'userId' => $conn->userId
        ], $conn);

        // Publish to other workers
        $this->publishCrossProcess('user_joined', [
            'room' => $room,
            'userId' => $conn->userId,
            'source_connection_id' => $conn->id
        ]);

        echo "Connection {$conn->id} joined {$room}\n";
    }

    protected function handleLeaveRoom(TcpConnection $conn, array $data)
    {
        $room = $data['room'] ?? null;
        if ($room) {
            $this->removeFromRoom($conn, $room);
            $this->trackConnection($conn);
        }
    }

    protected function removeFromRoom(TcpConnection $conn, string $room)
    {
        if (isset($this->rooms[$room][$conn->id])) {
            unset($this->rooms[$room][$conn->id]);

            if ($this->redis) {
                $this->redis->sRem("room:{$room}:members", $conn->userId);
            }

            // Cleanup empty room
            if (empty($this->rooms[$room])) {
                unset($this->rooms[$room]);
            }

            $conn->rooms = array_filter($conn->rooms, fn($r) => $r !== $room);

            // Notify
            $this->broadcastToRoom($room, [
                'action' => 'user_left',
                'room' => $room,
                'userId' => $conn->userId
            ]);

             $this->publishCrossProcess('user_left', [
                'room' => $room,
                'userId' => $conn->userId
             ]);

            echo "Connection {$conn->id} left {$room}\n";
        }
    }

    protected function handleMessage(TcpConnection $conn, array $data)
    {
        $room = $data['room'] ?? null;
        $msgContent = $data['message'] ?? null;

        if (!$room || !$msgContent) {
            return;
        }

        $payload = [
            'action' => 'message',
            'room' => $room,
            'userId' => $conn->userId,
            'message' => $msgContent,
            'timestamp' => time()
        ];

        // Store history
        $this->addMessageHistory($room, $payload);

        // Local broadcast
        $this->broadcastToRoom($room, $payload);

        // Cross-process
        $this->publishCrossProcess('message', $payload);
    }

    protected function handleBroadcast(TcpConnection $conn, array $data)
    {
        // Broadcast to ALL
        $msg = $data['message'] ?? null;
        if (!$msg) {
            return;
        }

        $payload = [
            'action' => 'broadcast',
            'userId' => $conn->userId,
            'message' => $msg,
            'timestamp' => time()
        ];

        foreach ($this->clients as $client) {
            $client->send(json_encode($payload));
        }

        $this->publishCrossProcess('broadcast', $payload);
    }

    // --- Helpers ---

    protected function broadcastToRoom(string $room, array $payload, ?TcpConnection $except = null)
    {
        if (!isset($this->rooms[$room])) {
            return;
        }

        $msg = json_encode($payload);
        foreach ($this->rooms[$room] as $client) {
            if ($except && $client === $except) {
                continue;
            }
            $client->send($msg);
        }
    }

    protected function trackConnection(TcpConnection $conn)
    {
        if (!$this->redis) {
            return;
        }
        $data = [
            'id' => $conn->id,
            'userId' => $conn->userId,
            'rooms' => $conn->rooms,
            'ip' => $conn->getRemoteIp(),
            'connected_at' => time(),
            'pid' => getmypid()
        ];
        $this->redis->hSet('websocket:connections', (string)$conn->id, json_encode($data));

        if ($conn->userId) {
            $this->redis->sAdd("user:{$conn->userId}:connections", (string)$conn->id);
        }
    }

    protected function untrackConnection(TcpConnection $conn)
    {
        if (!$this->redis) {
            return;
        }
        $this->redis->hDel('websocket:connections', (string)$conn->id);
        if ($conn->userId) {
            $this->redis->sRem("user:{$conn->userId}:connections", (string)$conn->id);
        }
    }

    protected function isConnectionLimitReached($userId): bool
    {
        if (!$this->redis) {
            return false;
        }
        $count = $this->redis->sCard("user:{$userId}:connections");
        return $count >= $this->maxConnectionsPerUser;
    }

    protected function addMessageHistory(string $room, array $msg)
    {
        if (!$this->redis) {
            return;
        }
        $key = "room:{$room}:history";
        $this->redis->lPush($key, json_encode($msg));
        $this->redis->lTrim($key, 0, $this->historyLimit - 1);
    }

    protected function getRoomHistory(string $room): array
    {
        if (!$this->redis) {
            return [];
        }
        $raw = $this->redis->lRange("room:{$room}:history", 0, $this->historyLimit - 1);
        $history = [];
        foreach ($raw as $item) {
            $history[] = json_decode($item, true);
        }
        return array_reverse($history);
    }

    protected function authorizePrivateChannel($userId, $room): bool
    {
        if (preg_match('/^private-user-(\d+)$/', $room, $matches)) {
            return (int)$matches[1] === (int)$userId;
        }
        return true;
    }

    protected function validateToken(string $token): int|false
    {
        $appKey = config('app.key', '');
        if (!$appKey) {
            return false;
        }

        $decoded = base64_decode($token, true);
        if (!$decoded) {
            return false;
        }

        $parts = explode(':', $decoded, 3);
        if (count($parts) !== 3) {
            return false;
        }

        [$userId, $timestamp, $hmac] = $parts;

        if (!is_numeric($userId) || !is_numeric($timestamp)) {
            return false;
        }

        if ((time() - (int) $timestamp) > 3600) {
            return false;
        }

        $expected = hash_hmac('sha256', "websocket:{$userId}:{$timestamp}", $appKey);
        return hash_equals($expected, $hmac) ? (int) $userId : false;
    }

    // --- Cross-Worker Communication ---

    protected function publishCrossProcess(string $type, array $data)
    {
        if (!$this->redis) {
            return;
        }

        $id = $this->redis->incr('websocket:event_id');
        $event = [
            'id' => $id,
            'type' => $type,
            'data' => $data,
            'pid' => getmypid(),
            'time' => time()
        ];
        $this->redis->rPush('websocket:global_events', json_encode($event));
        if ($id % 100 == 0) {
            $this->redis->lTrim('websocket:global_events', -500, -1); // Keep last 500
        }
    }

    protected $lastEventId = 0;

    protected function processBroadcastQueue()
    {
        if (!$this->redis) {
            return;
        }

        if ($this->lastEventId === 0) {
            $last = $this->redis->lIndex('websocket:global_events', -1);
            if ($last) {
                $data = json_decode($last, true);
                $this->lastEventId = $data['id'] ?? 0;
            }
            return;
        }

        $events = $this->redis->lRange('websocket:global_events', -50, -1);
        foreach ($events as $json) {
            $event = json_decode($json, true);
            if ($event['id'] <= $this->lastEventId) {
                continue;
            }

            $this->lastEventId = $event['id'];

            if ($event['pid'] == getmypid()) {
                continue;
            }

            $this->handleCrossProcessEvent($event);
        }
    }

    protected function handleCrossProcessEvent(array $event)
    {
        $type = $event['type'];
        $data = $event['data'];

        switch ($type) {
            case 'message':
                $this->broadcastToRoom($data['room'], [
                    'action' => 'message',
                    'room' => $data['room'],
                    'userId' => $data['userId'],
                    'message' => $data['message'],
                    'timestamp' => $data['timestamp'] ?? time()
                ]);
                break;
            case 'broadcast':
                foreach ($this->clients as $client) {
                    $client->send(json_encode([
                        'action' => 'broadcast',
                        'userId' => $data['userId'],
                        'message' => $data['message'],
                        'timestamp' => $data['timestamp'] ?? time()
                    ]));
                }
                break;
            case 'user_joined':
                $this->broadcastToRoom($data['room'], [
                   'action' => 'user_joined',
                   'room' => $data['room'],
                   'userId' => $data['userId']
                ]);
                break;
            case 'user_left':
                $this->broadcastToRoom($data['room'], [
                   'action' => 'user_left',
                   'room' => $data['room'],
                   'userId' => $data['userId']
                ]);
                break;
        }
    }
}
