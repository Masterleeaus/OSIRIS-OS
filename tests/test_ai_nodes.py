"""Tests for the AI Nodes component."""

import pytest
import numpy as np
from unittest.mock import MagicMock, patch
from src.ai_nodes import AINode, ModelUpdate

class MockModel:
    """Mock model class for testing."""
    
    def __init__(self):
        self.weights = {"dense/kernel": np.random.rand(10, 5)}
    
    def get_weights(self):
        return self.weights
    
    def set_weights(self, weights):
        self.weights = weights

@pytest.fixture
def mock_model():
    """Fixture providing a mock model for testing."""
    return MockModel()

@pytest.fixture
def ai_node(mock_model):
    """Fixture providing an AINode instance for testing."""
    return AINode("test_node", mock_model)

@pytest.mark.asyncio
async def test_ai_node_initialization(ai_node, mock_model):
    """Test AINode initialization."""
    assert ai_node.node_id == "test_node"
    assert ai_node.model == mock_model
    assert isinstance(ai_node.updates, dict)
    assert len(ai_node.updates) == 0
    assert isinstance(ai_node.peers, set)
    assert len(ai_node.peers) == 0

@pytest.mark.asyncio
async def test_train(ai_node, mock_model):
    """Test training the model."""
    # Create test data
    test_data = {
        'x': np.random.rand(100, 10),
        'y': np.random.rand(100, 1)
    }
    
    # Train the model
    update = await ai_node.train(test_data, epochs=2)
    
    # Verify the update
    assert isinstance(update, ModelUpdate)
    assert update.node_id == "test_node"
    assert update.samples_count == 100
    assert isinstance(update.timestamp, float)
    
    # Verify the update was stored
    assert ai_node.node_id in ai_node.updates
    assert ai_node.updates[ai_node.node_id] == update

@pytest.mark.asyncio
async def test_aggregate_updates(ai_node, mock_model):
    """Test aggregating model updates."""
    # Create test updates
    weights1 = {"dense/kernel": np.ones((10, 5))}
    weights2 = {"dense/kernel": np.ones((10, 5)) * 2}
    
    ai_node.updates = {
        "node1": ModelUpdate("node1", weights1, 100, 1000.0),
        "node2": ModelUpdate("node2", weights2, 200, 1000.0)
    }
    
    # Aggregate updates
    aggregated_weights = await ai_node.aggregate_updates()
    
    # Verify the aggregation
    assert "dense/kernel" in aggregated_weights
    expected_weights = (weights1["dense/kernel"] * 100 + weights2["dense/kernel"] * 200) / 300
    np.testing.assert_allclose(aggregated_weights["dense/kernel"], expected_weights)
    
    # Verify the model weights were updated
    np.testing.assert_allclose(
        mock_model.weights["dense/kernel"],
        expected_weights
    )

@pytest.mark.asyncio
async def test_aggregate_empty_updates(ai_node):
    """Test aggregating with no updates."""
    ai_node.updates = {}
    result = await ai_node.aggregate_updates()
    assert result == {}

def test_add_peer(ai_node):
    """Test adding a peer to the node."""
    ai_node.add_peer("peer1")
    assert "peer1" in ai_node.peers
    
    # Adding the same peer again should be idempotent
    ai_node.add_peer("peer1")
    assert len(ai_node.peers) == 1

def test_save_and_load_model(tmp_path, ai_node, mock_model):
    """Test saving and loading a model."""
    # Save the model
    model_path = tmp_path / "test_model.json"
    ai_node.save_model(str(model_path))
    
    # Verify the file was created
    assert model_path.exists()
    
    # Load the model (in a real implementation, this would load the actual model)
    loaded_node = AINode.load_model("new_node", str(model_path), mock_model)
    
    # Verify the loaded node
    assert isinstance(loaded_node, AINode)
    assert loaded_node.node_id == "new_node"
    assert loaded_node.model is not None

@pytest.mark.asyncio
async def test_model_update_serialization():
    """Test serialization and deserialization of ModelUpdate."""
    # Create a model update with NumPy arrays
    weights = {"dense/kernel": np.ones((2, 2))}
    update = ModelUpdate(
        node_id="test_node",
        weights=weights,
        samples_count=100,
        timestamp=1000.0
    )
    
    # Convert to dict and back
    update_dict = {
        'node_id': update.node_id,
        'weights': {k: v.tolist() for k, v in update.weights.items()},
        'samples_count': update.samples_count,
        'timestamp': update.timestamp,
        'signature': update.signature
    }
    
    # In a real implementation, you would have a from_dict method
    # This is a simplified version for testing
    restored_weights = {k: np.array(v) for k, v in update_dict['weights'].items()}
    
    # Verify the data
    assert update_dict['node_id'] == "test_node"
    assert update_dict['samples_count'] == 100
    np.testing.assert_array_equal(restored_weights["dense/kernel"], np.ones((2, 2)))
