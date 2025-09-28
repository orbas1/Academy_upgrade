# Platform Upgrade & Hardening Blueprint (Sections 0–3)

## 1. Executive Summary
The objective of the Platform Upgrade & Hardening tranche is to raise the Academy platform to an enterprise-ready baseline ahead of feature delivery. This blueprint consolidates the cross-functional approach for:

* Upgrading the Laravel application stack to Laravel 11 on PHP 8.3 with modern project conventions.
* Establishing first-class security controls and operational hygiene for the web tier and background services.
* Delivering sustained performance and resiliency improvements across API, web, and worker workloads.

Delivery is organized into three parallel but coordinated workstreams (Laravel core upgrade, security baseline, and performance). Each workstream has its own set of deliverables, milestones, and rollback levers while reporting into a unified release calendar.

## 2. Governance & Release Management

| Role | Owner | Responsibilities |
| --- | --- | --- |
| Engineering Program Manager | Release lead | Drives overall schedule, cutover checklists, status reporting. |
| Backend Lead | Laravel upgrade captain | Oversees composer upgrades, framework refactors, automated testing. |
| Security Lead | Security baseline captain | Manages header enforcement, secrets, 2FA rollout, penetration readiness. |
| Platform Lead | Performance & infra captain | Coordinates caching, Horizon tuning, load testing, rollback drills. |
| QA Lead | Quality gates | Owns regression plan, test automation, coverage reporting. |

* **Change window:** Saturday 02:00–06:00 UTC with 30-minute hold for smoke verification.
* **Communication:** #academy-upgrade Slack channel + PagerDuty incidents for SEV1/SEV2.
* **Approvals:** CAB sign-off 72 hours prior to production cutover.

## 3. Environments & Dependencies

* **Runtime matrix:** PHP 8.3, MySQL 8.0.36+, Redis 7.x, Node 20.x, Flutter 3.24.x.
* **Infrastructure prerequisites:** provision additional Redis logical DBs (app/cache/horizon/broadcast), enable S3 buckets with correct lifecycle policies, ensure ACME automation in place.
* **Access controls:** GitHub environments protected with branch rules; Vault/SSM parameters staged for new secrets (Stripe, FCM, Mapbox, Meilisearch, WebSockets, Sentry).
* **Observability:** Sentry DSN for Laravel, Crashlytics for mobile, Prometheus scrapings for Horizon and nginx.

## 4. Implementation Plan by Workstream

### 4.1 Workstream A – Laravel Core Upgrade (Section 1)
1. **Repository preparation**
   * Create feature branch `upgrade/laravel11-php83`.
   * Enable Composer 2 memory limit overrides for CI.
2. **Dependency uplift**
   * Lock platform to PHP 8.3.0, upgrade to `laravel/framework:^11`, update supporting packages, remove deprecated dependencies (SwiftMailer).
   * Introduce dev tooling (`larastan`, `phpstan`) and configure baseline.
3. **Bootstrap modernization**
   * Adopt Laravel 11 bootstrap pipeline (`bootstrap/app.php`), migrate middleware registration, consolidate HTTP kernel definitions.
   * Remove legacy helper polyfills superseded in Laravel 11.
4. **Auth modernization**
   * Configure Argon2id hashing, add WebAuthn scaffolding behind feature flag, implement session driver hardening.
5. **Quality automation**
   * Update GitHub Actions with PHP 8.3 matrix, parallel tests, static analysis, artifacts upload.
   * Enforce `phpunit.xml` coverage thresholds (80% lines) prior to merge.
6. **Cutover procedure**
   * Execute database expand migrations, run smoke suite, promote feature flag, remove Laravel 10 compatibility shims post-cutover.

### 4.2 Workstream B – Security Baseline (Section 2)
1. **HTTP header enforcement**
   * Ship nginx include `security-headers.conf`, add Laravel middleware for context-sensitive relaxations (e.g., Stripe Checkout).
2. **Session & secret policy**
   * Set `SESSION_SECURE_COOKIE=true`, `SAMESITE` adjustments, rotate `APP_KEY`, store secrets exclusively in Vault/SSM with deployment automation.
3. **AuthN/AuthZ enhancements**
   * Implement TOTP 2FA with backup codes, add WebAuthn optional flows, build device/session management UI, enforce RBAC via policies and IP allowlists for admin.
4. **Input & file protection**
   * Require form request validation across writes, integrate ClamAV scanning queue, re-encode and strip EXIF for media, establish quarantine bucket.
5. **Rate-limiting & anti-abuse**
   * Configure Redis rate limiters for login/register/reset/post/comment, integrate abuse scoring heuristics, instrument hCaptcha fallback hook.
6. **Compliance & incident readiness**
   * Encrypt PII columns, create GDPR export/delete artisan commands, build immutable audit log sink on S3 Object Lock, publish incident response playbook.
7. **Security testing regimen**
   * Schedule Larastan/PHPStan (SAST) in CI, configure Trivy image scans, run OWASP ZAP weekly, integrate dependency alerts with patch window calendar.

### 4.3 Workstream C – Performance & Resilience (Section 3)
1. **Caching architecture**
   * Enable config/route/view caches during build, introduce repository-layer query caching with Redis tags, implement HTTP caching middleware with SWR semantics.
2. **Queue & worker tuning**
   * Segment Horizon queues (`notifications`, `media`, `webhooks`, `search-index`), define concurrency per queue, add autoscaling hooks and graceful shutdown scripts.
3. **Octane evaluation**
   * Benchmark Octane for API nodes, implement memory leak guard rails, maintain fallback to FPM for admin routes.
4. **Database optimization**
   * Tune MySQL parameters (buffer pool, flush log), add partial indexes for feed queries, adopt keyset pagination for infinite feeds, enable slow query log review.
5. **Media delivery**
   * Configure S3 buckets with lifecycle policies, integrate CloudFront CDN, implement responsive image pipeline (AVIF/WebP) and background transcode jobs.
6. **Page performance**
   * Configure Vite code splitting, prefetch critical routes, purge unused CSS, inline critical CSS for landing routes.
7. **Load & chaos testing**
   * Author k6 scripts for feed interactions, target API p95 < 250 ms at 500 RPS, schedule chaos drills (Redis outage, queue lag) with rollback procedures.
8. **Rollback & recovery**
   * Prepare cache clear scripts, Octane disable toggle, feature flag kill switches for heavy endpoints, document restoration playbook.

## 5. Milestones & Timeline

| Week | Milestone | Exit Criteria |
| --- | --- | --- |
| Week 1 | Dependency uplift & bootstrap refactor | CI green on PHP 8.3, Laravel 11 bootstraps locally, smoke tests pass. |
| Week 2 | Security baseline enforcement | Headers active in staging, 2FA alpha ready, ClamAV pipeline queues running. |
| Week 3 | Performance tuning | Horizon segmentation live, cache hit rate > 80%, load test meets p95 target. |
| Week 4 | Cutover & validation | Production traffic on new stack, monitoring dashboards clean, rollback plan rehearsed. |

## 6. Testing & Quality Gates

* **Automated suites:** PHPUnit feature/unit, Pest (new), static analysis (Larastan/PHPStan), Dusk smoke for admin flows, Playwright for public pages.
* **Manual validation:** QA checklist for auth flows, upload pipeline, caching invalidation, Stripe checkout.
* **Performance testing:** k6 load suite, Synthetics for CDN endpoints, Octane soak test (12-hour run).
* **Security testing:** OWASP ZAP baseline, 3rd-party pen test readiness, dependency audit triage.

## 7. Monitoring & Telemetry Enhancements

* Dashboards in Grafana for queue depth, job failure rate, cache hit/miss, HTTP p95, DB slow queries.
* SLO definitions: API availability 99.9%, queue processing < 2 minutes, cache hit rate ≥ 80%.
* Alerts: PagerDuty for SEV1 (downtime), SEV2 (queue lag > 10 min), SEV3 (elevated 4xx/5xx).

## 8. Risk Register & Mitigations

| Risk | Impact | Likelihood | Mitigation |
| --- | --- | --- | --- |
| Hidden package incompatibilities | High | Medium | Maintain staging parity, run Laravel Shift analyzer, incremental merges. |
| Security regression due to header strictness | Medium | Medium | Provide per-route overrides, run CSP report-only phase before enforcing. |
| Octane-induced memory leaks | Medium | Low | Stress test in staging, implement watchdog to recycle workers, maintain FPM fallback. |
| ClamAV throughput bottleneck | Medium | Medium | Autoscale scanning workers, queue length alerts, fallback to manual review queue. |
| Load test gaps | High | Low | Integrate k6 into CI nightly, capture baseline metrics, require sign-off prior to launch. |

## 9. Deliverables Checklist

- [x] Dependency upgrade plan with Composer actions and rollback steps.
- [x] Security enforcement plan covering headers, secrets, 2FA, incident response.
- [x] Performance tuning roadmap including caching, Horizon, Octane, DB, CDN.
- [x] Testing and monitoring strategy mapped to SLOs and alerting pathways.
- [x] Timeline, ownership matrix, and risk register documented for stakeholders.

## 10. Acceptance Evidence

* Blueprint circulated to backend, security, platform, and QA leads (recorded in release notes).
* Linked tasks created in project management tool with swimlanes matching Sections 1–3.
* Readiness review scheduled with stakeholders; sign-off captured in meeting notes.
* This document stored under `docs/upgrade/platform_upgrade_blueprint.md` with version control tracking for audits.
