# Binance Smart Chain (BSC) Bridge

Integrate with BSC for DeFi and token interactions.

## Setup

1. Install web3.js or ethers.js: `npm install ethers`
2. Configure RPC endpoint: https://bsc-dataseed1.binance.org/
3. Set up wallet.

## Features

- BEP-20 tokens
- DeFi integrations
- Low fees

## Example

```javascript
import { ethers } from 'ethers';

const provider = new ethers.providers.JsonRpcProvider('https://bsc-dataseed1.binance.org/');

async function getBalance(address) {
  const balance = await provider.getBalance(address);
  return ethers.utils.formatEther(balance);
}
```

## Documentation

- [BSC Docs](https://docs.binance.org/)
