# Stage 10 DevOps Terraform Stack

This Terraform stack provisions the AWS resources required for the DevOps &
Environments tranche: hardened S3 buckets with lifecycle and WORM policies,
KMS encryption keys, CloudWatch alerts for queue health, and SSM Parameter Store
secrets for CI/CD hydration.

## Prerequisites

- Terraform 1.5+
- AWS provider credentials with permissions for S3, KMS, IAM, CloudWatch, and
  SSM Parameter Store

## Usage

```bash
terraform init
terraform apply \
  -var="environment=staging" \
  -var="aws_region=us-east-1" \
  -var='secret_parameters={"/academy/staging/stripe/secret"="s3cr3t"}'
```

Bucket names are suffixed automatically with the environment to avoid
cross-account conflicts. Lifecycle policies match the defaults enforced by the
Laravel configuration (`config/storage_lifecycle.php`).
