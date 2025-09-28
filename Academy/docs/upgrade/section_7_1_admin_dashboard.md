# Section 7.1 – Admin Dashboard Enhancements

## Information Architecture
- Navigation: `/admin/communities` index → detail view with tabs (Overview, Members, Moderation, Paywall, Analytics, Automation, Settings).
- Breadcrumbs reflecting community name and current tab; search bar across communities with filters by status, tier, owner.
- Responsive layout supporting 1280px desktop and 1024px tablet breakpoints.

## Feature Requirements
1. **Overview Tab**
   - KPI cards (Members, Online Now, Posts/Day, Comments/Day, Growth %, MRR, Open Flags).
   - Activity feed for admin actions, automation job statuses, upcoming events.
2. **Members Tab**
   - Paginated table with columns: Member, Role, Joined Date, Last Active, Tier, Flags, Actions (Promote, Demote, Suspend, Message).
   - Bulk actions: email cohort, export CSV, assign automation tags.
   - Inline drawer with member profile, notes, and audit history.
3. **Moderation Tab**
   - Queue of flagged posts/comments with filters by severity, reason, SLA remaining.
   - Bulk approve/reject, escalate to trust & safety, leave moderator notes.
   - Real-time presence indicator for online moderators.
4. **Paywall Tab**
   - Tier cards with pricing, benefits, active subscribers, churn rate.
   - Controls to adjust pricing (feature-flagged), manage Stripe products, and preview landing page.
   - Revenue chart with projections and delinquent payments widget.
5. **Analytics Tab**
   - Embedded Metabase dashboard (see Section 6.3) with RBAC-signed token.
   - Quick export buttons for PDF/CSV.
6. **Automation Tab**
   - Schedule overview (daily digest, weekly summary, leaderboard recalculation, cleanup jobs).
   - Manual run triggers with safeguards (double confirmation, concurrency lock status).
7. **Settings Tab**
   - Community metadata (name, slug, description, tags), privacy level (public/private/paid), onboarding checklist.
   - Webhook configuration (Slack, Discord, email digests) with health status.

## UX & Design System
- Follows design tokens defined in `design-system.md` (primary #1C64F2, success #10B981, warning #F59E0B, danger #EF4444).
- Uses component library built on Tailwind + Headless UI; virtualization via `vue-virtual-scroller` for large tables.
- Accessibility: WCAG 2.1 AA (focus states, ARIA for tabs, keyboard shortcuts for moderation actions).

## Technical Implementation
- Inertia + Vue 3 pages under `resources/js/Pages/Admin/Communities/*`.
- API endpoints namespaced `/api/admin/communities` secured by `AdminAccess` middleware.
- Server-side caching for KPI cards using Redis with per-community tags.
- GraphQL gateway optional for future multi-app reuse (documented but not required in v1).

## Testing Strategy
- Jest + Vue Testing Library for component tests (KPI card, member table interactions).
- Laravel feature tests ensuring RBAC enforcement and API shape.
- Cypress end-to-end tests for major workflows (moderation decision, paywall update, automation trigger).

## Rollout Considerations
- Beta release to internal admins with feature flag `admin.dashboard.v2`.
- Migration script to import legacy admin notes and moderation history.
- Collect feedback via in-app survey widget after 3 days of usage.
