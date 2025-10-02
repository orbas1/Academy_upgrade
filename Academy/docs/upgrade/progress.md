# Orbas Learn Upgrade Progress Tracker

_Last updated: 2025-02-18_

This tracker summarizes the completion state of the Orbas Learn replatforming effort across the Laravel web application, Flutter mobile client, and shared services. Status reflects committed work inside the repository and highlights gaps that must be addressed before launch.

## Overall Readiness Snapshot

Refer to `AGENTS.md` for the milestone roadmap that converts this 20% foundation
into a production-ready release. Each status row below maps to those milestones,
and the quality scorecard (Section 0) defines the evidence required before a
row can advance to "Production" status.

| Area | Status | Notes |
| --- | --- | --- |
| Platform upgrade (Laravel 11, PHP 8.3) | ⏳ In progress | Composer now targets Laravel 11/PHP 8.3 and Horizon, queue autoscaling, and security providers are registered; Apache/Nginx dual hosting and some hardening tasks remain.【F:Web_Application/Academy-LMS/composer.json†L1-L40】【F:Web_Application/Academy-LMS/app/Console/Commands/QueueAutoscaleCommand.php†L1-L120】 |
| Domain services & contracts | ⏳ In progress | Feed, membership, moderation, paywall, subscriptions, geo, and leaderboard services are implemented with adapters and bindings, though coverage for classroom sync/realtime is still outstanding.【F:Web_Application/Academy-LMS/app/Domain/Communities/Services/CommunityFeedService.php†L1-L77】【F:Web_Application/Academy-LMS/app/Providers/CommunityServiceProvider.php†L1-L60】 |
| Data model alignment | ⏳ In progress | Community core and engagement migrations define schema, indexes, and geo/paywall tables; additional seed data for paywall tiers, geo fixtures, and device registrations is pending.【F:Web_Application/Academy-LMS/database/migrations/2024_12_24_000000_create_community_core_tables.php†L1-L159】【F:Web_Application/Academy-LMS/database/seeders/Communities/CommunityFoundationSeeder.php†L1-L116】 |
| API surface & quality gate | ⏳ In progress | API v1 exposes community CRUD/feed/members/geo plus admin modules with throttling, and the `communities:quality-gate` command checks configuration gaps. OpenAPI/SDK generation remains on the backlog.【F:Web_Application/Academy-LMS/routes/api.php†L57-L208】【F:Web_Application/Academy-LMS/app/Console/Commands/CommunitiesQualityGateCommand.php†L1-L214】 |
| Frontend (Blade / Web UI) | ⏳ In progress | Vue-based admin SPA modules exist for communities and moderation, but public member-facing feeds still rely on legacy Blade + Alpine flows and lack the new UX treatment.【F:Web_Application/Academy-LMS/resources/js/modules/communities/views/CommunitiesIndexView.vue†L1-L160】【F:Web_Application/Academy-LMS/resources/js/app.js†L1-L24】 |
| Mobile (Flutter) | ⚠️ At risk | Provider/http stack remains in place, navigation centres on legacy LMS tabs, and community/push/payment features are unimplemented pending Riverpod/Dio/FCM adoption.【F:Student Mobile APP/academy_lms_app/lib/providers/auth.dart†L1-L68】【F:Student Mobile APP/academy_lms_app/lib/screens/tab_screen.dart†L1-L120】 |
| Payments & subscriptions | ⚠️ At risk | Stripe subscription services and webhook handling exist server-side, yet entitlement UI, refunds/disputes workflows, and analytics dashboards are still missing across web/mobile.【F:Web_Application/Academy-LMS/app/Services/Community/StripeSubscriptionService.php†L1-L200】【F:Web_Application/Academy-LMS/app/Services/Billing/StripeWebhookService.php†L1-L175】 |
| Analytics, notifications, messaging | ⚠️ At risk | Notification preference APIs and events are present, but consolidated notification centres, push delivery, and analytics visualisations remain TODO.【F:Web_Application/Academy-LMS/app/Http/Controllers/Api/V1/Community/CommunityNotificationPreferenceController.php†L1-L66】【F:Web_Application/Academy-LMS/app/Events/Community/PostCreated.php†L1-L80】 |
| DevOps, automation, & testing | ⏳ In progress | CI runs Pint, PHPUnit, Larastan, Dusk, and Flutter tests; runbooks cover rollback/search. Infrastructure as code, secrets scanning, and load/security automation still need to be wired.【F:.github/workflows/ci.yml†L1-L160】【F:docs/upgrade/runbooks/search-reindex.md†L1-L80】 |

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
9. **Licensing removal & documentation alignment:** Strip CodeCanyon purchase checks across installers/controllers, ship the unified `install.sh`, and update all README/AGENTS docs to reflect milestone-driven progress tracking.

## High-Level Timeline Guidance

Assuming a focused team with dedicated backend, frontend, mobile, and DevOps contributors, the remaining backlog represents **multiple sprints (6–10+)** of work. The project is currently in an early integration phase with ~20% of the required foundation represented in code.

To stay on track for real-world testing, prioritize:

1. Finalizing core backend services (feed, posts, paywall, subscriptions, points leaderboard) with real data models and tests.
2. Building the critical user flows (join/subscribe → compose → interact → notify) end-to-end across web and mobile.
3. Standing up CI pipelines and automated quality gates to prevent regressions as features land.

Regularly update this tracker as additional services, migrations, and UI features are implemented.
