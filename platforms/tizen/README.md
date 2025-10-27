# Tizen Platform

This directory contains the Tizen-specific implementation for AIPlatform.

## Setup

1. Install Tizen Studio and SDK.
2. Install dependencies: `npm install`.
3. Configure build settings in `tizen/config.xml`.
4. Run `npm run build:tizen` to build the app.

## Features

- Native Tizen UI components.
- Integration with Tizen APIs (e.g., Samsung services).
- Lightweight for IoT devices.

## Documentation

- [Tizen Setup Guide](./docs/setup.md)
- [API Reference](./docs/api.md)

## Building

For development:
```bash
npm run dev:tizen
```

For production:
```bash
npm run build:tizen
```
