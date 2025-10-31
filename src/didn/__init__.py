"""
Distributed Identity & Data Network (DIDN)

A decentralized identity and data layer that replaces DNS, PKI, and SSL.
"""

import hashlib
import json
from typing import Dict, Optional
from dataclasses import dataclass, asdict
from datetime import datetime

@dataclass
class Identity:
    """Represents a decentralized identity in the network."""
    public_key: str
    signature: str
    timestamp: str
    metadata: Dict
    
    def to_dict(self) -> Dict:
        """Convert identity to dictionary."""
        return asdict(self)
    
    @classmethod
    def from_dict(cls, data: Dict) -> 'Identity':
        """Create Identity from dictionary."""
        return cls(**data)

class DIDN:
    """Distributed Identity & Data Network implementation."""
    
    def __init__(self):
        self.identities = {}
        self.data_store = {}
    
    def register_identity(self, public_key: str, signature: str, metadata: Dict = None) -> str:
        """Register a new identity in the network."""
        if metadata is None:
            metadata = {}
            
        identity_id = self._generate_identity_id(public_key)
        identity = Identity(
            public_key=public_key,
            signature=signature,
            timestamp=datetime.utcnow().isoformat(),
            metadata=metadata
        )
        
        self.identities[identity_id] = identity
        return identity_id
    
    def store_data(self, identity_id: str, data: Dict, signature: str) -> str:
        """Store data in the network."""
        if identity_id not in self.identities:
            raise ValueError("Unknown identity")
            
        data_id = self._generate_data_id(data)
        self.data_store[data_id] = {
            'data': data,
            'identity': identity_id,
            'timestamp': datetime.utcnow().isoformat(),
            'signature': signature
        }
        return data_id
    
    def resolve_identity(self, identity_id: str) -> Optional[Identity]:
        """Resolve an identity by its ID."""
        return self.identities.get(identity_id)
    
    def resolve_data(self, data_id: str) -> Optional[Dict]:
        """Resolve data by its ID."""
        return self.data_store.get(data_id)
    
    def _generate_identity_id(self, public_key: str) -> str:
        """Generate a unique ID for an identity."""
        return hashlib.sha256(public_key.encode()).hexdigest()
    
    def _generate_data_id(self, data: Dict) -> str:
        """Generate a unique ID for data."""
        data_str = json.dumps(data, sort_keys=True)
        return hashlib.sha256(data_str.encode()).hexdigest()
