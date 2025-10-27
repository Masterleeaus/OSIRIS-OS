# Logging Horizontal

Centralized logging service for AIPlatform.

## Features

- Log aggregation from all services
- Structured logging with levels
- Log rotation and archiving
- Real-time log streaming

## Setup

1. Install logging library: `composer require monolog/monolog`
2. Configure in config/logging.php

## Usage

```php
Log::info('User action', ['user_id' => 123]);
Log::error('Error occurred', ['error' => $e->getMessage()]);
```

## Documentation

- [Logging Config](./docs/config.md)
- [Best Practices](./docs/practices.md)
