<?php

namespace App\Services\WebSocket;

use Ratchet\ConnectionInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WebSocketConnectionManager
{
    protected $connections = [];
    protected $connectionLimits = [
        'per_ip' => 5,          // Max connections per IP
        'per_user' => 3,        // Max connections per user
        'global' => 1000,       // Global connection limit
    ];
    protected $rateLimits = [
        'messages_per_second' => 100,  // Messages per second per connection
        'bytes_per_second' => 1048576, // 1MB per second
    ];
    protected $throttleConfig = [
        'enabled' => true,
        'ban_duration' => 300, // 5 minutes
        'max_violations' => 5,
    ];

    public function addConnection(ConnectionInterface $connection, ?int $userId = null): void
    {
        $connectionId = $connection->resourceId;
        $ip = $this->getClientIp($connection);
        
        // Check connection limits
        if (!$this->canAcceptConnection($connection, $userId)) {
            $this->rejectConnection($connection, 'connection_limit_exceeded');
            return;
        }

        $this->connections[$connectionId] = [
            'connection' => $connection,
            'ip' => $ip,
            'user_id' => $userId,
            'connected_at' => time(),
            'message_count' => 0,
            'bytes_received' => 0,
            'last_activity' => time(),
            'channels' => [],
            'throttle' => [
                'messages' => [
                    'count' => 0,
                    'last_reset' => time(),
                ],
                'bytes' => [
                    'count' => 0,
                    'last_reset' => time(),
                ],
            ],
        ];

        // Track connection counts
        $this->incrementConnectionCounts($ip, $userId);
    }

    public function removeConnection(ConnectionInterface $connection): void
    {
        $connectionId = $connection->resourceId;
        
        if (isset($this->connections[$connectionId])) {
            $connectionData = $this->connections[$connectionId];
            
            // Decrement connection counts
            $this->decrementConnectionCounts(
                $connectionData['ip'],
                $connectionData['user_id']
            );
            
            // Remove from channels
            foreach ($connectionData['channels'] as $channel) {
                $this->unsubscribe($connection, $channel);
            }
            
            unset($this->connections[$connectionId]);
        }
    }

    public function subscribe(ConnectionInterface $connection, string $channel): bool
    {
        $connectionId = $connection->resourceId;
        
        if (!isset($this->connections[$connectionId])) {
            return false;
        }
        
        if (!in_array($channel, $this->connections[$connectionId]['channels'], true)) {
            $this->connections[$connectionId]['channels'][] = $channel;
        }
        
        return true;
    }

    public function unsubscribe(ConnectionInterface $connection, string $channel): bool
    {
        $connectionId = $connection->resourceId;
        
        if (!isset($this->connections[$connectionId])) {
            return false;
        }
        
        $this->connections[$connectionId]['channels'] = array_filter(
            $this->connections[$connectionId]['channels'],
            fn($c) => $c !== $channel
        );
        
        return true;
    }

    public function throttle(ConnectionInterface $connection, int $messageSize): bool
    {
        if (!$this->throttleConfig['enabled']) {
            return true;
        }

        $connectionId = $connection->resourceId;
        
        if (!isset($this->connections[$connectionId])) {
            return false;
        }
        
        $now = time();
        $throttle = &$this->connections[$connectionId]['throttle'];
        
        // Reset counters if needed
        if ($now - $throttle['messages']['last_reset'] >= 1) {
            $throttle['messages'] = ['count' => 0, 'last_reset' => $now];
            $throttle['bytes'] = ['count' => 0, 'last_reset' => $now];
        }
        
        // Check message rate
        if (++$throttle['messages']['count'] > $this->rateLimits['messages_per_second']) {
            $this->handleThrottleViolation($connection, 'message_rate_exceeded');
            return false;
        }
        
        // Check bandwidth
        $throttle['bytes']['count'] += $messageSize;
        if ($throttle['bytes']['count'] > $this->rateLimits['bytes_per_second']) {
            $this->handleThrottleViolation($connection, 'bandwidth_exceeded');
            return false;
        }
        
        return true;
    }

    public function getConnectionStats(ConnectionInterface $connection): ?array
    {
        $connectionId = $connection->resourceId;
        
        if (!isset($this->connections[$connectionId])) {
            return null;
        }
        
        $data = $this->connections[$connectionId];
        
        return [
            'connection_id' => $connectionId,
            'ip' => $data['ip'],
            'user_id' => $data['user_id'],
            'connected_at' => $data['connected_at'],
            'uptime' => time() - $data['connected_at'],
            'message_count' => $data['message_count'],
            'bytes_received' => $data['bytes_received'],
            'channels' => $data['channels'],
            'throttle' => [
                'messages' => [
                    'count' => $data['throttle']['messages']['count'],
                    'limit' => $this->rateLimits['messages_per_second'],
                    'remaining' => max(0, $this->rateLimits['messages_per_second'] - $data['throttle']['messages']['count']),
                    'reset_in' => 1 - (time() - $data['throttle']['messages']['last_reset']),
                ],
                'bandwidth' => [
                    'bytes' => $data['throttle']['bytes']['count'],
                    'limit' => $this->rateLimits['bytes_per_second'],
                    'remaining' => max(0, $this->rateLimits['bytes_per_second'] - $data['throttle']['bytes']['count']),
                    'reset_in' => 1 - (time() - $data['throttle']['bytes']['last_reset']),
                ],
            ],
        ];
    }

    public function getGlobalStats(): array
    {
        $stats = [
            'total_connections' => count($this->connections),
            'connections_by_ip' => [],
            'connections_by_user' => [],
            'channels' => [],
        ];
        
        foreach ($this->connections as $connection) {
            // Count by IP
            $ip = $connection['ip'];
            $stats['connections_by_ip'][$ip] = ($stats['connections_by_ip'][$ip] ?? 0) + 1;
            
            // Count by user
            if ($connection['user_id']) {
                $userId = $connection['user_id'];
                $stats['connections_by_user'][$userId] = ($stats['connections_by_user'][$userId] ?? 0) + 1;
            }
            
            // Count channels
            foreach ($connection['channels'] as $channel) {
                $stats['channels'][$channel] = ($stats['channels'][$channel] ?? 0) + 1;
            }
        }
        
        return $stats;
    }

    protected function canAcceptConnection(ConnectionInterface $connection, ?int $userId): bool
    {
        $ip = $this->getClientIp($connection);
        
        // Check global limit
        if (count($this->connections) >= $this->connectionLimits['global']) {
            return false;
        }
        
        // Check IP limit
        $ipConnections = array_filter($this->connections, fn($c) => $c['ip'] === $ip);
        if (count($ipConnections) >= $this->connectionLimits['per_ip']) {
            return false;
        }
        
        // Check user limit
        if ($userId !== null) {
            $userConnections = array_filter($this->connections, fn($c) => $c['user_id'] === $userId);
            if (count($userConnections) >= $this->connectionLimits['per_user']) {
                return false;
            }
        }
        
        return true;
    }

    protected function incrementConnectionCounts(string $ip, ?int $userId): void
    {
        // In a production environment, you might want to use Redis for distributed counting
        Cache::increment("ws:connections:ip:$ip");
        if ($userId) {
            Cache::increment("ws:connections:user:$userId");
        }
        Cache::increment('ws:connections:global');
    }

    protected function decrementConnectionCounts(string $ip, ?int $userId): void
    {
        Cache::decrement("ws:connections:ip:$ip");
        if ($userId) {
            Cache::decrement("ws:connections:user:$userId");
        }
        Cache::decrement('ws:connections:global');
    }

    protected function handleThrottleViolation(ConnectionInterface $connection, string $reason): void
    {
        $connectionId = $connection->resourceId;
        $ip = $this->connections[$connectionId]['ip'] ?? 'unknown';
        
        // Log the violation
        Log::warning("WebSocket throttle violation", [
            'connection_id' => $connectionId,
            'ip' => $ip,
            'reason' => $reason,
        ]);
        
        // Track violations
        $violationKey = "ws:throttle:violations:$ip";
        $violations = Cache::get($violationKey, 0) + 1;
        Cache::put($violationKey, $violations, now()->addSeconds($this->throttleConfig['ban_duration']));
        
        // Close connection if too many violations
        if ($violations >= $this->throttleConfig['max_violations']) {
            $this->rejectConnection($connection, 'too_many_violations');
            Cache::put("ws:banned_ips:$ip", true, now()->addSeconds($this->throttleConfig['ban_duration']));
        }
    }

    protected function rejectConnection(ConnectionInterface $connection, string $reason): void
    {
        $response = [
            'event' => 'error',
            'code' => 'connection_rejected',
            'reason' => $reason,
            'retry_after' => $this->throttleConfig['ban_duration'],
        ];
        
        $connection->send(json_encode($response));
        $connection->close();
    }

    protected function getClientIp(ConnectionInterface $connection): string
    {
        $server = $connection->httpRequest->getServerParams();
        $ip = $server['REMOTE_ADDR'] ?? 'unknown';
        
        // Handle proxy headers if behind a load balancer
        if (isset($server['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $server['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        }
        
        return $ip;
    }
}
