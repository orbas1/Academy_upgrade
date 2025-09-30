# CSS & User Interface Styling and Design Guide

## Overview
This guide documents the current styling conventions and the target upgraded design system for the Academy web application and Flutter mobile app. It covers layout scaffolding, component styling, states, responsive behavior, and accessibility treatments to ensure a cohesive experience across surfaces.

## 1. Web Application Styling

### 1.1 Current Styling Baseline
- **Framework mix**: Bootstrap 5 grid with legacy custom CSS under `public/assets/frontend/css`. Inline styles common in Blade templates for hero sections and course cards.
- **Color usage**: Primary `#2E5BFF`, secondary `#00C48C`, backgrounds `#F5F7FB`, but inconsistent contrast on banners (`#F1F6FF` text overlay `#FFFFFF`). Alerts reuse Bootstrap contextual classes without theme overrides.
- **Typography**: Google Fonts `Poppins` for headings (`700/600 weights`), `Open Sans` body (`400`). Font sizes hard-coded per template; limited use of CSS variables. Line heights vary (1.2–1.8) causing rhythm mismatch.
- **Spacing**: Reliance on Bootstrap utility classes (`pt-5`, `mb-4`). Section padding inconsistent due to nested containers. No vertical rhythm scale.
- **Buttons**: Bootstrap `.btn-primary` / `.btn-outline-primary` with custom gradient overrides on hero. Hover states lighten background but do not change border color. Focus outlines removed via `outline: none`.
- **Forms**: Default `.form-control` with square edges. Validation feedback uses Bootstrap `.invalid-feedback` but not triggered for AJAX submissions.
- **Cards**: Course cards use shadow `0 15px 25px rgba(46,91,255,0.15)`; admin widgets use flatter `0 3px 6px rgba(0,0,0,0.08)`. Border radius inconsistent (`6px`, `12px`, `20px`).
- **Navigation**: Sticky header with dark mode toggle absent; mobile nav collapses to hamburger but lacks animation. Active states rely on `.active` class color swap.
- **Tables**: Admin uses Bootstrap striped tables with minimal spacing. No row hover highlight. Actions appear as inline icons without tooltips.
- **Modals**: Default Bootstrap modals with static backdrop for destructive actions but without accent color alignment.
- **Responsive behavior**: Breakpoints follow Bootstrap defaults (`576/768/992/1200`), but hero images break on `lg` due to fixed heights.
- **Accessibility**: Skip links missing. Focus states suppressed. Text contrast occasionally below AA (CTA on hero: `#FFFFFF` on `#2E5BFF` meets AA for large text but not small body copy).

### 1.2 Upgraded Styling Vision
- **Design tokens**: Introduce CSS variables in `:root` for colors, typography, spacing, elevation, radii.
  - Colors: `--color-primary-500 #3056D3`, `--color-primary-600 #2544B0`, `--color-accent-500 #00B894`, `--color-surface-100 #F6F8FC`, `--color-surface-900 #0F172A`.
  - Typography scale: `--font-size-900 3.5rem`, `--font-size-800 2.75rem`, `--font-size-700 2rem`, `--font-size-600 1.5rem`, `--font-size-500 1.25rem`, `--font-size-400 1rem`, `--font-size-300 0.875rem`.
  - Spacing scale: `--space-100 4px`, `--space-200 8px`, `--space-300 12px`, `--space-400 16px`, `--space-500 20px`, `--space-600 24px`, `--space-700 32px`, `--space-800 40px`, `--space-900 56px`.
  - Elevation tokens: `--elevation-100 0 1px 2px rgba(15,23,42,0.08)`, `--elevation-200 0 6px 12px rgba(15,23,42,0.12)`, `--elevation-300 0 12px 24px rgba(15,23,42,0.16)`.
- **Global resets**: Adopt `@layer base` with modern CSS reset (Open Props or Tailwind preflight equivalent). Use container queries for layout adjustments beyond breakpoints.
- **Typography**: Switch to `Inter` for UI text (weights 400–700) and `Clash Display` for hero headlines (weights 500/600). Apply fluid typography (`clamp`) for responsive scaling.
- **Layout grid**: Introduce `max-width` containers (`1200px` standard, `1440px` admin analytics). Use CSS Grid for dashboards (auto-fit columns) and `flex` for navigation. Define content gutter tokens (`var(--space-700)` desktop, `var(--space-400)` mobile).
- **Buttons**: Build `.btn` variants with CSS custom properties for color transitions, focus rings, icon alignment. Provide `solid`, `outline`, `ghost`, `link`. Use `transition: background-color 160ms ease, box-shadow 160ms` and focus ring `0 0 0 3px rgba(48,86,211,0.35)`.
- **Forms**: Introduce component classes for text fields, selects, toggles. Provide inline validation icons, helper text, success state. Support `:disabled`, `:read-only`, `:focus-visible`. Group labels with `font-weight 600`.
- **Cards**: Standardize radius `var(--radius-lg 16px)` and `var(--radius-sm 8px)`. Use gradient top border for featured modules. Provide card header, body, footer structure with spacing tokens.
- **Navigation**: Build responsive `mega-nav` for admin with segmented controls, filter chips. Provide micro-interactions (underline animation) using `transform`.
- **Tables**: Convert to CSS Grid tables with sticky headers, zebra stripes `rgba(48,86,211,0.04)`. Provide row selection states, inline badges.
- **Modals, drawers**: Add slide-in drawers for filters. Apply `backdrop-filter: blur(6px)` and `box-shadow var(--elevation-300)`.
- **Dark mode**: Provide `data-theme="dark"` palette overrides using CSS variables. Ensure 4.5:1 contrast minimum.
- **Accessibility**: Re-enable focus outlines via `:focus-visible`. Add skip link anchored to `#main`. Provide `prefers-reduced-motion` adjustments.

## 2. Mobile App Styling (Flutter)

### 2.1 Current Styling Baseline
- **ThemeData**: Primary color `Color(0xFF2E5BFF)`, secondary `Color(0xFF00C48C)`, accent `Color(0xFFFFB74D)`. Typography uses `GoogleFonts.poppinsTextTheme()` overriding Material defaults.
- **Components**: ElevatedButtons with `RoundedRectangleBorder(borderRadius: BorderRadius.circular(12))`. FloatingActionButton standard circular. AppBar uses solid primary background, white text.
- **Spacing**: Magic numbers (`EdgeInsets.symmetric(horizontal: 24, vertical: 18)`) repeated. No centralized spacing scale.
- **Cards**: `Card(elevation: 4, shape: RoundedRectangleBorder(BorderRadius.circular(16)))`. Shadow color default black 0.2.
- **List tiles**: Use `ListTile` with icons tinted primary. Dividers default `Divider()`.
- **Forms**: `InputDecoration` with filled grey backgrounds, label style `FontWeight.w600`. Error color default Material red.
- **State styles**: Loading indicators default `CircularProgressIndicator`. No skeleton loaders.
- **Dark mode**: Not implemented; app locked to light theme.

### 2.2 Upgraded Styling Vision
- **Design system**: Introduce `ThemeExtension` for tokens.
  - Colors: `primary = Color(0xFF3056D3)`, `primaryDark = Color(0xFF1F3A8A)`, `secondary = Color(0xFF00B894)`, `warning = Color(0xFFF59E0B)`, `error = Color(0xFFDC2626)`, `background = Color(0xFFF8FAFC)`, `surface = Color(0xFFFFFFFF)`.
  - Radii: `Radius.small = 8`, `Radius.medium = 12`, `Radius.large = 20`.
  - Elevation: `ShadowLevel.low = BoxShadow(color: Color(0x1A0F172A), blurRadius: 8, offset: Offset(0,4))`, `medium` and `high` increments.
  - Spacing: `AppSpacing.xs = 4`, `sm = 8`, `md = 12`, `lg = 16`, `xl = 24`, `xxl = 32`.
- **Typography**: Use `TextTheme` with `DisplayLarge` for hero, `HeadlineMedium` for dashboards, `BodyLarge` for standard copy. Implement `GoogleFonts.inter()` base and `ClashDisplay` for hero headlines.
- **Component theming**:
  - `FilledButtonThemeData` with gradient backgrounds (primary to accent). Provide `ButtonStyle` with `MaterialStateProperty` for hover, focus, disabled.
  - `OutlinedButtonThemeData` uses 1.5px border, stateful color transitions.
  - `CardTheme` uses `shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20))`, `clipBehavior: Clip.antiAlias`, custom `shadowColor` from tokens.
  - `BottomNavigationBarTheme` with pill indicator `AnimatedContainer`, active icon tinted `primary`, label style `fontWeight.w600`.
  - `TabBarTheme` includes dynamic indicator width based on label size.
- **Dark mode**: Provide `ThemeMode.system` with `ColorScheme.dark` mapping (primary `0xFF93C5FD`, background `0xFF0F172A`). Use `FlexColorScheme` for variant generation.
- **Animations**: Standardize curves `Curves.easeOutCubic` and durations (fast 150ms, medium 240ms, slow 360ms). Provide shimmer skeletons using `shimmer` package.
- **Accessibility**: Ensure `MediaQuery.textScaleFactor` up to 200% supported. Provide `highContrast` color scheme variant.
- **Responsive layout**: Use `LayoutBuilder` breakpoints (`<600` compact, `600–1024` medium, `>1024` extended for tablets/desktop). Provide two-pane layout for tablets.

## 3. Component Library Inventory
- **Atoms**: Buttons, icon buttons, tags, badges, avatars, toggles, chips, progress indicators (linear/circular), tooltips.
- **Molecules**: Search bars with filter chips, user summary tiles, content cards (feed, course, event), stepper, breadcrumbs.
- **Organisms**: Dashboard hero (stats + CTA), analytics board (charts, filters), message composer, moderation queue list, forms (multi-step), onboarding wizard.
- **Templates**: Admin dashboard layout (sidebar + header + content grid), community feed layout, course detail layout, mobile bottom nav shell.

## 4. Interaction States & Micro-interactions
- **Hover**: Buttons lighten 8%, apply shadow level increase. Cards elevate from `--elevation-100` to `--elevation-200`.
- **Focus**: Distinct `outline` using `focus-visible` + accessible color. Provide `:focus-within` for form groups.
- **Active**: Buttons darken 12%, translate Y by 1px. Nav links show underline slide-in animation.
- **Disabled**: Lower opacity to 40% but retain contrast for text. Cursor `not-allowed`.
- **Feedback**: Provide toast component with status colors, icons, and ARIA live regions.

## 5. Asset Management & Delivery
- **CSS architecture**: Adopt layered CSS (`@layer base, components, utilities`). Use SCSS modules for composition with PostCSS autoprefixer. Purge unused styles with `@fullhuman/postcss-purgecss` configured via safelist.
- **Naming convention**: BEM-style classes for legacy compatibility; new components use CSS modules naming `component__element--modifier`.
- **Build pipeline**: Vite with PostCSS (Autoprefixer, cssnano). Generate critical CSS for landing pages, lazy load rest.
- **Iconography**: Use `Phosphor Icons` web + Flutter packages for consistency. Provide sprite sheet for web.

## 6. Governance
- **Design review**: Weekly design critiques with annotated Figma frames. Document accepted patterns in Storybook (web) and Widgetbook (Flutter).
- **Versioning**: Semantic version tokens (v1.0). Document changes in `docs/design/changelog.md`.
- **Accessibility audits**: Quarterly AXE scans + manual screen reader testing. Track issues in Jira board.

