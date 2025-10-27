# Android Platform

This directory contains the Android-specific implementation for AIPlatform.

## Setup

1. Install Android Studio and SDK.
2. Install dependencies: `npm install` (for React Native or Capacitor).
3. Configure build settings in `android/build.gradle`.
4. Run `npm run build:android` to build the app.

## Features

- Native Android UI components.
- Integration with Android APIs (e.g., Google Play Services).
- Web5 DID support via native modules.

## Documentation

- [Android Setup Guide](./docs/setup.md)
- [API Reference](./docs/api.md)

## Building

For development:
```bash
npm run dev:android
```

For production:
```bash
npm run build:android
```
