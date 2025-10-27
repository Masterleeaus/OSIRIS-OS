# Polkadot Bridge

Integrate with Polkadot network for cross-chain interoperability.

## Setup

1. Install @polkadot/api: `npm install @polkadot/api`
2. Configure RPC endpoint: wss://rpc.polkadot.io
3. Set up keyring for signing transactions.

## Features

- Connect to Polkadot parachains.
- Subscribe to chain events.
- Send extrinsics (transactions).

## Example

```javascript
import { ApiPromise, WsProvider } from '@polkadot/api';

const provider = new WsProvider('wss://rpc.polkadot.io');
const api = await ApiPromise.create({ provider });

// Query balance
const balance = await api.query.system.account(ALICE);
console.log(`Balance: ${balance}`);
```

## Documentation

- [Polkadot API Docs](https://polkadot.js.org/docs/)
