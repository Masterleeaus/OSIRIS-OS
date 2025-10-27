# Linux Platform

This directory contains the Linux-specific implementation for AIPlatform.

## Setup

1. Install Linux development tools (e.g., build-essential, Qt for GUI).
2. Install dependencies: `npm install`.
3. Configure build settings in `linux/CMakeLists.txt` or equivalent.
4. Run `npm run build:linux` to build the app.

## Features

- Native Linux UI components (GTK or KDE).
- Integration with Web4 AI frameworks.
- Cross-distro compatibility (Ubuntu, Fedora, Arch).

## Documentation

- [Linux Setup Guide](./docs/setup.md)
- [API Reference](./docs/api.md)

## Building

For development:
```bash
npm run dev:linux
```

For production:
```bash
npm run build:linux
```
