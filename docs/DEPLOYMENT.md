# Deployment & Production Checklist

## Recommended infra
- Kubernetes (EKS/GKE/AKS) or Docker Compose for smaller installs
- Managed DB (Postgres) with backups
- Object store (S3-compatible)
- GPU nodes for model inference (if self-hosting heavy models)

## Environment variables
- Keep secrets in a secret manager (Vault / AWS Secrets Manager)
- Do not store secrets in `.env` in production.

## Build & release
1. Build containers (multi-arch if needed)
2. Run integration tests
3. Tag a release (SemVer)
4. Publish images to registry
5. Deploy via helm / k8s manifests or compose

## Scaling
- Horizontal scale API; vertical on GPU nodes for models
- Use job queue worker autoscaling for bursts

## Backups & DR
- DB backups daily
- Object store lifecycle rules
- Disaster Recovery plan tested quarterly
