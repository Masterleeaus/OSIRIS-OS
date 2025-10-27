# AI Training Vertical

This vertical handles AI model training, deployment, and management.

## Features

- Train models on federated data
- Deploy models to edge devices
- Monitor model performance
- Version control for models

## Setup

1. Install TensorFlow: `pip install tensorflow`
2. Configure training datasets in config/ai.php

## API Endpoints

- POST /api/ai/train - Start training
- GET /api/ai/models - List models
- PUT /api/ai/models/{id}/deploy - Deploy model

## Documentation

- [Training Guide](./docs/training.md)
- [Model API](./docs/api.md)
