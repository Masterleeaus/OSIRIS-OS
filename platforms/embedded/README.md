# Embedded Systems Platform

Platform for embedded devices, microcontrollers, and edge AI applications.

## Supported Hardware

### Microcontrollers
- **ESP32/ESP8266**: WiFi-enabled microcontrollers
- **Arduino**: Classic microcontroller platform
- **Raspberry Pi Pico**: RP2040 microcontroller
- **STM32**: ARM Cortex-M microcontrollers
- **nRF52**: Bluetooth Low Energy microcontrollers

### Single-Board Computers
- **Raspberry Pi**: Full Linux SBC
- **Jetson Nano**: AI edge computing
- **BeagleBone**: Open hardware SBC
- **Orange Pi**: Alternative to Raspberry Pi

## Setup

### ESP32 Development
```bash
# Install ESP-IDF
git clone --recursive https://github.com/espressif/esp-idf.git
cd esp-idf
./install.sh
. ./export.sh

# Create project
cp -r examples/get-started/hello_world aiplatform-embedded
cd aiplatform-embedded
```

### Arduino IDE
```bash
# Install Arduino IDE
wget https://downloads.arduino.cc/arduino-ide_2.0.3_Linux_64bit.zip
unzip arduino-ide_2.0.3_Linux_64bit.zip
cd arduino-ide_2.0.3
./arduino-ide

# Install AIPlatform library
# Sketch > Include Library > Manage Libraries > AIPlatform
```

## ESP32 Implementation

### WiFi Setup
```cpp
// ESP32 WiFi configuration
#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>

const char* ssid = "AIPlatform-Network";
const char* password = "your-password";

void setupWiFi() {
    WiFi.begin(ssid, password);
    while (WiFi.status() != WL_CONNECTED) {
        delay(1000);
        Serial.println("Connecting to WiFi...");
    }
    Serial.println("Connected to WiFi");
}
```

### AIPlatform Integration
```cpp
// AIPlatform client for ESP32
#include <AIPlatformClient.h>

AIPlatformClient aiClient;

void setup() {
    Serial.begin(115200);
    setupWiFi();

    // Initialize AIPlatform client
    aiClient.begin("https://api.aiplatform.org");
    aiClient.setApiKey("your-api-key");
    aiClient.setDeviceId("esp32-sensor-001");
}

void loop() {
    // Read sensor data
    float temperature = readTemperature();
    float humidity = readHumidity();

    // Send to AIPlatform
    DynamicJsonDocument doc(1024);
    doc["temperature"] = temperature;
    doc["humidity"] = humidity;
    doc["timestamp"] = millis();

    String payload;
    serializeJson(doc, payload);

    String response = aiClient.sendSensorData(payload);

    // Process AI response
    if (response.length() > 0) {
        processAIResponse(response);
    }

    delay(60000); // Send every minute
}
```

## Arduino Implementation

### Basic Sketch
```cpp
// Arduino sketch for AIPlatform integration
#include <AIPlatform.h>
#include <DHT.h>

#define DHTPIN 2
#define DHTTYPE DHT22

DHT dht(DHTPIN, DHTTYPE);
AIPlatform aiPlatform("your-api-key");

void setup() {
    Serial.begin(9600);
    dht.begin();
    aiPlatform.begin();
}

void loop() {
    // Read sensors
    float humidity = dht.readHumidity();
    float temperature = dht.readTemperature();

    // Send to AIPlatform
    SensorData data = {
        .deviceId = "arduino-sensor-001",
        .temperature = temperature,
        .humidity = humidity,
        .timestamp = millis()
    };

    aiPlatform.sendData(data);

    // Check for commands
    Command command = aiPlatform.receiveCommand();
    if (command.type == "LED_CONTROL") {
        digitalWrite(13, command.value);
    }

    delay(30000); // Send every 30 seconds
}
```

## Raspberry Pi Implementation

### Python Client
```python
#!/usr/bin/env python3
# Raspberry Pi AIPlatform client

import time
import json
import requests
import Adafruit_DHT
import RPi.GPIO as GPIO

class AIPlatformPi:
    def __init__(self, api_key, device_id):
        self.api_key = api_key
        self.device_id = device_id
        self.base_url = "https://api.aiplatform.org"
        self.headers = {
            'Authorization': f'Bearer {api_key}',
            'Content-Type': 'application/json'
        }

    def send_sensor_data(self, temperature, humidity):
        data = {
            'deviceId': self.device_id,
            'temperature': temperature,
            'humidity': humidity,
            'timestamp': int(time.time() * 1000)
        }

        response = requests.post(
            f"{self.base_url}/iot/data",
            headers=self.headers,
            json=data
        )

        return response.json()

    def receive_commands(self):
        response = requests.get(
            f"{self.base_url}/iot/commands/{self.device_id}",
            headers=self.headers
        )

        return response.json()

def main():
    # Initialize sensors
    DHT_SENSOR = Adafruit_DHT.DHT22
    DHT_PIN = 4

    # Initialize AIPlatform
    api_key = "your-api-key"
    device_id = "raspberry-pi-sensor"
    ai_client = AIPlatformPi(api_key, device_id)

    # Setup GPIO
    GPIO.setmode(GPIO.BCM)
    GPIO.setup(18, GPIO.OUT)  # LED pin

    try:
        while True:
            # Read sensor data
            humidity, temperature = Adafruit_DHT.read(DHT_SENSOR, DHT_PIN)

            if humidity is not None and temperature is not None:
                # Send to AIPlatform
                result = ai_client.send_sensor_data(temperature, humidity)
                print(f"Sent data: {result}")

                # Check for commands
                commands = ai_client.receive_commands()
                for command in commands:
                    if command['type'] == 'LED_CONTROL':
                        GPIO.output(18, command['value'])

            time.sleep(60)  # Send every minute

    except KeyboardInterrupt:
        GPIO.cleanup()

if __name__ == "__main__":
    main()
```

## Edge AI

### TensorFlow Lite
```python
# TensorFlow Lite for edge devices
import tensorflow as tf
import numpy as np

# Load TFLite model
interpreter = tf.lite.Interpreter(model_path="model.tflite")
interpreter.allocate_tensors()

# Get input/output details
input_details = interpreter.get_input_details()
output_details = interpreter.get_output_details()

def run_inference(data):
    # Prepare input data
    input_data = np.array(data, dtype=np.float32)
    interpreter.set_tensor(input_details[0]['index'], input_data)

    # Run inference
    interpreter.invoke()

    # Get output
    output_data = interpreter.get_tensor(output_details[0]['index'])
    return output_data
```

### ONNX Runtime
```python
# ONNX Runtime for edge AI
import onnxruntime as ort
import numpy as np

# Load ONNX model
session = ort.InferenceSession("model.onnx")

def run_onnx_inference(data):
    # Prepare input
    input_name = session.get_inputs()[0].name
    output_name = session.get_outputs()[0].name

    # Run inference
    result = session.run([output_name], {input_name: data})
    return result[0]
```

## Firmware Over-the-Air (FOTA)

### ESP32 OTA
```cpp
// ESP32 OTA update
#include <WiFi.h>
#include <HTTPClient.h>
#include <Update.h>

void performOTA() {
    WiFiClient client;
    HTTPClient http;

    http.begin(client, "https://firmware.aiplatform.org/esp32/latest.bin");
    int httpCode = http.GET();

    if (httpCode == HTTP_CODE_OK) {
        int contentLength = http.getSize();

        if (contentLength > 0) {
            bool canBegin = Update.begin(contentLength);

            if (canBegin) {
                WiFiClient* stream = http.getStreamPtr();
                size_t written = Update.writeStream(*stream);

                if (written == contentLength) {
                    if (Update.end()) {
                        if (Update.isFinished()) {
                            Serial.println("OTA update successful");
                            ESP.restart();
                        }
                    }
                }
            }
        }
    }

    http.end();
}
```

## Device Management

### Remote Monitoring
```javascript
// Monitor embedded devices
const deviceMonitor = new EmbeddedDeviceMonitor({
    devices: ['esp32-001', 'arduino-002', 'rpi-003'],
    metrics: ['cpu', 'memory', 'temperature', 'uptime'],
    alertThresholds: {
        temperature: 70,  // Celsius
        memory: 80,       // Percentage
        uptime: 30        // Days
    }
});

deviceMonitor.onAlert = (deviceId, metric, value) => {
    console.log(`Alert: ${deviceId} ${metric} = ${value}`);
    // Send notification or trigger action
};
```

### Batch Updates
```javascript
// Update multiple devices simultaneously
const batchUpdate = await embedded.updateDevices({
    devices: ['esp32-*', 'arduino-*'],
    firmware: '2.1.0',
    schedule: 'maintenance-window',
    rollback: true
});
```

## Security

### Secure Boot
```cpp
// ESP32 secure boot configuration
#include "esp_secure_boot.h"

void setupSecureBoot() {
    // Enable secure boot
    esp_secure_boot_enable();

    // Verify firmware signature
    if (!verifyFirmwareSignature()) {
        Serial.println("Invalid firmware signature!");
        while (1) { delay(1000); }
    }
}
```

### Encrypted Communication
```cpp
// Encrypted MQTT communication
#include <WiFiClientSecure.h>
#include <MQTTClient.h>

WiFiClientSecure net;
MQTTClient client;

void setupEncryptedMQTT() {
    net.setCACert(ca_cert);
    net.setCertificate(client_cert);
    net.setPrivateKey(private_key);

    client.begin("mqtts://secure.aiplatform.org:8883", net);
    client.setOptions(5, true, 1000); // Keep alive, clean session, timeout
}
```

## Deployment

### Docker for SBCs
```dockerfile
# Dockerfile for Raspberry Pi
FROM arm32v7/node:16-alpine

WORKDIR /app
COPY package*.json ./
RUN npm ci --only=production

COPY . .
RUN npm run build:embedded

CMD ["node", "dist/embedded-server.js"]
```

### Cross-Compilation
```bash
# Cross-compile for ARM
npm install -g cross-env
cross-env ARCH=arm npm run build:embedded
```

## Documentation

- [Embedded Setup Guide](./docs/embedded-setup.md)
- [Hardware Integration](./docs/hardware.md)
- [Edge AI](./docs/edge-ai.md)
- [Security](./docs/embedded-security.md)
- [Deployment](./docs/embedded-deployment.md)
