# Builds

This directory contains build configurations and output for each platform.

## Structure

- `desktop/`: Electron builds for macOS, Windows, Linux.
- `mobile/`: Capacitor builds for iOS, Aurora, etc.
- `web/`: Vite builds for web.

## Scripts

Use the main package.json scripts:

- `npm run build:web` - Build for web.
- `npm run build:ios` - Build for iOS.
- `npm run build:macos` - Build for macOS.
- `npm run build:windows` - Build for Windows.
- `npm run build:linux` - Build for Linux.
- `npm run build:winuwp` - Build for WinUWP.
- `npm run build:aurora` - Build for Aurora.

## CI/CD

Builds are automated via GitHub Actions in `.github/workflows/`.
