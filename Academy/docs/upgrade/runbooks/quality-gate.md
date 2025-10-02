# Orbas Learn Communities Quality Gate

The `communities:quality-gate` artisan command validates whether the community stack is ready for staging or production rollouts. It inspects schema alignment, baseline seed data, and operational configuration required for Orbas Learn.

## Prerequisites

- Application dependencies installed (`composer install`).
- Database connection configured and migrated.
- Baseline seeder executed if running in a fresh environment: `php artisan db:seed --class=Database\Seeders\Communities\CommunityFoundationSeeder`.

## Running the Gate

```bash
cd Web_Application/Academy-LMS
php artisan communities:quality-gate
```

The command exits with:

- `0` when all checks pass.
- `1` when any section fails (schema, seeders, or configuration).

To integrate with CI or infrastructure automation, request JSON output:

```bash
php artisan communities:quality-gate --json
```

Example JSON payload:

```json
{
    "passed": false,
    "sections": {
        "schema": {
            "status": true,
            "summary": "Schema baseline verified.",
            "details": [
                "All community tables and critical columns are present."
            ]
        },
        "seeders": {
            "status": false,
            "summary": "Baseline seed data missing for Orbas Learn communities.",
            "details": [
                "Expected at least 5 community categories, found 0."
            ]
        },
        "config": {
            "status": false,
            "summary": "Environment configuration incomplete for communities rollout.",
            "details": [
                "Stripe secret key missing – set STRIPE_SECRET for billing flows.",
                "Queue driver is `sync`; use redis or database to process community jobs.",
                "Cache store is `file`; configure redis/memcached for multi-node readiness.",
                "Search driver `database` configured – expected `meilisearch`.",
                "Meilisearch host missing – set MEILISEARCH_HOST."
            ]
        }
    }
}
```

## CI Integration

Add a step to the backend job in `.github/workflows/ci.yml` to block merges until the quality gate passes:

```yaml
- name: Community quality gate
  run: php artisan communities:quality-gate --json
```

Consume the JSON payload to publish a status summary or trigger notifications. When the command exits with `1`, surface the failing section details to the build log or chatops channel to accelerate remediation.

## Remediation Checklist

| Section | Symptom | Resolution |
| --- | --- | --- |
| Schema | Missing tables/columns | Run `php artisan migrate` and confirm latest community migrations are deployed. |
| Seeders | Category/level/rule counts below baseline | Execute `php artisan db:seed --class=Database\Seeders\Communities\CommunityFoundationSeeder`. |
| Config | Stripe, queues, cache, or Meilisearch errors | Populate `.env` with `STRIPE_SECRET`, `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`, `SEARCH_DRIVER=meilisearch`, `MEILISEARCH_HOST=...`. |

Once all remediation steps are complete, rerun the command to verify a passing status.
