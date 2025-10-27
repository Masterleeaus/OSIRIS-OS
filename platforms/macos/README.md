# macOS Platform

This directory contains the macOS-specific implementation for AIPlatform.

## Setup

1. Install Xcode and macOS development tools.
2. Install dependencies: `npm install`.
3. Configure build settings in `macos/Info.plist`.
4. Run `npm run build:macos` to build the app.

## Features

- Native macOS UI components.
- Integration with Web5 for decentralized identity.
- AI model inference on macOS hardware.

## Documentation

- [macOS Setup Guide](./docs/setup.md)
- [API Reference](./docs/api.md)

## Building

For development:
```bash
npm run dev:macos
```

For production:
```bash
npm run build:macos
```
