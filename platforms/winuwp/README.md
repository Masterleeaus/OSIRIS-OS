# WinUWP Platform

This directory contains the Universal Windows Platform (UWP) implementation for AIPlatform.

## Setup

1. Install Visual Studio with UWP development tools.
2. Install dependencies: `npm install`.
3. Configure build settings in `winuwp/Package.appxmanifest`.
4. Run `npm run build:winuwp` to build the app.

## Features

- UWP app for Windows Store deployment.
- Integration with Windows APIs for AI acceleration.
- Web3 support via Microsoft Edge WebView2.

## Documentation

- [WinUWP Setup Guide](./docs/setup.md)
- [API Reference](./docs/api.md)

## Building

For development:
```bash
npm run dev:winuwp
```

For production:
```bash
npm run build:winuwp
```
