# Academy Platform Finalized Wireframes

This finalized companion document provides exhaustive logic maps, state inventories, and configuration references for every wireframe across web, mobile, Firebase, and security surfaces. Each section documents the **current experience** and the **post-upgrade experience** with full option coverage, branching logic, error states, integrations, and governance hooks. Use this as the authoritative specification when implementing UI flows, settings, and backend contracts.

---

## 1. Web Application Wireframes (Finalized)

### 1.1 Admin Dashboard Panel

#### 1.1.1 Current State — Full Logic Map
- **Entry Conditions**
  - Authenticated user with `role in {owner, admin}`; otherwise redirect to `/login`.
  - Session locale loaded from profile or defaults to `en`.
- **Global Layout**
  - **Header**: Logo (links to `/admin/dashboard`), search bar (courses, users, orders), notification bell (dropdown: system alerts, support tickets, update reminders), avatar menu (Profile, Billing, Settings, Logout), language selector (en, fr, es — triggers page reload with locale param).
  - **Left Navigation**: Dashboard, Courses, Classrooms, Students, Instructors, Orders, Coupons, Reports, Settings, Support. Hover reveals tooltip, active item highlighted.
  - **Keyboard Shortcuts**: `/` focuses search; `g c` jumps to Courses; `g r` to Reports.
- **Dashboard Canvas**
  - **KPI Tiles** (Total Students, Revenue, New Orders, Active Courses) with period dropdown (Today, 7d, 30d, Custom). Each tile toggled via settings drawer.
  - **Latest Enrollments Table**: Paginated list (20 per page) with columns (Student, Course, Timestamp, Status). Clicking row opens enrollment detail modal.
  - **Announcements Editor**: Rich text (bold, italic, lists, media embed). Buttons: Save Draft, Publish, Preview. Publish prompts confirmation modal with visibility scope (All users, Students only, Instructors only).
  - **Upcoming Live Classes** widget: Source from `meetings` table; CTA "Launch" (admins) or "View details".
- **Settings Drawer** (gear icon):
  - Widget toggles (KPI tiles, Enrollments, Announcements, Live Classes).
  - Default landing page (Dashboard, Reports, Courses).
  - Data range default.
  - Auto-refresh toggle (off by default, sets `autoRefreshInterval` to 5 min when on).
- **Data Tables** (Courses, Students, Orders):
  - Sorting (ascending/descending). Filtering chips (status, category, instructor).
  - Bulk actions: Export CSV, Delete, Assign Instructor. Confirmation modal for destructive actions with reason text area.
  - Empty state with CTA to create first record.
- **Reports**
  - Tabs: Sales, Enrollment, Engagement.
  - Filters: date picker, segment dropdown (All, Course, Instructor), format selector (Line, Bar, Table).
  - Export button triggers job; toast displays success/failure.
- **Settings Pages**
  - **General**: Site name, logo upload (PNG/JPG, max 2MB). Validation errors inline.
  - **Payment**: Gateway select (PayPal, Stripe), API keys, currency, tax rate.
  - **Email**: SMTP host, port, username, password, from name/email, test email button (modal input for recipient).
  - **Roles**: Table of users with checkboxes (Admin, Instructor, Student). Saves on submit, no granular permissions.
- **Error States**
  - API failures show toast + inline message.
  - Session timeout redirects to login with flash message.

#### 1.1.2 Upgraded State — Full Logic Map
- **Entry Conditions**
  - Authenticated user with granular permissions. RBAC matrix resolves capabilities (dashboard.view, moderation.manage, automation.edit).
  - Feature flags determine visibility of Beta features (e.g., AI Insights).
- **Global Layout**
  - **Header**: Global search with scope chips (All, Communities, Posts, People, Automations). Search results open overlay with keyboard nav.
  - **Health Indicators**: Inline status pills (Queue, Jobs, Errors). Clicking opens observability panel with logs.
  - **Quick Create Menu**: Dropdown (Post, Event, Automation, Tier, Announcement). Each option opens guided modal wizard with validation and preview.
  - **Notification Center**: Supports categories (System, Moderation, Revenue, Security). Inline actions (Acknowledge, Assign, Snooze).
- **Navigation Schema**
  - Accordion groups: Operations, Monetization, Engagement, Governance, Developer.
  - Each route annotated with required permissions and breadcrumbs.
- **Overview Dashboard**
  - KPI cards with dynamic metrics. Each card includes:
    - Metric selector.
    - Trend sparkline.
    - CTA "View Report" linking to analytics module with applied filters.
    - Alert thresholds (configurable per card).
  - **Funnel Visualization** with interactive segments (hover reveals counts, drop-off, recommended actions).
  - **Activity Heatmap** (weekday vs. hour). Clicking cell filters feed.
  - **Revenue Timeline** with overlays (campaigns, pricing changes).
  - **Cohort Retention** matrix with segmentation dropdown (plan tier, acquisition source).
  - **Automation Timeline** listing upcoming workflows; inline toggle to pause/resume.
  - **Health Alerts** widget summarizing incidents with severity, owner, due date.
- **Community Detail**
  - Tabs (Overview, Moderation, Members, Levels & Points, Paywalls & Tiers, Geo Tools, Automation, Settings, Insights, Files).
  - Each tab includes toolbars, filters, detail panels, event logs.
  - **Moderation**: Kanban + table hybrid, SLA timers, assignment workflow, canned responses, escalation to Security tab.
  - **Members**: Faceted search with saved filters, segment builder, CSV import with column mapper.
  - **Levels & Points**: Rule builder supporting triggers (post published, reply accepted, event attendance), conditions (role, tier, geo), actions (points, badge, DM).
  - **Paywalls**: Pricing matrix, plan dependencies, lifecycle events (trial start/convert, churn reasons). Integrations with Stripe, Paddle toggles.
  - **Geo Tools**: Map layers (communities, events, members). Privacy controls, audit log, version history.
  - **Automation**: Visual builder (IF/THEN branches). Supports manual runs, test mode, versioning, rollback.
  - **Settings**: Multi-level accordions (Access Control, Community Policies, Content Rules, Integrations, Notifications, Data Governance). Each field annotated with data type, validation, default.
  - **Insights**: AI summaries, trend anomalies, recommended actions with acceptance workflow.
- **Governance & Compliance**
  - **Audit Timeline**: Filterable feed with export, evidence attachments, signature workflow.
  - **Policy Center**: Manage policies (moderation, privacy). Track acknowledgements by staff. Renewals scheduled.
  - **Maintenance Planner**: Manage update packs, hotfixes, schema migrations (ties to update lifecycle).
- **Settings Expansion**
  - Security (SSO, OAuth, 2FA enforcement, session rules, IP ranges, AV scanning, secrets rotation schedule).
  - Data governance (retention policies, anonymization jobs, consent templates, data export endpoints, DSR workflow).
  - Integrations (search engine credentials, analytics sinks, CRM connectors, webhook signing keys, queue backends, AI provider keys).
  - Appearance (theme tokens, layout templates, custom CSS/JS with approval workflow, preview modes, publish schedule).
- **Error Handling & Resilience**
  - Centralized toast system with severity.
  - Retry modals for failed automation runs.
  - Offline detection fallback.
  - Observability panel linking to logs and metrics.

### 1.2 User Type Experiences (Web)

#### 1.2.1 Current State
- **Roles**: Instructor, Student, Guest.
- **Instructor**
  - Dashboard showing course KPIs, pending grading, student questions.
  - Left nav (Dashboard, My Courses, Assignments, Messages, Earnings, Profile).
  - Course detail pages with tabs (Overview, Curriculum, Students, Q&A).
  - Settings modal for profile, payout, notifications (email toggles).
- **Student**
  - Dashboard (progress summary, quick actions, upcoming events, recommendations).
  - Course pages with modules, progress, downloads.
  - Settings (profile, password, notifications, marketing opt-in, timezone).
- **Guest**
  - Marketing landing with feature highlights, pricing, testimonials, FAQ.
  - CTA to sign up; limited navigation.

#### 1.2.2 Upgraded State
- **Expanded Roles**: Owner, Admin, Moderator, Instructor, Member, Prospect, Analyst, Partner.
- **Owner/Admin**
  - Global dashboard aggregator with cross-community metrics.
  - Task center (compliance, billing, integration alerts). Accept/assign/reschedule actions.
  - Organization settings (profile, billing, SSO, domains, SLA windows, feature flags, sandbox environments).
- **Moderator**
  - Specialized moderation workspace with triage board, backlog filters, real-time updates, SLA countdown, assignment, canned responses, evidence viewer.
  - Training mode with sample cases.
- **Instructor**
  - Combined course + community management (feed composer, post scheduling, event management, revenue share tracking).
  - Analytics (engagement, response time, revenue breakdown, challenge participation).
  - Monetization settings (bundles, upsells, cross-post to newsletter).
- **Member (Student)**
  - Personalized home feed with content chips, pinned announcements, recommended challenges.
  - Rewards hub (points, badges, quests, redemption catalog).
  - Unified messaging center (DMs, announcements, automation, support). Message filters, search, read receipts.
  - Settings: privacy, notification matrix (channel × event), accessibility (themes, text size), device management, security (sessions, 2FA, recovery codes), data control (export, delete request).
- **Prospect**
  - Community teaser with interactive tier comparison, dynamic testimonials, upcoming events preview.
  - Request invite form with progress indicator, risk assessment (spam detection), captcha.
  - Post-submission status page with ETA, referral incentives.
- **Analyst**
  - Analytics workspace with dashboard builder, schedule exports, API key management, IP allowlist, SQL runner (read-only), BI connectors (Snowflake, BigQuery, Tableau).
- **Partner**
  - Access limited to assigned communities and monetization data. View-only analytics, lead capture exports.

### 1.3 Update Pack Management (Web)

#### 1.3.1 Current State
- Manual update list (versions 1.2 – 1.8).
- Actions per version: Download instructions modal, Upload zip (name validation), Verify checkbox, Apply button (runs script), manual backup reminder, progress indicator, completion message.
- Settings: email notification toggle, show beta versions.

#### 1.3.2 Upgraded State
- Lifecycle manager with catalog cards, filters, dependency graph, environment scoping.
- Version detail drawer (Overview, Change Log, Preflight Checklist, Assets, Rollback plan).
- Guided flow: select environment → run dry run → schedule maintenance → execute (automated backup, checksum verification, extraction, migration, health checks) → post-update validation.
- Governance: policy toggles (auto security hotfix, multi-approver), integration hooks (CI/CD, monitoring), audit timeline with signatures, rollback orchestrations.

---

## 2. Phone Application Wireframes (Flutter) — Finalized

### 2.1 Admin Phone Experience

#### 2.1.1 Current State
- **Login Flow**
  - Email/password, Forgot password (sends email), no MFA.
  - Session stored via shared preferences.
- **Dashboard**
  - KPI cards (Students, Revenue, Orders).
  - Tabs: Courses, Orders, Notifications, Profile.
  - Notifications list (push + email). Swipe to archive.
- **Courses Tab**
  - List view, filter by status, search by name. Row tap opens detail (metrics, curriculum, students).
- **Orders Tab**
  - Transaction list with status, filter by date.
- **Profile**
  - Edit profile, change password, toggle notifications, sign out.

#### 2.1.2 Upgraded State
- **Authentication**
  - Supports SSO, biometrics, OTP MFA, device enrollment.
  - Session scope selection (Prod, Staging, Dev).
- **Admin Home**
  - Real-time metrics (DAU/WAU/MAU, revenue, churn, queue size).
  - Alerts center with severity and assignment.
  - Quick actions (Approve member, Resolve flag, Schedule automation, Launch update).
  - Offline indicator, sync banner.
- **Navigation**
  - Bottom tabs: Overview, Communities, Moderation, Automations, Settings.
- **Communities Tab**
  - Cards with metrics, sync status, quick links (View feed, Members, Paywall).
  - Detail view replicates desktop tabs optimized for mobile (swipe between sections).
- **Moderation Tab**
  - Queue list with filters (severity, SLA, assigned to me).
  - Item detail: content, context, actions (approve, hide, warn, ban, escalate). Quick templates, policy references.
  - Batch actions via multi-select.
- **Automations**
  - Workflow list (status, next run). Create/edit wizard with conditions, actions, testing (simulate input, preview outputs), scheduling.
- **Settings**
  - Account (profile, security, devices, notifications).
  - Organization (policies, tiers, integrations, update management).
  - Support (contact, run diagnostics, submit feedback).
  - Dark mode toggle, accessibility options (text size, high contrast).
- **Error & Offline Handling**
  - Local cache, queue actions, conflict resolution modals.
  - Diagnostics log export.

### 2.2 User Phone Experience

#### 2.2.1 Current State
- **Roles**: Student.
- **Flows**
  - Onboarding slides, login, registration.
  - Home dashboard with enrolled courses, recommended content.
  - Course detail with lessons, downloads, quizzes.
  - Notifications center (course updates).
  - Profile & settings (notifications, downloads, logout).

#### 2.2.2 Upgraded State
- **Roles**: Member, Moderator, Instructor, Prospect.
- **Onboarding**
  - Adaptive journey by role (select persona, join codes, invite acceptance).
  - Consent screens (privacy, notifications, data use).
- **Home**
  - Personalized feed (posts, events, challenges). Filter chips, infinite scroll, reactions, comments, share.
  - Quick actions (Create post, Join live event, Redeem reward).
  - Leaderboard preview, upcoming events, quests.
- **Community View**
  - Tabs: Feed, Events, Members, Resources, Rewards.
  - Top-level actions vary by role (moderation queue for moderators, insights for instructors).
- **Messages**
  - Unified inbox (DMs, group chats, announcements). Search, pinned, mute threads, attachments, voice notes, reactions.
- **Events**
  - Calendar view, RSVP, add to calendar, join streaming, event chat, replay.
- **Rewards & Progress**
  - Points history, badges, quests, redemption catalog (gift cards, perks).
- **Settings**
  - Notification matrix (channel vs. event), privacy controls, security (2FA, devices, passkeys), accessibility (font, colors, animations), data (download, delete request), payment methods, subscription management.
- **Offline/Sync**
  - Content caching, background sync, conflict resolution, push resubscription.

---

## 3. Firebase Wireframes (Finalized)

### 3.1 Current State
- **Projects**: Single project with default app (`academy-mobile`), limited environments (prod only).
- **Authentication**: Email/password, Google provider, no custom claims.
- **Firestore**: Collections `users`, `courses`, `enrollments`, `messages`. Rules allow authenticated read/write with coarse checks.
- **Storage**: Bucket for course assets; simple path rules (`/courses/{courseId}/media`).
- **Functions**: Minimal HTTP triggers for notifications.
- **Analytics**: Default events (session_start, screen_view).
- **Remote Config**: Not utilized.
- **Monitoring**: Crashlytics enabled, no alert routing.

### 3.2 Upgraded State
- **Project Structure**
  - Separate projects (Dev, Staging, Prod) with shared config via `firebase.json`. IAM roles per environment.
  - Naming convention: `academy-{env}-{region}`.
- **Authentication**
  - Providers: Email/password, OAuth (Google, Apple, Microsoft), SAML, phone.
  - MFA enforcement for admins/moderators.
  - Custom claims (role, tier, feature flags, beta access, compliance status).
  - Session management (revocation, device metadata).
- **Firestore**
  - Collections: `communities`, `posts`, `comments`, `members`, `events`, `automation_runs`, `audit_logs`, `reward_catalog`, `moderation_queue`.
  - Document schema tables with fields, types, indexes (composite, array). Partitioning by community.
  - Security rules with granular checks (role-based, community membership, content visibility, paywall enforcement). Tests in Firebase Emulator Suite.
  - Automated backups & PITR configuration.
- **Storage**
  - Buckets per environment with lifecycle policies, signed URL enforcement, AV scan integration via Functions.
  - Metadata tags (communityId, role, visibility).
- **Cloud Functions**
  - Modules: moderation actions, automation workflows, analytics events, Stripe webhooks, push notifications, geo updates.
  - CI/CD deploy pipeline with canary releases, observability (Cloud Logging, Error Reporting).
- **Analytics**
  - Custom events (member_joined, post_published, challenge_completed, tier_upgraded, retention_checkpoint, automation_failed).
  - Audiences, funnels, conversions mapped to business KPIs.
- **Remote Config**
  - Feature flags, experiment variants, personalization.
- **Extensions & Integrations**
  - Extensions for SendGrid email, Stripe payments, Firestore trigger analytics.
  - BigQuery export for analytics, Data Studio dashboards.
- **Monitoring & Alerts**
  - Crashlytics alerts with on-call rotation.
  - Performance Monitoring thresholds and dashboards.
  - Security rules monitoring with anomaly detection.

---

## 4. Security Wireframes (Finalized)

### 4.1 Current Security Center
- **Access**
  - Located under Admin → Settings → Security.
  - Sections: Password policy (minimum length, complexity), session timeout (30 min default), login attempts (lockout after 5), IP blacklist manual list.
- **Audit Logs**
  - Basic table (user, action, timestamp). CSV export.
- **Compliance**
  - Links to privacy policy. Manual checkbox for GDPR consent log.
- **Incident Response**
  - Email support alias, no runbooks.
- **Infrastructure**
  - No CSP controls UI, SSL monitoring manual, webhook secrets static.

### 4.2 Upgraded Security Orchestration
- **Security Command Center Dashboard**
  - Widgets: Posture Score, Open Incidents, MFA Coverage, Active Sessions, Update Compliance, Vulnerability Scan status.
  - Filters: Environment (Prod, Staging, Dev), Severity, Owner.
  - Actions: Assign incident, Trigger scan, Export report, Schedule review.
- **Identity & Access**
  - MFA policies per role, passwordless options (WebAuthn), device trust management (list, revoke, risk score).
  - Session management (list active sessions, terminate, set max concurrency).
  - API keys & service accounts management, rotation reminders, scopes.
- **Policy Management**
  - Policy catalog (Security, Privacy, Moderation, Data retention). Each policy has versioning, approval workflow, attestation tracking, expiration alerts.
  - Control matrix mapping policies to controls and owners.
- **Threat Monitoring**
  - Real-time alerts feed (auth anomalies, AV hits, rate limit breaches, suspicious geo, DDoS signals).
  - Integrations: SIEM (Splunk), PagerDuty, Slack.
  - Runbook links per alert type.
  - Evidence collection panel (attach logs, screenshots, notes).
- **Vulnerability Management**
  - Scans (SAST, DAST, Dependency) with status, remediation owner, SLA countdown, CVSS score, fix instructions.
  - Exceptions workflow (request waiver, approve, review date).
- **Update & Patch Compliance**
  - Mirrors update pack lifecycle (applied vs. pending). Requires attestation and supporting evidence (screenshots, logs).
- **Data Governance**
  - DSR dashboard (access, delete, rectification). Workflow statuses, assignees, deadlines.
  - Data retention policies per entity with timers, purge schedules, legal hold overrides.
- **Incident Response**
  - Playbooks (Intrusion, Data Breach, Abuse, Availability). Steps with owners, timers, communication templates.
  - Post-incident review capture with lessons learned and action items.
- **Audit Center**
  - Comprehensive log explorer with filters, saved searches, export to SIEM, retention schedule, immutability settings.
  - Evidence locker (WORM storage) with tagging, chain-of-custody.
- **Settings**
  - CSP builder (directives, report-only toggle, fallback sources).
  - Security headers configuration (HSTS, COOP/COEP, referrer policy).
  - Bot mitigation (reCAPTCHA keys, rate limit thresholds, challenge settings).
  - Encryption controls (key rotation schedule, envelope encryption toggles, secrets vault integration).
  - Legal (DPAs, certifications, audit logs for attestations).

---

## 5. Cross-Surface Traceability & Governance

- **Traceability Matrix**: Each module includes requirement IDs linking to this finalized spec. Updates tracked via change log.
- **Versioning**: Document version stored with semantic version. Change history appended at bottom.
- **Approval Workflow**: Requires sign-off from Product, Design, Security, and Engineering leads. Status tracked in governance tool.
- **Implementation Hooks**
  - API contracts referenced via OpenAPI/GraphQL specs.
  - Telemetry requirements enumerated per flow (events, properties, timing).
  - Accessibility criteria (WCAG 2.2 AA) mapped per screen.

---

## 6. Change Log

- **v1.0.0 (Finalized)** — Derived from existing wireframes, expanded to include exhaustive logic, settings, and governance touchpoints for each application surface, Firebase integration, and security orchestration.
