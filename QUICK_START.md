# Quick Start — AIPlatform (local/dev)

This file gets you running fast in a development environment.

## Prerequisites
- Docker (>= 20.10)
- docker-compose (>= 1.29) or Docker Compose V2
- Node.js (>= 18) for frontend development
- PHP / Composer only if using legacy modules (optional)

## 1) Clone repo
```bash
git clone https://github.com/REChain-Network-Solutions/AIPlatform.git
cd AIPlatform
```

## 2) Copy example env
```bash
cp .env.example .env
# Edit .env if necessary
```

## 3) Start with Docker compose (dev)
```bash
docker compose -f docker-compose.yml up --build
```

For advanced multi-service:
```bash
docker compose -f docker-compose.advanced.yml up --build
```

## 4) Open UI
- Frontend: http://localhost:3000 (or the port in `.env`)
- API: http://localhost:8000

## 5) Run tests
```bash
# Backend tests (example)
docker exec -it ai-platform-api php artisan test

# Frontend tests
cd app && npm ci && npm test
```

## 6) Common troubleshooting
- Permission errors with Docker volumes: ensure correct UID/GID or use `:delegated` volumes.
- Ports busy: check `lsof -i :<port>` and update `.env`
- Check logs: `docker compose logs -f`

---

For production deployment and scaling, see `DEPLOYMENT.md`.
