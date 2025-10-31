<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TonWebSocketHandler;
use App\Services\WebSocketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class WebSocketController extends Controller
{
    protected $connections = [];
    protected $connectionTimeout = 300; // 5 minutes
    protected $maxConnectionsPerUser = 5;
    
    // Connection states
    const STATE_CONNECTING = 'connecting';
    const STATE_AUTHENTICATED = 'authenticated';
    const STATE_SUBSCRIBED = 'subscribed';
    const STATE_RECONNECTING = 'reconnecting';
    const STATE_ERROR = 'error';
    
    // Message acknowledgment timeout (seconds)
    const MESSAGE_ACK_TIMEOUT = 30;
    
    // Connection metadata structure
    protected function newConnectionData()
    {
        return [
            'id' => null,
            'user_id' => null,
            'state' => self::STATE_CONNECTING,
            'channels' => [],
            'last_activity' => now(),
            'ip' => null,
            'user_agent' => null,
            'metadata' => [],
            'ping_sent_at' => null,
            'pong_received_at' => null,
            'subscriptions' => []
        ];
    }
    protected $webSocketService;
    protected $tonWebSocketHandler;

    public function __construct(WebSocketService $webSocketService, TonWebSocketHandler $tonWebSocketHandler)
    {
        $this->webSocketService = $webSocketService;
        $this->tonWebSocketHandler = $tonWebSocketHandler;
    }

    /**
     * Handle WebSocket connection
     */
    public function onOpen(ConnectionInterface $connection): void
    {
        $connectionId = $connection->resourceId;
        $this->connections[$connectionId] = $this->newConnectionData();
        $this->connections[$connectionId]['id'] = $connectionId;
        $this->connections[$connectionId]['ip'] = $this->getClientIp($connection);
        $this->connections[$connectionId]['user_agent'] = $connection->httpRequest->getHeader('User-Agent')[0] ?? 'unknown';
        $this->connections[$connectionId]['state'] = self::STATE_CONNECTING;
        $this->connections[$connectionId]['last_activity'] = now();
        
        Log::info('New WebSocket connection', [
            'connection_id' => $connectionId,
            'ip' => $this->connections[$connectionId]['ip']
        ]);
        
        // Check connection limits
        if (!$this->checkConnectionLimits($connection)) {
            return;
        }
        
        // Send welcome message
        $connection->send(json_encode([
            'event' => 'welcome',
            'data' => [
                'connection_id' => $connectionId,
                'message' => 'Connected to WebSocket server',
                'server_time' => now()->toDateTimeString(),
                'requires_auth' => true,
                'ping_interval' => 30 // seconds
            ]
        ]));
        
        // Start ping-pong
        $this->startPingPong($connection);
        
        // Set authentication timeout
        $connection->authTimer = $this->loop->addTimer(30, function() use ($connection, $connectionId) {
            if ($this->getConnectionState($connectionId) !== self::STATE_AUTHENTICATED) {
                $this->closeConnection($connection, 4001, 'Authentication timeout');
            }
        });
    }

    /**
     * Handle WebSocket messages
     */
    public function onMessage(ConnectionInterface $connection, MessageInterface $message): void
    {
        try {
            $payload = json_decode($message->getPayload(), true);
            
            if (!isset($payload['event'])) {
                throw new \InvalidArgumentException('Event type is required');
            }

            $this->handleMessage($connection, $payload);
            
        } catch (\Exception $e) {
            Log::error('WebSocket error: ' . $e->getMessage(), [
                'exception' => $e,
                'payload' => $message->getPayload()
            ]);
            
            $connection->send(json_encode([
                'event' => 'error',
                'data' => [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode()
                ]
            ]));
        }
    }

    /**
     * Handle WebSocket connection close
     */
    public function onClose(ConnectionInterface $connection): void
    {
        $connectionId = $connection->resourceId;
        
        Log::info('WebSocket connection closed', [
            'connection_id' => $connectionId,
            'user_id' => $this->connections[$connectionId]['user_id'] ?? null,
            'channels' => $this->connections[$connectionId]['channels'] ?? []
        ]);
        
        // Clean up timers
        if (isset($connection->authTimer)) {
            $this->loop->cancelTimer($connection->authTimer);
        }
        
        if (isset($connection->pingTimer)) {
            $this->loop->cancelTimer($connection->pingTimer);
        }
        
        // Unsubscribe from all channels
        $this->unsubscribeFromAllChannels($connection);
        
        // Remove connection from tracking
        unset($this->connections[$connectionId]);
    }

    /**
     * Handle WebSocket errors
     */
    public function onError(ConnectionInterface $connection, \Exception $e): void
    {
        $connectionId = $connection->resourceId;
        
        Log::error('WebSocket error', [
            'connection_id' => $connectionId,
            'user_id' => $this->connections[$connectionId]['user_id'] ?? null,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        // Update connection state
        if (isset($this->connections[$connectionId])) {
            $this->connections[$connectionId]['state'] = self::STATE_ERROR;
            $this->connections[$connectionId]['last_error'] = [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'time' => now()->toDateTimeString()
            ];
        }
        
        // Notify client about the error
        $connection->send(json_encode([
            'event' => 'error',
            'data' => [
                'message' => 'An error occurred',
                'code' => $e->getCode() ?: 5000,
                'details' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ]
        ]));
        
        // Close the connection
        $connection->close();
    }

    /**
     * Handle incoming messages
     */
    protected function handleMessage(ConnectionInterface $connection, array $payload): void
    {
        $event = $payload['event'] ?? null;
        $data = $payload['data'] ?? [];
        $messageId = $payload['id'] ?? null;
        
        // Handle message acknowledgment
        if (isset($payload['ack'])) {
            $this->handleAcknowledgment($connection, $payload['ack'], $messageId);
            return;
        }

        try {
            $this->updateLastActivity($connection);
            
            switch ($event) {
                case 'subscribe':
                    $this->handleSubscription($connection, $data, $messageId);
                    break;
                case 'unsubscribe':
                    $this->handleUnsubscription($connection, $data, $messageId);
                    break;
                case 'ping':
                    $this->handlePing($connection, $data, $messageId);
                    break;
                case 'reconnect':
                    $this->handleReconnect($connection, $data, $messageId);
                    break;
                case 'sync':
                    $this->handleSync($connection, $data, $messageId);
                    break;
                default:
                    $this->handleCustomEvent($connection, $event, $data, $messageId);
            }
            
            // Send acknowledgment if message ID is provided
            if ($messageId) {
                $this->sendAcknowledgment($connection, $messageId);
            }
        } catch (\Exception $e) {
            $this->handleError($connection, $e, $messageId);
        }
    }

    /**
     * Handle subscription requests
     */
    protected function handleSubscription(ConnectionInterface $connection, array $data, ?string $messageId = null): void
    {
        $channel = $data['channel'] ?? null;
        $recoveryId = $data['recovery_id'] ?? null;
        
        if (!$channel) {
            throw new \InvalidArgumentException('Channel name is required');
        }
        
        // Handle reconnection with recovery
        if ($recoveryId && $this->isValidRecoveryId($recoveryId)) {
            $this->recoverConnection($connection, $recoveryId, $channel);
            return;
        }
        
        // Handle new subscription
        $this->webSocketService->subscribeToChannel($connection, $channel, $data);
        
        // If this is a private channel, store the last message ID
        if (str_starts_with($channel, 'private-')) {
            $this->connections[$connection->resourceId]['last_message_id'] = $messageId;
        }
    }

    /**
     * Handle unsubscription requests
     */
    protected function handleUnsubscription(ConnectionInterface $connection, array $data, ?string $messageId = null): void
    {
        $channel = $data['channel'] ?? null;
        
        if (!$channel) {
            throw new \InvalidArgumentException('Channel is required for unsubscription');
        }

        // Unsubscribe from the channel
        $this->webSocketService->unsubscribe($connection, $channel);
    }

    /**
     * Handle ping requests
     */
    protected function handlePing(ConnectionInterface $connection, array $data, ?string $messageId = null): void
    {
        $response = [
            'event' => 'pong',
            'data' => [
                'time' => time(),
                'server_time' => now()->toDateTimeString(),
                'latency' => $data['time'] ? time() - $data['time'] : null
            ]
        ];
        
        if ($messageId) {
            $response['id'] = $messageId;
        }
        
        $connection->send(json_encode($response));
    }

    /**
     * Handle reconnection request
     */
    protected function handleReconnect(ConnectionInterface $connection, array $data, ?string $messageId = null): void
    {
        $recoveryId = $data['recovery_id'] ?? null;
        $lastMessageId = $data['last_message_id'] ?? null;
        
        if (!$recoveryId || !$this->isValidRecoveryId($recoveryId)) {
            throw new \InvalidArgumentException('Invalid recovery ID');
        }
        
        // Update connection state
        $this->connections[$connection->resourceId]['state'] = self::STATE_RECONNECTING;
        $this->connections[$connection->resourceId]['recovery_id'] = $recoveryId;
        
        // Send acknowledgment with recovery information
        $response = [
            'event' => 'reconnect_ack',
            'data' => [
                'recovery_id' => $recoveryId,
                'recovered' => true,
                'last_message_id' => $lastMessageId
            ]
        ];
        
        if ($messageId) {
            $response['id'] = $messageId;
        }
        
        $connection->send(json_encode($response));
        
        // Process any missed messages
        $this->processMissedMessages($connection, $recoveryId, $lastMessageId);
    }

    /**
     * Handle sync request for missed messages
     */
    protected function handleSync(ConnectionInterface $connection, array $data, ?string $messageId = null): void
    {
        $channel = $data['channel'] ?? null;
        $lastMessageId = $data['last_message_id'] ?? null;
        
        if (!$channel) {
            throw new \InvalidArgumentException('Channel is required for sync');
        }
        
        // Get missed messages from message store
        $missedMessages = $this->messageStore->getMessagesAfter($channel, $lastMessageId);
        
        // Send missed messages
        foreach ($missedMessages as $message) {
            $connection->send(json_encode([
                'event' => 'message',
                'data' => $message['data'],
                'message_id' => $message['id'],
                'timestamp' => $message['created_at']
            ]));
        }
        
        // Send sync complete
        $response = [
            'event' => 'sync_complete',
            'data' => [
                'channel' => $channel,
                'message_count' => count($missedMessages),
                'last_message_id' => $missedMessages->last()['id'] ?? null
            ]
        ];
        
        if ($messageId) {
            $response['id'] = $messageId;
        }
        
        $connection->send(json_encode($response));
    }

    /**
     * Send acknowledgment for a received message
     */
    protected function sendAcknowledgment(ConnectionInterface $connection, string $messageId): void
    {
        $connection->send(json_encode([
            'event' => 'ack',
            'ack' => $messageId,
            'timestamp' => now()->toDateTimeString()
        ]));
    }

    /**
     * Handle message acknowledgment from client
     */
    protected function handleAcknowledgment(ConnectionInterface $connection, string $ackId, ?string $messageId = null): void
    {
        // Update message status in the message store
        if ($this->messageStore->acknowledge($ackId)) {
            // Message was successfully acknowledged
            $connection->send(json_encode([
                'event' => 'ack_received',
                'data' => [
                    'ack_id' => $ackId,
                    'status' => 'delivered'
                ],
                'id' => $messageId
            ]));
        } else {
            // Message not found or already acknowledged
            $connection->send(json_encode([
                'event' => 'error',
                'data' => [
                    'message' => 'Invalid acknowledgment',
                    'code' => 4004,
                    'ack_id' => $ackId
                ],
                'id' => $messageId
            ]));
        }
    }

    /**
     * Generate a unique recovery ID for connection resumption
     */
    protected function generateRecoveryId(string $connectionId): string
    {
        return hash_hmac('sha256', $connectionId . ':' . microtime(true), config('app.key'));
    }

    /**
     * Validate a recovery ID
     */
    protected function isValidRecoveryId(string $recoveryId): bool
    {
        // Basic validation - in production, you might want to verify against a stored token
        return preg_match('/^[a-f0-9]{64}$/', $recoveryId) === 1;
    }

    /**
     * Process messages that were missed during disconnection
     */
    protected function processMissedMessages(ConnectionInterface $connection, string $recoveryId, ?string $lastMessageId = null): void
    {
        $connectionId = $connection->resourceId;
        $channels = $this->connections[$connectionId]['channels'] ?? [];
        $missedMessages = [];
        
        foreach ($channels as $channel) {
            $messages = $this->messageStore->getMessagesAfter($channel, $lastMessageId);
            $missedMessages = array_merge($missedMessages, $messages);
        }
        
        // Sort messages by timestamp
        usort($missedMessages, function($a, $b) {
            return $a['created_at'] <=> $b['created_at'];
        });
        
        // Send missed messages
        foreach ($missedMessages as $message) {
            $connection->send(json_encode([
                'event' => 'message',
                'data' => $message['data'],
                'message_id' => $message['id'],
                'timestamp' => $message['created_at'],
                'recovery_id' => $recoveryId
            ]));
        }
        
        // Update connection state
        $this->connections[$connectionId]['state'] = self::STATE_SUBSCRIBED;
        $this->connections[$connectionId]['recovery_id'] = $recoveryId;
        
        // Send recovery complete
        $connection->send(json_encode([
            'event' => 'recovery_complete',
            'data' => [
                'recovery_id' => $recoveryId,
                'message_count' => count($missedMessages),
                'last_message_id' => $missedMessages ? end($missedMessages)['id'] : null
            ]
        ]));
    }

    /**
     * Handle custom events
     */
    protected function handleCustomEvent(ConnectionInterface $connection, string $event, array $data, ?string $messageId = null): void
    {
        // Handle custom events here
        // This can be extended based on your application's needs
        
        $connection->send(json_encode([
            'event' => 'error',
            'data' => [
                'message' => 'Unsupported event type',
                'code' => 4001
            ],
            'id' => $messageId
        ]));
    }

    /**
     * Authenticate WebSocket connection
     */
    protected function authenticateConnection(ConnectionInterface $connection): bool
    {
        // Implement your authentication logic here
        // For example, verify JWT token from query parameters
        
        $query = $connection->httpRequest->getUri()->getQuery();
        parse_str($query, $params);
        
        $token = $params['token'] ?? null;
        
        if (!$token) {
            $connection->send(json_encode([
                'event' => 'error',
                'data' => [
                    'message' => 'Authentication required',
                    'code' => 4001
                ]
            ]));
            $connection->close();
            return false;
        }
        
        try {
            // Verify the token and get the authenticated user
            // $user = JWTAuth::parseToken()->authenticate();
            // $connection->user = $user;
            
            return true;
        } catch (\Exception $e) {
            $connection->send(json_encode([
                'event' => 'error',
                'data' => [
                    'message' => 'Invalid token',
                    'code' => 4001
                ]
            ]));
            $connection->close();
            return false;
        }
    }
}
