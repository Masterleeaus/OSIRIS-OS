<?php

namespace App\Services;

use BeyondCode\LaravelWebSockets\WebSockets\Channels\ChannelManager as BaseChannelManager;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\Channel;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\PresenceChannel;
use BeyondCode\LaravelWebSockets\WebSockets\Channels\PrivateChannel;
use BeyondCode\LaravelWebSockets\WebSockets\Exceptions\InvalidChannel;
use Illuminate\Support\Str;
use Ratchet\ConnectionInterface;

class WebSocketChannelManager extends BaseChannelManager
{
    /**
     * The list of active channels with their connections.
     *
     * @var array
     */
    protected $channels = [];

    /**
     * Get all channels.
     *
     * @return array
     */
    public function getChannels(): array
    {
        return $this->channels;
    }

    /**
     * Find a channel by name.
     *
     * @param  string  $channelName
     * @param  mixed  $appId
     * @return Channel|PrivateChannel|PresenceChannel|null
     */
    public function find(string $channelName, $appId = null)
    {
        return $this->channels[$channelName] ?? null;
    }

    /**
     * Find a channel by name or create it if it doesn't exist.
     *
     * @param  string  $channelName
     * @param  mixed  $appId
     * @return Channel|PrivateChannel|PresenceChannel
     */
    public function findOrCreate(string $channelName, $appId = null)
    {
        if (! isset($this->channels[$channelName])) {
            $channelClass = $this->determineChannelClass($channelName);
            $this->channels[$channelName] = new $channelClass($channelName);
        }

        return $this->channels[$channelName];
    }

    /**
     * Determine the channel class by its name.
     *
     * @param  string  $channelName
     * @return string
     */
    protected function determineChannelClass(string $channelName): string
    {
        if (Str::startsWith($channelName, 'private-')) {
            return PrivateChannel::class;
        }

        if (Str::startsWith($channelName, 'presence-')) {
            return PresenceChannel::class;
        }

        return Channel::class;
    }

    /**
     * Remove a connection from all channels.
     *
     * @param  ConnectionInterface  $connection
     * @return void
     */
    public function removeFromAllChannels(ConnectionInterface $connection): void
    {
        foreach ($this->channels as $channelName => $channel) {
            $this->unsubscribeFromChannel($connection, $channelName);
        }
    }

    /**
     * Subscribe a connection to a channel.
     *
     * @param  ConnectionInterface  $connection
     * @param  string  $channelName
     * @param  array  $payload
     * @return void
     */
    public function subscribeToChannel(ConnectionInterface $connection, string $channelName, array $payload = []): void
    {
        $channel = $this->findOrCreate($channelName, $connection->app->id);
        
        // Handle different channel types
        if ($channel instanceof PrivateChannel) {
            $this->subscribeToPrivateChannel($connection, $channel, $payload);
        } elseif ($channel instanceof PresenceChannel) {
            $this->subscribeToPresenceChannel($connection, $channel, $payload);
        } else {
            $channel->subscribe($connection, $payload);
        }
    }

    /**
     * Unsubscribe a connection from a channel.
     *
     * @param  ConnectionInterface  $connection
     * @param  string  $channelName
     * @return bool
     */
    public function unsubscribeFromChannel(ConnectionInterface $connection, string $channelName): bool
    {
        if (! $channel = $this->find($channelName, $connection->app->id)) {
            return false;
        }

        $channel->unsubscribe($connection);

        if (count($channel->getSubscribedConnections()) === 0) {
            unset($this->channels[$channelName]);
        }

        return true;
    }

    /**
     * Broadcast a message to all connections in a channel.
     *
     * @param  string  $channelName
     * @param  string  $message
     * @param  mixed  $appId
     * @return void
     */
    public function broadcastToChannel(string $channelName, string $message, $appId = null): void
    {
        if (! $channel = $this->find($channelName, $appId)) {
            return;
        }

        $channel->broadcast([
            'event' => 'message',
            'channel' => $channelName,
            'data' => $message,
        ]);
    }

    /**
     * Subscribe to a private channel.
     *
     * @param  ConnectionInterface  $connection
     * @param  PrivateChannel  $channel
     * @param  array  $payload
     * @return void
     */
    protected function subscribeToPrivateChannel(ConnectionInterface $connection, $channel, array $payload): void
    {
        // Verify the channel signature
        $signature = $payload['auth'] ?? '';
        
        if (! $this->verifyPrivateChannelSignature($connection, $channel->getName(), $signature)) {
            $connection->send(json_encode([
                'event' => 'subscription_error',
                'channel' => $channel->getName(),
                'data' => [
                    'message' => 'Invalid authentication',
                    'code' => 4001,
                ],
            ]));
            return;
        }

        $channel->subscribe($connection, $payload);
    }

    /**
     * Subscribe to a presence channel.
     *
     * @param  ConnectionInterface  $connection
     * @param  PresenceChannel  $channel
     * @param  array  $payload
     * @return void
     */
    protected function subscribeToPresenceChannel(ConnectionInterface $connection, $channel, array $payload): void
    {
        $authPayload = json_decode($payload['channel_data'] ?? '{}', true);
        $userId = $authPayload['user_id'] ?? null;
        $userInfo = $authPayload['user_info'] ?? [];

        if (! $userId) {
            $connection->send(json_encode([
                'event' => 'subscription_error',
                'channel' => $channel->getName(),
                'data' => [
                    'message' => 'User ID is required',
                    'code' => 4002,
                ],
            ]));
            return;
        }

        $channel->subscribe($connection, $userId, $userInfo);
    }

    /**
     * Verify the signature for a private channel.
     *
     * @param  ConnectionInterface  $connection
     * @param  string  $channelName
     * @param  string  $signature
     * @return bool
     */
    protected function verifyPrivateChannelSignature(ConnectionInterface $connection, string $channelName, string $signature): bool
    {
        // Implement your signature verification logic here
        // This is a simplified example
        $stringToSign = $connection->socketId . ':' . $channelName;
        $expectedSignature = hash_hmac('sha256', $stringToSign, $connection->app->secret);
        
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Get all channels that a connection is subscribed to.
     *
     * @param  ConnectionInterface  $connection
     * @return array
     */
    public function getChannelsForConnection(ConnectionInterface $connection): array
    {
        $channels = [];
        
        foreach ($this->channels as $channelName => $channel) {
            if ($channel->hasConnections() && $channel->hasConnection($connection)) {
                $channels[] = $channelName;
            }
        }
        
        return $channels;
    }

    /**
     * Get all connections in a channel.
     *
     * @param  string  $channelName
     * @param  mixed  $appId
     * @return array
     */
    public function getChannelConnections(string $channelName, $appId = null): array
    {
        if (! $channel = $this->find($channelName, $appId)) {
            return [];
        }
        
        return $channel->getSubscribedConnections();
    }

    /**
     * Get the number of connections in a channel.
     *
     * @param  string  $channelName
     * @param  mixed  $appId
     * @return int
     */
    public function getConnectionCount(string $channelName, $appId = null): int
    {
        if (! $channel = $this->find($channelName, $appId)) {
            return 0;
        }
        
        return $channel->getConnectionCount();
    }
}
