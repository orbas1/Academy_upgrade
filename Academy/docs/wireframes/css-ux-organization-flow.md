# CSS & User Experience Organization and Flow Guide

## Overview
This document maps the user experience structure, layout flows, and CSS organization for both the current platform and the upgraded design system. It focuses on navigation hierarchies, layout grids, interaction logic, and responsive orchestration for the web app and mobile apps.

## 1. Web Application Experience Architecture

### 1.1 Current State
- **Navigation hierarchy**:
  - Top navigation: Home, Courses, Communities (links to placeholder), Pricing, Blog, Login/Register.
  - Secondary admin sidebar: Dashboard, Courses, Students, Instructors, Payments, Settings. Communities absent.
  - Footer replicates key links plus support email.
- **Information architecture**:
  - Landing page sections: Hero → Course categories → Featured instructors → Testimonials → Newsletter signup. Each section manually stacked using `.container` blocks.
  - Admin dashboard layout: Sidebar (fixed 280px) + content area using Bootstrap grid. Widgets arranged via `.row .col-md-6`.
- **Interaction flow**:
  - Enrollment: Landing page CTA → Login/Register modal → Catalog listing → Course detail → Checkout.
  - Admin content edit: Dashboard → Courses → Edit (tabbed: Overview, Curriculum, SEO, Pricing). Save reloads page.
- **CSS organization**:
  - `app.css` global, `custom.css` overrides, page-specific files under `assets/frontend/css/pages`. No component-level segmentation. Duplicated selectors (`.hero-banner h1` defined in multiple files).
  - Media queries appended per file; no mobile-first approach. Breakpoints tied to Bootstrap defaults.
- **Responsive behavior**:
  - On `md` screens, hero text centers, CTA stacks, but forms overflow due to fixed widths. Sidebar collapses to off-canvas requiring manual toggle.
- **User feedback**:
  - Success/error messages appear as inline alerts at top of form. No toasts. Loading states use spinner overlay with `position: fixed` but not accessible.

### 1.2 Upgraded Flow Vision
- **Navigation model**:
  - Primary nav with mega-menu for Communities (submenus: Overview, Memberships, Events, Resources).
  - Contextual header actions per role (Create Post, Manage Automation). Introduce profile menu with quick settings, theme switcher, locale toggle.
  - Admin global nav: Collapsible left rail (72px icon rail collapsed, 280px expanded) with section grouping (Operate, Grow, Govern). Secondary top bar for breadcrumbs and filters.
- **Information architecture**:
  - Landing page reorganized into narrative flow: Hero → Value Pillars → Product Surfaces (Web, Mobile, Admin) → Success Metrics → Testimonials → Pricing → FAQ → Footer CTA.
  - Admin dashboard: Modular workspace with draggable widgets, persistent filter drawer, timeline feed for alerts.
  - Community area: Feed → Modules (Lessons, Events) accessible via top tabs; filter chips for segmenting content.
- **Interaction flow**:
  - Enrollment: Multi-step flow with progress indicator (Select Plan → Account → Payment → Onboarding). Autosave form data, show stepper at top.
  - Admin edit: Inline editing within cards (click to edit) with autosave. Bulk actions accessible via selection bar.
  - Moderation: Alerts panel → Filter by severity → Inline decision with comment requirement → Audit log modal.
- **CSS organization**:
  - Adopt component-based architecture: `/resources/css/tokens`, `/resources/css/layouts`, `/resources/css/components`. Use ITCSS layering.
  - Use SCSS partials with `@use` for tokens. Each layout has corresponding Figma frame ID references in comments.
  - Introduce utility classes generated via PostCSS (e.g., `.u-flex-center`, `.u-gap-400`).
- **Responsive orchestration**:
  - Mobile-first media queries: `@media (min-width: 640px)`, `768px`, `1024px`, `1280px`.
  - Container queries to adjust card density: feed cards show metadata stack on mobile, inline on desktop.
  - Sidebar transitions: Off-canvas overlay on <1024px, persistent rail on ≥1024px. Use CSS transitions for width changes.
- **User feedback**:
  - Toast system anchored bottom-right with queue and auto-dismiss. Provide ARIA live region.
  - Skeleton loaders for dashboards, shimmer lines for text.
  - Form validation: inline with icon, color-coded border, accessible error summary at top.

## 2. Mobile Application Experience Architecture

### 2.1 Current State
- **Navigation hierarchy**:
  - Bottom navigation: Home, Courses, Categories, My Learning, Profile.
  - No dedicated Communities tab. Settings accessible via profile overflow.
  - Drawer menu on tablets replicates nav items.
- **Screen flows**:
  - Login → OTP (if enabled) → Home feed listing courses.
  - Course detail: tabs for Overview, Curriculum, Reviews.
  - Admin mobile: Hidden behind `/admin` route, uses webview rather than native screens.
- **State handling**:
  - Each screen fetches data on `initState`. No caching between tabs.
  - Error states show SnackBar with generic text.
- **Layout**:
  - Column-based, minimal use of `Sliver` widgets. Scroll performance impacted by nested ListViews.
  - Use of `Expanded` inconsistent; overflow on small devices.

### 2.2 Upgraded Flow Vision
- **Navigation model**:
  - Five-tab bottom nav: Feed, Learn, Events, Messages, Profile. Contextual FAB (Compose) appears on Feed and Messages.
  - Admin persona switcher accessible via Profile → Admin Console (native screens) with segmented controls.
  - Global search accessible via top app bar with hero search field, filter drawer slide-over.
- **Screen flows**:
  - Onboarding wizard: Welcome → Personalize interests → Choose communities → Notifications opt-in. Each step uses progress indicator and skip logic.
  - Feed: Cards for posts, pinned announcements, events. Pull-to-refresh, infinite scroll with keyset pagination.
  - Events: Calendar view (monthly) + list; support RSVP and add-to-calendar actions.
  - Admin dashboard mobile: KPIs carousel, moderation queue list with swipe actions, approvals sheet.
  - Offline flows: Provide offline banner, cached content view, retry button.
- **State handling**:
  - Adopt Riverpod for state management with `AsyncNotifier`. Use `StatefulShellRoute` for tab persistence.
  - Preload data via background isolates. Implement caching using `Hive` or `Drift`.
- **Layout**:
  - Use `CustomScrollView` + `SliverAppBar` for feed, `SliverGrid` for community discovery.
  - Define responsive breakpoints for tablets with two-pane layout (list + detail).
- **Feedback**:
  - Toast/snackbar patterns unified with design tokens. Inline validation on forms.
  - Provide success checkmark animations for completed actions.

## 3. Flow Diagrams & Logic Mapping
- **Navigation graph (web)**: Documented in Figma with nodes for Landing, Community Feed, Events, Admin Dashboard, Settings. Each edge annotated with access control (role: guest, member, admin).
- **State diagrams**:
  - Form submission: Idle → Editing → Validating → Error/Success. Add loading overlay with accessible status text.
  - Moderation queue: Pending → Under Review → Resolved (Approve/Reject) → Archived.
- **Journey mapping**:
  - Guest to Member: Landing → Pricing → Checkout → Onboarding → Community Selection → Feed Tour. Include tooltips guiding new features.
  - Moderator workflow: Alert → Queue → Review detail (media viewer, history) → Action → Audit log.

## 4. Governance & Documentation
- **CSS documentation**: Generate Storybook with controls and docs tab referencing tokens. Include guidelines for layout usage per breakpoint.
- **UX playbooks**: Maintain FigJam journey maps for new features. Capture heuristics review (Nielsen) for each release.
- **Accessibility**: Provide keyboard nav map, screen reader labels inventory, color contrast audit log.

