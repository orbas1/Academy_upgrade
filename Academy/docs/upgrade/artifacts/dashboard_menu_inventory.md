# Dashboard, Menu, and Page Inventory (Expanded)

The sections below capture the current implementation that exists in the Laravel and Flutter codebases alongside the full list of gaps required to satisfy the Academy Community Upgrade specification in `AGENTS.md`. Each area is expanded with explicit missing work items to make remediation plans actionable.

## 1. Landing Top Menu

**Current implementation**

* Blade header renders a marketing-oriented navigation with Home, Courses (mega menu of categories), Bootcamp, Ebooks, and Find a Tutor links, plus global search, wishlist/cart counts, and the authenticated user dropdown leading to role dashboards.【F:Web_Application/Academy-LMS/resources/views/components/home_made_by_developer/header.blade.php†L25-L195】

**Missing requirements**

* Main nav lacks `/communities` entry points, deep links for community detail tabs (members, leaderboard, calendar, classroom, settings), and contextual promotions for tiers or featured communities described for the upgraded web routing model.【F:AGENTS.md†L503-L521】
* No notification bell, realtime presence indicator, or feature-flag aware menu items to expose the community experience called for in the acceptance criteria.【F:AGENTS.md†L551-L552】

## 2. User Dashboard Menus & Settings (Student & Instructor)

**Current implementation**

* **Student sidebar:** Provides navigation to My Courses, Bootcamps, Bookings, Teams, Ebooks, Profile, Wishlist, Messages, Purchase History, Logout, and a Become Instructor call-to-action, with device-aware logout link decoration.【F:Web_Application/Academy-LMS/resources/views/frontend/default/student/left_sidebar.blade.php†L24-L157】
* **Instructor sidebar:** Organised into collapsible groups for Dashboard, Course (manage/add), Bootcamp, Tutor Booking, Ebook, Sales, Payout, Blogs, and Manage Profile, reflecting legacy LMS responsibilities.【F:Web_Application/Academy-LMS/resources/views/instructor/navigation.blade.php†L17-L214】

**Missing requirements**

* Student experience has no menu surface for Communities Home, Joined vs Discover views, community inbox/notifications, leaderboards, streak trackers, or membership management demanded by the upgrade acceptance items (community feed, follow/following, levels, subscriptions).【F:AGENTS.md†L1001-L1028】
* Instructor menu does not expose community authoring, automation, analytics, or moderation tooling—no links for scheduling posts, managing levels/points, or community paywall setup as required for role-scoped control.【F:AGENTS.md†L720-L729】【F:AGENTS.md†L1030-L1033】
* Neither sidebar differentiates Owner/Admin/Moderator capabilities or surfaces settings for notification preferences, geo tools, or classroom sync mandated in the spec.【F:AGENTS.md†L718-L735】【F:AGENTS.md†L1009-L1021】

## 3. User Dashboard Content (Web)

**Current implementation**

* Profile dashboard emphasises account management: personal info editing, social links, taggable skills, biography, password changes, full TOTP two-factor setup (enable/disable, QR, recovery codes), trusted device/session management, and logout flows.【F:Web_Application/Academy-LMS/resources/views/frontend/default/student/my_profile/index.blade.php†L14-L204】【F:Web_Application/Academy-LMS/resources/views/frontend/default/student/my_profile/index.blade.php†L112-L200】

**Missing requirements**

* No aggregated community feed widget with filter chips (new/top/media/paid), followers/following insights, streaks, levels, leaderboard placement, or automation status required for the community profile upgrade.【F:AGENTS.md†L1001-L1027】
* Lacks Stripe membership management, geo map participation, classroom cross-posting, and analytics cards (contributions, conversion funnel) outlined in the requirements mapping.【F:AGENTS.md†L1007-L1015】【F:AGENTS.md†L1019-L1025】

## 4. Admin Dashboard Menu & Settings

**Current implementation**

* Admin sidebar covers Dashboard, Category, Course (manage/add/coupons), Bootcamp, Ebook, Student Enrollment, Payment Report (offline/admin/instructor/purchase), Users, Message center, Newsletter, Contacts, Blog, Knowledge Base, Settings (system/website/payment/language/live class/SMTP/S3/Certificate/Player/OpenAI/Home Builder/SEO/About), and Manage Profile.【F:Web_Application/Academy-LMS/resources/views/admin/navigation.blade.php†L18-L255】

**Missing requirements**

* Absent `/admin/communities` navigation hierarchy with Overview, Moderation Queue, Members, Levels & Points, Paywalls & Tiers, Geo Tools, Automation, and Settings tabs demanded for community operations.【F:AGENTS.md†L718-L729】
* No role-sensitive toggles for Owner/Admin/Moderator privileges or quick access to audit logs, automation status, or scheduled report downloads mandated by the ops specification.【F:AGENTS.md†L731-L753】

## 5. Admin Dashboard Content

**Current implementation**

* Dashboard summarises LMS KPIs (course/lesson/enrollment/student/instructor counts), admin revenue line chart, course status pie, and pending payout table.【F:Web_Application/Academy-LMS/resources/views/admin/dashboard/index.blade.php†L18-L166】

**Missing requirements**

* No real-time community metrics (online members, posts/minute, moderation queue depth), retention funnels, ARPU/LTV/Churn charts, automation health widgets, or CSV export tooling specified for upgraded analytics.【F:AGENTS.md†L685-L742】
* Lacks moderation queues, flag status, or health monitors expected to surface on the admin landing experience.【F:AGENTS.md†L722-L748】

## 6. Phone App Menu

**Current implementation**

* Flutter bottom navigation (ConvexAppBar) exposes Home, Communities (single explorer screen), My Courses, My Cart, and Account tabs with a floating filter FAB for course discovery.【F:Student Mobile APP/academy_lms_app/lib/screens/tab_screen.dart†L49-L216】

**Missing requirements**

* No dedicated navigation for Joined vs Discover communities, notifications center, calendar/events, subscriptions, or deep link aware entry points required by the mobile spec.【F:AGENTS.md†L605-L617】
* FAB is tied to course filtering only—missing quick actions for composing posts, joining live rooms, or accessing paywall tiers outlined in the upgrade roadmap.【F:AGENTS.md†L608-L616】

## 7. Phone App User Dashboard

**Current implementation**

* Account screen displays avatar, name, profile editing, wishlist, password update, account deletion, and logout tiles, loading persisted user data via `SharedPreferences` and Provider auth state.【F:Student Mobile APP/academy_lms_app/lib/screens/account.dart†L27-L256】

**Missing requirements**

* Absent community activity feed, streaks, levels/badges, subscription status, notification preferences, and device security review expected for the upgraded mobile dashboard.【F:AGENTS.md†L605-L617】
* No integration with presence, deep link session binding, or privacy controls to mirror the backend/community feature set.【F:AGENTS.md†L612-L617】

## 8. Profile Dashboard (Web)

**Current implementation**

* Shared student profile view (see section 3) provides personal info editing, security, and account tooling but no community context beyond LMS data.【F:Web_Application/Academy-LMS/resources/views/frontend/default/student/my_profile/index.blade.php†L14-L204】

**Missing requirements**

* Requires community activity timeline, filter chips (new/top/media/paid), followers/following counters, automation toggles, and contribution analytics aligned with the profile upgrade acceptance criteria.【F:AGENTS.md†L1001-L1027】
* Lacks controls for paywall subscriptions, classroom linkage, geo participation, and follow recommendations noted in the roadmap.【F:AGENTS.md†L1007-L1019】

## 9. Phone App Screens (Coverage)

**Current implementation**

* Flutter project ships screens for splash, auth (login/signup/verification), home, catalog/course details, cart, wishlist, search, filter, offline handling, meetings, file viewer, account, and a placeholder community explorer, as seen in the `lib/screens` directory list.【71e8e5†L1-L6】
* Community explorer leverages Riverpod-ish notifiers for listing communities, onboarding prompts, and membership actions but stops at list-level browsing without detail tabs.【F:Student Mobile APP/academy_lms_app/lib/features/communities/presentation/community_explorer_screen.dart†L19-L175】

**Missing requirements**

* No implementations for community detail tabs (Feed, About, Members, Leaderboard, Calendar, Classroom, Map, Settings), composer with media uploads, subscriptions, presence indicators, or notification center mandated in the spec.【F:AGENTS.md†L605-L617】
* Absent background uploads, offline caching, Stripe paywall flows, universal/deep links, and segmented notification channels outlined for mobile enhancements.【F:AGENTS.md†L618-L623】

## 10. Page Builder Artifacts

**Current implementation**

* Layout switches between permanent developer-made sections (top bar, header, footer) and builder HTML stored on `builder_page` records; includes fallback includes when builder modules (top bar/header/footer) are enabled.【F:Web_Application/Academy-LMS/resources/views/layouts/default.blade.php†L63-L103】

**Missing requirements**

* Builder lacks modules for community discovery carousels, membership tier callouts, activity feeds, analytics tiles, or upgrade-related hero units demanded by the new product positioning.【F:AGENTS.md†L503-L520】【F:AGENTS.md†L1001-L1024】
* No dynamic slots for leaderboard snapshots, map teasers, or automation highlights referenced in the roadmap, limiting marketing flexibility.【F:AGENTS.md†L520-L521】【F:AGENTS.md†L1009-L1021】

## 11. Styling Descriptions (Buttons, Forms, Cards, Icons, Typography)

**Current implementation**

* Frontend CSS defines gradient CTA buttons (`.eBtn`), radius utilities (10–24px), typography utilities, mega menu spacing, and responsive tweaks for header/search layouts.【F:Web_Application/Academy-LMS/public/assets/frontend/default/css/style.css†L220-L320】
* Backend/admin CSS sets Inter font stack, card styles (`.ol-card`), button variants, and elevation/hover states for dashboard widgets.【F:Web_Application/Academy-LMS/public/assets/backend/css/style.css†L340-L440】

**Missing requirements**

* No centralized design tokens for color, elevation, spacing, or component variants adhering to the Tailwind + shadcn/UI system mandated by the upgrade (radius 16–20px, neutral palettes, accessibility tokens).【F:AGENTS.md†L503-L544】
* Missing shared dark mode, focus states, WCAG-compliant states, and component coverage (NotificationBell, CommunityFeed cards) required across web/mobile surfaces.【F:AGENTS.md†L507-L520】【F:AGENTS.md†L523-L535】

## 12. Fonts

**Current implementation**

* Frontend bundles multiple weights/styles of Euclid Circular A via custom font-face declarations.【F:Web_Application/Academy-LMS/public/assets/frontend/default/css/custome-front/custom-fronts.css†L1-L99】
* Admin dashboard loads Inter family variants and applies them globally for backend UI elements.【F:Web_Application/Academy-LMS/public/assets/backend/css/style.css†L1-L104】

**Missing requirements**

* Spec requires harmonised typography tokens using Poppins/Inter (with Tailwind tokens) across web and mobile, meaning Euclid Circular usage must be replaced or supplemented for consistency.【F:AGENTS.md†L541-L544】
* No shared typography scale exported for mobile, nor fallback stacks for community-branded themes referenced in the upgrade guidelines.【F:AGENTS.md†L503-L544】

## 13. Website Pages

**Current implementation**

* Legacy Blade directories cover marketing and LMS flows: home, about, contact, blog, bootcamp, tutor booking, ebooks, FAQ, policy pages, and student dashboards.【F:Web_Application/Academy-LMS/resources/views/frontend/default/home/index.blade.php†L1-L40】【affff6†L1-L6】

**Missing requirements**

* No routes/templates for Communities list, detail tabs, leaderboards, calendars, classroom integrations, notification centers, or profile activity pages mandated by the community upgrade.【F:AGENTS.md†L503-L520】【F:AGENTS.md†L1001-L1024】
* Absent SEO-ready landing pages for tiers, automation explainers, or analytics highlights to support the new community product narrative.【F:AGENTS.md†L520-L521】【F:AGENTS.md†L1009-L1021】

