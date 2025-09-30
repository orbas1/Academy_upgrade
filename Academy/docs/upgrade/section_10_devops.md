# Section 10 – DevOps & Environments Implementation Notes

This document captures the concrete assets delivered for the DevOps tranche outlined in `AGENTS.md`. It links the specification to the repository so platform engineers can operationalize the stack consistently across staging and production.

## 10.1 Nginx & Networking

The hardened virtual host defined in `infra/nginx/academy_communities.conf` now references the websocket upgrade map, HTTP/2 TLS policies, ModSecurity, and cache controls requested in the spec. Teams should:

- Deploy `infra/nginx/security-headers.conf` alongside the vhost to guarantee CSP, HSTS, and COOP/COEP headers.
- Use the `/ws/` upstream block for Laravel WebSockets or Pusher-compatible services at port `6001`.
- Apply the static asset cache policy (`public, max-age=2592000, immutable`) and the `/storage/` CDN proxy to keep community media behind CloudFront while masking S3 headers.

## 10.2 Queues & Workers

`config/horizon.php` segments queue workloads across `notifications`, `media`, `webhooks`, and `search-index` with auto-scaling limits. Systemd templates should launch one supervisor per queue group, using the environment variables in `.env.example` (`HORIZON_*`) to tune concurrency without code changes. Queue observability is enforced through `config/queue-monitor.php`, which exports thresholds for alerts.

## 10.3 Storage & Lifecycle

The storage topology now differentiates buckets:

- `config/filesystems.php` introduces dedicated `community-media`, `community-avatars`, `community-banners`, and `audit-logs` disks with per-bucket KMS and object-lock hints.
- `config/storage_lifecycle.php` defines lifecycle transitions for each content type and enforces WORM retention on audit logs (10-year default).
- `.env.example` exposes the required AWS parameters so Infra can bind buckets, KMS keys, and lifecycle retention without editing PHP.

These settings ensure media flows remain public CDN-backed while compliance archives stay immutable.

## 10.4 CI/CD Pipeline

A platform-wide GitHub Actions workflow (`.github/workflows/platform-ci.yml`) gates every push/PR:

1. **Backend job** runs on Ubuntu, installs PHP 8.3, executes `php artisan test`, and enforces `phpstan` static analysis from the Laravel project root.
2. **Mobile job** runs on macOS, installs Flutter 3.24, executes `flutter test`, builds the Android app bundle (`--flavor staging`), and produces an iOS IPA (`--no-codesign`) for distribution automation.
3. **Deploy gate** aggregates job status to provide a single approval checkpoint for promotion into staging/production pipelines.

Caching (Composer and pub cache) keeps turnaround times low while concurrency controls prevent duplicate pipelines on long-running branches.

## 10.5 Secrets & Observability

Secrets are sourced from AWS SSM/Secrets Manager or Vault through `config/secrets.php`. Deployment pipelines should hydrate environment variables at runtime instead of committing secrets. Observability defaults in `config/observability.php` and `.env.example` emit StatsD metrics for queue latency, HTTP p95, and error rates that Grafana dashboards and alert rules can consume.

## Next Steps

- Wire the GitHub Actions artifacts (IPA/AAB) into Fastlane for TestFlight and Play Internal Track pushes.
- Provision S3 lifecycle rules matching `config/storage_lifecycle.php` and enable Object Lock on the audit bucket.
- Configure ModSecurity CRS and queue worker systemd units in infrastructure-as-code (Terraform/Ansible) to solidify drift-free deployments.
