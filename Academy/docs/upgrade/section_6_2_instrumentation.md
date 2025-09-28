# Section 6.2 – Instrumentation Architecture

This document outlines the technical implementation for emitting, transporting, and validating analytics events across the Laravel backend and Flutter mobile clients.

## Architecture Overview
1. **Client SDK Layer**
   - Web: TypeScript wrapper around Segment Analytics.js loaded via first-party script, proxied through our domain.
   - Mobile: Flutter plugin built on `analytics` package with platform channels to Firebase Analytics and Segment.
2. **Edge Proxy**
   - `/analytics/collect` Laravel route accepting batched events (max 50 per request), validating against JSON Schema, and writing to Kafka topic `analytics.events.raw`.
   - Supports gzip compression, signed requests (HMAC derived from device key), and idempotency tokens.
3. **Streaming Pipeline**
   - Kafka → Flink job for schema enforcement, PII hashing, enrichment (geo-IP, plan tier), and fan-out to:
     - Snowflake (structured warehouse)
     - ClickHouse (real-time dashboards)
     - S3 (long-term cold storage, partitioned by `event_date`)
4. **Monitoring & Alerting**
   - Prometheus metrics on ingest success, schema failures, latency.
   - DataDog monitors on event drop thresholds, pipeline lag, and consumer health.

## Laravel Implementation
- Middleware `App\Http\Middleware\EnsureAnalyticsConsent` verifying user consent flag before allowing analytics requests.
- Controller `App\Http\Controllers\Analytics\CollectController` handling POST `/analytics/collect` with validation via `AnalyticsBatchRequest` FormRequest.
- JSON Schemas stored under `resources/analytics-schemas/{event}.schema.json` with automated loading into a `SchemaRegistry` service.
- Failed validation triggers `AnalyticsEventRejected` event, logged to audit table with hashed payload for review.
- Queue job `DispatchAnalyticsBatch` publishes to Kafka using `laravel-kafka` producer with retries and dead-letter queue.
- Feature flag `analytics.edge_proxy_enabled` toggles path in staging environments.

### Example Schema Validation Snippet
```php
$schema = $this->schemaRegistry->for($eventName);
$this->validator->validate($payload, $schema);
```

## Flutter Implementation
- `AnalyticsClient` singleton configured via Riverpod provider.
- Uses offline queue persisted in Hive (`analytics_events.hive`) with background upload worker using `workmanager`.
- Automatic context injection: device info, app version, locale, experiment assignments.
- Retry policy: exponential backoff up to 5 attempts, then fallback to local log for support export.
- Consent gating: integrates with `PrivacySettingsRepository`; if user toggles opt-out, flush queue and disable capture.

### Sample Dart Usage
```dart
ref.read(analyticsClientProvider).track(
  event: AnalyticsEvent.communityJoin(
    communityId: community.id,
    source: AnalyticsJoinSource.inviteLink,
  ),
);
```

## Server-Side Events
- Laravel domain services emit events for billing, automation, and moderation actions using `AnalyticsEmitter` contract.
- Example: when subscription webhook processed, `SubscriptionStarted` domain event triggers analytics emitter.

## Data Quality Processes
- Nightly dbt tests verifying row counts, mandatory field population, and referential integrity vs. OLTP snapshots.
- Synthetic monitoring: k6 smoke job hitting `/analytics/collect` with golden payload to confirm ingestion.
- Alert thresholds:
  - `analytics_ingest_failure_rate > 0.5%` for 5 minutes.
  - `analytics_pipeline_lag_seconds > 120`.

## Security Controls
- HMAC signature computed with device secret stored in secure storage (mobile) or HTTP-only cookie (web).
- Payloads encrypted in transit via HTTPS; data at rest encrypted using warehouse-managed keys.
- Access to raw events restricted via IAM roles; PII hashed at ingestion with salt rotated quarterly.

## Rollout Plan
1. Deploy proxy endpoint in dark mode (captures but discards) for schema validation.
2. Enable staging clients; monitor metrics for 48 hours.
3. Gradually roll out to 5%, 25%, 100% of production traffic with guardrail alerts.
4. Conduct post-launch review with analytics and security stakeholders.
