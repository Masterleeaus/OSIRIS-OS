<?php

namespace App\Services\WebSocket;

use Illuminate\Support\Facades\Validator;

class WebSocketMessage
{
    protected $id;
    protected $channel;
    protected $event;
    protected $data;
    protected $meta = [];
    protected $schema;
    protected $retryCount = 0;
    protected $maxRetries = 3;
    protected $createdAt;
    protected $expiresAt;
    protected $priority = 3;
    protected $acknowledged = false;
    protected $validationRules = [];
    protected $validationMessages = [];

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? bin2hex(random_bytes(16));
        $this->channel = $data['channel'] ?? null;
        $this->event = $data['event'] ?? 'message';
        $this->data = $data['data'] ?? [];
        $this->meta = $data['meta'] ?? [];
        $this->createdAt = $data['created_at'] ?? time();
        $this->expiresAt = $data['expires_at'] ?? null;
        $this->priority = $data['priority'] ?? 3;
        $this->maxRetries = $data['max_retries'] ?? 3;
        $this->validationRules = $data['validation_rules'] ?? [];
        $this->validationMessages = $data['validation_messages'] ?? [];
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getChannel(): ?string
    {
        return $this->channel;
    }

    public function getEvent(): string
    {
        return $this->event;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getMeta(): array
    {
        return $this->meta;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt && $this->expiresAt < time();
    }

    public function shouldRetry(): bool
    {
        return $this->retryCount < $this->maxRetries && !$this->isExpired();
    }

    public function incrementRetryCount(): void
    {
        $this->retryCount++;
    }

    public function markAsAcknowledged(): void
    {
        $this->acknowledged = true;
    }

    public function isAcknowledged(): bool
    {
        return $this->acknowledged;
    }

    public function validate(): bool
    {
        if (empty($this->validationRules)) {
            return true;
        }

        $validator = Validator::make(
            $this->data,
            $this->validationRules,
            $this->validationMessages
        );

        return !$validator->fails();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'channel' => $this->channel,
            'event' => $this->event,
            'data' => $this->data,
            'meta' => array_merge($this->meta, [
                'created_at' => $this->createdAt,
                'expires_at' => $this->expiresAt,
                'priority' => $this->priority,
                'retry_count' => $this->retryCount,
                'max_retries' => $this->maxRetries,
            ]),
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);
        return new self($data);
    }
}
