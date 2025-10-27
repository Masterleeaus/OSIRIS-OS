# Horizontals

This directory contains horizontal layers for shared services across AIPlatform.

## Purpose

Horizontals provide shared functionality like logging, caching, authentication, that cut across verticals.

## Structure

- `logging/`: Centralized logging service.
- `caching/`: Caching layer (Redis, etc.).
- `authentication/`: Shared auth system.
- `monitoring/`: Monitoring and metrics.

## Implementation

Horizontals are middleware or services that can be injected into verticals.

## Documentation

- [Horizontal Layers](./docs/layers.md)
- [Integration Guide](./docs/integration.md)
