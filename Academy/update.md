# Academy Laravel + Mobile Community Upgrade – Full Technical Specification

**Scope:** End‑to‑end upgrade of Academy (Laravel) web app and connected Flutter mobile apps to add “Communities” (Skool‑style) with feeds, memberships, paywalls, gamification, calendars, classrooms integration, notifications, security hardening, and full UI/UX refinements. Includes database migrations, backend API, admin dashboards, real‑time services, file storage, search, analytics, and DevOps.

**Assumptions**

* Current stack: Laravel 9.x/10.x (PHP 8.2+), MySQL 8.x, Redis, Nginx, Horizon/Queues, Flutter 3.x (Dart 3), Firebase (FCM), S3‑compatible storage, Git.
* Current Academy LMS features: courses, lessons, classrooms, user profiles, roles/permissions, notifications, calendar, search.
* All new features must be **multi‑tenant aware** if applicable and respect existing RBAC.

---

## 0) Platform Upgrade & Hardening (Foundation)

1. **Laravel Core Upgrade**

   * Target Laravel 11 LTS (if feasible) and PHP 8.3.
   * Update deps via Composer; replace deprecated facades/helpers; adopt typed enums where useful.
   * Run full test suite + static analysis (PHPStan lvl 6+).
2. **Security Baseline**

   * Enforce HTTPS (HSTS), Content Security Policy, Referrer Policy, COOP/COEP/CORP, X‑Frame‑Options, X‑Content‑Type‑Options, X‑XSS‑Protection (legacy), Permissions‑Policy.
   * CSRF on all stateful endpoints; SameSite=Lax/Strict cookies; Rotate APP_KEY per procedure.
   * Rate‑limit auth + write endpoints (Throttle: Redis). IP allowlist for admin.
   * Input validation with Form Requests; output escaping; upload MIME sniffing + ClamAV.
   * Secret management via dotenv + cloud KMS; no secrets in Git.
3. **Performance**

   * Cache: Redis for config/routes/views; model caching for heavy lookups.
   * Horizon queues for notifications, media processing, webhooks.
   * Octane (RoadRunner/Swoole) optional; page cache for public community pages.
4. **Observability**

   * Centralized logs (JSON) with context (request_id, user_id); OpenTelemetry traces; error reporting (Sentry/Bugsnag); audit trails for admin actions.

---

## 1) Data Model & Database Migrations (Communities & Social)

**New Tables (core):**

* `communities` (id, slug, name, tagline, bio, about_html, banner_path, avatar_path, links JSON, category_id FK, visibility enum[public, private, unlisted], join_policy enum[open, request, invite], geo_bounds GEOMETRY NULL, created_by, updated_by, created_at, updated_at).
* `community_categories` (id, slug, name, description, icon, order).
* `community_members` (id, community_id, user_id, role enum[owner, admin, moderator, member], status enum[active, pending, banned, left], joined_at, last_seen_at, is_online bool, points int default 0, level int default 1, badges JSON).
* `community_posts` (id, community_id, author_id, type enum[text, image, video, link, poll], body_md, body_html, media JSON, is_pinned bool, is_locked bool, visibility enum[community, public, paid], paywall_tier_id FK NULL, like_count, comment_count, share_count, created_at, updated_at).
* `community_comments` (id, post_id, author_id, body_md, body_html, parent_id NULL, like_count, created_at, updated_at).
* `community_likes` (id, likeable_type, likeable_id, user_id, created_at).
* `community_follows` (id, follower_id, followable_type [community|user], followable_id, created_at).
* `community_leaderboards` (id, community_id, period enum[daily, weekly, monthly, alltime], snapshot_date, data JSON, created_at).
* `community_levels` (id, community_id, name, min_points, perks JSON, color, icon, order).
* `community_points_rules` (id, community_id, event enum[post, comment, like_received, login_streak, course_complete, assignment_submit], points int, daily_cap int NULL, metadata JSON).
* `community_admin_settings` (id, community_id, settings JSON) // moderation, profanity, media limits, auto‑pin, join questions, etc.
* `community_geo_places` (id, community_id, name, description, lat, lng, geo JSON).
* `community_subscriptions` (id, community_id, user_id, tier_id, status enum[active, cancelled, past_due], trial_ends_at, current_period_end, created_at, updated_at).
* `community_subscription_tiers` (id, community_id, name, slug, price_cents, currency, interval enum[month, year], benefits JSON, is_default bool).
* `community_paywall_access` (id, post_id, user_id, granted_by enum[tier, single_purchase, admin], expires_at NULL, created_at).
* `community_single_purchases` (id, post_id, user_id, price_cents, currency, provider enum[stripe], provider_ref, status enum[paid, refunded], created_at).
* `notifications` (extend if needed) to include community event types.
* `search_index` (if using Meilisearch/Elastic, external service index definitions rather than tables).

**Foreign Keys & Indexes:**

* Composite unique on (`community_id`,`user_id`) in `community_members`.
* Fulltext/Meili indexes on posts/comments body.
* Partial indexes for recent activity (created_at DESC), popularity (like_count DESC).

**Migrations:** deliver as versioned, idempotent migrations with down() safe guards; seeders for categories, default levels, points rules.

---

## 2) Backend (Laravel) – Domain, Services & API

**Namespaces & Layers**

* Domain models: `App\Models\Community\*`
* Services: `App\Services\Community\*` (MembershipService, FeedService, PointsService, LeaderboardService, GeoService, SubscriptionService, PaywallService, NotificationPublisher)
* Policies & Gates: granular abilities (view/join/post/moderate/manage_paywall/manage_levels).
* Events & Listeners: `PostCreated`, `CommentCreated`, `MemberJoined`, `PointsAwarded`, `PaymentSucceeded`, etc.
* Jobs/Queues: `DistributeNotification`, `GenerateLeaderboardSnapshot`, `TranscodeVideo`, `ScanMediaForMalware`, `ReindexSearch`.
* Realtime: Laravel WebSockets or Pusher channels (`community.{id}`, `user.{id}.notifications`).

**API Endpoints (REST + minimal GraphQL optional)**

* `/api/v1/communities` CRUD (list, create, update, delete [owner/admin])
* `/api/v1/communities/{id}` (view; includes about, admins, counts)
* `/api/v1/communities/{id}/members` (list, invite, approve, roles, ban)
* `/api/v1/communities/{id}/feed` (GET; pagination; filters: newest, top, media, admin_pins)
* `/api/v1/communities/{id}/posts` (POST create; supports text, images, video, polls; visibility)
* `/api/v1/posts/{id}` (GET view; PUT update [author/admin]; DELETE)
* `/api/v1/posts/{id}/comments` (GET; POST create comment; nested threads)
* `/api/v1/like` (POST/DELETE for post or comment)
* `/api/v1/follow` (POST/DELETE follow community/user)
* `/api/v1/communities/{id}/levels` (CRUD)
* `/api/v1/communities/{id}/leaderboard` (GET current + snapshots)
* `/api/v1/communities/{id}/points‑rules` (CRUD)
* `/api/v1/communities/{id}/geo` (CRUD places; map bounds)
* `/api/v1/communities/{id}/calendar` (GET merged events; POST create community events)
* `/api/v1/communities/{id}/classroom‑links` (GET/PUT mapping courses↔communities)
* `/api/v1/communities/{id}/subscriptions/tiers` (CRUD)
* `/api/v1/communities/{id}/subscriptions/checkout` (POST; return Stripe payment intent / checkout URL)
* `/api/v1/paywalls/access` (GET check; POST grant for admins)
* `/api/v1/search` (global search: communities, posts, users; dashboard + landing)
* `/api/v1/notifications` (GET feed; mark‑as‑read)
* `/api/v1/profile/activity` (GET contributions/followers/following; privacy settings)

**Search Integration (Req. #3)**

* Laravel Scout + Meilisearch (preferred) or Elastic.
* Indexed entities: Community, Post, Comment, User.
* Synonyms & facets for categories; query suggestions; typo tolerance.

**Calendar Integration (Req. #9)**

* Community events appear in user dashboard calendar; ICS export; reminder notifications.
* Mapping: community events ↔ classroom sessions (optional linkage).

**Classroom Integration (Req. #10)**

* `classrooms` ↔ `communities` pivot; learning events feed into community updates.
* Completion events award points (rule‑based); class announcements mirror to community.

**Notifications (Req. #8)**

* Types: new post/comment/like, mentions, membership approvals, paywall purchase, leaderboard wins, calendar reminders.
* Channels: in‑app (web & mobile), email, push (FCM/APNs), realtime sockets.
* Digest emails daily/weekly; granular notification settings per user.

**Payments & Paywalls (Req. #24)**

* Stripe Checkout + Billing for tiers & single purchases.
* Webhooks: `invoice.paid`, `customer.subscription.updated`, `charge.refunded` → update `community_subscriptions`, grant access.
* VAT handling, invoices, proration, trials, coupons.

**Media Uploads (Req. #29)**

* S3 storage; pre‑signed URLs; chunked uploads for video; serverless transcode hook or queue.
* Thumbnails, image optimization; virus scan; size & type limits from admin settings.

**Maps (Req. #19)**

* Store `geo_bounds` (POLYGON) for community coverage; `community_geo_places` for points/regions.
* Map tiles via Mapbox or Google; privacy‑friendly toggles.

**Online Presence & Last Seen (Req. #16)**

* Heartbeat via websockets; last_seen_at updates; presence channels (`presence-community.{id}`).

**Leaderboards & Levels (Req. #20–22)**

* Recalculation cron (daily) + live increments on events.
* Levels unlock perks (posting limits, badges, access to premium threads, coupons).

**Profiles & Activity (Req. #25–26)**

* New profile tabs: Activity, Followers, Following, Contributions (posts, comments, courses completed, badges).
* Track streaks; privacy controls.

---

## 3) Frontend (Web) – UI/UX & Components

**Design System (Req. #35)**

* Tailwind + shadcn/ui. Dark‑mode ready. Accessible (WCAG 2.1 AA).
* Components: CommunityCard, FeedComposer, PostCard, CommentThread, MemberList, AdminPanel, LevelBadge, LeaderboardTable, SubscriptionCTA, MapPanel, CalendarWidget, NotificationBell.

**Pages & Flows**

* **Dashboard Communities Block (Req. #1, #4):**

  * "My Communities" list, quick actions (post, view feed, manage).
  * Embedded feed: aggregated from joined communities, filterable (new/top/media/paid).
  * Global search input (users, communities, posts) with suggestions (Req. #3).
* **Community Detail (Req. #7, #34):**

  * Banner, avatar, name, tagline, bio, links; follow/join/subscribe CTA; online count.
  * Tabs: Feed, About, Members (Req. #15), Levels & Leaderboard (Req. #20–22), Calendar (Req. #9), Classroom (Req. #10), Geo (Req. #19), Settings (admins; Req. #21, #31, #32).
* **Composer (Req. #36):** text, images, video, polls, visibility (public/community/paid), post to multiple communities (if allowed), schedule, attachments; preview & markdown helper.
* **Notifications (Req. #8):** icon with unread badge; panel & full page; per‑type filters.
* **Subscriptions:** tier cards, comparison, checkout; manage billing; paywall unlock on success.
* **Admin Dashboard (Req. #30–33):** moderation queue, member management, analytics (DAU/WAU/MAU, posts/day, retention, revenue), settings (levels, points, paywalls, geo, profanity list, blocked words, media limits).

---

## 4) Mobile (Flutter) – App Integration (Req. #12–14, #27)

**Architecture**

* State management: Riverpod/Bloc. Offline cache via Hive/SQLite. Dio for HTTP. WebSocket channel for realtime.
* Push notifications: Firebase Messaging + background handlers.
* Auth: secure storage of tokens, refresh flow.

**Screens**

* Communities Home: joined & discover; global search.
* Community Detail: tabs (Feed, About, Members, Leaderboard, Calendar, Classroom, Map, Settings* if admin).
* Feed & Composer: text/image/video uploads; visibility selector; markdown preview.
* Members (Req. #15–18): list with avatars, roles (admin), online indicator (presence), joined date.
* Leaderboards/Levels (Req. #20–22): badges; progress to next level.
* Subscriptions: tiers, Stripe Checkout via webview/SDK, paywall gates.
* Profile Upgrades (Req. #25–27): Activity, Followers, Following, Contributions; follow/unfollow; streaks.

**Mobile‑specific**

* Lazy media loading; background uploads; retry queues.
* Deep links to posts/communities; universal links.
* iOS/Android notification channels (replies, mentions, system).

---

## 5) Security (Req. #11) – Detailed Controls

* **HTTP Security Headers** (Nginx + Laravel middleware):

  * `Strict-Transport-Security: max-age=63072000; includeSubDomains; preload`
  * `Content-Security-Policy` default-src 'self'; img-src 'self' data: https:; media-src https:; script-src 'self' 'unsafe-inline' 'unsafe-eval' cdn.*; style-src 'self' 'unsafe-inline' fonts.googleapis.com; font-src 'self' fonts.gstatic.com; connect-src 'self' wss: https:; frame-ancestors 'none'; base-uri 'self'.
  * `Referrer-Policy: same-origin`
  * `Cross-Origin-Opener-Policy: same-origin`
  * `Cross-Origin-Embedder-Policy: require-corp`
  * `Cross-Origin-Resource-Policy: same-site`
  * `X-Frame-Options: DENY`
  * `X-Content-Type-Options: nosniff`
  * `Permissions-Policy: geolocation=(self), camera=(), microphone=()`
* **AuthN/Z**: Password hashing Argon2id; 2FA (TOTP/WebAuthn); email verification; device sessions; RBAC via Policies. Admin SSO optional.
* **Data**: Encrypt PII columns at rest; signed URLs for media; redact logs; GDPR export/delete tools.
* **Uploads**: AV scan, image re‑encode, EXIF strip; quota per user/community.
* **Payments**: PCI scope minimized (Stripe‑hosted); webhook signature verification; anti‑fraud checks.
* **Abuse/Moderation**: profanity filter; spam score; rate limits; shadow bans; report/appeal workflow; audit logs.

---

## 6) Analytics & Tracking

* Event taxonomy: community_join, post_create, comment_create, like_add, follow_add, paywall_purchase, subscription_start, calendar_add, classroom_link_click.
* Dashboards (admin): cohort retention, funnel to first post, ARPU, churn, LTV by community, content performance heatmaps.

---

## 7) Admin & Ops (Req. #30–33)

* **Admin Dashboard**: moderation queue, member actions (promote/demote/ban), content flags, revenue reports, level/points configuration, geo tools (draw polygon, add places), bulk invites, join questions.
* **Metrics**: members, online, posts, comments, DAU/WAU/MAU, MRR by tier; export CSV.
* **Automation**: scheduled posts; auto‑archive inactive threads; auto‑messages to new members.

---

## 8) Search (Req. #3) – Web + Dashboard

* Unified search bar with entity chips; fuzzy matching; category facets; recent searches; keyboard nav.
* API returns mixed results with highlighting; infinite scroll.

---

## 9) Email & Push Messaging

* Templated emails for invites, approvals, replies, mentions, payment receipts, reminders.
* Batch digests; unsubscribe granularity; internationalization.
* Push: rich notifications with deep links; action buttons (like/reply).

---

## 10) DevOps & Environments

* **Nginx** vhost with security headers; gzip/brotli; WebSockets upstream.
* **Queues**: Horizon with supervisor; autoscaling workers.
* **Storage**: S3 buckets (`community-media`, `avatars`, `banners`); lifecycle policies.
* **CI/CD**: lint, test, Dusk E2E, build Flutter (Android/iOS), upload to stores/TestFlight; env promotion gates.
* **Secrets**: `.env` keys for Stripe, FCM, Mapbox, Meili, WebSockets.

---

## 11) Migration & Backfill Plan

1. Ship migrations behind feature flags.
2. Seed categories, default points rules, base levels.
3. Backfill memberships from classroom enrollments (optional rule).
4. Incremental rollout to beta community; monitor errors; enable for all.
5. Data migration scripts for legacy profile → new activity model.

---

## 12) Testing Strategy

* Unit tests for services (points, leaderboards, subscriptions, permissions).
* Feature tests for API endpoints with policy checks and rate limits.
* Dusk E2E for feed, compose, paywall, notifications, calendar, classroom links.
* Load tests (k6) for feeds and notifications.

---

## 13) Acceptance Criteria Mapping to Requirements

1. **Add communities like Skool**: new domain models, dashboards, feeds, categories, search, memberships.
2. **Skool Category**: `community_categories` model + UI filter & seeder.
3. **Search**: Scout + Meili; dashboard + homepage inputs w/ suggestions.
4. **Community Feed in Profile Dashboard**: aggregated feed widget w/ filters.
5. **Community updates**: posts/comments/likes with realtime.
6. **Update posts comments & likes**: CRUD + soft delete + edit windows.
7. **View community in full**: detailed page w/ tabs and counts.
8. **Notifications icon**: bell with unread badge; panel & page; push.
9. **Calendar integration**: community events into user calendar + ICS.
10. **Classroom integration**: pivot mapping; mirrored announcements; points on completion.
11. **Full security**: headers, CSRF, rate limits, uploads scan, RBAC, logging.
12. **Integrate into phone app**: Flutter screens + APIs + push + sockets.
13. **Phone app community area**: communities home & discover.
14. **Phone app community feed**: full feed + composer + media.
15. **View community members**: list, roles, search, sort.
16. **Show as online**: presence channels + heartbeat.
17. **Show when joined**: `joined_at` field display.
18. **Show community admins**: members with role badges.
19. **Community maps**: geo bounds + places + map UI.
20. **Leaderboards & levels**: points rules, levels, snapshots, badges.
21. **Community settings**: naming, levels, points, moderation, media.
22. **Leaderboard points**: defined rules + daily caps.
23. **About sections**: About/Links editable rich text.
24. **Subscriptions & paywalls**: Stripe tiers + single‑content unlocks.
25. **Profile upgrade & activity**: followers/following, contributions, streaks.
26. **My profile trackers**: counters; pages; privacy.
27. **Integrate into Flutter each level**: show levels, badges, progress.
28. **Community following**: follow/unfollow entities.
29. **Video/picture uploads + text**: composer + S3 + transcode.
30. **Community admins**: roles, permissions, UI badges.
31. **Admin settings**: full admin panel.
32. **Admin dashboard**: moderation, analytics, revenue.
33. **Tracking counts**: followers, following, admins, member count, online.
34. **Community profile**: picture, banner, name, tagline, bio, links.
35. **UI/UX**: responsive, accessible, dark mode, modern components.
36. **Write something in feed**: composer present across web/mobile.

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
  $t->foreignId('category_id')->nullable()->constrained('community_categories');
  $t->enum('visibility', ['public','private','unlisted'])->default('public');
  $t->enum('join_policy', ['open','request','invite'])->default('open');
  $t->geometry('geo_bounds')->nullable();
  $t->foreignId('created_by');
  $t->foreignId('updated_by')->nullable();
  $t->timestamps();
});
```

---

## 15) Example Policies (Abbrev.)

* `CommunityPolicy@view`: public OR member.
* `CommunityPolicy@post`: member & not banned; if paywalled, must have access.
* `CommunityPolicy@moderate`: admin/moderator.

---

## 16) Example .env Additions

```
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=https://meili.internal
MEILISEARCH_KEY=...
STRIPE_KEY=...
STRIPE_SECRET=...
STRIPE_WEBHOOK_SECRET=...
PUSHER_APP_ID=...
PUSHER_APP_KEY=...
PUSHER_APP_SECRET=...
PUSHER_HOST=ws.orbas.io
PUSHER_PORT=443
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=eu-west-1
AWS_BUCKET=academy-community
MAPBOX_TOKEN=...
FCM_SERVER_KEY=...
```

---

## 17) Nginx Security Snippet (Abbrev.)

```
add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload" always;
add_header X-Frame-Options DENY;
add_header X-Content-Type-Options nosniff;
add_header Referrer-Policy same-origin;
add_header Cross-Origin-Opener-Policy same-origin;
add_header Cross-Origin-Embedder-Policy require-corp;
add_header Cross-Origin-Resource-Policy same-site;
add_header Permissions-Policy "geolocation=(self)";
```

---

## 18) Rollout Plan (Web + Mobile)

* Phase 1: Backend APIs + Web Admin (internal beta)
* Phase 2: Web user feed & composer; limited community
* Phase 3: Flutter beta (TestFlight/Play Internal) with communities
* Phase 4: Payments & paywalls enabled
* Phase 5: Full release; marketing & guides; monitoring & A/B tests

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
