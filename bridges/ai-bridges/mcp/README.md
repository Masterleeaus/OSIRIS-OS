# MCP (Model Context Protocol) Integration

Bridge to MCP for model context management.

## Purpose

MCP allows sharing context between AI models for better collaboration.

## Setup

1. Install MCP client library: `npm install mcp-client`
2. Configure MCP server endpoints.

## Features

- Share context between models
- Contextual query responses
- Model chaining

## Example

```javascript
import { MCPClient } from 'mcp-client';

const client = new MCPClient('wss://mcp.example.com');
await client.connect();

const context = await client.getContext('model-id');
```

## Documentation

- [MCP Specification](https://mcp-spec.org/)
