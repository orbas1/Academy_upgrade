# Section 2.8 – Security Testing Implementation

This document records the controls delivered to operationalize security testing across the Academy upgrade program.

## Objectives
- Provide continuous visibility into dependency vulnerabilities across PHP, Node.js, and Flutter packages.
- Embed static analysis and configuration checks into CI to block regressions before deployment.
- Schedule recurring deep scans (SAST/DAST/SCA) with documented ownership and escalation paths.

## Controls Delivered
### 1. Automated Pipeline
- Added `.github/workflows/security-scan.yml` to run on pull requests, the main branch, and a weekly cron.
- Jobs cover:
  - Composer install + `composer audit` for PHP dependency advisories.
  - Larastan (`vendor/bin/phpstan`) level 6 static analysis for Laravel code paths.
  - `npm ci` followed by `npm audit --omit=dev --audit-level=high` to catch frontend CVEs.
  - Flutter dependency review via `flutter pub outdated --mode=null-safety`.
  - Dependency review gate on pull requests to block known vulnerable transitive upgrades.

### 2. Local Guardrails
- Introduced `tools/security/security_scan.sh` mirroring CI checks for engineers to run before creating release branches.
- Script emits timestamped logs suitable for ingestion by the central SIEM.

### 3. Governance & Reporting
- Vulnerability thresholds: builds fail on HIGH/CRITICAL findings; MEDIUM issues trigger ticket creation with a 5-day SLA.
- Weekly run artifacts (SARIF + audit JSON) are retained for 90 days to support compliance audits.
- Ownership rotation documented in the incident response playbook with PagerDuty escalation if scans fail >24 hours.

## Test Evidence
- Composer, npm, and Flutter commands executed locally produce non-zero exit codes when vulnerabilities are detected, enforcing remediation before merge.
- CI workflow configures caching to keep runtime within the 15-minute SLO.

## Next Steps
- Integrate DAST smoke tests (OWASP ZAP baseline) after the staging environment publishes authenticated endpoints.
- Extend SARIF uploads to GitHub Security tab for centralized triage.
