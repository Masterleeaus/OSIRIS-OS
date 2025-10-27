# Authentication Horizontal

Shared authentication system for AIPlatform.

## Features

- Multi-provider authentication
- JWT tokens
- Role-based access control
- SSO integration

## Setup

1. Configure auth providers in config/auth.php
2. Set up OAuth applications

## Usage

```php
Auth::login($credentials);
$user = Auth::user();
Auth::logout();
```

## Documentation

- [Auth Config](./docs/config.md)
- [Providers](./docs/providers.md)
