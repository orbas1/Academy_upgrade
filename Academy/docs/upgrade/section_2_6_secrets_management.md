# Section 2.6 – Secrets & Keys Management Program

## 1. Objectives
Centralize Academy secret storage, rotation, and access governance so that application, mobile, and infrastructure components obtain credentials securely and auditable rotations occur on a predictable cadence.

## 2. Secret Inventory & Classification
| Category | Examples | Sensitivity | Storage Location |
| --- | --- | --- | --- |
| Application keys | `APP_KEY`, `JWT_SECRET`, `PASSPORT_PRIVATE_KEY` | Critical | AWS Secrets Manager (prod), Vault dev path |
| Third-party APIs | Stripe, Segment, Mapbox, SendGrid, Firebase | High | AWS Secrets Manager / GCP Secret Manager |
| Infrastructure | Database passwords, Redis auth, Meilisearch tokens | Critical | Vault (prod/staging), Parameter Store (dev) |
| Certificates | TLS private keys, APNs, FCM service accounts | Critical | AWS Certificate Manager & secure S3 bucket with KMS |
| Mobile signing | Android keystore, iOS p12 | Critical | Hashicorp Vault transit engine |

## 3. Architecture Overview
1. **Source of truth** – Hashicorp Vault in production, AWS Secrets Manager for cross-region replication, Parameter Store for non-critical dev secrets.
2. **Access model** – short-lived IAM roles (web, worker, mobile CI) retrieving secrets via instance metadata or workload identity federation.
3. **Secret retrieval** – Laravel config cached using `orchestra/testbench` friendly `aws-secrets-manager` client; Flutter builds access via Fastlane plugin fetching to keychain only during build.
4. **Rotation** – scheduled Lambdas / Vault rotation jobs for database, Stripe, and webhook secrets with Terraform-managed schedules.
5. **Audit** – Vault audit devices streaming logs to CloudWatch + SIEM; Secrets Manager events forwarded to Security Lake.

## 4. Implementation Steps
1. **Define Terraform modules** under `infra/secrets/` provisioning:
   - Vault mounts (`secret/data/academy/web`, `secret/data/academy/mobile`).
   - AWS Secrets Manager entries with replication to secondary region.
   - IAM roles `academy-web`, `academy-worker`, `academy-ci` with scoped policies.
2. **Laravel integration**
   ```php
   // bootstrap/app.php
   $app->afterBooting(function () {
       app(\App\Support\Secrets\SecretManager::class)->warm();
   });
   ```
   ```php
   // app/Support/Secrets/SecretManager.php
   class SecretManager
   {
       public function warm(): void
       {
           cache()->remember('secrets.app', now()->addMinutes(30), function () {
               return [
                   'app_key' => $this->client()->getSecretValue('academy/web/app-key'),
                   'stripe_secret' => $this->client()->getSecretValue('academy/web/stripe-secret'),
               ];
           });
       }

       public function client(): AwsSecretsManagerClient
       {
           // constructs client with IMDS credentials and region failover
       }
   }
   ```
3. **CI/CD** – GitHub Actions uses OpenID Connect to assume `academy-ci` role and fetch secrets via `aws secretsmanager get-secret-value` storing in ephemeral environment variables.
4. **Mobile signing** – Fastlane lane `fetch_signing_keys` interacts with Vault transit engine to decrypt keystore and installs them temporarily during build.
5. **Rotation automation**
   - Database credentials rotated quarterly using Lambda `rotate-rds-credential` with SNS notifications.
   - Stripe webhook secret rotated monthly using automation script `tools/security/rotate_stripe_webhook.php` and update to Laravel config broadcast via config cache bust.
   - `APP_KEY` rotation executed by runbook `docs/upgrade/runbooks/app-key-rotation.md` with automatic session revocation.

## 5. Operational Safeguards
- **No secrets in Git** – `.env.example` contains only placeholder tokens (e.g., `STRIPE_SECRET=env('AWS_SECRET_academy/stripe')`).
- **Access reviews** – quarterly audit ensures IAM roles limited to required paths; results stored in `docs/upgrade/backups/access-reviews/`.
- **Alerting** – CloudWatch alarm on Secrets Manager retrieval > 95th percentile or unauthorized attempts; PagerDuty SEV2 triggered.
- **Versioning** – Secrets Manager retains last 5 versions; rollback script `tools/security/rollback_secret_version.sh` documented.
- **Incident response** – detection triggers `security:rotate` artisan command invalidating cached credentials and forcing warm re-fetch.

## 6. Testing & Validation
1. **Automated tests** verifying SecretManager caches secrets and respects TTL.
2. **Integration tests** using `Localstack` to simulate AWS retrieval during CI.
3. **Disaster recovery drill** – simulate Vault outage; ensure fallback to Secrets Manager using cached copy limited to 15 minutes.
4. **Penetration test** – red-team scenario verifying secrets never logged or exposed in responses.

## 7. Documentation & Runbooks
- **Runbook**: `docs/upgrade/runbooks/secret-rotation-checklist.md` describes manual rotation steps and verification.
- **Onboarding**: secrets access granted via Okta workflow; training doc stored in Confluence linked here.
- **Diagram**: architecture diagram stored at `docs/upgrade/backups/diagrams/secrets-architecture.drawio`.

## 8. Acceptance Checklist
- [ ] Terraform secrets modules applied to dev/staging/prod with drift detection.
- [ ] Laravel boot sequence fetches secrets on warm cache without blocking request lifecycle.
- [ ] CI/CD pipelines pulling secrets through OIDC with zero long-lived credentials.
- [ ] Rotation jobs executed and evidence archived for CAB review.
- [ ] Security team sign-off on audit and alerting configuration.

## 9. Evidence & Deliverables
- Terraform plan outputs stored in `docs/upgrade/backups/terraform/secrets/`.
- Secrets integration tests under `tests/Feature/Security/SecretManagerTest.php`.
- Fastlane lane definitions updated in `fastlane/Fastfile` for mobile builds.
- Alert definitions and dashboards captured under `docs/upgrade/runbooks/secrets-monitoring.md`.
- Quarterly access review minutes archived under `docs/upgrade/backups/access-reviews/Q1-2025.md`.
