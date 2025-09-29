# Section 3.8 – Rollback & Recovery

## Scope

Operational procedures required to unwind performance release candidates while preserving data integrity and meeting RTO ≤ 15 minutes.

## Key Artifacts

- Runbook: `docs/upgrade/runbooks/rollback-procedure.md`
- Scripts: `tools/performance/scripts/bootstrap.sh`, `tools/preflight/compatibility_audit.sh`
- Monitoring dashboards: Grafana – **Academy/Platform – Rollback readiness**

## Controls

1. **Cache & Horizon Recovery**
   - Warm caches pre-release using `php artisan optimize:clear && php artisan communities:cache --all`.
   - Document worker scale expectations in `horizon.php` to avoid cold start thrash.
   - During rollback execute `php artisan horizon:pause` to quiesce writes before traffic shift.

2. **Octane / FPM Switch**
   - Maintain `OCTANE_ENABLED` feature flag; fallback to FPM by disabling flag and reloading systemd unit `sudo systemctl reload php-fpm`.
   - Purge Octane cache store with `php artisan octane:clear` to prevent stale sockets once fallback completes.

3. **Database Safeguards**
   - Expand/contract migrations must wrap destructive steps in feature flags; rollback uses `php artisan migrate:rollback --path=database/migrations/contract`.
   - Trigger `database:schema:dump` prior to release to accelerate restore.

4. **Validation Checklist**
   - [ ] Horizon queue depth < 100 across `feed`, `notifications`, `search-index`.
   - [ ] Prometheus `http_5xx_rate` returns to < 0.1% within 5 minutes.
   - [ ] API healthcheck `/healthz` returns `200` for blue stack twice consecutively.
   - [ ] Analytics event ingestion lag < 60 seconds.

5. **Communication & Governance**
   - Incident commander announces rollback start/end in `#academy-status` with links to Datadog snapshots.
   - Customer Success receives templated email (stored in `runbooks/communication-template.md`).
   - CAB sign-off recorded in Jira issue comment.

## Post-Rollback Actions

- Execute `tools/preflight/compatibility_audit.sh --mode=post-rollback` to detect config drift.
- Attach k6 artefacts from failing run to Jira for RCA.
- Schedule follow-up load test after remediation; update `section_3_deliverables_summary.md` with status.
