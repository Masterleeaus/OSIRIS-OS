<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Redis;
use Ratchet\ConnectionInterface;

class WebSocketRateLimit
{
    protected $maxRequestsPerMinute = 60;
    protected $blockDuration = 60; // seconds

    public function handle(ConnectionInterface $connection, $next)
    {
        $ip = $this->getClientIp($connection);
        $key = "ws:ratelimit:{$ip}";
        
        $current = (int) Redis::get($key) ?? 0;
        
        if ($current >= $this->maxRequestsPerMinute) {
            $connection->send(json_encode([
                'event' => 'error',
                'data' => [
                    'message' => 'Rate limit exceeded',
                    'code' => 429,
                    'retry_after' => $this->blockDuration
                ]
            ]));
            $connection->close();
            return false;
        }
        
        Redis::multi();
        Redis::incr($key);
        Redis::expire($key, $this->blockDuration);
        Redis::exec();
        
        return $next($connection);
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
