# Docker & Containers

## Build local image
```bash
docker build -t rechain/aiplatform:dev .
```

## Run with docker-compose
```bash
docker compose -f docker-compose.yml up --build
```

## Multi-platform build
```bash
docker buildx build --platform linux/amd64,linux/arm64 -t rechain/aiplatform:latest --push .
```

## Images & tags
- Use semantic tags and immutable tags for releases: `rechain/aiplatform:vX.Y.Z`
