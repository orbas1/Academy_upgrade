# Academy Platform Wireframes — Current vs. Upgraded States

This document captures textual wireframes and logic maps for the Academy platform across web, mobile, Firebase, and security layers. For each scope we document both the **current experience** (as-is) and the **post-upgrade experience** (to-be) with exhaustive option coverage, interaction states, and settings.

---

## 1. Web Application

### 1.1 Admin Dashboard Panel

#### 1.1.1 Current State Wireframe
- **Global Layout**
  - **Header Bar**: Logo (left), search box (global course/community search), notification bell (dropdown with latest alerts), profile avatar (dropdown with profile, settings, logout), language selector.
  - **Left Navigation Rail** (icon + label): Dashboard (default landing), Courses, Classrooms, Students, Instructors, Orders, Coupons, Reports, Settings, Support.
  - **Content Canvas**: Two-column grid.
    - **Column A (2/3 width)**: KPI tiles (Total Students, Revenue, New Orders, Active Courses); latest enrollments list (student avatar, course name, timestamp, status pill); announcements editor (rich text box with publish/save draft buttons).
    - **Column B (1/3 width)**: Upcoming live classes (date/time, host, CTA to join/launch), support tickets (count, link to table), quick links (Add course, Create coupon).
- **Dashboard Settings Drawer** (modal triggered from gear icon):
  - Toggle widgets (KPI tiles, enrollments, announcements, upcoming classes).
  - Data range selector (Today, 7 days, 30 days, Custom).
  - Default dashboard landing (Dashboard, Reports, Courses).
- **Data Tables** (Courses, Students, Orders):
  - Column headers with sort icons; filters row for status, date range, category.
  - Row actions (view, edit, disable/archive).
  - Bulk actions toolbar (select all, export CSV, delete, assign instructor).
- **Reports Page**:
  - Tabs: Sales, Enrollment, Engagement.
  - Charts: line chart (sales by month), bar chart (course completion), pie chart (payment gateway split).
  - Export dropdown (CSV, PDF) with date picker.
- **Settings Section** (current scope limited):
  - General: site name, logo upload.
  - Payment: payment gateway keys, currency, tax rate.
  - Email: SMTP host, port, sender name, test email button.
  - Roles: assign admin/instructor/student checkboxes per user (no granular permissions).

#### 1.1.2 Upgraded State Wireframe
- **Global Layout Enhancements**
  - **Header Bar**: Adds real-time health indicators (queue depth, online members), global search with scope chips (All, Communities, Posts, People), quick-create menu (post, event, automation).
  - **Left Navigation** (grouped sections with collapsible accordions):
    1. **Operations**: Overview, Communities, Members, Moderation Queue, Automation, Geo Tools.
    2. **Monetization**: Paywalls & Tiers, Revenue Reports, Coupons, Stripe Dashboard link.
    3. **Engagement**: Levels & Points, Content Scheduler, Notifications.
    4. **Governance**: Audit Logs, Compliance, Settings.
  - **Contextual Right Sidebar** (dynamic):
    - Displays AI insights (retention alerts, trending content), quick filters (role, status, cohort), collaboration notes.
- **Overview Dashboard Canvas** (three-column responsive grid):
  - **Top Row KPI Cards**: DAU/WAU/MAU, Conversion to First Post, MRR, Churn %, Net New Members, Queue Size; each card has dropdown to change metric, hover tooltip with definition, click to drill down.
  - **Middle Row**: Member Funnel Sankey chart, Activity Heatmap (day/hour matrix), Revenue Timeline (line + bar), Cohort Retention chart (select timeframe).
  - **Bottom Row**: Flags summary widget (counts by severity with "Review" CTA), Scheduled Automations list (status pill, next run, toggle), Health Alerts (auto-archive triggers, error rates) with acknowledge/dismiss actions.
- **Community Detail Page** (tabbed interface):
  1. **Overview Tab**: At-a-glance metrics, top posts, membership trend, paywall summary, map preview of geo boundaries.
  2. **Moderation Queue Tab**: Table with filters (type, reason, severity, reporter). Bulk actions (approve, hide, ban, escalate). Side panel preview with context, evidence attachments, policy links. Assign to moderator dropdown.
  3. **Members Tab**: Faceted search (role chips: Owner, Admin, Moderator, Member, Guest), status filters (Active, Pending, Banned), segments (Joined <30d, High Spend, Dormant). Row actions: promote/demote, DM, ban/unban, export, add notes. Bulk invite (CSV upload wizard), merge duplicates flow.
  4. **Levels & Points Tab**: Points rules table (trigger, base points, caps, automation triggers). Preview leaderboard (top 10). Simulate rule change modal (input scenario, preview results).
  5. **Paywalls & Tiers Tab**: Pricing matrix (tier, price, billing cycle, benefits). Controls for trials, coupons, upgrade/downgrade rules. Stripe session log viewer. Issue comp access modal (recipient, tier, expiration, reason).
  6. **Geo Tools Tab**: Map canvas with draw/edit polygon tools, import GeoJSON button, place list with radius settings. Privacy toggle (public vs members-only map). Audit log of geo edits.
  7. **Automation Tab**: Workflow cards (Scheduled Posts, Welcome DM, Auto-Archive). Each card expands to show triggers, conditions, actions. Builder with drag-and-drop steps (IF new member -> wait 1 day -> send DM). Calendar view of scheduled items.
  8. **Settings Tab**: Multi-section accordions covering join policy, visibility, moderation flags, profanity lists, media limits, join questions (custom fields with validation), webhooks (URL, secret, event types), integrations (Slack, Zapier, Salesforce), notification preferences, consent toggles, data retention policies.
- **RBAC Management**
  - Role matrix (Owner, Admin, Moderator, Analyst, Support). Checkbox grid for capabilities (community.manage, paywall.manage, moderation.review, analytics.view, automation.edit, geo.edit).
  - Device trust management (list of devices, revoke buttons), 2FA enforcement toggle per role.
- **Audit & Compliance Dashboard**
  - Timeline feed of actions (who, what, before/after snapshot). Filters (date, role, action type).
  - Export scheduler (CSV, JSON, WORM S3 sync status).
- **Settings Expansion**
  - **Security**: Password/2FA policies, OAuth providers, session length, IP allowlists, webhook signing keys, AV scanning toggles, rate limit settings, secret rotation reminders.
  - **Data Governance**: Retention windows per entity, anonymization schedule, consent templates, data export pipelines.
  - **Integrations**: Meilisearch endpoint configuration, analytics destinations, CRM, email providers, push keys.
  - **Appearance**: Theme builder (colors, typography), layout presets, custom CSS/JS with approval workflow.

### 1.3 Update Pack Management (Web)

#### 1.3.1 Current State Wireframe
- **Location**: Hidden under Admin → System Update → “Manual update” tab within settings.
- **Update Pack List**: Static table sourced from bundled folders (`Web_Application/Update pack/v1.2` through `v1.8`). Columns: Version (1.2, 1.2.1, 1.2.2, 1.2.3, 1.3, 1.4, 1.5, 1.5.1, 1.6, 1.7, 1.8), Release Date (text entry), File Name (`update_1.x.zip`), Notes (textarea with pasted changelog).
- **Actions per row**: Download instructions (opens modal with steps from `Update Instructions.txt`), Upload button (opens file picker for corresponding zip), Verify checkbox (manual confirmation after upload), Apply button (runs server-side script; currently triggers maintenance mode & runs migrations sequentially).
- **Logic Flow**:
  1. Admin selects target version row.
  2. Upload step requires matching zip name; validation only checks extension.
  3. Upon clicking Apply, modal warns about backups (manual). Progress bar shows extraction, file replace, cache clear, migration. Errors displayed inline; no rollback.
  4. Completion screen shows “Update success” message with reminder to clear browser cache.
- **Settings Drawer**: Toggle for email notification post-update, checkbox for “Show beta versions” (hidden by default).

#### 1.3.2 Upgraded State Wireframe
- **Location**: Admin → Governance → Platform Lifecycle → “Update Packs & Hotfixes”.
- **Update Catalog Grid**:
  - Cards for each release including historical packs (1.2 – 1.8) and future upgrade tracks.
  - Card fields: Version, Release channel (Stable/Beta/Hotfix), Compatibility (current schema level), Mandatory before upgrade flag, Release notes link, Pre-check summary (files affected, database migrations, downtime estimate).
  - Filters: channel, release year, dependency status (Required/Optional/Applied).
- **Version Detail Drawer**:
  - Tabs: Overview, Change Log, Preflight Checklist, Assets.
  - Overview lists prerequisites (backup snapshot, cron pause), target environments, automation scripts.
  - Preflight Checklist includes automated environment scan (storage write, queue worker status, maintenance window) with pass/fail icons.
  - Assets tab surfaces download links for `update_1.x.zip`, checksum (SHA-256), signed instructions PDF, rollback scripts, docker compose overrides.
- **Action Flow**:
  1. Choose environment scope (Production, Staging, Dev) → toggles per environment.
  2. Run “Dry Run” (simulated apply) capturing file diff summary and DB migration preview; output stored in audit log.
  3. Schedule maintenance window (datetime picker, duration) with stakeholder notifications (email, Slack, in-app) and optional reminder.
  4. Execute update: pipeline orchestrates backup snapshot, package verification via checksum, extraction to staging path, health checks, swap, DB migrations with transactional guard; errors trigger automated rollback.
  5. Post-update validation: smoke test checklist, telemetry ping, create release note entry, prompt to tag update with notes (who, why, risk level).
- **Settings & Governance**:
  - Policy toggles for auto-applying security hotfixes, requiring multi-approver sign-off, enforcing staging validation before production.
  - Integration hooks to CI/CD (GitHub Actions artifact import), cloud backups, and monitoring (Datadog status check).
  - Audit timeline capturing each step with user, timestamp, result, logs.

### 1.2 User Type Experiences (Web)

#### 1.2.1 Current State Wireframes
- **Roles Covered**: Instructor, Student, Guest (unauthenticated).
- **Instructor Dashboard**
  - Header with shortcuts (Create course, Live class, Messages).
  - Left nav: Dashboard, My Courses, Assignments, Messages, Earnings, Profile.
  - Main: Course performance cards (enrollment, completion rate), assignments needing grading, recent student questions.
  - Settings modal: profile info, payout method, notification toggles (email for questions/orders).
- **Student Dashboard**
  - Hero banner with progress summary (overall completion). Quick actions (Resume course, View calendar, Join live session).
  - Tiles: Enrolled courses (cards with progress bar and CTA), Upcoming events list, Certificates earned.
  - Right column: Announcements, Recommended courses, Support CTA.
  - Settings page: profile info, password change, notification toggles (email reminders, marketing opt-in).
- **Guest Landing**
  - Marketing hero, featured courses grid, testimonials, sign-up/login prompts.
  - No personalization; simple footer with links.

#### 1.2.2 Upgraded State Wireframes
- **Expanded Roles**: Owner, Admin, Moderator, Instructor, Member (student), Prospect (lead), Analyst.
- **Owner/Admin Web Experience**
  - Entry landing shows global dashboard (mirrors admin panel with cross-community metrics), alerts for compliance tasks, tasks due, integration health.
  - Settings hub (profile, organization profile, billing, SSO connections, domain management, feature flags).
- **Moderator Workspace**
  - Focused moderation queue board (Kanban columns: New, In Review, Resolved, Escalated).
  - Context viewer (post preview, history, reporter info). Actions: apply labels, request clarification, escalate to admin, mute user (duration slider).
  - Quick filters (community, severity, SLA timer).
  - Settings: notification routing (email, push, Slack), working hours, substitution rules.
- **Instructor Portal (Community-enabled)**
  - Hybrid view combining course management and community feed.
  - Tabs: Courses, Community Posts, Events, Analytics, Monetization (upsell packs).
  - Course tab: same as current plus community cross-post toggle, bundling, AB test module visibility.
  - Community tab: feed composer (post types: text, poll, challenge), quick templates, tag selection, schedule post.
  - Analytics tab: member engagement charts, cohort analysis, question response time, revenue share dashboards.
  - Settings: course-to-community mapping, content visibility rules, auto-responders.
- **Member (Student) Dashboard**
  - Personalized feed (communities joined) with filter chips (All, Courses, Challenges, Events, Announcements).
  - Right rail: leaderboard (levels, points, badges), upcoming events calendar, recommended posts.
  - Top nav: Feed, Courses, Messages, Events, Rewards, Settings.
  - Messages area: unified inbox (DMs, announcements, automation messages) with read/unread states.
  - Settings: notification matrix (email, push, in-app per event type), privacy controls (profile visibility, DM permissions), data download, device sessions, linked accounts, accessibility preferences (font size, theme).
- **Prospect Experience**
  - Community teaser page with sections gated behind sign-up (blurred content preview), tier comparison table, CTA to join free tier.
  - Request invite flow (multi-step form: profile info, goals, compliance questions, captcha).
- **Analyst Workspace**
  - Focus on reports with ability to save dashboards, schedule exports, connect BI tools (Snowflake, BigQuery) via secure tokens.
  - Settings: data access scopes, IP allowlist, API key management.

---

## 2. Phone Application (Flutter)

### 2.1 Admin Phone Experience

#### 2.1.1 Current State Wireframe
- **Login Screen**: Email/password fields, "Forgot password" link, remember me toggle.
- **Dashboard (Tabbed)**: Tabs for Overview, Orders, Students.
  - **Overview Tab**: KPI tiles (revenue today, new enrollments), notifications list.
  - **Orders Tab**: List with status badges, filter drawer (status, date range), detail screen (order info, refund button).
  - **Students Tab**: Search bar, list with avatars, quick actions (call, email, view profile).
- **Drawer Menu**: Profile, Settings (basic), Sign out.
- **Settings Screen**: Notification toggle, currency display preference, dark mode switch.

#### 2.1.2 Upgraded State Wireframe
- **Auth Suite**
  - Splash with brand + environment badge (dev/stage/prod).
  - Login options: Email/Password, SSO (SAML/OAuth), Passkey/WebAuthn fallback, biometric quick login.
  - Device enrollment screen (name device, trust toggle, view active sessions).
- **Admin Home** (bottom navigation: Home, Communities, Moderation, Automations, More).
  - **Home Tab**: Scrollable cards (Realtime KPIs, Alerts, Revenue trend mini-chart, Pending approvals). Quick action FAB (create announcement/post/event/automation).
  - **Communities Tab**: Card list with key stats, filter chip row (All, Trending, Needs Attention). Tap opens detail with mini-tabs (Overview, Members, Moderation, Paywall, Settings) adapted for mobile.
  - **Moderation Tab**: Queue list with swipe actions (approve, reject). Batch mode toggle. Detail screen with evidence gallery, comment history, action buttons (ban with duration picker, escalate, assign).
  - **Automations Tab**: Kanban list (Scheduled, Running, Paused, Error). Each automation card opens step editor (condition > action). Inline toggle to pause/resume.
  - **More Tab**: Access to Audit Logs, Reports, Integrations, Settings, Support.
- **Settings (Extensive)**
  - Account: profile, password/passkey, languages, timezone.
  - Security: 2FA setup, device management, IP restrictions, session timeout, notification for new login.
  - Notifications: per event type (moderation alerts, revenue, automation failures) across channels (push, email, SMS, Slack).
  - Data: export data request, retention policy view, consent records.
  - Integrations: configure Stripe keys, Slack webhooks, Zapier, Meilisearch endpoint (view-only), analytics destinations.
  - Appearance: theme, font size, haptics toggle.

### 2.2 User-Type Phone Experiences

#### 2.2.1 Current State Wireframes
- **Student App**
  - Bottom nav: Home, Courses, Downloads, Profile.
  - **Home**: Carousel (featured courses), continuing courses list, announcements card.
  - **Courses**: Grid with filter dropdown (category, level). Course detail with description, lessons list, enroll/continue button.
  - **Downloads**: Offline content list, storage indicator, delete button.
  - **Profile**: Progress stats, wishlist, settings (profile info, password, notification toggle, logout).
- **Instructor (Mobile)**
  - Similar base app with extra tab: Manage (course list with status, create course CTA), messages inbox.
- **Guest**
  - Onboarding screens, sign-in/sign-up prompts, limited browse of catalog.

#### 2.2.2 Upgraded State Wireframes
- **Member (Student) App**
  - Bottom nav: Feed, Courses, Events, Messages, Rewards.
  - **Feed**: Stories row (challenges, announcements), feed posts (text, media, polls), reaction bar, comment preview, CTA to join tiers when encountering gated post.
  - **Composer**: FAB with options (Post, Poll, Question, Challenge submission, Share resource). Scheduling, attach files from device/cloud.
  - **Events**: Calendar view (month/week), list of upcoming events, RSVP controls, add to device calendar, map view for geo-enabled events.
  - **Messages**: Tabs for Direct Messages, Channels, Automation inbox. Actions: mark unread, mute thread, escalate to support.
  - **Rewards**: Points tally, level progress bar, badges gallery, missions list (earn points by tasks). Claim reward flow (select reward, confirm, view redemption code).
  - **Settings**: Notification matrix, privacy (profile visibility, DM permissions, location sharing), accessibility (text size, high contrast, captions), connected services (Google, Apple, Slack), data export, delete account, session management, security (biometric lock, passcode).
- **Moderator Mobile**
  - Dedicated app mode accessible via role detection.
  - Tabs: Queue, Alerts, Members, Tools.
  - Queue: card stack UI with swipe actions, detail overlay (context, policy references, prior offenses). Bulk operations using multi-select.
  - Alerts: system-generated alerts (spam spike, queue SLA breach) with acknowledge/resolution controls.
  - Members: search and manage users, apply tags, view flags history, send quick messages.
  - Tools: canned responses, resource links, escalation flow to admin.
- **Instructor Mobile**
  - Tabs: Feed, Courses, Insights, Monetization, Settings.
  - Insights: charts (engagement, completion, revenue), export/share options.
  - Monetization: manage offerings, create bundles, price edits subject to `paywall.manage` permission.
  - Settings: community linking, automation toggles, notification preferences.
- **Admin/Owner Mobile (non-dashboard)**
  - Additional "Organization" section (billing overview, usage limits, seat management, feature flags) with approvals queue.
- **Prospect (Lead) Mode**
  - Controlled preview: limited posts (blurred), CTA to apply, tier comparison, testimonials, FAQ, compliance disclaimers.

---

## 3. Firebase Requirements Wireframes

### 3.1 Current State
- **Projects**: Single Firebase project per environment not fully defined; basic FCM usage for push notifications (mobile only).
- **Services Utilized**: Cloud Messaging (limited), Analytics (not instrumented), Authentication (not integrated), Firestore/RTDB (unused).
- **Console Navigation Wireframe**
  - Home → Cloud Messaging: topic list (General, Promotions), device token upload CSV.
  - Project Settings: app registration (Android/iOS), server key copy, no web app configured.
  - No alerting/dashboard customization.
- **Settings Coverage**
  - API keys stored manually, no environment separation, no IAM roles beyond owner.

### 3.2 Upgraded State
- **Project Structure**
  - Multi-project setup: `academy-dev`, `academy-staging`, `academy-prod` with App Distribution.
  - Apps registered: Web, Android, iOS (admin + member variants) with SHA fingerprints, APNs auth keys.
- **Services & Wireframes**
  - **Cloud Messaging**: Topics (Announcements, Moderation, Events, Revenue Alerts, Automations). Conditional segments (role, tier, activity). Notification composer templates with localization, scheduling, A/B tests. Delivery analytics dashboard.
  - **Authentication**: Identity provider list (Email/Password, Google, Apple, SAML via custom auth). User management view (search, disable, add custom claims for roles). MFA enforcement toggles per app.
  - **Firestore**: Collections (notifications_queue, realtime_presence, automation_jobs). Rules viewer showing security rules per collection.
  - **Remote Config**: Parameter groups (feature flags, UI copy, paywall experiments). Targeting (tier, platform, locale). Version history and rollback controls.
  - **Crashlytics**: Issue list, alerts, velocity chart, linked to Slack/Email.
  - **Performance Monitoring**: Traces dashboard, custom metrics (feed_load_time, post_publish_latency).
  - **App Check**: Provider selection (DeviceCheck, Play Integrity, reCAPTCHA Enterprise). Enforcement toggles per app.
- **IAM & Settings**
  - Role-based access (Owner, Admin, Developer, Support, Analyst). Service accounts per environment with limited scopes.
  - Audit logs, alerting via Google Cloud Monitoring, billing exports enabled.

---

## 4. Security Wireframes

### 4.1 Current Security Posture
- **Admin Security Panel**
  - Minimal settings page: enable/disable registration, set password policy (length only), view login history (basic list).
  - No visualization of rate limits, no incident workflow.
- **User Account Security Screen**
  - Change password form, show recent logins, logout all sessions button.
- **Infrastructure Diagram (textual)**
  - Single web server (Apache), MySQL, Redis. No WAF, no CDN, no AV pipeline.
- **Policies**
  - Manual secrets rotation, no compliance checklist, no automated scans.

### 4.2 Upgraded Security Wireframe
- **Security Command Center (Web)**
  - Dashboard cards: Auth Status (2FA adoption), Session Health, Rate Limit events, ClamAV queue, Incident timeline, Compliance checklist.
  - Tabs:
    1. **Authentication**: Manage MFA enforcement, WebAuthn registrations, session expiration policies, device trust list. Visual map of login locations (with suspicious highlight). IP allowlist editor with CIDR input and approval workflow.
    2. **Authorization**: RBAC matrix, permission diff viewer, recent policy overrides, role provisioning workflow (request → approve → activate).
    3. **Threat Protection**: Rate limit graph, bot detection status, firewall/WAF integration toggles, hCaptcha config, DDoS alerts.
    4. **Data Protection**: Encryption settings, data retention schedules, right-to-erasure requests queue, export requests log, PII field catalog with encryption status.
    5. **File Security**: Upload pipeline monitor (scan status, quarantine items list, manual review queue), media policy settings (size limits, allowed types, auto-delete schedule).
    6. **Compliance & Audits**: Checklist (SOC2, GDPR, FERPA) with tasks, document upload, attestation tracker, audit log export scheduling.
    7. **Incident Response**: Playbook builder, active incidents list (severity, owner, timeline), communication templates, post-mortem repository.
  - Settings: security alert routing (Email, SMS, Slack, PagerDuty), severity thresholds, automation toggles (auto-disable compromised accounts), secrets rotation scheduler with integrations (AWS KMS, HashiCorp Vault).
- **End-User Security Settings (Web & Mobile)**
  - Manage passkeys, TOTP, backup codes, session/device list (revoke), login notifications, connected apps, privacy controls, data download/delete account flow.
  - Security status indicator with checklist (Complete profile, Enable 2FA, Review devices).
- **Infrastructure Map Wireframe**
  - Layers showing WAF/CDN, load balancers, web app cluster, queue workers, background scanners, logging/monitoring stack (ELK, Prometheus/Grafana), security services (ClamAV, Vault), backup systems.
  - Control plane view: CI/CD pipeline with SAST/DAST gates, artifact signing, deployment approval steps.

---

## 5. Traceability Matrix (Wireframes to Requirements)

| Scope | Current Coverage | Upgrade Goals |
| --- | --- | --- |
| Web Admin | Basic LMS-focused metrics, limited settings | Full community ops, monetization, automation, compliance, geo, RBAC |
| Web User Roles | Instructor/Student only | Expanded roles with personalized workspaces and governance |
| Mobile Admin | Simple revenue/students view | Comprehensive operations, moderation, automation, security settings |
| Mobile Users | Course consumption | Community feed, events, rewards, moderation tools |
| Firebase | Minimal FCM usage | Multi-service setup with security, analytics, automation |
| Security | Basic toggles | Centralized security ops center with automation and compliance |

---

## 6. Wireframe Notation Legend

- **Tabs** represent segmented navigation within a screen.
- **Cards** denote modular widgets with individual settings.
- **FAB** indicates floating action button for quick creation.
- **Matrix/Grid** references permission or metric tables with interactive controls.
- **Queues** represent task/incident lists with state transitions.
- **Settings** sections are exhaustive, including toggles, selectors, thresholds, integration keys, and audit hooks.

