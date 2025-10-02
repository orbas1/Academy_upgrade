# Academy Laravel + Mobile Community Upgrade – Full Technical Specification

**Scope:** End‑to‑end upgrade of Academy (Laravel) web app and connected Flutter mobile apps to add “Communities” (Skool‑style) with feeds, memberships, paywalls, gamification, calendars, classrooms integration, notifications, security hardening, and full UI/UX refinements. Includes database migrations, backend API, admin dashboards, real‑time services, file storage, search, analytics, and DevOps. we use apache

**Assumptions**

* Current stack: Laravel 9.x/10.x (PHP 8.2+), MySQL 8.x, Redis, Apache & Nginx , Horizon/Queues, Flutter 3.x (Dart 3), Firebase (FCM), S3‑compatible storage, Git.
* Current Academy LMS features: courses, lessons, classrooms, user profiles, roles/permissions, notifications, calendar, search.
* All new features must be **multi‑tenant aware** if applicable and respect existing RBAC.

---

# 0) Platform Upgrade & Hardening (Foundation) — Enterprise, Modular Plan (Sections 1–3)

> Scope of this tranche: **(1) Laravel Core Upgrade**, **(2) Security Baseline**, **(3) Performance**. Observability will be delivered in the next group.

---

## 1) Laravel Core Upgrade (→ Laravel 11 LTS, PHP 8.3)

### 1.1 Pre‑flight & Risk Controls

* **Deployment strategy:** Blue/Green with database **expand‑migrate‑contract** pattern; feature flags (config‑driven via `APP_FEATURE_FLAGS` JSON) for risky changes.
* **Backups:** Logical (`mysqldump --single-transaction`) + physical snapshots; verify restore in staging before cutover.
* **Compatibility matrix:**

  * PHP **8.3** (OPcache, JIT off for CLI).
  * MySQL **8.0.36+** (utf8mb4_0900_ai_ci), Redis **7.x**.
  * Node **20.x** for Vite build.

### 1.2 Upgrade Steps (Scriptable)

1. **Branch:** `upgrade/laravel11-php83`.
2. **Composer core:**

   ```bash
   composer config platform.php 8.3.0
   composer require laravel/framework:^11.0 laravel/tinker --with-all-dependencies
   composer require nunomaduro/larastan:^2.9 phpstan/phpstan:^1.11 --dev
   composer require laravel/scout meilisearch/meilisearch-php --no-update
   composer update
   ```
3. **App bootstrap changes:** adopt new `bootstrap/app.php` signature; consolidate middleware to `bootstrap/app.php` pipeline per L11 conventions.
4. **Deprecations & Replacements**

   * `Str::random()` → OK; remove helper polyfills.
   * `Route::middleware()` group updates; use typed Enums for statuses/roles.
   * Replace any `SwiftMailer` remnants with Symfony Mailer (L11 default).
   * Filesystem: ensure S3 driver uses `visibility` & `temporaryUrls` correctly.
5. **Auth & Hashing**

   * Ensure **Argon2id** (`config/hashing.php`).
   * Add **WebAuthn** (Passkeys) optional package for later.
6. **Testing & Static Analysis**

   ```bash
   php artisan test --parallel
   vendor/bin/phpstan analyse --memory-limit=1G
   vendor/bin/larastan analyse --level=6 app database routes
   ```
7. **CI Gate** (GitHub Actions snippet)

   ```yaml
   jobs:
     test:
       runs-on: ubuntu-latest
       steps:
         - uses: actions/checkout@v4
         - uses: shivammathur/setup-php@v2
           with: { php-version: '8.3', extensions: mbstring, intl, redis }
         - run: composer install --prefer-dist --no-interaction
         - run: php artisan key:generate
         - run: php artisan test --parallel
         - run: vendor/bin/phpstan analyse --no-progress
   ```
8. **Rollout:** deploy Green environment → run DB expand migrations (no drops) → warm caches → switch traffic → contract migrations.

### 1.3 Coding Standards & Modularity

* **DDD‑ish** packages: `App/Domain/*`, `App/Http/*`, `App/Support/*`.
* **Contracts & Service Providers** for cacheable read services; prohibit facades in domain layer.
* **DTOs** (spatie/laravel-data optional) for API I/O.

---

## 2) Security Baseline (Enterprise)

### 2.1 HTTP Security Headers (Nginx + Middleware)

* **Nginx snippet** (applied on all vhosts):

  ```nginx
  add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload" always;
  add_header Content-Security-Policy "default-src 'self'; img-src 'self' data: https:; media-src https:; script-src 'self' 'unsafe-inline' 'unsafe-eval' cdn.*; style-src 'self' 'unsafe-inline' fonts.googleapis.com; font-src 'self' fonts.gstatic.com; connect-src 'self' https: wss:; frame-ancestors 'none'; base-uri 'self'" always;
  add_header Referrer-Policy "same-origin" always;
  add_header Cross-Origin-Opener-Policy "same-origin" always;
  add_header Cross-Origin-Embedder-Policy "require-corp" always;
  add_header Cross-Origin-Resource-Policy "same-site" always;
  add_header X-Frame-Options "DENY" always;
  add_header X-Content-Type-Options "nosniff" always;
  add_header Permissions-Policy "geolocation=(self), microphone=(), camera=()" always;
  ```
* **Laravel middleware** to append/override and set per‑route relaxations (e.g., Stripe Checkout origins).

### 2.2 Session, CSRF, Cookies

* `SESSION_SECURE_COOKIE=true`, `SAMESITE=strict` for dashboard; `lax` for cross‑app needs.
* Rotate `APP_KEY` under maintenance window with re‑login policy; store secrets in **KMS** (AWS KMS or HashiCorp Vault) and inject via env at deploy.

### 2.3 Authentication & Authorization

* **2FA (TOTP)** + backup codes; optional **WebAuthn**.
* **RBAC** via Policies/Gates; admin routes behind IP allowlist (+ per‑user device trust).
* Device/session management UI with remote sign‑out; session fixation protection.

### 2.4 Input & File Security

* **Form Request** validation across all write endpoints.
* **Upload pipeline:** size limits, MIME sniffing, image re‑encode (Intervention Image), **EXIF strip**, **ClamAV** scan via queue; quarantine bucket for fails.

### 2.5 Rate Limiting & Anti‑Abuse

* Redis‑backed throttle: **auth**, **register**, **password reset**, **post/comment/like**.
* Abuse scoring (heuristics) → temp blocks; bot‑detection hooks (hCaptcha optional).

### 2.6 Secrets & Keys

* **No secrets in Git.** `.env` template only. CI pulls from secret manager.
* Rotate Stripe webhooks & API keys quarterly; audit with checklist.

### 2.7 Compliance & Data Protection

* PII field‑level encryption where feasible; right‑to‑erasure tooling; export (GDPR Art. 20) jobs.
* Audit log for admin actions (immutable store / WORM S3 bucket).

### 2.8 Security Testing

* **SAST:** Larastan/PHPStan, Trivy on container images.
* **DAST:** OWASP ZAP baseline weekly against staging.
* **Dependency** alerts enabled; monthly patch window.

### 2.9 Incident Response

* Playbook: detection → triage (SEV levels) → containment (revoke keys, disable routes) → comms templates → post‑mortem with action items.

---

## 3) Performance (Web, API, Queues)

### 3.1 Caching Strategy

* **Config/Route/View cache** at build; cache‑busting on deploy.
* **Query caching** via repository layer + tagged Redis caches (`community:*, profile:*`). TTLs + cache stampedes avoided with locks.
* **HTTP caching** for public community pages: `Cache-Control: public, max-age=60, stale-while-revalidate=120` via middleware.

### 3.2 Redis & Horizon

* Dedicated Redis DBs: `app`, `cache`, `horizon`, `broadcasting`.
* Horizon queues: `notifications`, `media`, `webhooks`, `search-index` with concurrency maps; auto‑scaling workers based on queue depth.

### 3.3 Octane (Optional)

* Enable for API nodes; warm singletons; guard against request‑leaks (no per‑request state in singletons). Fallback to FPM for admin if needed.

### 3.4 Database Performance

* MySQL config: `innodb_buffer_pool_size` ≥ 60% RAM; `innodb_flush_log_at_trx_commit=1`; slow query log with pt‑query‑digest.
* Add **partial indexes** for feed queries (`created_at DESC`, `like_count DESC`).
* Use **READ COMMITTED** isolation; avoid N+1 with eager loads; paginate with **keyset pagination** for infinite feeds.

### 3.5 Media & CDN

* S3 + CloudFront (or equivalent) for images/video; presigned uploads; background transcode.
* Image sizes responsive (AVIF/WebP); lazy loading.

### 3.6 Page Performance (Vite)

* Code‑split community pages; prefetch critical routes; purge unused CSS; inline critical CSS for top pages.

### 3.7 Load & Stress Testing

* k6 scripts for feed, post create, notifications; targets: **p95 < 250ms** API, **TTFB < 200ms** for cached public pages at 500 RPS.

### 3.8 Rollback & Recovery

* Cache clear & route rollback scripts; ability to disable Octane; feature‑flag kill‑switch for heavy endpoints.

---

### Deliverables in this Tranche

* Upgrade PR with passing CI on PHP 8.3 & L11.
* Nginx + middleware security headers, rate‑limiters, ClamAV pipeline.
* Redis/Horizon tuned config, cache layers, HTTP cache middleware.
* k6 load scripts & baseline results.
* Runbooks: Key rotation, incident response, rollback, cache warmup.


---

# Sections 1–3 — Enterprise, Modular Upgrade for Communities

Scope in this tranche: **(1) Data Model & DB Migrations**, **(2) Backend Domain/Services & API**, **(3) Frontend Web UI/UX**. All content aligns with Laravel 11/PHP 8.3 foundation and the security/performance baselines from the previous tranche.

---

## 1) Data Model & Database Migrations (Communities & Social)

### 1.1 Naming & Conventions

* **Schema name:** default (MySQL). Table names snake_case plural. PK `id BIGINT UNSIGNED`.
* Timestamps: `created_at`, `updated_at`; soft deletes only where moderation requires recovery.
* Multi-tenancy (optional): add `tenant_id` to community‑bound tables if needed later; guard via global scopes.
* JSON columns use **strict JSON schema** in validation layer; keep medium payloads (≤16 KB typical).

### 1.2 Core Tables (DDL outline)

#### `community_categories`

* Columns: `id`, `slug UNIQUE`, `name`, `description NULL`, `icon NULL`, `order INT DEFAULT 0`.
* Indexes: `UNIQUE(slug)`, `INDEX(order)`.

#### `communities`

* Columns: `id`, `slug UNIQUE`, `name`, `tagline NULL`, `bio TEXT NULL`, `about_html LONGTEXT NULL`,
  `banner_path NULL`, `avatar_path NULL`, `links JSON NULL`, `category_id FK`,
  `visibility ENUM('public','private','unlisted') DEFAULT 'public'`,
  `join_policy ENUM('open','request','invite') DEFAULT 'open'`,
  `geo_bounds GEOMETRY NULL SRID 4326`, `created_by`, `updated_by`, timestamps.
* Indexes: `UNIQUE(slug)`, `INDEX(category_id)`, `SPATIAL INDEX(geo_bounds)`.
* FKs: `category_id → community_categories.id [CASCADE SET NULL]`, `created_by → users.id`.

#### `community_members`

* Columns: `id`, `community_id`, `user_id`,
  `role ENUM('owner','admin','moderator','member') DEFAULT 'member'`,
  `status ENUM('active','pending','banned','left') DEFAULT 'active'`,
  `joined_at`, `last_seen_at NULL`, `is_online BOOL DEFAULT 0`,
  `points INT DEFAULT 0`, `level INT DEFAULT 1`, `badges JSON NULL`.
* Indexes: `UNIQUE(community_id, user_id)`, `INDEX(user_id)`, `INDEX(status)`, `INDEX(role)`.
* FKs: `community_id → communities.id [CASCADE]`, `user_id → users.id [CASCADE]`.

#### `community_posts`

* Columns: `id`, `community_id`, `author_id`,
  `type ENUM('text','image','video','link','poll')`,
  `body_md MEDIUMTEXT NULL`, `body_html MEDIUMTEXT NULL`,
  `media JSON NULL`, `is_pinned BOOL DEFAULT 0`, `is_locked BOOL DEFAULT 0`,
  `visibility ENUM('community','public','paid') DEFAULT 'community'`,
  `paywall_tier_id NULL`, `like_count INT DEFAULT 0`, `comment_count INT DEFAULT 0`, `share_count INT DEFAULT 0`, timestamps.
* Indexes: `INDEX(community_id, created_at DESC)`, `INDEX(author_id, created_at DESC)`, `INDEX(visibility)`, `INDEX(is_pinned)`.
* FKs: `community_id → communities.id`, `author_id → users.id`, `paywall_tier_id → community_subscription_tiers.id [SET NULL]`.

#### `community_comments`

* Columns: `id`, `post_id`, `author_id`, `body_md`, `body_html`, `parent_id NULL`, `like_count INT DEFAULT 0`, timestamps.
* Indexes: `INDEX(post_id, created_at)`, `INDEX(parent_id)`, `INDEX(author_id)`.
* FKs: `post_id → community_posts.id [CASCADE]`, `author_id → users.id`.

#### `community_likes`

* Columns: `id`, `likeable_type VARCHAR(32)`, `likeable_id BIGINT`, `user_id`, `created_at`.
* Indexes: `UNIQUE(likeable_type, likeable_id, user_id)`, `INDEX(user_id, created_at)`.
* Polymorphic refs to posts or comments.

#### `community_follows`

* Columns: `id`, `follower_id`, `followable_type ENUM('community','user')`, `followable_id`, `created_at`.
* Indexes: `UNIQUE(follower_id, followable_type, followable_id)`, `INDEX(followable_type, followable_id)`.

#### `community_leaderboards`

* Columns: `id`, `community_id`, `period ENUM('daily','weekly','monthly','alltime')`, `snapshot_date DATE`, `data JSON`, `created_at`.
* Indexes: `UNIQUE(community_id, period, snapshot_date)`.

#### `community_levels`

* Columns: `id`, `community_id`, `name`, `min_points INT`, `perks JSON`, `color`, `icon`, `order INT DEFAULT 0`.
* Indexes: `UNIQUE(community_id, name)`, `INDEX(min_points)`.

#### `community_points_rules`

* Columns: `id`, `community_id`, `event ENUM('post','comment','like_received','login_streak','course_complete','assignment_submit')`, `points INT`, `daily_cap INT NULL`, `metadata JSON NULL`.
* Indexes: `UNIQUE(community_id, event)`.

#### `community_admin_settings`

* Columns: `id`, `community_id`, `settings JSON` (moderation flags, profanity lists, media caps, join questions, auto‑pin rules).
* Indexes: `UNIQUE(community_id)`.

#### `community_geo_places`

* Columns: `id`, `community_id`, `name`, `description NULL`, `lat DECIMAL(10,7)`, `lng DECIMAL(10,7)`, `geo JSON NULL`.
* Indexes: `INDEX(community_id)`, `INDEX(lat,lng)`.

#### `community_subscription_tiers`

* Columns: `id`, `community_id`, `name`, `slug`, `price_cents INT`, `currency CHAR(3)`, `interval ENUM('month','year')`, `benefits JSON`, `is_default BOOL DEFAULT 0`.
* Indexes: `UNIQUE(community_id, slug)`.

#### `community_subscriptions`

* Columns: `id`, `community_id`, `user_id`, `tier_id`, `status ENUM('active','cancelled','past_due')`, `trial_ends_at NULL`, `current_period_end`, timestamps.
* Indexes: `UNIQUE(community_id, user_id)`, `INDEX(status)`, `INDEX(current_period_end)`.

#### `community_paywall_access`

* Columns: `id`, `post_id`, `user_id`, `granted_by ENUM('tier','single_purchase','admin')`, `expires_at NULL`, `created_at`.
* Indexes: `UNIQUE(post_id, user_id)`.

#### `community_single_purchases`

* Columns: `id`, `post_id`, `user_id`, `price_cents`, `currency CHAR(3)`, `provider ENUM('stripe')`, `provider_ref`, `status ENUM('paid','refunded')`, `created_at`.
* Indexes: `INDEX(user_id, created_at)`, `INDEX(post_id)`.

### 1.3 Indices, Partitions & Performance

* **Feed queries:** composite index `(community_id, is_pinned DESC, created_at DESC)`; consider partial index `is_pinned=1` (via generated col) if needed.
* **Hot counters:** keep `like_count`, `comment_count` denormalized; update via atomic `increment` and reconcile via nightly job.
* **Partitioning:** optional monthly partitions on `community_posts` for very large datasets; archive old posts to cold storage.

### 1.4 FKs & On‑Delete Behavior

* Communities cannot be hard‑deleted if members/posts exist; use **soft delete** + archival job.
* `post_id` cascade deletes comments/likes to avoid orphans.

### 1.5 Migrations (Laravel examples)

```php
// 2025_10_01_000001_create_communities_table.php
Schema::create('communities', function (Blueprint $t) {
    $t->id();
    $t->string('slug')->unique();
    $t->string('name');
    $t->string('tagline')->nullable();
    $t->text('bio')->nullable();
    $t->longText('about_html')->nullable();
    $t->string('banner_path')->nullable();
    $t->string('avatar_path')->nullable();
    $t->json('links')->nullable();
    $t->foreignId('category_id')->nullable()->constrained('community_categories')->nullOnDelete();
    $t->enum('visibility', ['public','private','unlisted'])->default('public');
    $t->enum('join_policy', ['open','request','invite'])->default('open');
    $t->geometry('geo_bounds')->nullable();
    $t->foreignId('created_by');
    $t->foreignId('updated_by')->nullable();
    $t->timestamps();
});
```

```php
// 2025_10_01_000010_create_community_posts_table.php
Schema::create('community_posts', function (Blueprint $t) {
    $t->id();
    $t->foreignId('community_id')->constrained('communities')->cascadeOnDelete();
    $t->foreignId('author_id')->constrained('users');
    $t->enum('type', ['text','image','video','link','poll']);
    $t->mediumText('body_md')->nullable();
    $t->mediumText('body_html')->nullable();
    $t->json('media')->nullable();
    $t->boolean('is_pinned')->default(false);
    $t->boolean('is_locked')->default(false);
    $t->enum('visibility', ['community','public','paid'])->default('community');
    $t->foreignId('paywall_tier_id')->nullable()->constrained('community_subscription_tiers')->nullOnDelete();
    $t->unsignedInteger('like_count')->default(0);
    $t->unsignedInteger('comment_count')->default(0);
    $t->unsignedInteger('share_count')->default(0);
    $t->timestamps();
    $t->index(['community_id','is_pinned','created_at']);
});
```

### 1.6 Seeders

* **Categories:** `General, Study Groups, Instructors, Alumni, Local Chapters`.
* **Default Levels:** `Newbie (0)`, `Contributor (100)`, `Leader (500)`, `Champion (1500)`.
* **Default Points Rules:** post=+10, comment=+4, like_received=+2, login_streak(day)=+1, course_complete=+50, assignment_submit=+15.

### 1.7 Data Quality & Integrity

* Pre‑save sanitization of HTML; markdown whitelist.
* Background job to rebuild counters; orphan sweeper.
* Periodic `CHECK TABLE` + `ANALYZE TABLE` on hot tables.

---

## 2) Backend (Laravel) — Domain, Services & API

### 2.1 Package Structure

```
app/
  Models/Community/
    Community.php CommunityCategory.php Member.php Post.php Comment.php Like.php Follow.php
    Leaderboard.php Level.php PointsRule.php AdminSetting.php GeoPlace.php Subscription.php Tier.php PaywallAccess.php SinglePurchase.php
  Services/Community/
    MembershipService.php FeedService.php PostService.php CommentService.php LikeService.php FollowService.php
    PointsService.php LeaderboardService.php GeoService.php SubscriptionService.php PaywallService.php CalendarService.php ClassroomLinkService.php
  Policies/Community/*.php
  Http/Controllers/Api/V1/Community/*.php
  Http/Requests/Community/*.php
  Events/Community/*.php
  Listeners/Community/*.php
  Jobs/Community/*.php
```

### 2.2 Service Responsibilities (interfaces)

* **MembershipService**: join/leave/approve/ban/promote; compute role checks; last_seen/online updates.
* **FeedService**: list feeds (new/top/media/pins); keyset pagination; visibility & paywall gates.
* **PostService**: create/update/delete; media orchestration; pin/lock; increment counters.
* **CommentService**: threads, parent/child; soft delete rules; rate limits.
* **LikeService**: idempotent like/unlike; denorm counter.
* **PointsService**: award points by event; enforce daily caps; emit `PointsAwarded`.
* **LeaderboardService**: snapshot (daily/weekly/monthly); read current; rank queries.
* **GeoService**: bounds & places CRUD; map tiles tokens; privacy toggles.
* **SubscriptionService**: manage Stripe customer/subscription; entitlement checks.
* **PaywallService**: post visibility check; grant single‑purchase access; webhook handling.
* **CalendarService**: merge community events with user calendar; reminders; ICS.
* **ClassroomLinkService**: pivot courses↔communities; mirror announcements.

### 2.3 Policies & Gates (matrix excerpt)

| Ability                | Guest       | Member      | Moderator | Admin/Owner |
| ---------------------- | ----------- | ----------- | --------- | ----------- |
| `community.view`       | public only | ✔           | ✔         | ✔           |
| `community.post`       | ✖           | ✔           | ✔         | ✔           |
| `community.moderate`   | ✖           | ✖           | ✔         | ✔           |
| `post.update` (author) | ✖           | author only | ✔         | ✔           |
| `post.pin`             | ✖           | ✖           | ✔         | ✔           |
| `member.ban`           | ✖           | ✖           | ✔         | ✔           |
| `paywall.manage`       | ✖           | ✖           | ✖         | ✔           |

### 2.4 Events & Jobs

* **Events:** `MemberJoined`, `MemberApproved`, `PostCreated`, `PostLiked`, `CommentCreated`, `PointsAwarded`, `SubscriptionStarted`, `PaymentSucceeded`.
* **Jobs:** `DistributeNotification`, `GenerateLeaderboardSnapshot`, `TranscodeVideo`, `ScanMediaForMalware`, `ReindexSearch`, `RebuildCounters`.

### 2.5 API (REST) — Contracts

* **Auth:** Bearer (JWT/Passport) or Sanctum tokens for SPA/mobile.
* **Pagination:** keyset (`?after=cursor`) with `X-Next-Cursor` header.
* **Rate Limits:** reads 120/min; writes 30/min; stricter for media 10/min.

**Examples**

* `GET /api/v1/communities?category=alumni&q=design&page_size=20`

  * 200: `{ data:[{id,slug,name,avatar,member_count,online_count,joined,levels_summary}], next_cursor }`
* `POST /api/v1/communities/{id}/posts`

  * Body: `{ type:'image', body_md, media:[{kind:'image', path, width,height}], visibility:'community'|"public"|"paid", paywall_tier_id? }`
  * 201: `{ id, created_at }`
* `GET /api/v1/communities/{id}/feed?filter=top&after=...`

  * 200: `{ data:[PostCard...], next_cursor }`
* `POST /api/v1/like` body `{ likeable_type:'post'|'comment', likeable_id }`

  * 200 idempotent.

### 2.6 Validation (Form Requests excerpt)

* Post store: `type in:text,image,video,link,poll`, body max 20k chars, media array ≤ 10; each media validated (mimes, size); visibility gate & paywall checks.
* Member invite/approve: ensure role hierarchy and status transitions.

### 2.7 Webhooks (Stripe)

* Endpoint: `POST /webhooks/stripe` with signature verification.
* Handle: `customer.subscription.updated`, `invoice.paid`, `charge.refunded` → update `community_subscriptions`, grant/revoke `community_paywall_access`.

### 2.8 Search (Scout + Meili)

* Indexed: Community (name, tagline, category, popularity), Post (body, tags, community), Comment (body), User (name, bio).
* Facets: category, visibility; ranking rules prioritize recency, engagement.

### 2.9 Error & Response Model

* Standard envelope `{ data, error:{code,message,fields?}, meta }`.
* 4xx with field errors; 429 for throttles; 403 for policy denials.

### 2.10 OpenAPI Stub

```yaml
openapi: 3.0.3
info: { title: Communities API, version: 1.0.0 }
paths:
  /api/v1/communities:
    get: { summary: List communities }
    post: { summary: Create community, security: [bearerAuth: []] }
  /api/v1/communities/{id}/feed:
    get: { summary: Community feed }
components:
  securitySchemes:
    bearerAuth: { type: http, scheme: bearer, bearerFormat: JWT }
```

---

## 3) Frontend (Web) — UI/UX & Components

### 3.1 Tech & Architecture

* **Stack:** Vite + Vue 3 or React 18; Tailwind + shadcn/ui; Typesafe API client (openapi‑typescript or zodios); State via Pinia/Zustand; Realtime via Pusher/Laravel WebSockets.
* **Routing:** `/communities`, `/c/{slug}`, `/c/{slug}/members`, `/c/{slug}/leaderboard`, `/c/{slug}/calendar`, `/c/{slug}/settings`.
* **Access:** Guard routes with policy hints from `/me/capabilities` endpoint to minimize 403s.

### 3.2 Components (atoms → organisms)

* **Atoms:** Avatar, LevelBadge, RolePill, LikeButton, MentionText, TimeAgo, OnlineDot.
* **Molecules:** PostCard, CommentThread, MemberRow, TierCard, MapPanel, CalendarWidget, NotificationBell, ComposerBar.
* **Organisms:** CommunityHeader (banner/avatar/tags/actions), CommunityFeed (virtualized), MembersList (infinite scroll + filters), AdminPanel (tabs: Moderation/Levels/Points/Paywalls/Geo/Settings), SearchResultGrid.

### 3.3 UX Flows

* **Join/Subscribe:** CTA decides `join` vs `request` vs `subscribe`. Show tier modal, then Stripe Checkout; on success, entitlement revalidation and paywall unlock.
* **Compose:** floating composer in feed; drag‑drop media; visibility selector; markdown/preview; scheduled posts.
* **Moderation:** inline actions (pin/lock/hide/ban); bulk actions from AdminPanel; reason codes.
* **Notifications:** bell badge, panel with tabs (All, Mentions, System), mark‑read; deep links to post/comment.
* **Members:** filters (admins/mods/online/newest); roles badges; joined date; follow/unfollow.
* **Leaderboards:** tabs for Daily/Weekly/Monthly/All‑time; rank, points, delta; progress to next level.
* **Geo:** map bounds display; places list; privacy toggle; click to filter posts near place (optional).

### 3.4 Accessibility & Internationalization

* All interactive elements ≥44px; focus states; ARIA live regions for feed updates.
* RTL support; i18n strings with ICU; date/time localization.

### 3.5 Performance

* Virtualized lists for feeds & members; media lazy load; responsive image `srcset`; code splitting per route; HTTP caching for public pages.

### 3.6 Error States

* Empty feeds with smart starters ("Write something…"); network retries with toasts; 403 explanatory banners ("Requires membership/tier").

### 3.7 Admin Dashboard (Web)

* Tabs: **Moderation**, **Members**, **Levels**, **Points Rules**, **Paywalls/Tiers**, **Revenue**, **Geo**, **Settings**.
* Charts: posts/day, DAU/WAU/MAU, conversion to first post, MRR by tier; CSV export.

### 3.8 Design System Tokens

* Color scale neutral/brand (no green/yellow/orange per brand preference); elevation, radius 16–20px; spacing scale; typography (Poppins / Inter).

---

## 4) Acceptance Criteria (for this tranche)

1. DB migrations run clean on staging; seeders create baseline categories/levels/points; FK & index coverage verified; feed queries < 30ms at p95 for 1M posts dataset (staged data).
2. API endpoints delivered for communities, membership, feed, posts, comments, likes, follows, levels, points, leaderboard, geo, subscriptions, paywalls; rate limits enforced; policy checks in place; OpenAPI generated.
3. Web UI delivers: Communities list, Community detail with tabs, working feed with composer (text+image), notifications bell, members list with online dots & joined dates, leaderboard/levels view, subscription tiers modal with Stripe handoff, admin panel skeleton with Moderation & Levels tabs.

## 5) Test Plan

* **Unit:** Services (Points, Leaderboard, Paywall, Membership transitions).
* **Feature:** API auth & policy; feed pagination; paywall enforcement; webhook processing.
* **E2E (Dusk/Playwright):** Join/Subscribe → Compose → Like/Comment → Notification → Leaderboard update.
* **Load:** k6 500 RPS feed read, 100 RPS write; observe p95 and error rate < 0.5%.

## 6) Deliverables

* Versioned migrations & seeders; Models & Policies; Services with interfaces; Controllers & Requests; OpenAPI spec; Frontend routes, components, and pages; initial admin dashboard tabs; fixtures & k6 scripts; runbooks for seed, rollback, and reindex.
---

# Sections 4–6 — Enterprise, Modular Upgrade

Scope: **(4) Mobile (Flutter) App Integration**, **(5) Security (Detailed Controls)**, **(6) Analytics & Tracking**. Built on Laravel 11/PHP 8.3 back end and Community domain from prior tranches.

---

## 4) Mobile (Flutter) — App Integration (Req. #12–14, #27)

### 4.1 Architecture & Foundations

* **Flutter 3.x / Dart 3**
* **Packages:**

  * HTTP & Auth: `dio`, `retrofit`, `json_serializable`, `oauth2`/**Sanctum** custom interceptor
  * State: **Riverpod** (preferred) or **Bloc**; `freezed`/`sealed_classes` for models
  * Storage: `flutter_secure_storage` (tokens), `hive` (cache), `shared_preferences` (prefs)
  * Realtime: `web_socket_channel` or `pusher_channels_flutter`; presence channels for online indicators
  * Uploads: `dio` multipart + background via `workmanager` (Android) / `BGProcessingTask` (iOS)
  * Media: `image_picker`, `video_player`, `chewie`, `photo_manager` (permissions)
  * Markdown: `flutter_markdown` (sanitize allowlist)
  * Payments: `stripe_sdk` / `flutter_stripe` (official), fallback **Checkout webview**
  * Maps: `google_maps_flutter` or `mapbox_gl`
  * Notifications: `firebase_messaging`, `flutter_local_notifications`
  * Deep links: `uni_links` or `firebase_dynamic_links`

### 4.2 Auth & Session

* **Token flow:** OAuth2 Password or Sanctum personal access + refresh endpoint. Store access in secure storage; rotate refresh tokens; device fingerprint.
* **Device binding:** register device push token + model in `/devices` endpoint; revoke on logout; multiple devices per user supported.

### 4.3 Networking & Reliability

* **Dio interceptors** for auth headers, retry with exponential backoff, circuit‑breaker on 5xx.
* **Offline‑first cache**: Hive boxes for feeds, members, levels; background sync when online.

### 4.4 Realtime & Presence

* Subscribe to `community.{id}` and `user.{id}.notifications` channels.
* Presence: heartbeat every 30s (foreground) / 5m (background) to update `last_seen_at`; show `OnlineDot` in lists.

### 4.5 Screens & Flows

* **Communities Home**: tabs *Joined* / *Discover*; global search; pull‑to‑refresh.
* **Community Detail** (tabs: Feed, About, Members, Leaderboard, Calendar, Classroom, Map, Settings* if admin):

  * Header with banner, avatar, join/subscribe CTA, online count.
  * **Feed**: virtualized list; filters (new/top/media/paid); composer.
  * **Composer**: text/image/video upload; visibility selector (community/public/paid); markdown preview; scheduled posts.
  * **Members**: avatars, roles, online indicators, joined date; search & sort; follow/unfollow.
  * **Leaderboards/Levels**: badges; progress bars to next level; streaks.
  * **Subscriptions**: tier cards; paywall gating; Stripe Checkout; entitlement refresh.
  * **Profile Upgrades**: Activity/Followers/Following/Contributions.

### 4.6 Mobile‑specific Enhancements

* Lazy media loading, background uploads, retry queues with idempotency keys.
* **Universal links**: `orbas.io/c/{slug}` → app; fall back to web.
* **Notification channels**: Replies, Mentions, System; action buttons (Like/Reply) with deep links.

### 4.7 Error Handling & Telemetry

* Error boundary per route; toast/snackbar with retry.
* Crash reporting via Firebase Crashlytics or Sentry SDK.

### 4.8 Build & Release

* Flavors: `dev`, `staging`, `prod` (separate API keys, bundle ids).
* CI: Fastlane for signing; automatic TestFlight/Internal App Sharing upload; changelogs auto‑generated from commits.

---

## 5) Security (Req. #11) — Detailed Controls

### 5.1 HTTP Security Headers (Nginx + Laravel middleware)

* Enforce:

  * `Strict-Transport-Security: max-age=63072000; includeSubDomains; preload`
  * `Content-Security-Policy: default-src 'self'; img-src 'self' data: https:; media-src https:; script-src 'self' 'unsafe-inline' 'unsafe-eval' cdn.*; style-src 'self' 'unsafe-inline' fonts.googleapis.com; font-src 'self' fonts.gstatic.com; connect-src 'self' https: wss:; frame-ancestors 'none'; base-uri 'self'`
  * `Referrer-Policy: same-origin`
  * `Cross-Origin-Opener-Policy: same-origin`
  * `Cross-Origin-Embedder-Policy: require-corp`
  * `Cross-Origin-Resource-Policy: same-site`
  * `X-Frame-Options: DENY`
  * `X-Content-Type-Options: nosniff`
  * `Permissions-Policy: geolocation=(self), camera=(), microphone=()`

### 5.2 Authentication & Authorization

* Argon2id password hashing; 2FA (TOTP/WebAuthn); email verification; device session tracking; RBAC enforced via Policies; optional SSO for admins.

### 5.3 Data Protection

* Encrypt PII columns; sign media URLs; structured log redaction; GDPR export/delete tooling.

### 5.4 Uploads Security

* AV scan (ClamAV), image re‑encode, EXIF strip; per‑user/community quotas.

### 5.5 Payments

* Stripe‑hosted checkout; webhook signature verification; anti‑fraud rules; refund reconciliation.

### 5.6 Abuse/Moderation

* Profanity/spam scoring; rate limits; shadow bans; report/appeal workflow; admin audit logs.

---

## 6) Analytics & Tracking

### 6.1 Event Taxonomy (Web + Mobile)

* `community_join`, `post_create`, `comment_create`, `like_add`, `follow_add`, `paywall_purchase`, `subscription_start`, `calendar_add`, `classroom_link_click`.

### 6.2 Instrumentation

* Web: front‑end events (Post create, Like, Comment) through a thin analytics SDK → server proxy → warehouse.
* Mobile: Firebase Analytics or Segment; enrich with user & community context; respect privacy/consent.

### 6.3 Admin Dashboards

* Cohort retention, funnel to first post, ARPU, churn, LTV by community, content heatmaps; CSV export.

### 6.4 Data Governance

* Pseudonymize user ids in analytics; consent gating; data retention windows.

---

## Acceptance for this tranche

1. Flutter app compiles for iOS & Android with dev/staging/prod flavors; auth + communities list & detail; feed read + compose (text, image); basic notifications with deep links.
2. Security headers live on staging; 2FA available; uploads pass AV scan; Stripe Checkout functional in sandbox.
3. Analytics events flowing to staging project; dashboards show baseline metrics.

## Deliverables

* Flutter modules/screens, Riverpod/Bloc providers, generated API client, push setup, deep‑links, background uploads.
* Security middleware + Nginx snippets + validation policies + AV scan jobs.
* Analytics SDK wrappers (web & mobile), event schemas, admin dashboard tiles.
---

# Sections 7–9 — Enterprise, Modular Upgrade

Scope: **(7) Admin & Ops**, **(8) Search**, **(9) Email & Push Messaging**. Built on prior tranches (DB, API, Web, Mobile, Security, Analytics).

---

## 7) Admin & Ops (Req. #30–33)

### 7.1 Admin Dashboard (Web)

* **Navigation**: `/admin/communities` (index → detail)
* **Index Grid**: KPIs per community: members, online now, posts/day, comments/day, growth %, MRR, flags open.
* **Detail Tabs**:

  1. **Overview**: cards for DAU/WAU/MAU, retention 7/28/90, member funnel (views→join→first post), revenue by tier.
  2. **Moderation Queue**: reported posts/comments; bulk actions (approve/hide/ban); reason codes; audit trail; evidence snapshots.
  3. **Members**: search/filter (role, status, online, joined range). Actions: promote/demote, ban/unban, DM, export CSV. Bulk invite via CSV.
  4. **Levels & Points**: CRUD levels (name, color, icon, min_points); points rules with daily caps; previews of leaderboard effects.
  5. **Paywalls & Tiers**: create/edit pricing, trial, benefits, default tier; view active subs, churn list; issue comped access.
  6. **Geo Tools**: draw polygon for `geo_bounds` on map; add places; import GeoJSON; privacy toggle.
  7. **Automation**: scheduled posts, auto‑archive rules (days since last activity), welcome auto‑message templates.
  8. **Settings**: join policy, visibility, moderation flags, profanity lists, media limits, join questions, webhooks.

### 7.2 Roles & Permissions (Admin Scope)

* **Owner**: full access across tabs (including delete community).
* **Admin**: all except delete; pricing changes gated by `paywall.manage`.
* **Moderator**: Moderation Queue + Members (without pricing or geo boundary edits).

### 7.3 Metrics & Reporting

* **Realtime tiles**: online members, posts/minute, queue size.
* **Time series**: posts/day, comments/day, DAU/WAU/MAU, conversion to first post, MRR & churn (Stripe), ARPU, LTV (simple model).
* **Exports**: CSV for members, posts (metadata only), revenues; scheduled email reports.

### 7.4 Automation Jobs

* **Scheduled Posts**: cron checks for `publish_at <= now`; pushes via websockets + notifications.
* **Auto‑Archive**: mark threads inactive → hide from default feed; unarchive on new activity.
* **Welcome DMs**: on `MemberApproved` send templated DM + tips; optional email.
* **Health Monitors**: if queue depth > threshold or error rate spikes, auto‑notify on Slack/Email.

### 7.5 Audit & Compliance

* Immutable audit logs for admin/mod actions (WORM S3 bucket mirror). Admin reasons required for destructive actions.

---

## 8) Search (Req. #3) — Web + Dashboard

### 8.1 Engine & Indexing

* **Engine**: Laravel Scout + Meilisearch (preferred). Alternative: Elastic.
* **Indexes**: `communities`, `posts`, `comments`, `users`.
* **Fields**

  * *Communities*: name, slug, tagline, category, popularity (members, posts), visibility
  * *Posts*: body (md/html plain), author name, community slug, tags, like_count, created_at
  * *Comments*: body, post ref, author name, created_at
  * *Users*: name, username/slug, bio, skills, community counts
* **Ranking rules**: Typo tolerance → phrase proximity → attribute importance (title > body) → recency → engagement.
* **Facets**: category, visibility, media type, has_paywall.

### 8.2 Ingestion & Sync

* Model observers (`created/updated/deleted`) + backfill command: `php artisan search:reindex --entities=communities,posts,comments,users`.
* Nightly reconcile job to ensure counts and deletes synced; dedupe by external ID.

### 8.3 Query UX (Web/Dashboard)

* **Unified search bar** with chips (All, Communities, Posts, People, Comments).
* **Fuzzy matching** with highlighted snippets; keyboard nav (↑/↓, Enter to open, Tab to facet).
* **Recent searches** (local) and **saved searches** (server) per user; shareable search URLs.
* **Infinite scroll** with keyset cursors and debounce; empty state suggestions.

### 8.4 Permissions & Privacy

* Index only public/community‑visible content; enforce paywall/visibility at query time with filter tokens (signed query constraints) to prevent leakage.

### 8.5 Admin Search Tools

* Admin can search across reports/flags; moderation filters; export results.

---

## 9) Email & Push Messaging

### 9.1 Templates & Localization

* Emails (Blade + MJML or React Email): invites, approvals, replies, mentions, receipts, reminders, digests.
* Localization via Laravel Lang + ICU; liquid‑style variables for community & user names; themeable (dark/light logos).

### 9.2 Delivery Pipeline

* **Mail**: queue all; provider fallback (SES → Resend/SMTP) with provider health circuit breaker.
* **Push**: FCM/APNs via unified NotificationPublisher; categories (mentions, replies, system); user‑level toggles.

### 9.3 Digests

* Daily/weekly digests summarizing top posts, mentions, upcoming events; opt‑in per community; density control (light/standard/verbose).

### 9.4 Unsubscribe & Preferences

* Per‑type unsubscribe; per‑community overrides; one‑click unsubscribe in footer compliant with regulations; preference center page.

### 9.5 Deep Links & Actions

* Rich push with action buttons (Like/Reply) that deep link to app routes; web fallbacks.

### 9.6 Monitoring & Deliverability

* Track bounce/complaint webhooks; auto‑suppress bad addresses; analytics on open/click/convert; seed list monitoring.

### 9.7 Acceptance Criteria

1. Admin dashboard delivers all tabs (Overview, Moderation, Members, Levels & Points, Paywalls & Tiers, Geo Tools, Automation, Settings) with role‑gated access.
2. Metrics charts render on staging with real data; CSV exports available; scheduled weekly report email works.
3. Search bar returns mixed results with highlighting and facets; permissions respected; infinite scroll works.
4. Email templates localized; digests toggleable; push notifications actionable with deep links.

### 9.8 Deliverables

* Admin UI (routes, components, charts), backend endpoints, audit log sinks, automation jobs & schedules.
* Search indexes, model observers, reindex commands, front‑end search UI.
* Email/push templates, preference center, provider integrations, monitoring dashboards.
---

# Sections 10–12 — Enterprise, Modular Upgrade

Scope: **(10) DevOps & Environments**, **(11) Migration & Backfill Plan**, **(12) Testing Strategy**.

---

## 10) DevOps & Environments

### 10.1 Nginx & Networking

* **Vhost baseline**

  * TLS (Let's Encrypt/ACME) with OCSP stapling; HTTP/2 + ALPN; HSTS preload.
  * **Security headers** (from foundation tranche) via `include security-headers.conf`.
  * **Compression**: gzip on dynamic; **brotli** for static with `brotli_comp_level 5`.
  * **Caching**: `Cache-Control` for static assets (1y immutable) and public community pages (60s SWR 120s).
  * **WebSockets upstream** for Laravel WebSockets/Pusher compatible:

    ```nginx
    map $http_upgrade $connection_upgrade { default upgrade; '' close; }
    server {
      location /app { try_files $uri /index.php?$query_string; }
      location /ws/ {
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection $connection_upgrade;
        proxy_pass http://websockets:6001;
      }
    }
    ```

### 10.2 Queues & Workers

* **Horizon** with queue segmentation: `notifications`, `media`, `webhooks`, `search-index`, `emails`.
* **Supervision**: systemd unit templates per worker group; autoscale on queue depth; graceful shutdown on deploy.
* **Reserved, retry, timeout** tuned per queue (e.g., media 300s timeout, retries 2; notifications 30s, retries 3).

### 10.3 Storage & Lifecycle

* **S3 buckets**: `academy-community-media`, `academy-avatars`, `academy-banners` with separate KMS keys.
* **Lifecycle**: move originals to IA after 30d; delete transcode intermediates after 7d; retain thumbnails indefinitely.
* **S3 Object‑Lock (WORM)** for audit logs bucket.

### 10.4 CI/CD Pipelines

* **Stages**: Lint → Unit → Feature → Dusk E2E (staging ephemeral) → Build Flutter (Android/iOS) → Push to TestFlight/Play Internal → Gate → Prod deploy.
* **Example GitHub Actions matrix** (excerpt):

  ```yaml
  jobs:
    backend:
      runs-on: ubuntu-latest
      steps:
        - uses: actions/checkout@v4
        - uses: shivammathur/setup-php@v2
          with: { php-version: '8.3', extensions: mbstring, intl, redis }
        - run: composer install --no-interaction --prefer-dist
        - run: php artisan test --parallel
        - run: vendor/bin/phpstan analyse
    flutter:
      runs-on: ubuntu-latest
      steps:
        - uses: actions/checkout@v4
        - uses: subosito/flutter-action@v2
          with: { flutter-version: '3.24.0' }
        - run: flutter pub get
        - run: flutter test
        - run: flutter build appbundle --flavor staging
        - run: flutter build ipa --export-options-plist ios/ExportOptions.plist
  ```
* **Env promotion gates**: require passing tests, security scan, manual approval, and Sentry error budget within limits.

### 10.5 Secrets Management

* `.env` only as template. Real secrets from **AWS SSM Parameter Store** or **HashiCorp Vault** at deploy.
* Keys required: **Stripe**, **FCM**, **Mapbox**, **Meili**, **WebSockets**, **S3**, **DB**, **Sentry**.
* Rotation policy: quarterly; emergency rotation playbook.

### 10.6 Observability & Alerts (tie‑in)

* Logs to centralized stack (ELK/CloudWatch); metrics (Prometheus) for queues, HTTP p95, DB; alerts on error rate, queue lag, 5xx spikes, disk near full, Redis memory.

---

## 11) Migration & Backfill Plan

### 11.1 Strategy

* **Feature flags** to hide UI/API routes until data is ready.
* **Expand → Migrate → Contract** pattern for any model changes that touch existing tables.

### 11.2 Steps

1. **Ship migrations** (new community tables) behind flags; run in staging, then prod maintenance window.
2. **Seed**: categories, base levels, default points rules.
3. **Backfill memberships**: optional rule to infer community membership from **classroom enrollments**; script uses batches of 1,000 with retry and idempotency keys.
4. **Incremental rollout**: enable a **beta community** for internal users; monitor Sentry/Horizon; gather performance metrics.
5. **Legacy profile → activity**: ETL scripts to create `profile_activity` rows (posts, comments, course completions) to populate contributions tab.

### 11.3 Scripts (outline)

* `artisan community:seed-baseline`
* `artisan community:backfill-membership --source=classrooms --batch=1000`
* `artisan community:migrate-legacy-activity --dry-run`
* `artisan community:enable-feature --flag=communities`

### 11.4 Rollback Plan

* Disable feature flag; stop writers; revert last migration batch if no data loss; preserve S3 media.

---

## 12) Testing Strategy

### 12.1 Unit Tests

* Services: `PointsServiceTest`, `LeaderboardServiceTest`, `SubscriptionServiceTest`, `MembershipServiceTest`, `PermissionsPolicyTest`.
* Pure logic verified with table‑driven datasets.

### 12.2 Feature Tests

* API auth, community CRUD, feed visibility (public/community/paid), paywall enforcement, rate limiting, Stripe webhook handling (signed payloads), notifications delivery.

### 12.3 E2E (Dusk/Playwright)

* Scenarios: join/subscribe → compose (text+image) → like/comment → notification → leaderboard update → paywall view.
* Calendar and classroom link flows validated (create event → ICS export → reminder push).

### 12.4 Load & Resilience (k6)

* Feed read 500 RPS, write 100 RPS; target API p95 < 250ms.
* Websocket fan‑out test: 5k concurrent clients, 1 msg/sec broadcast; ensure CPU < 70% and message delivery < 2s.

### 12.5 Security Testing

* SAST (Larastan/PHPStan), Composer audit; OWASP ZAP baseline; dependency patch cadence.

### 12.6 CI Gates & Coverage

* Coverage thresholds: backend 80% lines/branches on services; UI critical path tests green.
* Build fails on failing gates; artifact uploads for test reports and coverage badges.

### 12.7 Test Data & Fixtures

* Factories & seeders for communities, tiers, posts, comments; S3 mock for media; Stripe test keys.

---

## Acceptance Criteria

1. Nginx/WebSockets configured; Horizon workers autoscale; S3 buckets with lifecycle and KMS; secrets injected from SSM/Vault.
2. Feature flags allow safe rollout; baseline seeders run; membership backfill works and idempotent; beta rollout executed.
3. Test suite covers services & API; Dusk/Playwright E2E scenarios pass; load & websocket tests meet targets.

## Deliverables

* Nginx configs & security header include; Horizon & systemd units; GitHub Actions workflows; S3 buckets & lifecycle JSON; SSM/Vault secret maps.
* Artisan commands & ETL scripts; runbooks for rollout/rollback.
* Test suites (unit/feature/E2E), k6 scripts, WebSocket load test harness, CI reports.

---
# Sections 13–15 — Acceptance Criteria Mapping, Migration Stubs, Policies

## 13) Acceptance Criteria Mapping to Requirements

For each numbered requirement below, acceptance means **implemented, tested (unit/feature/E2E), and documented** with observable outcomes.

1. **Add communities like Skool** → Domain models (`Community`, `Member`, `Post`, etc.), dashboards, feeds, categories, search, memberships live; create/join/post flows work.
2. **Skool Category** → `community_categories` table, seeder, and UI filter chip; categories appear in Discover + admin.
3. **Search** → Laravel Scout + Meilisearch wired; dashboard + homepage inputs with suggestions; highlighted results and facets.
4. **Community Feed in Profile Dashboard** → Aggregated feed widget on user dashboard with filters (new/top/media/paid).
5. **Community updates** → Posts/comments/likes flow; realtime fan‑out via websockets; counters update.
6. **Update posts/comments/likes** → CRUD endpoints + soft delete + edit windows; audit log entries for edits/deletes.
7. **View community in full** → Detail page with tabs and counts; About/Members/Leaderboard/Calendar/Classroom/Geo/Settings.
8. **Notifications icon** → Bell with unread badge; panel & page views; push to mobile/web; preference toggles.
9. **Calendar integration** → Community events in user calendar; ICS export; reminder notifications fire.
10. **Classroom integration** → Pivot mapping courses↔communities; mirrored announcements; completion → points rule.
11. **Full security** → Headers live; CSRF enabled; rate limits in place; upload scanning; RBAC & admin audit logs.
12. **Integrate into phone app** → Flutter screens and providers; API tokens; websockets; push handling.
13. **Phone app community area** → Communities Home (Joined/Discover) operational with search.
14. **Phone app community feed** → Feed + composer (text/image/video) functional; visibility selector.
15. **View community members** → Members list with roles, search, sort, online dots, joined date.
16. **Show as online** → Presence channels + heartbeat; online status visible.
17. **Show when joined** → `joined_at` rendered on web & mobile.
18. **Show community admins** → Badges + filter; admin list appears on About/Members.
19. **Community maps** → Geo bounds + places; Map UI renders; privacy toggle.
20. **Leaderboards & levels** → Points rules, levels, snapshots, badges; leaderboard tabs.
21. **Community settings** → Naming, levels, points, moderation, media limits editable in Admin.
22. **Leaderboard points** → Rules enforced with daily caps; PointsService unit tests.
23. **About sections** → Rich text About/Links editable; validation & sanitization.
24. **Subscriptions & paywalls** → Stripe tiers + single‑content unlocks; entitlement checks; webhooks processed.
25. **Profile upgrade & activity** → Followers/Following, Contributions, streaks visible; privacy controls.
26. **My profile trackers** → Counters and pages show correct counts; API covered by tests.
27. **Integrate into Flutter each level** → Levels, badges, progress bars in mobile; synced with backend.
28. **Community following** → Follow/unfollow for communities/users; updates feeds.
29. **Video/picture uploads + text** → S3 uploads, server transcode, thumbnails; composer supports all.
30. **Community admins** → Roles/permissions enforced; UI badges on posts/members.
31. **Admin settings** → Full admin panel with all tabs; role‑gated.
32. **Admin dashboard** → Moderation, analytics, revenue tabs; charts populated.
33. **Tracking counts** → Followers/following/admins/member/online counts accurate and cached.
34. **Community profile** → Picture, banner, name, tagline, bio, links present and editable (with policy checks).
35. **UI/UX** → Responsive, accessible (WCAG 2.1 AA), dark mode; performance budgets met.
36. **Write something in feed** → Composer present across web/mobile; “Write something…” states.

---

## 14) Sample Migration Stubs (Abbrev.)

```php
Schema::create('communities', function (Blueprint $t) {
  $t->id();
  $t->string('slug')->unique();
  $t->string('name');
  $t->string('tagline')->nullable();
  $t->text('bio')->nullable();
  $t->longText('about_html')->nullable();
  $t->string('banner_path')->nullable();
  $t->string('avatar_path')->nullable();
  $t->json('links')->nullable();
  $t->foreignId('category_id')->nullable()->constrained('community_categories')->nullOnDelete();
  $t->enum('visibility', ['public','private','unlisted'])->default('public');
  $t->enum('join_policy', ['open','request','invite'])->default('open');
  $t->geometry('geo_bounds')->nullable();
  $t->foreignId('created_by');
  $t->foreignId('updated_by')->nullable();
  $t->timestamps();
});
```

**Additional stubs (brief):**

```php
Schema::create('community_members', function (Blueprint $t) {
  $t->id();
  $t->foreignId('community_id')->constrained('communities')->cascadeOnDelete();
  $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
  $t->enum('role', ['owner','admin','moderator','member'])->default('member');
  $t->enum('status', ['active','pending','banned','left'])->default('active');
  $t->timestamp('joined_at');
  $t->timestamp('last_seen_at')->nullable();
  $t->boolean('is_online')->default(false);
  $t->integer('points')->default(0);
  $t->integer('level')->default(1);
  $t->json('badges')->nullable();
  $t->timestamps();
  $t->unique(['community_id','user_id']);
});

Schema::create('community_posts', function (Blueprint $t) {
  $t->id();
  $t->foreignId('community_id')->constrained('communities')->cascadeOnDelete();
  $t->foreignId('author_id')->constrained('users');
  $t->enum('type', ['text','image','video','link','poll']);
  $t->mediumText('body_md')->nullable();
  $t->mediumText('body_html')->nullable();
  $t->json('media')->nullable();
  $t->boolean('is_pinned')->default(false);
  $t->boolean('is_locked')->default(false);
  $t->enum('visibility', ['community','public','paid'])->default('community');
  $t->foreignId('paywall_tier_id')->nullable()->constrained('community_subscription_tiers')->nullOnDelete();
  $t->unsignedInteger('like_count')->default(0);
  $t->unsignedInteger('comment_count')->default(0);
  $t->unsignedInteger('share_count')->default(0);
  $t->timestamps();
  $t->index(['community_id','is_pinned','created_at']);
});
```

---

## 15) Example Policies (Abbrev.)

```php
class CommunityPolicy
{
    public function view(User $user = null, Community $community): bool
    {
        if ($community->visibility === 'public') return true;
        return $user && $user->isMemberOf($community);
    }

    public function post(User $user, Community $community): bool
    {
        return $user->isMemberOf($community) && !$user->isBannedFrom($community);
    }

    public function moderate(User $user, Community $community): bool
    {
        return $user->hasCommunityRole($community, ['admin','moderator','owner']);
    }
}
```

**Notes**

* Paywall checks enforced in `PostPolicy@view` and middlewares when visibility=`paid`.
* Policy responses provide **deny messages** for UI hints (Laravel 11 feature).

---

# Sections 16–18 — Environment Variables, Nginx Security Snippet, Rollout Plan

## 16) Example `.env` Additions

```
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=https://meili.internal
MEILISEARCH_KEY=***

STRIPE_KEY=***
STRIPE_SECRET=***
STRIPE_WEBHOOK_SECRET=***

PUSHER_APP_ID=***
PUSHER_APP_KEY=***
PUSHER_APP_SECRET=***
PUSHER_HOST=ws.orbas.io
PUSHER_PORT=443

FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=***
AWS_SECRET_ACCESS_KEY=***
AWS_DEFAULT_REGION=eu-west-1
AWS_BUCKET=academy-community

MAPBOX_TOKEN=***
FCM_SERVER_KEY=***
SENTRY_DSN=***
APP_FEATURE_FLAGS={"communities":false,"paywalls":false}
```

**Notes**

* Secrets are injected from SSM/Vault at deploy; `.env` checked in only as a template.
* `APP_FEATURE_FLAGS` controls staged rollouts.

---

## 17) Nginx Security Snippet (Abbrev.)

```
# /etc/nginx/snippets/security-headers.conf
add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload" always;
add_header X-Frame-Options DENY always;
add_header X-Content-Type-Options nosniff always;
add_header Referrer-Policy same-origin always;
add_header Cross-Origin-Opener-Policy same-origin always;
add_header Cross-Origin-Embedder-Policy require-corp always;
add_header Cross-Origin-Resource-Policy same-site always;
add_header Permissions-Policy "geolocation=(self)" always;

# Optional additional protections
add_header X-Permitted-Cross-Domain-Policies none always;
add_header X-XSS-Protection "0" always; # modern browsers ignore this, but set explicitly
```

**Include in vhost**

```
server {
  include snippets/security-headers.conf;
  # ... rest of vhost
}
```

---

## 18) Rollout Plan (Web + Mobile)

**Phase 1 – Backend APIs + Web Admin (internal beta)**

* Ship DB + services behind flags; admin panel (moderation, levels/points) available to staff only.
* QA: unit/feature tests green; staging data load; k6 baseline; Sentry zero new criticals.

**Phase 2 – Web user feed & composer (limited community)**

* Enable communities for internal pilot group; collect UX telemetry; iterate on feed performance & moderation.

**Phase 3 – Flutter beta (TestFlight/Play Internal)**

* Ship Communities Home/Detail/Feed/Composer; push notifications + deep links.
* Closed testers (12) invited; crash‑free users ≥ 98% target.

**Phase 4 – Payments & paywalls enabled**

* Stripe live keys; tiers/single‑purchase; entitlement checks; reconcile webhooks.
* Marketing warm‑up; docs for creators/community admins.

**Phase 5 – Full release + monitoring & A/B tests**

* Turn on feature flags for all; add tutorials/guides; A/B test feed ranking & points rules; watch MRR, retention, and content creation rates.

**Cutover Guardrails**

* Rollback plan documented; feature kill‑switches; error budget alarms; capacity headroom ≥30%; SLOs: API p95 < 250ms, WS delivery < 2s,

---

## 19) Risks & Mitigations

* **Scale spikes** → Autoscale workers; CDN for media; paginate aggressively.
* **Abuse** → Moderation tools, rate limits, shadow bans.
* **Payments disputes** → Clear refund policy; webhook reconciliation jobs.
* **App store reviews** → Clear feature flags; staged rollout; crashlytics.

---

## 20) Documentation & Handover

* API reference (OpenAPI); ERD; runbooks (alerts, webhooks, queue backlog, websocket down); admin guides; community manager SOPs; security checklist.

---

**Deliverables:** code, migrations, seeders, tests, CI/CD pipelines, Flutter screens & components, icon sets, email/push templates, admin dashboards, monitoring dashboards, runbooks.



---
## Roadmap from 20% → 100% Completion

> Current state: foundational contracts and documentation exist, but the production experience is ~20% complete. Complete the milestones below in order; do not move forward until every task in the current milestone meets the quality gates and is backed by production-grade logic (no stubs).

### 0. Quality Grading Model

#### 0.1 Grade Scale (applies to every task)

- **100%** – Top production level (launch-ready with polish, monitoring, and documentation).
- **90%** – Production level with minor fit-and-finish issues documented for follow-up.
- **80%** – Production-capable but requires targeted tweaks before release.
- **70%** – Testing only; feature incomplete or blocked by known gaps.
- **60% and below** – Not ready; must not ship or graduate to the next milestone.

#### 0.2 Task Weighting & Scoring

- Every roadmap task (A1–E5) carries equal weight. Compute the overall milestone or program grade as the arithmetic mean of the latest task grades.
- Record grades in the scoring worksheet (Section 6) each time work lands. A milestone is considered complete only when every task is ≥90% and the milestone average is ≥92%.

#### 0.3 Quality Checker Reference

Use the shorthand references below whenever you evaluate or ship a task. Each task lists the required checkers; capture test evidence, screenshots, or runbook links for every checker before closing work. If a checker is truly not applicable, log an explicit `N/A` rationale with the reviewer.

| Ref | Dimension | Expectations |
| --- | --- | --- |
| **QC-01** | Code Quality | Meets coding standards, static analysis clean, patterns follow architecture guidelines. |
| **QC-02** | Security | Threat model addressed, middleware and policies enforced, secrets handled correctly, scanners green. |
| **QC-03** | Functionality | Business logic complete with automated tests proving happy/sad paths. |
| **QC-04** | Integration | Works across modules/services, with contract tests or end-to-end validation. |
| **QC-05** | Phone App – Core | Flutter build passes, architecture patterns (Riverpod/Dio) followed, platform services wired. |
| **QC-06** | Phone App – Screens | Screens implemented per spec with navigation, states, and offline handling. |
| **QC-07** | Phone App – Admin Screen | Administrative/creator tools available in mobile with permissions. |
| **QC-08** | Phone App – Profile | Profile editing, avatars, achievements, and settings complete and synced. |
| **QC-09** | Web App – Core | Laravel app stable, routes registered, caching/queues configured. |
| **QC-10** | Web App – Pages | Public/member-facing pages implemented and responsive. |
| **QC-11** | Web App – Admin Dashboard | Admin dashboards feature-complete with charts and controls. |
| **QC-12** | Web App – User Dashboard | Learner/member dashboard delivers required widgets and personalization. |
| **QC-13** | Web App – Provider Dashboard | Instructor/creator tooling implemented with reporting. |
| **QC-14** | Web App – Settings | Settings area covers notifications, privacy, billing, and integrations. |
| **QC-15** | Design | Visual language matches Orbas Learn brand, assets exported, tokens maintained. |
| **QC-16** | Wireframes | Wireframes updated to reflect shipped experience and kept in sync. |
| **QC-17** | UI/UX | Interaction design validated via usability review, accessibility, and performance checks. |

### 1. Milestone A (20% → 35%): Repository Cleanup & Installation Readiness

| Task ID | Summary | Expected Deliverables | Required Quality Checkers |
| --- | --- | --- | --- |
| **A1** | Remove CodeCanyon artefacts | Purge licence middleware, purchase-code prompts, and vendor callbacks. Replace with Orbas Learn onboarding copy and regression tests. | QC-01, QC-02, QC-03, QC-04, QC-09, QC-10, QC-11, QC-14 |
| **A2** | Unified installer automation | Harden `install.sh` with non-interactive flags, Apache/Nginx support, queue/scheduler provisioning, idempotent reruns, and checksum verification. | QC-01, QC-02, QC-03, QC-04, QC-09 |
| **A3** | Documentation and branding alignment | Update every README, `.env.example`, installer prompt, and screenshot to the Orbas Learn brand; cross-link roadmap and grading rubric. | QC-01, QC-03, QC-09, QC-10, QC-15, QC-16, QC-17 |
| **A4** | Repository hygiene guardrails | Introduce EditorConfig, lint/format hooks, Dependabot, secrets scanning, static analysis pipelines, and contributor guide updates. | QC-01, QC-02, QC-03, QC-04, QC-09 |

### 2. Milestone B (35% → 55%): Domain & Data Model Implementation

| Task ID | Summary | Expected Deliverables | Required Quality Checkers |
| --- | --- | --- | --- |
| **B1** | Community data model rollout | New migrations and Eloquent models for communities, memberships, posts, comments, reactions, follows, tiers/paywalls, points, leaderboards, geo, calendars, classroom links, and device registrations with FK/index coverage. | QC-01, QC-02, QC-03, QC-04, QC-09, QC-10, QC-11, QC-12, QC-13, QC-14 |
| **B2** | Seeders and factories | High-fidelity seeders/factories feeding installer, plus rollback/backfill artisan commands with expand/contract strategy docs. | QC-01, QC-03, QC-04, QC-09, QC-10, QC-12 |
| **B3** | Compliance audit trails | Event-sourced audit tables for moderation, payments, automation jobs; reporting endpoints and retention policies documented. | QC-01, QC-02, QC-03, QC-04, QC-09, QC-11, QC-13 |

### 3. Milestone C (55% → 75%): Backend Services, APIs, and Integrations

| Task ID | Summary | Expected Deliverables | Required Quality Checkers |
| --- | --- | --- | --- |
| **C1** | Community service implementations | Concrete services for membership, feed, composer, moderation, reactions, points, leaderboard, subscriptions, geo, classroom sync with automated tests. | QC-01, QC-02, QC-03, QC-04, QC-09, QC-10, QC-11, QC-12, QC-13 |
| **C2** | Public API & realtime surface | REST + realtime endpoints, validators, policies, resources, OpenAPI spec, and generated TypeScript/Dart SDKs. | QC-01, QC-02, QC-03, QC-04, QC-05, QC-09, QC-10, QC-11, QC-12, QC-13, QC-14 |
| **C3** | Search platform integration | Scout + Meilisearch/Elastic integration, indexing observers, synonyms, reindex commands, and permission-aware queries. | QC-01, QC-02, QC-03, QC-04, QC-09, QC-10 |
| **C4** | Platform reliability upgrades | Standardized error envelopes, rate limiting, Horizon configuration, scheduled jobs, notification pipelines, and observability wiring. | QC-01, QC-02, QC-03, QC-04, QC-09, QC-11, QC-13 |

### 4. Milestone D (75% → 90%): Web Experience & Admin Control Center

| Task ID | Summary | Expected Deliverables | Required Quality Checkers |
| --- | --- | --- | --- |
| **D1** | Communities web UX | Discovery, joined list, feed, composer (text/media/scheduled), comments, reactions, bookmarks, saved searches, offline states built in Blade/Vite or Inertia/Vue. | QC-01, QC-02, QC-03, QC-04, QC-09, QC-10, QC-15, QC-17 |
| **D2** | Admin operations suite | Moderation dashboard, member management, levels/points tuning, paywall tier editor, automation jobs, geo/calendar admin, audit trail viewers. | QC-01, QC-02, QC-03, QC-04, QC-09, QC-11, QC-13, QC-14, QC-15, QC-17 |
| **D3** | Design system & accessibility | Tokenized design library, WCAG AA compliance, localization, dark mode, analytics instrumentation across interactions. | QC-01, QC-02, QC-03, QC-09, QC-15, QC-16, QC-17 |
| **D4** | Notifications & messaging hub | Web notifications center, email template previews, preference management, and analytics on engagement. | QC-01, QC-02, QC-03, QC-04, QC-09, QC-10, QC-11, QC-12, QC-13, QC-14, QC-17 |

### 5. Milestone E (90% → 100%): Mobile, Messaging, Payments, and Operations

| Task ID | Summary | Expected Deliverables | Required Quality Checkers |
| --- | --- | --- | --- |
| **E1** | Flutter architecture & parity | Riverpod/Dio refactor, generated models, full community flows (join/subscribe, feed, compose, notifications, leaderboard, calendar, geo, classroom) with offline caching and deep links. | QC-01, QC-02, QC-03, QC-04, QC-05, QC-06, QC-07, QC-08, QC-15, QC-17 |
| **E2** | Mobile and web messaging unification | Push notifications (FCM/APNs), in-app messaging, moderation alerts, unified preference management. | QC-01, QC-02, QC-03, QC-04, QC-05, QC-06, QC-07, QC-08, QC-09, QC-10, QC-11, QC-12, QC-13, QC-14, QC-17 |
| **E3** | Payments and monetization | Stripe billing portal, webhook resiliency, entitlement syncing, refunds/disputes workflows, revenue analytics, and reporting dashboards. | QC-01, QC-02, QC-03, QC-04, QC-05, QC-08, QC-09, QC-10, QC-11, QC-12, QC-13, QC-14, QC-17 |
| **E4** | Security and compliance hardening | Device/session management, 2FA/WebAuthn, upload scanning, secrets rotation, incident response runbooks, compliance automation. | QC-01, QC-02, QC-03, QC-04, QC-05, QC-09, QC-11, QC-13, QC-14, QC-17 |
| **E5** | CI/CD, IaC, and launch readiness | GitHub/GitLab pipelines, infrastructure scripts (Docker Compose/Ansible/Terraform), load & security testing suites, observability dashboards, final launch checklist with go/no-go metrics. | QC-01, QC-02, QC-03, QC-04, QC-05, QC-09, QC-10, QC-11, QC-12, QC-13, QC-14, QC-17 |

### 6. Score Aggregation Worksheet

Maintain the following table (copy into tracking docs or project boards) to compute milestone and overall readiness grades. Update the `Grade` column after every review and average the column to derive the program score.

| Task ID | Latest Grade (%) | Evidence Links / Notes |
| --- | --- | --- |
| A1 | | |
| A2 | | |
| A3 | | |
| A4 | | |
| B1 | | |
| B2 | | |
| B3 | | |
| C1 | | |
| C2 | | |
| C3 | | |
| C4 | | |
| D1 | | |
| D2 | | |
| D3 | | |
| D4 | | |
| E1 | | |
| E2 | | |
| E3 | | |
| E4 | | |
| E5 | | |

> **Completion rule:** Declare the roadmap at 100% only when every task grade is ≥95% and the aggregate average is ≥97%. Record interim assessments in Section 7 and document any remediation backlog separately.

### 7. Current Completion Snapshot (Last updated: 2025-02-19)

The table below captures the **baseline assessment of the repository as of this commit**. Update the grade, status, and evidence links every time work lands. Grades map directly to the rubric in Section 0.

| Task ID | Status | Current Grade (%) | Evidence Highlights / Gaps |
| --- | --- | --- | --- |
| **A1** | 🚫 Blocked | 25 | Purchase-code prompts and validation remain in the installer flow and controllers (e.g., `resources/views/install/step2.blade.php`, `app/Http/Controllers/InstallController.php`). |
| **A2** | ⏳ In progress | 60 | `tools/Start_Up Script` automates .env, Composer/npm, migrations, and Apache stubs but lacks Nginx parity, queue/scheduler provisioning, and checksum verification. |
| **A3** | ⏳ In progress | 40 | Branding updates are partial; installer templates and seed SQL still reference Creativeitem/CodeCanyon (`resources/views/install/index.blade.php`, `public/assets/install.sql`). |
| **A4** | ⏳ In progress | 65 | GitHub Actions run Pint, PHPUnit, Larastan, and Dusk (`.github/workflows/ci.yml`), yet the repo lacks `.editorconfig`, secrets scanning, and automated dependency updates. |
| **B1** | ✅ On track | 88 | Comprehensive community schema exists with FK/index coverage (`database/migrations/2024_12_24_000000_create_community_core_tables.php`, `2024_12_24_000100_create_community_engagement_tables.php`). |
| **B2** | ⏳ In progress | 68 | `CommunityFoundationSeeder` seeds categories, levels, and points rules but omits paywall tiers, geo fixtures, and classroom/device data. |
| **B3** | ⏳ In progress | 72 | Audit logging pipelines capture admin actions and retention jobs (`app/Http/Middleware/RecordAdminAction.php`, `app/Services/Security/DataRetentionService.php`), yet moderation/payments reporting is incomplete. |
| **C1** | ⏳ In progress | 82 | Feed, membership, paywall, and subscription services are implemented with feature tests (`app/Domain/Communities/Services/CommunityFeedService.php`, `tests/Feature/Community/CommunityFeedPaywallTest.php`). |
| **C2** | ⏳ In progress | 78 | API v1 exposes community CRUD, feed, geo, and admin endpoints with throttling and events (`routes/api.php`, `app/Events/Community/PostCreated.php`), but realtime channel bindings and SDK generation remain TODO. |
| **C3** | ⏳ In progress | 70 | Meilisearch client, sync jobs, and configuration commands exist (`app/Services/Search/MeilisearchClient.php`, `app/Console/Commands/SyncSearchConfiguration.php`); synonym management and permission-scoped queries are partially wired. |
| **C4** | ⏳ In progress | 76 | Horizon auth/notifications and queue autoscaling runbooks are committed (`app/Providers/HorizonServiceProvider.php`, `app/Console/Commands/QueueAutoscaleCommand.php`), but unified error envelopes and rate-limit telemetry are pending. |
| **D1** | ⏳ In progress | 62 | Vue admin SPA delivers community index/detail/insights views (`resources/js/modules/communities/views/CommunitiesIndexView.vue`), while public member-facing pages remain on legacy Blade templates. |
| **D2** | ⚠️ At risk | 58 | Admin SPA shell and module registry exist (`resources/js/admin/router/index.ts`, `resources/js/modules/moderation/index.ts`), yet moderation queues, automation jobs, and audit dashboards lack UI wiring. |
| **D3** | ⚠️ At risk | 55 | Design tokens and UI kit scaffolding ship with the SPA (`resources/js/admin/ui/tokens.ts`), but accessibility audits, localization assets, and dark-mode variants are unverified. |
| **D4** | ⚠️ At risk | 50 | Notification preferences API exists (`routes/api.php`, `app/Http/Controllers/Api/V1/Community/CommunityNotificationPreferenceController.php`), but web notification center and analytics views are not implemented. |
| **E1** | ⏳ In progress | 72 | Community explorer/detail/onboarding flows, offline action queues, and presence scaffolding exist under `lib/features/communities`, but Riverpod/Dio migration and richer offline UX remain open.【F:Student Mobile APP/academy_lms_app/lib/features/communities/presentation/community_explorer_screen.dart†L1-L120】【F:Student Mobile APP/academy_lms_app/lib/features/communities/state/community_notifier.dart†L1-L120】 |
| **E2** | ⚠️ At risk | 45 | Notification preference APIs and mobile push routing helpers exist, yet FCM/APNs bindings and unified messaging orchestration are incomplete (`pubspec.yaml` lacks messaging packages).【F:Web_Application/Academy-LMS/app/Http/Controllers/Api/V1/Community/CommunityNotificationPreferenceController.php†L1-L66】【F:Student Mobile APP/academy_lms_app/lib/services/messaging/push_notification_router.dart†L1-L40】 |
| **E3** | ❌ Not started | 32 | Stripe subscription services exist server-side (`app/Services/Community/StripeSubscriptionService.php`), but entitlement sync UI, refunds, and analytics dashboards remain TODO. |
| **E4** | ⚠️ At risk | 45 | Device/session hardening migrations and middleware are present (`database/migrations/2024_10_10_000001_harden_authentication_tables.php`, `app/Http/Middleware/RecordAdminAction.php`), yet WebAuthn, upload scanning, and incident runbooks need implementation. |
| **E5** | ⏳ In progress | 60 | CI pipelines cover PHP/Flutter test suites (`.github/workflows/ci.yml`, `.github/workflows/flutter-ci.yml`), but IaC, load/security automation, and go-live checklist enforcement are incomplete.

### Cutover Acceptance Criteria

- All automated tests (PHPUnit/Pest, Playwright/k6, Flutter integration, static analysis) pass in CI; installer script provisions a fresh environment with zero manual edits.
- Feature flags removed or default-on; documentation, runbooks, and screenshots updated to reflect production flows across web and mobile.
- Operations sign-off: monitoring alerts configured, rollback rehearsed, risk register closed, and onboarding materials delivered to support/community teams.

## Current Codebase State

### Repository layout
- **Web application** lives under `Web_Application/Academy-LMS` and now mixes the legacy LMS stack with a domain-oriented communities module. Controllers and services for communities, moderation, subscriptions, and geo live under `app/Domain/Communities` and `app/Http/Controllers/Api/V1/Community`, while classic course features remain in `app/Http/Controllers/frontend` and related namespaces.【F:Web_Application/Academy-LMS/app/Domain/Communities/Services/CommunityFeedService.php†L1-L77】【F:Web_Application/Academy-LMS/routes/api.php†L1-L126】
- **Student mobile app** under `Student Mobile APP/academy_lms_app` still uses Provider with `http` clients across `lib/providers` and course-centric screens. No Riverpod/Dio architecture or community feature set is wired yet.【F:Student Mobile APP/academy_lms_app/lib/providers/auth.dart†L1-L68】
- Installer SQL and historical assets persist under `Web_Application/Academy-LMS/public/assets/install.sql`, so manual data loads continue alongside the new migrations.【F:Web_Application/Academy-LMS/public/assets/install.sql†L2519-L2568】

### Section 1 – Domain & Data Foundations
- Community schemas, indexes, and geo/paywall tables are delivered via the December 2024 migration set, and engagement tables (members, posts, comments, leaderboards, subscriptions) are defined with foreign keys and composite indexes.【F:Web_Application/Academy-LMS/database/migrations/2024_12_24_000000_create_community_core_tables.php†L1-L159】【F:Web_Application/Academy-LMS/database/migrations/2024_12_24_000100_create_community_engagement_tables.php†L1-L168】
- Seeders initialise global categories, default levels, and points rules, but paywall tiers, geo fixtures, and device/classroom seeds are still missing from the bundle.【F:Web_Application/Academy-LMS/database/seeders/Communities/CommunityFoundationSeeder.php†L1-L116】
- Legacy helpers such as `Common_helper.php` still surface cross-cutting LMS queries, highlighting ongoing coexistence between the new domain layer and historical procedural code.【F:Web_Application/Academy-LMS/app/Helpers/Common_helper.php†L1203-L1407】

### Section 2 – Backend Services & APIs
- API v1 now exposes community CRUD, feed, members, geo, subscription, and admin endpoints with Sanctum, throttling, and feature-flag support while legacy LMS routes remain alongside.【F:Web_Application/Academy-LMS/routes/api.php†L57-L163】
- Domain services implement feed curation, moderation, subscriptions, points, geo, and paywall logic, paired with feature tests covering pagination and paywall enforcement.【F:Web_Application/Academy-LMS/app/Domain/Communities/Services/CommunityFeedService.php†L1-L77】【F:Web_Application/Academy-LMS/tests/Feature/Community/CommunityFeedPaywallTest.php†L1-L92】
- Horizon configuration, queue autoscaling, Stripe webhook handling, and Meilisearch sync tooling are present, signalling an expanded operational layer over the base Laravel queue system.【F:Web_Application/Academy-LMS/app/Console/Commands/QueueAutoscaleCommand.php†L1-L120】【F:Web_Application/Academy-LMS/app/Services/Billing/StripeWebhookService.php†L1-L175】【F:Web_Application/Academy-LMS/app/Console/Commands/SyncSearchConfiguration.php†L1-L92】

### Section 3 – Web Experience & Admin
- A Vue 3 SPA powers the Communities Control Center with module registration, navigation, and admin-facing views (index/detail/insights) sourced from API manifests.【F:Web_Application/Academy-LMS/resources/js/admin/router/index.ts†L1-L29】【F:Web_Application/Academy-LMS/resources/js/modules/communities/views/CommunitiesIndexView.vue†L1-L160】
- Public-facing member experiences still lean on legacy Blade partials and Alpine.js helpers instead of a modernised community feed, while the SPA lacks moderation queue dashboards and notification hubs referenced in the roadmap.【F:Web_Application/Academy-LMS/resources/js/app.js†L1-L24】【F:Web_Application/Academy-LMS/resources/js/modules/moderation/index.ts†L1-L40】
- Design tokens exist for the admin SPA, but comprehensive accessibility, localization, and theming still require completion to meet the design milestones.【F:Web_Application/Academy-LMS/resources/js/admin/ui/tokens.ts†L1-L120】

### Section 4 – Mobile App Status
- Provider-based state management remains across the mobile app, with REST calls made through `http` and session storage handled manually rather than via Riverpod/Dio abstractions.【F:Student Mobile APP/academy_lms_app/lib/providers/auth.dart†L1-L68】
- Community flows, push messaging, deep links, and Stripe-powered monetisation are not implemented; `pubspec.yaml` lacks required messaging/payments packages and the primary navigation still routes through legacy LMS tabs.【F:Student Mobile APP/academy_lms_app/pubspec.yaml†L1-L120】【F:Student Mobile APP/academy_lms_app/lib/screens/tab_screen.dart†L1-L120】
- Offline capabilities continue to rely on the bespoke `DatabaseHelper` cache; there is no Hive-backed feed cache or background sync queue to satisfy the parity goals.【F:Student Mobile APP/academy_lms_app/lib/providers/database_helper.dart†L1-L120】
- Community explorer, detail, onboarding, and feed experiences exist under `lib/features/communities`, but they rely on Provider + manual `http` clients; Riverpod/Dio refactors, offline-first caching, and richer engagement views remain on the roadmap.【F:Student Mobile APP/academy_lms_app/lib/features/communities/presentation/community_explorer_screen.dart†L1-L120】【F:Student Mobile APP/academy_lms_app/lib/features/communities/state/community_notifier.dart†L1-L120】

### Sections 5 & 10 – Security, Infrastructure, and Operations
- Security middleware now applies configurable headers, device/session telemetry, and admin audit logging, but WebAuthn, malware scanning, and secrets rotation automation are unfinished.【F:Web_Application/Academy-LMS/app/Http/Middleware/EnsureSecurityHeaders.php†L1-L120】【F:Web_Application/Academy-LMS/app/Http/Middleware/RecordAdminAction.php†L1-L72】
- Upload sanitisation still depends on Intervention Image and manual validation; ClamAV scanning, signed URL expirations, and storage lifecycle rules are tracked under Milestone E4.
- GitHub Actions cover backend and Flutter pipelines, yet IaC, deployment scripts, and installer parity (Nginx, Horizon provisioning) remain outstanding backlog items.【F:.github/workflows/ci.yml†L1-L160】【F:tools/Start_Up Script†L1-L200】

### Sections 6 & 7 – Analytics and Admin Enhancements
- Community admin SPA surfaces insights placeholders and queue manifests but requires real analytics ingestion, charting, and automation job orchestration before milestones D2/D3 can close.【F:Web_Application/Academy-LMS/resources/js/modules/communities/views/CommunityInsightsView.vue†L1-L160】
- RBAC improvements, scheduled digests, and automation jobs are partially represented via contracts/runbooks; enforcement tooling and UI remain incomplete.

### Section 8 – Search
- Meilisearch integration is implemented via service bindings, sync commands, and observers, yet front-end typeahead, permission-aware search UI, and synonym curation dashboards still need to ship.【F:Web_Application/Academy-LMS/app/Domain/Search/Services/SearchQueryService.php†L1-L80】【F:Web_Application/Academy-LMS/app/Console/Commands/SyncSearchConfiguration.php†L1-L92】

### Section 9 – Messaging & Notifications
- Notification preference APIs, broadcast events, and mobile push routing helpers exist, but unified inboxes, segmentation, and provider orchestration are still planned work.【F:Web_Application/Academy-LMS/app/Http/Controllers/Api/V1/Community/CommunityNotificationPreferenceController.php†L1-L66】【F:Student Mobile APP/academy_lms_app/lib/services/messaging/push_notification_router.dart†L1-L40】
- Preference enforcement requires extension—current flows update per-community toggles without policy automation or analytics feedback loops.

### Sections 11–18 – Migration, Testing, and Governance
- Expand/contract strategies and rollback procedures are documented in `docs/upgrade`, and the `communities:quality-gate` command enforces schema/config health, but automated rehearsal pipelines remain a gap.【F:docs/upgrade/section_3_8_rollback_recovery.md†L1-L120】【F:Web_Application/Academy-LMS/app/Console/Commands/CommunitiesQualityGateCommand.php†L1-L214】
- PHPUnit feature tests, Dusk suites, and Flutter integration tests are configured in CI; coverage must broaden to hit the milestone quality thresholds and include load/security tooling.【F:Web_Application/Academy-LMS/tests/Feature/Community/CommunityFeedPaywallTest.php†L1-L92】【F:.github/workflows/flutter-ci.yml†L1-L120】
- Runbooks for seeding, search reindexing, incident triage, and rate-limit validation exist, but analytics/privacy governance artifacts are still light and need expansion to meet enterprise acceptance criteria.【F:docs/upgrade/runbooks/search-reindex.md†L1-L80】【F:docs/upgrade/runbooks/incident-triage.md†L1-L120】

### Overall readiness assessment
- The current stack delivers an LMS-oriented product rather than the community-centric experience detailed in the upgrade roadmap. Major foundational work is required across backend domain modeling, API surface, frontend/mobile experiences, security, analytics, and DevOps before the upgrade acceptance criteria can be met.

## Program Guidance Summary (February 2025)

- **Foundations in place:** Laravel 11 upgrade, community data model, service layer implementations, Meilisearch integration, Stripe webhook processing, Horizon automation, Vue admin SPA modules, and Flutter community flows are all represented in code and wired into CI where applicable.【F:Web_Application/Academy-LMS/routes/api.php†L57-L208】【F:Web_Application/Academy-LMS/app/Domain/Communities/Services/CommunityFeedService.php†L1-L77】【F:.github/workflows/ci.yml†L1-L160】【F:Student Mobile APP/academy_lms_app/lib/features/communities/state/community_notifier.dart†L1-L120】
- **High-priority gaps:** Licence removal/Nginx installer parity, branding cleanup, UI polish for public communities, analytics dashboards, push messaging orchestration, payments UI, and DevOps/IaC automation are still incomplete—see Section 7 grades for precise focus areas.【F:AGENTS.md†L1373-L1394】
- **Operational readiness:** Security headers, audit logs, queue autoscaling, rollback runbooks, and quality gates exist but require completion of WebAuthn, malware scanning, secrets rotation, load/security testing, and go-live rehearsal workflows before the platform can exit Milestone E.【F:Web_Application/Academy-LMS/app/Http/Middleware/EnsureSecurityHeaders.php†L1-L120】【F:Web_Application/Academy-LMS/app/Console/Commands/QueueAutoscaleCommand.php†L1-L120】【F:docs/upgrade/runbooks/search-reindex.md†L1-L80】
- **Next steps:** Use the Roadmap tables plus the Current Completion Snapshot to select work. Every PR should update Section 7 grades, attach evidence for the required quality checkers, and ensure `docs/upgrade/progress.md` mirrors the latest reality.【F:AGENTS.md†L1373-L1394】【F:docs/upgrade/progress.md†L9-L32】

### Completeness vs. Upgrade Goals
- Core LMS flows remain functional, but community-centric upgrade requirements (Sections 1–20) are largely unaddressed across schema, services, UI, security, analytics, and operations.
- Estimated readiness: <20% of targeted scope delivered. Foundational work (data modeling, API design, modular UI, mobile architecture, security hardening, observability, testing, and DevOps) must be established before feature development can proceed.
- Significant refactoring and platform investments are required: introduce modular domain boundaries, design event-driven services, adopt modern front-end/mobile stacks, implement security/compliance controls, and build CI/CD plus testing infrastructure.

