# TVOS Platform

This directory contains the tvOS implementation for AIPlatform.

## Setup

1. Install Xcode and tvOS development tools.
2. Install dependencies: `pod install` for CocoaPods.
3. Configure build settings in `tvos/Info.plist`.
4. Run `npm run build:tvos` to build the app.

## Features

- Native tvOS UI components.
- Integration with Apple TV APIs.
- AI features optimized for TV screens.

## Documentation

- [tvOS Setup Guide](./docs/setup.md)
- [API Reference](./docs/api.md)

## Building

For development:
```bash
npm run dev:tvos
```

For production:
```bash
npm run build:tvos
```
