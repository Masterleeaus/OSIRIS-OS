# Quantum Encryption Horizontal

Quantum-resistant encryption and security services.

## Features

- Post-quantum cryptography
- Quantum key distribution
- Zero-knowledge proofs
- Homomorphic encryption
- Secure multi-party computation

## Setup

1. Install quantum crypto: `npm install quantum-crypto-sdk`
2. Configure encryption in config/quantum-encryption.php

## Post-Quantum Cryptography

### Key Exchange
```javascript
// Quantum-resistant key exchange
const quantumKE = new QuantumKeyExchange();
const { publicKey, secretKey } = await quantumKE.generateKeyPair();

// Establish shared secret
const sharedSecret = await quantumKE.deriveSharedSecret(
  publicKey,
  secretKey
);
```

### Digital Signatures
```javascript
// Post-quantum signatures
const signature = await quantumEncryption.sign(
  message,
  privateKey,
  'dilithium'
);

const isValid = await quantumEncryption.verify(
  message,
  signature,
  publicKey,
  'dilithium'
);
```

## Quantum Key Distribution

### BB84 Protocol
```javascript
// Implement BB84 quantum key distribution
const qkd = new QuantumKeyDistribution();
const secureKey = await qkd.bb84Protocol({
  photonCount: 1000,
  basis: 'random',
  distance: '100km'
});
```

### E91 Protocol
```javascript
// Entanglement-based QKD
const secureKey = await qkd.e91Protocol({
  entangledPairs: 500,
  measurement: 'bell-state'
});
```

## Homomorphic Encryption

### Fully Homomorphic Encryption
```javascript
// Perform computations on encrypted data
const fhe = new FullyHomomorphicEncryption();
const encrypted = await fhe.encrypt(data, publicKey);

// Compute on encrypted data
const encryptedResult = await fhe.add(encrypted, encryptedValue);
const result = await fhe.decrypt(encryptedResult, privateKey);
```

### Partially Homomorphic
```javascript
// Addition-only homomorphic encryption
const phe = new PartiallyHomomorphicEncryption();
const encryptedSum = await phe.add(encryptedA, encryptedB);
```

## Zero-Knowledge Proofs

### zk-SNARKs
```javascript
// Generate zero-knowledge proof
const proof = await zkSNARK.prove({
  statement: 'I know x such that y = x^2',
  witness: x,
  circuit: quadraticCircuit
});

const isValid = await zkSNARK.verify(proof, verificationKey);
```

### zk-STARKs
```javascript
// Scalable transparent arguments of knowledge
const proof = await zkSTARK.prove({
  computation: fibonacciComputation,
  input: 100,
  output: 354224848179261915075
});
```

## Secure Multi-Party Computation

### Secret Sharing
```javascript
// Shamir's secret sharing
const shares = await smpc.shareSecret(secret, {
  participants: 5,
  threshold: 3
});

// Reconstruct secret
const reconstructed = await smpc.reconstructSecret(shares.slice(0, 3));
```

### MPC Protocols
```javascript
// Multi-party computation
const result = await smpc.compute({
  function: 'auction-winner',
  inputs: [bid1, bid2, bid3],
  parties: ['party1', 'party2', 'party3']
});
```

## API Endpoints

- POST /api/encryption/qkd/generate-key - Generate quantum key
- POST /api/encryption/fhe/encrypt - Homomorphic encryption
- POST /api/encryption/zk/prove - Generate ZK proof
- POST /api/encryption/smpc/compute - Secure computation

## Documentation

- [Quantum Crypto Guide](./docs/quantum-crypto.md)
- [Post-Quantum Standards](./docs/standards.md)
- [Security Implementation](./docs/security.md)
