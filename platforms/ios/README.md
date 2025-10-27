# AIPlatform iOS Platform

This directory contains the iOS-specific implementation for AIPlatform using Capacitor.

## Setup

### Prerequisites
- Xcode 14+
- iOS 13+
- Node.js 16+
- CocoaPods

### Installation

1. **Install Capacitor**:
   ```bash
   npm install @capacitor/core @capacitor/ios
   ```

2. **Initialize Capacitor**:
   ```bash
   npx cap init "AIPlatform" "com.rechain.aiplatform"
   npx cap add ios
   ```

3. **Install dependencies**:
   ```bash
   npm install
   cd ios/App
   pod install
   ```

## Development

### Running in Simulator
```bash
npm run dev:ios
```

### Building for Production
```bash
npm run build:ios
npx cap sync ios
npx cap open ios
```

Then in Xcode:
1. Select your target device/simulator
2. Build and run (⌘ + R)

## Features

- **Native iOS Integration**: Camera, Photos, Location, Push Notifications
- **Web3 Support**: WalletConnect, MetaMask integration
- **AI Processing**: On-device ML with Core ML
- **Offline Support**: Service Workers and caching
- **Security**: Biometric authentication, secure storage

## Capacitor Plugins

### Required Plugins
```bash
npm install @capacitor/camera @capacitor/geolocation @capacitor/push-notifications
npm install @capacitor/biometric-auth @capacitor/secure-storage @capacitor/network
npm install @codetrix-studio/capacitor-google-auth
```

### Custom Plugins
- **AIPlatform Blockchain Plugin**: Web3 integration
- **AIPlatform AI Plugin**: On-device AI processing
- **AIPlatform Sync Plugin**: Cross-device synchronization

## Configuration

### capacitor.config.json
```json
{
  "appId": "com.rechain.aiplatform",
  "appName": "AIPlatform",
  "webDir": "dist",
  "bundledWebRuntime": false,
  "plugins": {
    "PushNotifications": {
      "presentationOptions": ["badge", "sound", "alert"]
    },
    "Camera": {
      "permissions": ["camera", "photos"]
    }
  },
  "ios": {
    "scheme": "AIPlatform",
    "path": "ios"
  }
}
```

### iOS-Specific Configuration

#### Info.plist
```xml
<key>CFBundleDisplayName</key>
<string>AIPlatform</string>
<key>LSApplicationCategoryType</key>
<string>public.app-category.productivity</string>
<key>UIBackgroundModes</key>
<array>
  <string>fetch</string>
  <string>remote-notification</string>
</array>
```

#### Signing & Capabilities
- **Push Notifications**: Enable for background sync
- **Background Processing**: Enable for AI tasks
- **Keychain Sharing**: Enable for secure storage
- **Associated Domains**: Enable for Web3 auth

## Building and Distribution

### TestFlight
```bash
npx cap sync ios
npx cap open ios
# In Xcode: Product > Archive > Distribute App > TestFlight
```

### App Store
1. Prepare App Store Connect
2. Create distribution certificate and provisioning profile
3. Archive and upload from Xcode

## Troubleshooting

### Common Issues
- **Build fails**: Clean build folder (⌘ + Shift + K)
- **Plugin not found**: Run `npx cap sync`
- **iOS Simulator issues**: Reset simulator and reinstall

### Debug Mode
```bash
npx cap run ios --livereload
```

## Documentation

- [API Reference](./docs/api.md)
- [Capacitor iOS Guide](https://capacitorjs.com/docs/v3/ios)
- [iOS Development Guide](./docs/ios-dev.md)
- [Plugin Development](./docs/plugins.md)
