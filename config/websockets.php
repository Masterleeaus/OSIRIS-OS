<?php

use App\Http\Controllers\Api\WebSocketController;

return [
    'dashboard' => [
        'port' => env('LARAVEL_WEBSOCKETS_PORT', 6001),
    ],

    'apps' => [
        [
            'id' => env('PUSHER_APP_ID'),
            'name' => env('APP_NAME'),
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'path' => env('PUSHER_APP_PATH'),
            'capacity' => null,
            'enable_client_messages' => true,
            'enable_statistics' => true,
        ],
    ],

    'ssl' => [
        'local_cert' => null,
        'local_pk' => null,
        'passphrase' => null,
        'verify_peer' => false,
    ],

    'route' => [
        'middleware' => ['web', 'auth'],
    ],

    'statistics' => [
        'model' => \App\Models\WebSocketsStatistic::class,
        'interval_in_seconds' => 60,
        'delete_statistics_older_than_days' => 60,
    ],

    'max_request_size_in_kb' => 250,
    
    /*
    |--------------------------------------------------------------------------
    | Compression Settings
    |--------------------------------------------------------------------------
    |
    | Configure WebSocket message compression to reduce bandwidth usage.
    |
    */
    'compression' => [
        'enabled' => env('WEBSOCKET_COMPRESSION_ENABLED', true),
        'level' => env('WEBSOCKET_COMPRESSION_LEVEL', 6), // 1-9, where 1 is fastest, 9 is best compression
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Message Retention
    |--------------------------------------------------------------------------
    |
    | Configure how long to retain messages for offline clients.
    |
    */
    'message_retention' => [
        'enabled' => env('WEBSOCKET_MESSAGE_RETENTION_ENABLED', true),
        'ttl' => env('WEBSOCKET_MESSAGE_TTL', 3600), // seconds (1 hour)
        'max_messages' => env('WEBSOCKET_MAX_RETAINED_MESSAGES', 100), // per channel
    ],

    'handlers' => [
        'websocket' => WebSocketController::class,
        'api' => WebSocketController::class,
    ],

    'replication' => [
        'mode' => env('WEBSOCKETS_REPLICATION_MODE', 'local'),
        'modes' => [
            'local' => [
                'channel_manager' => \App\Services\WebSocketChannelManager::class,
            ],
            'redis' => [
                'connection' => 'default',
                'channel_manager' => \BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManagers\RedisChannelManager::class,
            ],
        ],
    ],

    'prometheus' => [
        'enabled' => env('PROMETHEUS_METRICS_ENABLED', false),
        'namespace' => env('PROMETHEUS_METRICS_NAMESPACE', 'websockets'),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Load Balancing Configuration
    |--------------------------------------------------------------------------
    |
    | Configure load balancing for WebSocket servers.
    |
    */
    'load_balancing' => [
        'enabled' => env('WEBSOCKET_LOAD_BALANCING_ENABLED', false),
        'strategy' => env('WEBSOCKET_LB_STRATEGY', 'round_robin'), // round_robin, least_connections, ip_hash
        'servers' => array_filter(explode(',', env('WEBSOCKET_SERVERS', ''))),
        'health_check_interval' => env('WEBSOCKET_HEALTH_CHECK_INTERVAL', 30), // seconds
        'sticky_sessions' => env('WEBSOCKET_STICKY_SESSIONS', true),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Health Monitoring
    |--------------------------------------------------------------------------
    |
    | Configure health monitoring for WebSocket connections.
    |
    */
    'health' => [
        'enabled' => env('WEBSOCKET_HEALTH_MONITORING', true),
        'ping_interval' => env('WEBSOCKET_PING_INTERVAL', 30), // seconds
        'max_missed_pings' => env('WEBSOCKET_MAX_MISSED_PINGS', 3),
        'memory_limit' => env('WEBSOCKET_MEMORY_LIMIT_MB', 128), // MB
        'auto_heal' => [
            'enabled' => env('WEBSOCKET_AUTO_HEAL', true),
            'restart_on_failure' => env('WEBSOCKET_RESTART_ON_FAILURE', true),
            'restart_delay' => env('WEBSOCKET_RESTART_DELAY', 5), // seconds
        ]
    ],

    'middleware' => [
        'api' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \App\Http\Middleware\AuthenticateSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\VerifyJwtToken::class,
        ],
    ],

    'max_connections' => 1000,
    'max_bytes_per_message' => 10000,
    'max_messages_per_minute' => 100,
];
