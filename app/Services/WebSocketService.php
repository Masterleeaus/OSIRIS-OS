<?php

namespace App\Services;

use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager;
use BeyondCode\LaravelWebSockets\WebSockets\Messages\PusherMessage;
use Illuminate\Support\Facades\Log;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use WebSocket\Compression\RFC7692Compression;
use WebSocket\Message\Text;

class WebSocketService
{
    protected $channelManager;
    protected $queueConnection;
    protected $queueName = 'ws-messages';
    protected $batchSize = 10;
    protected $batch = [];
    protected $priorityBatch = [];
    protected $lastFlush;
    protected $batchInterval = 0.1; // seconds
    protected $lastBatchTime = 0;
    protected $compressionEnabled = true;
    protected $compressionLevel = 6; // 1-9, where 1 is fastest, 9 is best compression

    public function __construct(ChannelManager $channelManager)
    {
        $this->channelManager = $channelManager;
        $this->queueConnection = config('queue.default');
        $this->lastFlush = time();
        $this->compressionEnabled = config('websockets.compression.enabled', true);
        $this->compressionLevel = config('websockets.compression.level', 6);
    }
    
    /**
     * Queue a message for broadcasting
     */
    /**
     * Queue a message with priority
     * 
     * @param string $channel The channel to send the message to
     * @param array $message The message data
     * @param int $priority Priority level (1-5, 1 being highest)
     * @param int|null $expiresAt Unix timestamp when the message expires
     * @param bool $immediate Send immediately without batching
     */
    public function queueMessage(
        string $channel, 
        array $message, 
        int $priority = 3, 
        ?int $expiresAt = null,
        bool $immediate = false
    ): ?string {
        $messageId = bin2hex(random_bytes(16));
        $now = time();
        
        // Skip expired messages
        if ($expiresAt && $expiresAt < $now) {
            return null;
        }
        
        $messageData = [
            'id' => $messageId,
            'channel' => $channel,
            'message' => $message,
            'priority' => max(1, min(5, $priority)),
            'created_at' => $now,
            'expires_at' => $expiresAt,
            'attempts' => 0
        ];
        
        // Add to appropriate batch based on priority
        if ($priority <= 2) {
            $this->priorityBatch[] = $messageData;
            
            // High priority messages are sent immediately or in the next batch
            if ($immediate || $priority === 1) {
                $this->flushQueue(true);
            }
        } else {
            $this->batch[] = $messageData;
            
            // Flush if batch size is reached or if immediate is true
            if (count($this->batch) >= $this->batchSize || $immediate) {
                $this->flushQueue();
            } elseif ($now - $this->lastFlush >= 1) { // Flush at least once per second
                $this->flushQueue();
            }
        }
        
        return $messageId;
    }
    
    /**
     * Flush queued messages to the message queue
     */
    /**
     * Flush queued messages to the queue
     * 
     * @param bool $force Force flush even if batch size isn't reached
     * @param bool $includePriority Whether to include priority messages
     */
    protected function flushQueue(bool $force = false, bool $includePriority = true): void
    {
        $now = time();
        $shouldFlush = $force || 
                      !empty($this->priorityBatch) || 
                      $now - $this->lastFlush >= 1 ||
                      count($this->batch) >= $this->batchSize;
        
        if (!$shouldFlush) {
            return;
        }
        
        // Process priority messages first
        $messages = [];
        
        if ($includePriority && !empty($this->priorityBatch)) {
            $messages = array_merge($messages, $this->priorityBatch);
            $this->priorityBatch = [];
        }
        
        // Add regular batch messages
        if (!empty($this->batch)) {
            $messages = array_merge($messages, $this->batch);
            $this->batch = [];
        }
        
        // Filter out expired messages
        $messages = array_filter($messages, function($message) use ($now) {
            return !isset($message['expires_at']) || $message['expires_at'] > $now;
        });
        
        if (empty($messages)) {
            return;
        }
        
        // Sort by priority (ascending) and creation time (ascending)
        usort($messages, function($a, $b) {
            if ($a['priority'] === $b['priority']) {
                return $a['created_at'] <=> $b['created_at'];
            }
            return $a['priority'] <=> $b['priority'];
        });
        
        $this->lastFlush = $now;
        
        // Dispatch to queue in chunks to prevent memory issues
        $chunks = array_chunk($messages, $this->batchSize);
        
        foreach ($chunks as $chunk) {
            dispatch(function () use ($chunk) {
                foreach ($chunk as $message) {
                    $this->processQueuedMessage($message);
                }
            })->onConnection($this->queueConnection)->onQueue($this->queueName);
        }
    }
    
    /**
     * Process a single message from the queue
     */
    protected function processQueuedMessage(array $message): void
    {
        try {
            // Skip if message has expired
            if (isset($message['expires_at']) && $message['expires_at'] < time()) {
                return;
            }
            
            $message['attempts']++;
            
            $this->broadcastToChannel(
                $message['channel'],
                array_merge($message['message'], [
                    '_meta' => [
                        'id' => $message['id'],
                        'attempt' => $message['attempts'],
                        'expires_at' => $message['expires_at'] ?? null
                    ]
                ])
            );
            
        } catch (\Exception $e) {
            $maxRetries = 3;
            
            if ($message['attempts'] < $maxRetries) {
                // Exponential backoff for retries
                $delay = min(pow(2, $message['attempts']), 30); // Cap at 30 seconds
                dispatch(function () use ($message) {
                    $this->processQueuedMessage($message);
                })->delay(now()->addSeconds($delay))
                  ->onQueue($this->queueName);
            } else {
                \Log::error('WebSocket message processing failed after retries', [
                    'exception' => $e,
                    'message_id' => $message['id'] ?? null,
                    'channel' => $message['channel'] ?? null,
                    'attempts' => $message['attempts'] ?? 0
                ]);
            }
        }
    }
    
    /**
     * Set the queue connection name
     */
    public function setQueueConnection(string $connection): self
    {
        $this->queueConnection = $connection;
        return $this;
    }
    
    /**
     * Set the queue name
     */
    public function setQueueName(string $name): self
    {
        $this->queueName = $name;
        return $this;
    }
    
    /**
     * Set the batch size for queuing messages
     */
    public function setBatchSize(int $size): self
    {
        $this->batchSize = max(1, $size);
        return $this;
    }

    /**
     * Broadcast an event to a specific channel
     */
    public function broadcast(string $channel, string $event, array $data): void
    {
        $message = new PusherMessage(
            json_encode([
                'event' => $event,
                'channel' => $channel,
                'data' => $data,
            ])
        );

        $this->channelManager->broadcastToChannels(
            $message,
            [$channel],
            null,
            false
        );
    }

    /**
     * Subscribe a connection to a channel
     */
    public function subscribe(ConnectionInterface $connection, string $channelName): void
    {
        $channel = $this->channelManager->findOrCreate($connection->app->id, $channelName);
        $channel->subscribe($connection);
    }

    /**
     * Unsubscribe a connection from a channel
     */
    public function unsubscribe(ConnectionInterface $connection, string $channelName): void
    {
        if ($channel = $this->channelManager->find($connection->app->id, $channelName)) {
            $channel->unsubscribe($connection);
        }
    }

    /**
     * Handle incoming WebSocket message
     */
    public function handleMessage(ConnectionInterface $connection, MessageInterface $message): void
    {
        try {
            $payload = json_decode($message->getPayload(), true);
            
            if (!isset($payload['event'])) {
                throw new \InvalidArgumentException('Event type is required');
            }

            switch ($payload['event']) {
                case 'subscribe':
                    $this->handleSubscribe($connection, $payload);
                    break;
                case 'unsubscribe':
                    $this->handleUnsubscribe($connection, $payload);
                    break;
                default:
                    $this->handleCustomEvent($connection, $payload);
            }
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

    protected function handleSubscribe(ConnectionInterface $connection, array $payload): void
    {
        if (!isset($payload['channel'])) {
            throw new \InvalidArgumentException('Channel name is required for subscription');
        }

        $this->subscribe($connection, $payload['channel']);
        
        $connection->send(json_encode([
            'event' => 'subscription_succeeded',
            'channel' => $payload['channel']
        ]));
    }

    protected function handleUnsubscribe(ConnectionInterface $connection, array $payload): void
    {
        if (!isset($payload['channel'])) {
            throw new \InvalidArgumentException('Channel name is required for unsubscription');
        }

        $this->unsubscribe($connection, $payload['channel']);
        
        $connection->send(json_encode([
            'event' => 'unsubscription_succeeded',
            'channel' => $payload['channel']
        ]));
    }

    protected function handleCustomEvent(ConnectionInterface $connection, array $payload): void
    {
        // Handle custom events here
        // This can be extended based on your application's needs
        
        $connection->send(json_encode([
            'event' => 'error',
            'data' => [
                'message' => 'Unsupported event type',
                'code' => 4001
            ]
        ]));
    }
}
