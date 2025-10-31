"""Tests for the Distributed Identity & Data Network (DIDN) component."""

import pytest
from src.didn import DIDN, Identity
import json

def test_identity_creation():
    """Test creating and retrieving an identity."""
    # Initialize DIDN
    didn = DIDN()
    
    # Register a new identity
    identity_id = didn.register_identity(
        public_key="test_public_key",
        signature="test_signature",
        metadata={"name": "Test User"}
    )
    
    # Verify the identity was created
    assert identity_id is not None
    assert len(identity_id) == 64  # SHA-256 hex digest length
    
    # Retrieve the identity
    identity = didn.resolve_identity(identity_id)
    assert identity is not None
    assert identity.public_key == "test_public_key"
    assert identity.metadata["name"] == "Test User"

def test_data_storage_and_retrieval():
    """Test storing and retrieving data from the network."""
    # Initialize DIDN
    didn = DIDN()
    
    # Register an identity first
    identity_id = didn.register_identity(
        public_key="test_key",
        signature="test_sig"
    )
    
    # Test data
    test_data = {
        "type": "test_message",
        "content": "Hello, Quantum World!"
    }
    
    # Store the data
    data_id = didn.store_data(identity_id, test_data, "data_signature")
    
    # Retrieve the data
    retrieved = didn.resolve_data(data_id)
    
    # Verify the data
    assert retrieved is not None
    assert retrieved['data'] == test_data
    assert retrieved['identity'] == identity_id

def test_nonexistent_identity():
    """Test resolving a non-existent identity."""
    didn = DIDN()
    assert didn.resolve_identity("nonexistent") is None

def test_data_validation():
    """Test data validation and error handling."""
    didn = DIDN()
    
    # Try to store data with invalid identity
    with pytest.raises(ValueError):
        didn.store_data("invalid_id", {"test": "data"}, "sig")

class TestIdentitySerialization:
    """Test identity serialization and deserialization."""
    
    def test_identity_to_dict(self):
        """Test converting identity to dictionary."""
        identity = Identity(
            public_key="pub_key",
            signature="sig",
            timestamp="2025-10-31T11:00:00",
            metadata={"name": "Test"}
        )
        
        data = identity.to_dict()
        assert data["public_key"] == "pub_key"
        assert data["metadata"]["name"] == "Test"
    
    def test_identity_from_dict(self):
        """Test creating identity from dictionary."""
        data = {
            "public_key": "pub_key",
            "signature": "sig",
            "timestamp": "2025-10-31T11:00:00",
            "metadata": {"name": "Test"}
        }
        
        identity = Identity.from_dict(data)
        assert identity.public_key == "pub_key"
        assert identity.metadata["name"] == "Test"
