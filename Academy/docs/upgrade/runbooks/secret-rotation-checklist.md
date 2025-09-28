# Secret Rotation Checklist

## Purpose
Define the standardized process for rotating sensitive credentials, satisfying Section 2.6 requirements.

## Rotation Cadence
| Secret Type | Frequency | Owner |
| --- | --- | --- |
| Database credentials | Quarterly | Platform Engineering |
| Stripe API & webhook | Monthly | Payments Team |
| JWT signing keys | Bi-annually | Backend Team |
| Mobile signing keys | Upon release or compromise | Mobile Team |
| OAuth client secrets | Quarterly | Security Engineering |

## Pre-Rotation Steps
1. Create CAB ticket with scope, schedule, and blast radius analysis.
2. Notify stakeholders via `#academy-announce` and PagerDuty change event.
3. Ensure latest backups available (DB snapshots, Vault export) and restore validated within last 30 days.
4. Confirm runbook operator has elevated access in Okta ("Secrets Rotation" group) valid for 4 hours via Just-in-Time approval.

## Execution Steps
1. **Database credentials**
   ```bash
   aws lambda invoke --function-name rotate-rds-credential --payload '{"cluster":"academy-prod"}' response.json
   ```
   - Wait for Lambda success status.
   - Update Laravel config cache via `php artisan config:cache` on all app nodes.
2. **Stripe secrets**
   ```bash
   php tools/security/rotate_stripe_webhook.php --env=production
   ```
   - Update Stripe dashboard with new endpoint secret.
   - Redeploy worker pods to pick up environment variables.
3. **JWT keys**
   ```bash
   php artisan secrets:rotate-jwt --broadcast
   ```
   - Invalidate existing sessions via `php artisan sessions:purge --before="now"`.
4. **Mobile signing**
   - Authenticate to Vault transit engine: `vault write transit/decrypt/mobile data=@keystore.enc`.
   - Generate new keystore with Fastlane lane `fastlane ios rotate_signing`.
   - Update CI secure storage and trigger build to verify signing.

## Post-Rotation Validation
- Run smoke tests: login, payment checkout, mobile build pipeline.
- Verify Secrets Manager version history increments and old versions disabled.
- Review logs for unauthorized access attempts within rotation window.
- Update CAB ticket with timestamps, validation evidence, and next rotation date.

## Rollback Plan
- Retrieve previous secret version ID: `aws secretsmanager describe-secret --secret-id ...`.
- Restore version via `aws secretsmanager update-secret-version-stage --remove-from-version-id AWSCURRENT --move-to-version-id <prev>`.
- Re-run config cache, redeploy services, and notify stakeholders.

## Evidence Archive
Save command transcripts, Lambda logs, and validation screenshots under `docs/upgrade/backups/terraform/secrets/<date>/`.
