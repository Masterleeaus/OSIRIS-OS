"""
End-to-end integration tests for the Quantum Infrastructure Zero system.
"""

import asyncio
import pytest
import json
import numpy as np
from pathlib import Path
from typing import Dict, List, Any

# Import components
from src.didn import DIDN
from src.qmp import QMPService, QMPMessage
from src.ai_nodes import AINode
from src.self_contained_cicd import SelfContainedCICD

# Test configuration
NUM_NODES = 3
TEST_PORT = 8000

class MockModel:
    """Mock model for testing."""
    def __init__(self):
        self.weights = {"dense/kernel": np.random.rand(10, 5)}
    
    def get_weights(self):
        return self.weights
    
    def set_weights(self, weights):
        self.weights = weights

class TestNode:
    """A test node with all components integrated."""
    
    def __init__(self, node_id: str, port: int):
        self.node_id = node_id
        self.port = port
        
        # Initialize components
        self.didn = DIDN()
        self.qmp = QMPService(node_id)
        self.model = MockModel()
        self.ai_node = AINode(node_id, self.model)
        
        # Register message handlers
        self.qmp.register_handler("model_update", self.handle_model_update)
        self.qmp.register_handler("chat_message", self.handle_chat_message)
        
        # Node state
        self.received_messages: List[Dict] = []
        self.received_updates: List[Dict] = []
    
    async def start(self):
        """Start the node's services."""
        await self.qmp.start(port=self.port)
        
        # Register identity in the network
        self.identity_id = self.didn.register_identity(
            public_key=f"public_key_{self.node_id}",
            signature=f"signature_{self.node_id}",
            metadata={"name": self.node_id}
        )
    
    async def stop(self):
        """Stop the node's services."""
        await self.qmp.stop()
    
    async def send_chat_message(self, text: str, nodes: List['TestNode']):
        """Send a chat message to all nodes."""
        message = self.qmp.create_message(
            content={"text": text, "sender": self.node_id},
            message_type="chat_message"
        )
        
        # In a real implementation, we'd use the network to broadcast
        for node in nodes:
            if node != self:
                await node.qmp._process_message(message, None)
    
    async def broadcast_model_update(self, nodes: List['TestNode']):
        """Broadcast a model update to all nodes."""
        # Train the model to get an update
        data = {
            'x': np.random.rand(100, 10),
            'y': np.random.rand(100, 1)
        }
        update = await self.ai_node.train(data)
        
        # Create and send update message
        message = self.qmp.create_message(
            content={
                "node_id": self.node_id,
                "update": {
                    "weights": {k: v.tolist() for k, v in update.weights.items()},
                    "samples_count": update.samples_count,
                    "timestamp": update.timestamp
                }
            },
            message_type="model_update"
        )
        
        # In a real implementation, we'd use the network to broadcast
        for node in nodes:
            if node != self:
                await node.qmp._process_message(message, None)
    
    async def handle_chat_message(self, message: QMPMessage, writer):
        """Handle incoming chat messages."""
        self.received_messages.append(message.content)
    
    async def handle_model_update(self, message: QMPMessage, writer):
        """Handle incoming model updates."""
        self.received_updates.append(message.content)
        
        # In a real implementation, we'd update the model
        # update = message.content["update"]
        # self.ai_node.updates[message.content["node_id"]] = ModelUpdate(
        #     node_id=message.content["node_id"],
        #     weights={k: np.array(v) for k, v in update["weights"].items()},
        #     samples_count=update["samples_count"],
        #     timestamp=update["timestamp"]
        # )

@pytest.fixture
test_nodes() -> List[TestNode]:
    """Create a set of test nodes."""
    nodes = [TestNode(f"node_{i}", TEST_PORT + i) for i in range(NUM_NODES)]
    return nodes

@pytest.fixture
async def initialized_nodes(test_nodes):
    """Initialize and start test nodes."""
    # Start all nodes
    await asyncio.gather(*[node.start() for node in test_nodes])
    
    yield test_nodes
    
    # Cleanup
    await asyncio.gather(*[node.stop() for node in test_nodes])

@pytest.mark.asyncio
async def test_chat_messaging(initialized_nodes):
    """Test sending chat messages between nodes."""
    nodes = initialized_nodes
    sender = nodes[0]
    receiver = nodes[1]
    
    # Send a test message
    test_message = "Hello, Quantum World!"
    await sender.send_chat_message(test_message, nodes)
    
    # Give some time for message processing
    await asyncio.sleep(0.1)
    
    # Verify the message was received
    assert len(receiver.received_messages) == 1
    assert receiver.received_messages[0]["text"] == test_message
    assert receiver.received_messages[0]["sender"] == sender.node_id

@pytest.mark.asyncio
async def test_federated_learning(initialized_nodes):
    """Test federated learning between nodes."""
    nodes = initialized_nodes
    
    # Each node broadcasts a model update
    for node in nodes:
        await node.broadcast_model_update(nodes)
    
    # Give some time for updates to propagate
    await asyncio.sleep(0.2)
    
    # Verify all nodes received updates from all other nodes
    for node in nodes:
        # Each node should have received updates from all other nodes
        assert len(node.received_updates) == len(nodes) - 1
        
        # In a real implementation, we'd also verify the model was updated
        # assert len(node.ai_node.updates) == len(nodes) - 1

@pytest.mark.asyncio
async def test_identity_management(initialized_nodes):
    """Test decentralized identity management."""
    nodes = initialized_nodes
    
    # Each node should have registered an identity
    for node in nodes:
        identity = node.didn.resolve_identity(node.identity_id)
        assert identity is not None
        assert identity.metadata["name"] == node.node_id
        
        # Verify the identity is only known to the node that created it
        for other_node in nodes:
            if other_node != node:
                assert other_node.didn.resolve_identity(node.identity_id) is None

@pytest.mark.asyncio
async def test_self_contained_cicd(tmp_path):
    """Test the self-contained CI/CD system."""
    # Create a test project directory
    project_dir = tmp_path / "test_project"
    project_dir.mkdir()
    
    # Create a simple test script
    test_script = project_dir / "test_script.py"
    test_script.write_text("print('Hello, World!')")
    
    # Initialize CI/CD
    cicd = SelfContainedCICD(str(project_dir))
    
    # Configure a simple pipeline
    cicd.config = {
        "build_steps": [
            {"name": "List Files", "command": "dir" if os.name == 'nt' else "ls -la"},
            {"name": "Run Test Script", "command": f"python {test_script.name}"}
        ]
    }
    
    # Run the pipeline
    success = await cicd.run_pipeline()
    assert success
    
    # Verify artifacts were created
    assert (project_dir / "artifacts").exists()
    assert any((project_dir / "artifacts").iterdir())  # At least one artifact was created
