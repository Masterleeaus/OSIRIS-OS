"""
Performance benchmarks for the Distributed Identity & Data Network (DIDN) component.
"""

import timeit
import random
import string
import numpy as np
from src.didn import DIDN, Identity

class DIDNBenchmark:
    """Benchmark suite for DIDN operations."""
    
    def __init__(self):
        self.didn = DIDN()
        self.identity_ids = []
        self.data_ids = []
        
    def _random_string(self, length=32):
        """Generate a random string of fixed length."""
        return ''.join(random.choices(string.ascii_letters + string.digits, k=length))
    
    def setup(self, num_identities=1000, num_data_items=1000):
        """Set up test data."""
        print(f"Setting up benchmark with {num_identities} identities and {num_data_items} data items...")
        
        # Create test identities
        self.identity_ids = []
        for _ in range(num_identities):
            identity_id = self.didn.register_identity(
                public_key=self._random_string(),
                signature=self._random_string(64),
                metadata={"name": self._random_string(10)}
            )
            self.identity_ids.append(identity_id)
        
        # Create test data
        self.data_ids = []
        for _ in range(num_data_items):
            identity_id = random.choice(self.identity_ids)
            data = {
                "type": "test_data",
                "content": self._random_string(1000)  # 1KB of random data
            }
            data_id = self.didn.store_data(identity_id, data, self._random_string(64))
            self.data_ids.append(data_id)
    
    def benchmark_register_identity(self, num_runs=1000):
        """Benchmark identity registration."""
        def _register():
            self.didn.register_identity(
                public_key=self._random_string(),
                signature=self._random_string(64),
                metadata={"name": self._random_string(10)}
            )
        
        # Warm-up
        for _ in range(10):
            _register()
        
        # Benchmark
        times = timeit.repeat(_register, number=1, repeat=num_runs)
        return self._analyze_times(times, "Identity Registration")
    
    def benchmark_resolve_identity(self, num_runs=1000):
        """Benchmark identity resolution."""
        if not self.identity_ids:
            self.setup()
        
        def _resolve():
            identity_id = random.choice(self.identity_ids)
            self.didn.resolve_identity(identity_id)
        
        # Warm-up
        for _ in range(10):
            _resolve()
        
        # Benchmark
        times = timeit.repeat(_resolve, number=1, repeat=num_runs)
        return self._analyze_times(times, "Identity Resolution")
    
    def benchmark_store_data(self, num_runs=1000):
        """Benchmark data storage."""
        if not self.identity_ids:
            self.setup()
        
        def _store():
            identity_id = random.choice(self.identity_ids)
            data = {
                "type": "benchmark_data",
                "content": self._random_string(1000)  # 1KB of random data
            }
            self.didn.store_data(identity_id, data, self._random_string(64))
        
        # Warm-up
        for _ in range(10):
            _store()
        
        # Benchmark
        times = timeit.repeat(_store, number=1, repeat=num_runs)
        return self._analyze_times(times, "Data Storage")
    
    def benchmark_resolve_data(self, num_runs=1000):
        """Benchmark data resolution."""
        if not self.data_ids:
            self.setup()
        
        def _resolve():
            data_id = random.choice(self.data_ids)
            self.didn.resolve_data(data_id)
        
        # Warm-up
        for _ in range(10):
            _resolve()
        
        # Benchmark
        times = timeit.repeat(_resolve, number=1, repeat=num_runs)
        return self._analyze_times(times, "Data Resolution")
    
    def _analyze_times(self, times, operation_name):
        """Analyze and print benchmark results."""
        times_ms = [t * 1000 for t in times]  # Convert to milliseconds
        
        stats = {
            "operation": operation_name,
            "runs": len(times),
            "min": min(times_ms),
            "max": max(times_ms),
            "mean": np.mean(times_ms),
            "median": np.median(times_ms),
            "stddev": np.std(times_ms),
            "p90": np.percentile(times_ms, 90),
            "p99": np.percentile(times_ms, 99),
        }
        
        print("\n" + "=" * 80)
        print(f"{operation_name} Benchmark Results")
        print("=" * 80)
        print(f"Runs: {stats['runs']}")
        print(f"Min: {stats['min']:.6f} ms")
        print(f"Max: {stats['max']:.6f} ms")
        print(f"Mean: {stats['mean']:.6f} ms")
        print(f"Median: {stats['median']:.6f} ms")
        print(f"Std Dev: {stats['stddev']:.6f} ms")
        print(f"90th %-tile: {stats['p90']:.6f} ms")
        print(f"99th %-tile: {stats['p99']:.6f} ms")
        print("=" * 80 + "\n")
        
        return stats

def run_all_benchmarks():
    """Run all benchmarks and print results."""
    print("Starting DIDN Performance Benchmarks")
    print("=" * 80)
    
    benchmark = DIDNBenchmark()
    
    # Run benchmarks
    results = {
        "register_identity": benchmark.benchmark_register_identity(),
        "resolve_identity": benchmark.benchmark_resolve_identity(),
        "store_data": benchmark.benchmark_store_data(),
        "resolve_data": benchmark.benchmark_resolve_data(),
    }
    
    return results

if __name__ == "__main__":
    run_all_benchmarks()
