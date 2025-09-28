# Section 7 – Admin & Operations Architecture

This document introduces the governance, tooling, and feature set required to transform the legacy admin experience into an enterprise-grade operations cockpit for communities.

## Objectives
- Deliver modern admin dashboards with moderation, member management, paywall controls, and analytics embeds.
- Enforce granular RBAC with auditable permission changes and immutable logs.
- Provide automation jobs for digests, leaderboards, cleanups, and report exports.
- Surface compliance reporting and operational health metrics for trust & safety.

## Platform Components
- Laravel Admin SPA (Vue 3 + Inertia) served under `/admin`.
- Background job suite using Laravel Horizon & Scheduler.
- Audit/event store leveraging PostgreSQL logical replication into immutable storage.
- Integration with analytics dashboards defined in Section 6.

## Stakeholders
- **Head of Community Operations** – accountable for moderation SLAs and policy enforcement.
- **Finance Ops** – monitors revenue and payouts.
- **Trust & Safety** – reviews audit logs, handles escalations.
- **Engineering** – maintains automation jobs and RBAC policies.
