"""
Self-Contained CI/CD System

A decentralized CI/CD system that runs on each node, enabling autonomous
building, testing, and deployment without centralized servers.
"""

import asyncio
import subprocess
import shutil
import os
import json
from pathlib import Path
from typing import Dict, List, Optional, Callable, Any
from dataclasses import dataclass
import hashlib
import logging

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

@dataclass
class BuildArtifact:
    """Represents a build artifact."""
    name: str
    path: str
    checksum: str
    size: int
    metadata: Dict[str, Any] = None

class SelfContainedCICD:
    """Self-contained CI/CD system for autonomous deployment."""
    
    def __init__(self, project_root: str):
        self.project_root = Path(project_root).absolute()
        self.build_dir = self.project_root / "build"
        self.artifacts_dir = self.project_root / "artifacts"
        self.config = self._load_config()
        self._setup_directories()
    
    def _load_config(self) -> Dict:
        """Load CI/CD configuration."""
        config_path = self.project_root / ".cicd" / "config.json"
        if config_path.exists():
            with open(config_path, 'r') as f:
                return json.load(f)
        return {
            "build_steps": [],
            "test_steps": [],
            "deploy_steps": [],
            "notifications": {}
        }
    
    def _setup_directories(self):
        """Set up required directories."""
        self.build_dir.mkdir(exist_ok=True)
        self.artifacts_dir.mkdir(exist_ok=True)
    
    async def run_pipeline(self):
        """Run the complete CI/CD pipeline."""
        logger.info("Starting CI/CD pipeline")
        
        try:
            # Run build steps
            build_success = await self.run_build()
            if not build_success:
                logger.error("Build failed")
                return False
            
            # Run tests
            test_success = await self.run_tests()
            if not test_success:
                logger.error("Tests failed")
                return False
            
            # Deploy
            deploy_success = await self.run_deployment()
            if not deploy_success:
                logger.error("Deployment failed")
                return False
            
            logger.info("CI/CD pipeline completed successfully")
            return True
            
        except Exception as e:
            logger.error(f"Pipeline failed: {str(e)}")
            return False
    
    async def run_build(self) -> bool:
        """Run build steps."""
        logger.info("Running build steps")
        
        for step in self.config.get("build_steps", []):
            success = await self._run_step(step, "build")
            if not success:
                return False
        
        return True
    
    async def run_tests(self) -> bool:
        """Run test steps."""
        logger.info("Running tests")
        
        for step in self.config.get("test_steps", []):
            success = await self._run_step(step, "test")
            if not success:
                return False
        
        return True
    
    async def run_deployment(self) -> bool:
        """Run deployment steps."""
        logger.info("Running deployment")
        
        for step in self.config.get("deploy_steps", []):
            success = await self._run_step(step, "deploy")
            if not success:
                return False
        
        return True
    
    async def _run_step(self, step: Dict, step_type: str) -> bool:
        """Execute a single CI/CD step."""
        step_name = step.get("name", "unnamed")
        logger.info(f"Running {step_type} step: {step_name}")
        
        try:
            if "command" in step:
                # Execute shell command
                proc = await asyncio.create_subprocess_shell(
                    step["command"],
                    cwd=self.project_root,
                    stdout=asyncio.subprocess.PIPE,
                    stderr=asyncio.subprocess.PIPE
                )
                
                stdout, stderr = await proc.communicate()
                
                if proc.returncode != 0:
                    logger.error(f"Step {step_name} failed with exit code {proc.returncode}")
                    logger.error(f"Stderr: {stderr.decode()}")
                    return False
                
                logger.debug(f"Step {step_name} output: {stdout.decode()}")
                
            elif "script" in step:
                # Execute Python script
                script_path = self.project_root / step["script"]
                if not script_path.exists():
                    logger.error(f"Script not found: {script_path}")
                    return False
                
                # In a real implementation, we would execute the script here
                logger.info(f"Would execute script: {script_path}")
                
            return True
            
        except Exception as e:
            logger.error(f"Error in step {step_name}: {str(e)}")
            return False
    
    def create_artifact(self, source_path: str, name: str, metadata: Dict = None) -> BuildArtifact:
        """Create a build artifact."""
        source = Path(source_path)
        if not source.exists():
            raise FileNotFoundError(f"Source file not found: {source_path}")
        
        # Calculate checksum
        checksum = self._calculate_checksum(source)
        
        # Copy to artifacts directory
        artifact_path = self.artifacts_dir / name
        shutil.copy2(source, artifact_path)
        
        artifact = BuildArtifact(
            name=name,
            path=str(artifact_path),
            checksum=checksum,
            size=artifact_path.stat().st_size,
            metadata=metadata or {}
        )
        
        # Save artifact metadata
        self._save_artifact_metadata(artifact)
        
        return artifact
    
    def _calculate_checksum(self, file_path: Path) -> str:
        """Calculate SHA-256 checksum of a file."""
        sha256_hash = hashlib.sha256()
        with open(file_path, "rb") as f:
            for byte_block in iter(lambda: f.read(4096), b""):
                sha256_hash.update(byte_block)
        return sha256_hash.hexdigest()
    
    def _save_artifact_metadata(self, artifact: BuildArtifact):
        """Save artifact metadata to a JSON file."""
        metadata_path = self.artifacts_dir / f"{artifact.name}.meta.json"
        with open(metadata_path, 'w') as f:
            json.dump({
                "name": artifact.name,
                "path": artifact.path,
                "checksum": artifact.checksum,
                "size": artifact.size,
                "metadata": artifact.metadata,
                "timestamp": str(datetime.utcnow())
            }, f, indent=2)

# Example usage
async def example_pipeline():
    """Example of using the SelfContainedCICD class."""
    cicd = SelfContainedCICD(project_root=".")
    
    # Example configuration (in a real project, this would be in .cicd/config.json)
    cicd.config = {
        "build_steps": [
            {"name": "Install Dependencies", "command": "pip install -r requirements.txt"},
            {"name": "Build Package", "command": "python setup.py build"}
        ],
        "test_steps": [
            {"name": "Run Tests", "command": "pytest tests/"}
        ],
        "deploy_steps": [
            {"name": "Deploy", "script": "deploy.py"}
        ]
    }
    
    # Run the pipeline
    success = await cicd.run_pipeline()
    print(f"Pipeline {'succeeded' if success else 'failed'}")
