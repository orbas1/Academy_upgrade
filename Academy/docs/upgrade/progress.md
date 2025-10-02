# Orbas Learn Upgrade Progress Tracker

_Last updated: 2025-02-18_

This tracker summarizes the completion state of the Orbas Learn replatforming effort across the Laravel web application, Flutter mobile client, and shared services. Status reflects committed work inside the repository and highlights gaps that must be addressed before launch.

## Overall Readiness Snapshot

| Area | Status | Notes |
| --- | --- | --- |
| Platform upgrade (Laravel 11, PHP 8.3) | ⚠️ At risk | Framework scaffolding and configuration hardening have not started. Current codebase still targets legacy Laravel conventions. |
| Domain services & contracts | ⏳ In progress | Membership contract is bridged to legacy Eloquent workflows and now exposes points awarding via a contract adapter. Remaining services (feed, posts, moderation, geo, subscriptions, paywall, calendar, classroom links) are interface-only with no bindings. |
| Data model alignment | ❌ Blocked | Community migrations exist but require verification, foreign keys, and seed coverage for tiers, geo, levels, and automation triggers. Legacy LMS tables still dominate. |
| API surface & quality gate | ⏳ In progress | Quality gate command exists, but API endpoints for the new domain remain unimplemented. No OpenAPI generation yet. |
| Frontend (Blade / Web UI) | ❌ Not started | Wireframed community experience is not represented in Blade templates. Legacy LMS UI remains. |
| Mobile (Flutter) | ⚠️ At risk | Documentation updated for Orbas Learn naming, but Riverpod/Dio refactors, realtime presence, and paywall flows are unimplemented. |
| Payments & subscriptions | ❌ Not started | Stripe integration, entitlement checks, and webhook handling remain TODO. |
| Analytics, notifications, messaging | ❌ Not started | No instrumentation, push notifications, or digest flows are committed. |
| DevOps, automation, & testing | ⚠️ At risk | Runbooks for seeding and reindexing exist. Automated tests cover new contracts superficially, but CI/CD, load testing, and infrastructure scripting are missing. |

## Delivered Work (Repo Artifacts)

- **Quality & Governance:** `communities:quality-gate` artisan command with documentation and feature coverage to flag schema, seeder, and configuration gaps.
- **Service Contracts:** Interfaces for community membership, feed, posts, comments, likes, points, leaderboard, geo, subscriptions, paywall, calendar, and classroom linking.
- **Membership Bridging:** `MembershipServiceAdapter` binds the domain contract to the legacy Eloquent implementation, including feature coverage for join and approval flows.
- **Points Bridging:** `PointsServiceAdapter` maps the domain points contract onto the transactional ledger service and enforces community rules with cap awareness and reporting.
- **Runbooks & Readmes:** Expanded Orbas Learn installation guides, quality checklists, community seeding, and search reindexing runbooks.

## Critical Outstanding Work

1. **Service Implementations:** Feed, Post, Comment, Like, Geo, Paywall, Subscription, Calendar, and Classroom services must receive adapters or fresh implementations that satisfy contract behaviors (keyset pagination, paywall enforcement, webhook handling, calendar merges, etc.).
2. **Database & Seeder Alignment:** Reconcile migrations with production schema, ensure seed data for communities, tiers, points, levels, and geo metadata, and script rollback/reindex flows.
3. **API & Policy Layer:** Build REST endpoints, policies, request validation, and OpenAPI specs for all community features with rate limiting and policy gates.
4. **Frontend & Mobile UX:** Implement the new Orbas Learn web interface (communities list/detail, composer, notifications, admin panel) and upgrade Flutter app architecture (Riverpod, Dio, offline cache, realtime presence, Stripe payments, maps).
5. **Payments & Monetization:** Integrate Stripe customer/subscription lifecycle, entitlement checks, and paywall gating, including webhook resiliency.
6. **Realtime & Notifications:** Deliver presence heartbeats, websocket feeds, notification center, push messaging, and moderation tooling.
7. **Testing & Observability:** Establish PHPUnit/Pest coverage for services, Playwright or Dusk E2E flows, load testing (k6), static analysis, and observability hooks.
8. **DevOps & Installer Integration:** Containerize or script the installer launch flow, align `.env` scaffolding, and ensure automation for deployments, backups, and health monitoring.

## High-Level Timeline Guidance

Assuming a focused team with dedicated backend, frontend, mobile, and DevOps contributors, the remaining backlog represents **multiple sprints (6–10+)** of work. The project is currently in an early integration phase with ~20% of the required foundation represented in code.

To stay on track for real-world testing, prioritize:

1. Finalizing core backend services (feed, posts, paywall, subscriptions, points leaderboard) with real data models and tests.
2. Building the critical user flows (join/subscribe → compose → interact → notify) end-to-end across web and mobile.
3. Standing up CI pipelines and automated quality gates to prevent regressions as features land.

Regularly update this tracker as additional services, migrations, and UI features are implemented.
