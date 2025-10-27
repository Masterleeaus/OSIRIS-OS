# Docker Configuration

AIPlatform includes Docker support for easy deployment.

## Services

- **App**: Laravel application with PHP 8.3
- **DB**: MySQL 8.0 database
- **Redis**: Caching and queuing
- **Blockchain**: Ethereum node (optional)
- **IPFS**: Decentralized storage (optional)

## Setup

1. Ensure Docker and Docker Compose are installed.
2. Copy `.env.example` to `.env` and configure.
3. Run `docker-compose up -d` to start services.

## Development

```bash
docker-compose up -d
docker-compose exec app php artisan migrate
docker-compose exec app npm run dev
```

## Production

Use the Dockerfile to build a production image.

## Environment Variables

Set in .env:
- APP_ENV=production
- DB_HOST=db
- REDIS_HOST=redis

## Troubleshooting

- Check logs: `docker-compose logs`
- Rebuild: `docker-compose up --build`
