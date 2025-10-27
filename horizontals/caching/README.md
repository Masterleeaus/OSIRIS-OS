# Caching Horizontal

Caching layer using Redis for performance optimization.

## Features

- In-memory caching with Redis
- Cache invalidation strategies
- Cache warming
- Distributed caching

## Setup

1. Install Redis
2. Configure in config/cache.php

## Usage

```php
Cache::put('key', 'value', 60); // Cache for 60 minutes
$value = Cache::get('key');
Cache::forget('key'); // Invalidate
```

## Documentation

- [Cache Config](./docs/config.md)
- [Strategies](./docs/strategies.md)
