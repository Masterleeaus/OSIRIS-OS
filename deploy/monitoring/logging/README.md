# Advanced Log Management for Quantum Infrastructure Zero

This directory contains configurations for an advanced log management system that includes log sampling, archiving, pattern analysis, and integration with Elasticsearch/OpenSearch for enhanced log analysis.

## Components

1. **Log Sampling**
   - Application-specific sampling rules
   - Dynamic rate adjustment
   - Comprehensive monitoring

2. **Log Archiving**
   - S3-compatible storage
   - Lifecycle management
   - Cost-effective retention policies

3. **Pattern Analysis**
   - Real-time log pattern detection
   - Anomaly detection
   - Custom alerting

4. **Elasticsearch/OpenSearch Integration**
   - Scalable log storage and analysis
   - Advanced querying capabilities
   - Visualization dashboards

## Prerequisites

1. Kubernetes cluster (v1.19+)
2. `kubectl` configured to access your cluster
3. `helm` (v3.0.0+)
4. S3-compatible storage (for log archiving)
5. (Optional) Elasticsearch/OpenSearch cluster

## Deployment

### 1. Namespace Setup

```bash
kubectl create namespace monitoring
```

### 2. Deploy Logging Infrastructure

#### 2.1. Deploy Fluent Bit with Sampling

```bash
# Apply the sampling configuration
kubectl apply -f application-sampling-rules.yaml
kubectl apply -f log-sampling-monitoring.yaml
```

#### 2.2. Deploy Log Archiving

1. Create a secret with your S3 credentials:

```bash
kubectl create secret generic aws-credentials \
  --from-literal=aws-access-key-id=YOUR_ACCESS_KEY \
  --from-literal=aws-secret-access-key=YOUR_SECRET_KEY \
  -n monitoring
```

2. Update the `log-archiving.yaml` with your S3 bucket details and apply:

```bash
kubectl apply -f log-archiving.yaml
```

#### 2.3. Deploy Log Pattern Monitoring

```bash
kubectl apply -f log-pattern-monitoring.yaml
```

#### 2.4. (Optional) Deploy Elasticsearch/OpenSearch

```bash
# For Elasticsearch
kubectl apply -f elasticsearch-opensearch.yaml -n monitoring

# For OpenSearch
kubectl apply -f elasticsearch-opensearch.yaml -n monitoring
# Then apply the OpenSearch-specific configurations
kubectl apply -f opensearch-dashboards-dashboard.yaml -n monitoring
```

## Configuration

### Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `S3_BUCKET` | S3 bucket for log archiving | `your-log-bucket` |
| `AWS_REGION` | AWS region for S3 | `us-west-2` |
| `ELASTICSEARCH_HOST` | Elasticsearch host | `elasticsearch` |
| `ELASTICSEARCH_PORT` | Elasticsearch port | `9200` |
| `OPENSEARCH_HOST` | OpenSearch host | `opensearch` |
| `OPENSEARCH_PORT` | OpenSearch port | `9200` |

### Customizing Sampling Rules

Edit `application-sampling-rules.yaml` to adjust sampling rates and rules for different applications and log levels.

### Adjusting Retention Policies

Modify the lifecycle rules in `log-archiving.yaml` to change retention periods and storage classes.

## Monitoring and Alerts

### Accessing Dashboards

1. Port-forward the Grafana service:

```bash
kubectl port-forward svc/prometheus-stack-grafana 3000:80 -n monitoring
```

2. Open http://localhost:3000 in your browser
3. Log in with the default credentials (admin/prom-operator)
4. Import the following dashboards:
   - Log Sampling Monitoring
   - Log Pattern Analysis
   - Kubernetes Log Analysis (if using OpenSearch)

### Pre-configured Alerts

- **HighErrorRate**: Triggered when error rate exceeds 5%
- **SuspiciousLogPattern**: Detects potential security issues
- **PerformanceDegradation**: Alerts on significant performance drops
- **LogAnomalyDetected**: Identifies unusual log volume patterns
- **ErrorRateSpike**: Detects sudden increases in error rates
- **LogPatternChange**: Alerts on significant changes in log patterns

## Maintenance

### Updating Configurations

1. Make changes to the appropriate YAML files
2. Apply the changes:

```bash
kubectl apply -f <updated-file>.yaml
```

3. (Optional) Restart the relevant pods:

```bash
kubectl rollout restart deployment/<deployment-name> -n monitoring
```

### Scaling

To scale the logging infrastructure:

```bash
# Scale Fluent Bit (one pod per node)
kubectl scale daemonset/fluent-bit --replicas=$(kubectl get nodes --no-headers | wc -l) -n monitoring

# Scale Elasticsearch data nodes
kubectl scale statefulset/elasticsearch-es-default --replicas=5 -n monitoring
```

## Troubleshooting

### Common Issues

1. **Logs not appearing in storage**
   - Check Fluent Bit logs: `kubectl logs -l app=fluent-bit -n monitoring`
   - Verify S3 credentials and permissions
   - Check network connectivity to S3

2. **High memory usage**
   - Adjust buffer sizes in Fluent Bit configuration
   - Increase memory limits if necessary
   - Review sampling rates

3. **Missing logs**
   - Verify log collection paths
   - Check for pod restarts
   - Review sampling rules

### Log Collection

```bash
# Get logs from all Fluent Bit pods
kubectl logs -l app=fluent-bit -n monitoring --tail=100

# Check for errors
kubectl logs -l app=fluent-bit -n monitoring | grep -i error

# Monitor log processing
kubectl logs -f -l app=fluent-bit -n monitoring
```

## Backup and Restore

### Backing Up Configurations

```bash
# Backup all configurations
kubectl get cm,secret,svc,deploy,ds -n monitoring -o yaml > logging-backup-$(date +%Y%m%d).yaml
```

### Restoring from Backup

```bash
kubectl apply -f logging-backup-20231031.yaml
```

## Security Considerations

1. **Secrets Management**
   - Store sensitive information in Kubernetes Secrets
   - Use sealed-secrets for GitOps workflows
   - Rotate credentials regularly

2. **Network Policies**
   - Restrict access to logging services
   - Use network policies to control traffic
   - Enable TLS for all communications

3. **Access Control**
   - Implement RBAC for all logging components
   - Use least privilege principle
   - Audit access to logs

## Performance Optimization

1. **Sampling**
   - Adjust sampling rates based on log importance
   - Use dynamic sampling for variable workloads
   - Monitor sampling effectiveness

2. **Buffering**
   - Tune buffer sizes based on workload
   - Configure retry policies
   - Monitor memory usage

3. **Storage**
   - Use appropriate storage classes
   - Implement lifecycle policies
   - Monitor storage usage

## Support

For issues and feature requests, please open an issue in the repository.

## License

This project is licensed under the [MIT License](LICENSE).
