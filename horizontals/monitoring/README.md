# Monitoring Horizontal

Monitoring and metrics collection for AIPlatform.

## Features

- Performance metrics
- Error tracking
- Health checks
- Alerting system

## Setup

1. Install monitoring tools
2. Configure in config/monitoring.php

## Usage

```php
Metrics::increment('api.requests');
Metrics::timing('api.response_time', $time);
```

## Documentation

- [Metrics](./docs/metrics.md)
- [Alerts](./docs/alerts.md)
