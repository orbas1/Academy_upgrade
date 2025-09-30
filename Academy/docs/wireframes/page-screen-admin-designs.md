# Page Designs, Screens, and Admin Control Guide

## Overview
This guide provides a comprehensive inventory of page and screen designs across the web and mobile experiences, detailing the current implementations and the upgraded blueprint. It includes admin panel controls, data entry workflows, and full edit/insert logic for every surface.

## 1. Web Application Pages

### 1.1 Landing & Marketing Pages
- **Current**
  - Hero section: Static background image, CTA button linking to `/register`. Form overlay uses Bootstrap modal. No personalization.
  - Feature sections: Three-column cards describing course modules; minimal iconography.
  - Testimonials: Carousel with manual jQuery plugin; lacks accessibility controls.
  - Footer: Four-column list of links; newsletter form posts to Mailchimp embed.
- **Upgraded**
  - Dynamic hero: Background video (muted, looped) with fallback image; CTA segmented (Explore Communities, Start Trial). Personalization banner uses user geolocation + industry persona.
  - Feature sections: Four cards with iconography (Phosphor). Include hover micro-interactions and deep-link CTAs to relevant community verticals.
  - Social proof: Testimonial grid with video modals, star ratings, and dynamic review feed. Provide filters by persona.
  - Footer: Mega footer with product, company, resources, legal columns. Include social icons, locale switcher, trust badges.
  - Landing page settings: Admin can reorder sections, toggle modules, update copy via CMS (structured blocks) with preview mode.

### 1.2 Authentication & Onboarding
- **Current**
  - Login/Register modals with email/password fields, optional social logins disabled.
  - Password reset page with email input.
  - Onboarding limited to welcome email.
- **Upgraded**
  - Dedicated login page with left brand panel, right form card. Supports email, SSO (Google, Microsoft), and passwordless link.
  - Registration: Multi-step (Account → Profile → Preferences). Optional invite code input validated server-side.
  - Onboarding wizard: Video tour, community preference selection, notification opt-in, default communication channel selection.
  - Admin controls: Toggle required fields, define custom profile questions, manage invite quotas.

### 1.3 Community & Feed
- **Current**
  - Communities page: Static grid of community cards linking to placeholder.
  - Feed absent.
- **Upgraded**
  - Community discovery: Filterable grid with categories, membership badges, member count, growth trend sparkline.
  - Community detail: Tabs (Feed, Modules, Events, Members, Resources). Header banner with join/leave button, status indicator.
  - Feed: Infinite scroll list, composer at top with attachments (image, video, file, poll), scheduling panel. Reactions, comments, share actions, moderation flags.
  - Admin feed settings: Configure default sort, moderation rules, auto-moderation thresholds, pinned posts, feed segmentation.

### 1.4 Learning Modules & Resources
- **Current**
  - Course detail page with curriculum accordion, instructor bio, reviews.
  - Resource library accessible via `/resources` listing.
- **Upgraded**
  - Module overview: Combined course + community resources with progress tracker, recommended next actions.
  - Resource cards: Tagging, format labels, quick add to playlists.
  - Lesson player: Theater mode with chat sidebar, transcripts, download controls (if permitted). Support polls, Q&A, timestamps.
  - Admin: Create/organize modules, define prerequisites, manage access tiers.

### 1.5 Events & Calendar
- **Current**
  - Basic calendar page listing upcoming webinars; RSVP by linking to Zoom.
- **Upgraded**
  - Events hub: Calendar (month/week/list) with filters by community, type, location. Cards show RSVP status, capacity, waitlist.
  - Event detail: Agenda timeline, speaker bios, materials, add-to-calendar integration, host controls.
  - Admin: Create recurring events, manage check-in, post-event surveys, analytics (attendance, engagement).

### 1.6 Admin Dashboard & Settings
- **Current**
  - Dashboard: KPI cards (students, instructors, revenue) plus course table.
  - Settings: Tabs for General, Payment, Email; limited toggles.
- **Upgraded**
  - Dashboard: Customizable layout with widgets (Cohort Retention, Revenue, Moderation, Automation). Real-time data with auto-refresh.
  - Moderation center: Queue with filter chips, evidence viewer, action drawer, audit log.
  - Automation suite: Builder canvas with triggers, conditions, actions. Library of templates.
  - Settings: Multi-section (Organization, Branding, Authentication, Integrations, Billing, Legal). Each section has granular toggles, environment-specific configs.
  - Role management: Matrix view with permissions per role. Bulk assign via CSV import.
  - Admin controls: In-place editing with autosave, version history, preview mode.

## 2. Mobile App Screens

### 2.1 Member Experience
- **Current**
  - Home tab: Course cards list.
  - Course detail: Video player with tabs.
  - Profile: Basic details, toggle for notifications.
- **Upgraded**
  - Feed tab: Post cards, pinned highlight, quick actions row (Create Post, Event RSVP, Poll). Composer supports text, media, voice note, scheduled posts.
  - Learn tab: Modular track view, progress ring, recommended modules, offline downloads manager.
  - Events tab: Calendar with day agenda slider. Event detail provides RSVP, location map, join livestream.
  - Messages tab: Direct messages and group chats. Support reactions, attachments, read receipts.
  - Profile tab: Achievements, stats, membership badges, preferences, device management. Settings sheet for notifications, privacy, language.
  - Offline states: Cached posts, offline banner, retry controls. Provide skeleton placeholders.

### 2.2 Admin & Moderator Screens
- **Current**
  - No native admin interface; rely on webview.
- **Upgraded**
  - Admin dashboard: KPI carousel, moderation queue list, quick approve/reject actions with reason codes.
  - Automation alerts: List of triggered workflows with action status.
  - Member management: Search, filter, view profile, adjust roles, send DM, reset password.
  - Event management: Edit details, manage attendees, send announcements.
  - Settings: Manage branding assets (logo, accent color), toggle features per community, manage payment settings (Stripe connect), configure notification defaults.
  - Incident response: Alert center with severity badges, quick escalate action (call, email, Slack).

## 3. Insert, Edit, and Control Logic

### 3.1 Content Creation (Web & Mobile)
- **Composer**
  - Rich text editor with formatting toolbar (bold, italic, code, bulleted, numbered lists, mentions, inline media).
  - Attachment panel: Upload images (drag & drop), video, documents, polls, events, tasks. Media pipeline triggers virus scan, transcoding, thumbnail generation.
  - Scheduling: Choose publish time, recurrence, timezone. Queue display for pending posts.
  - Drafts: Auto-save every 10 seconds; drafts accessible from composer dropdown.
- **Moderation**
  - Pre-publish checks: Keyword filtering, link scanning. Provide warnings with override option for admins.
  - Post-publish: Report flow with categories, attachments (screenshots). Moderators see context, history.

### 3.2 Admin CRUD Interfaces
- **Community management**
  - Create community: Form sections (Basics, Branding, Access, Monetization, Automation). Live preview.
  - Edit community: Tabbed layout with autosave. Track unsaved changes, confirm navigation.
  - Delete/Archive: Requires reason, confirmation, optional export.
- **Member management**
  - Invite: Upload CSV, preview parsed entries, assign role, send invites.
  - Edit member: Adjust roles, add notes, assign tags, set onboarding status.
  - Bulk actions: Select multiple members, apply tag, send message, export data.
- **Event management**
  - Create event: Steps (Details, Schedule, Hosts, Access, Notifications). Support multi-session events.
  - Check-in: QR scan, manual mark, export attendance.
  - Post-event: Trigger follow-up automation, collect feedback.
- **Billing & Pricing**
  - Create tier: Set price, billing cycle, trial length, benefits, limited seats.
  - Promo codes: Define code, discount type, usage limits, expiration, segment eligibility.
  - Reports: Revenue analytics, churn, failed payments. Provide filters, export.

### 3.3 Settings Controls
- **Branding**
  - Upload logos, icons, hero images. Validate file type/size. Provide cropping tool.
  - Color pickers with contrast checkers. Provide presets.
  - Typography: Choose from approved font list or upload custom (with license acknowledgment).
- **Security**
  - Enable 2FA (TOTP, SMS, WebAuthn). Manage trusted devices.
  - Session policies: Idle timeout, concurrent session limits, geo-fencing.
  - Audit logs: Filter by actor, action, resource. Export to CSV.
- **Notifications**
  - Global defaults per channel (Email, Push, SMS, In-App). Allow per-community overrides.
  - Digest frequency settings, quiet hours, escalation paths.
- **Integrations**
  - Configure webhooks, Zapier, Slack, Stripe, Zoom. Provide test connection.
  - API keys management with scopes, expiration, IP restrictions.

## 4. Data Entry Validation & Error Handling
- **Form validations**: Inline + summary, regex checks, conditional logic (if monetization enabled → require Stripe account).
- **Error states**: Provide descriptive messages, recovery options, support link.
- **Autosave & versioning**: Save history, allow revert to prior version. Display timeline of changes with author.

## 5. Documentation & Review
- **Design specs**: Maintain Figma design kit with dev-ready redlines. Each component includes margin/padding, states, responsive notes.
- **Storybook/Widgetbook**: Document all screens with controls for states.
- **QA checklists**: Page-level checklists covering layout, accessibility, data, performance.
- **Release notes**: Log changes per module in `docs/release/page-designs.md`.

