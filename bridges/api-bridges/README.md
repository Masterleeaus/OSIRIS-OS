# API Bridges

This directory contains bridges for API integrations and custom transfers.

## Supported APIs

- REST APIs
- GraphQL APIs
- Custom protocols for code/vibe transfer

## Setup

1. Configure API endpoints in config.
2. Install axios or similar: `npm install axios`

## Features

- Code transfer between systems
- Vibe (mood/context) synchronization
- Custom data bridges

## Example

```javascript
import axios from 'axios';

const response = await axios.post('https://api.example.com/transfer', {
  code: 'your code here',
  vibe: 'innovative'
});
```

## Documentation

- [Custom Transfers](./custom/README.md)
