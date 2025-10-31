# API Reference (Overview)

This document gives a high-level overview. For auto-generated OpenAPI, see `docs/openapi.yaml` (or generate from code).

## Authentication
- All endpoints use Bearer tokens (JWT).
- Token exchange: `POST /auth/token` with client credentials or password flow.

## Main endpoints (examples)
- `GET /api/v1/models` — list available model adapters
- `POST /api/v1/pipelines/run` — run a GenAI pipeline (body: pipeline id + input)
- `GET /api/v1/jobs/{id}` — job status and logs
- `POST /api/v1/tenants` — create tenant (admin)

## Error handling
- Standard HTTP codes used (400/401/403/404/429/500)
- Error body:
```json
{ "error": { "code": "VALIDATION_ERROR", "message": "Validation failed", "details": [...] } }
```

## Rate limiting
- 100 requests/min by default (per API key). Quotas configurable.
