# Branding, Landing Pages, and Visual Identity Guide

## Overview
This guide documents the current branding assets and defines the upgraded visual identity system across landing pages, typography, color schemes, buttons, cards, and logo management. It includes admin controls for configuring defaults, uploading assets, and enforcing consistency across web and mobile applications.

## 1. Brand Foundations

### 1.1 Current State
- **Logo usage**: Primary horizontal logo (blue text on white) used on web header and login screen. No dark-mode variant. Favicon uses outdated icon.
- **Color palette**: Primary blue `#2E5BFF`, secondary green `#00C48C`, accent orange `#FFB74D`, neutrals limited to grayscale `#1F1F1F`–`#F5F5F5`. No documented palette usage or tokenization.
- **Typography**: `Poppins` headings, `Open Sans` body. No brand typography guidelines, font files loaded via Google Fonts CDN.
- **Imagery**: Stock photography featuring classroom scenes. No illustration system or iconography guidelines.
- **Voice & tone**: Not documented; landing page copy mixes course-centric messaging with community hints.
- **Admin controls**: Branding settings limited to uploading logo (single slot) and specifying primary color hex.

### 1.2 Upgraded Identity System
- **Logo system**: Horizontal, stacked, and icon-only marks. Provide light and dark variants (SVG). Include safe area guidance (1x logo height). Define minimum size (24px icon, 120px horizontal) and clear space rules.
- **Color palette**:
  - **Core palette**: Primary `#3056D3`, Primary Dark `#1F3A8A`, Accent `#00B894`, Secondary `#F97316`, Neutral 900 `#0F172A`, Neutral 700 `#1E293B`, Neutral 500 `#64748B`, Neutral 300 `#CBD5F5`, Neutral 100 `#F8FAFC`.
  - **Extended palette**: Status colors—Success `#0EA5E9`, Warning `#F59E0B`, Danger `#DC2626`, Info `#3B82F6`.
  - Provide usage ratios (60% neutral, 25% primary, 10% accent, 5% highlight). Document accessible combinations.
- **Typography**:
  - Display: `Clash Display` (weights 500/600) for hero headlines and callouts.
  - Body: `Inter` (weights 400–700) for UI copy, `Inter 300` for captions.
  - Monospace: `IBM Plex Mono` for code snippets and data labels.
  - Provide typographic scale with `clamp` values for responsive sizing.
  - Document usage contexts (Display for hero, Headline for section titles, Body for paragraphs, Label for buttons).
- **Imagery & illustration**:
  - Establish brand illustration style (geometric gradients, abstract community motifs). Provide asset library in SVG.
  - Photography guidelines: Diverse communities, candid collaboration, consistent lighting. Provide color grading presets.
  - Iconography: Use Phosphor Icons with 1.5px stroke for outline set, filled variant for active states.
- **Voice & tone**:
  - Voice pillars: Empowering, Insightful, Approachable.
  - Tone adjustments: Excited for launches, Calm for support, Direct for compliance messaging.
  - Provide copy examples per surface (landing hero, feature highlight, error message).
- **Motion**:
  - Define motion principles: purposeful, subtle, supportive. Standard durations (fast 120ms, medium 200ms, slow 320ms). Use easing `cubic-bezier(0.4, 0, 0.2, 1)`.
  - Provide animation tokens for hero background gradients and card hover lifts.

## 2. Landing Page System

### 2.1 Current State
- Single landing page with static sections, minimal personalization. Form submissions handled via Mailchimp embed. No AB testing.

### 2.2 Upgraded Landing Framework
- **Section catalog**: Hero, Product Overview, Features, Community Showcase, Testimonials, Pricing, FAQ, CTA Banner, Footer.
- **Hero layout**: Split screen (copy vs. imagery), gradient background (primary 500 → accent 500). CTA button pair (primary solid, secondary outline). Social proof row with logos.
- **Feature section**: 3x2 grid with cards including icon, title, description, micro-CTA. Use CSS Grid with `repeat(auto-fit, minmax(240px, 1fr))`.
- **Community showcase**: Carousel showing community cards with stats. Provide filters (Industry, Region, Membership level).
- **Pricing section**: Toggle monthly/annual, display cards with plan details, CTA. Provide feature comparison table.
- **FAQ accordion**: Expand/collapse with smooth animation, arrow rotation.
- **Footer**: Multi-column with quick links, resources, policies. Include language selector, theme toggle.
- **Personalization**: Use geolocation and referral parameters to adjust hero copy and testimonial order.
- **Experimentation**: Integrate with LaunchDarkly for variant testing. Provide content management via CMS (Prismic or Sanity) with preview environment.

## 3. Component Styling (Buttons, Cards, Forms)

### 3.1 Buttons
- **Variants**: Primary solid, Primary outline, Secondary solid, Tertiary ghost, Icon-only.
- **States**: Default, hover, focus, active, disabled, loading. Document color, shadow, border, text changes per state.
- **Sizing**: Small (36px height), Medium (44px), Large (52px). Padding `0 var(--space-500)` for medium. Icon placement guidelines (8px gap).
- **Mobile**: Expand full width for primary CTAs. Provide min tap target 48px.
- **Admin controls**: Toggle gradient usage, set default radius, update copy style (sentence vs. title case).

### 3.2 Cards & Surfaces
- **Card types**: Feature card, Metric card, Feed card, Testimonial card, Pricing card.
- **Styling**:
  - Radius tokens (8px, 16px, 24px).
  - Shadow tokens tied to elevation.
  - Border accent using 3px top border for highlight.
  - Provide background overlays for hero cards (gradient overlay 0.8 opacity).
- **Content structure**: Header, meta, body, actions. Use spacing tokens for vertical rhythm.
- **Admin controls**: Manage card templates, reorder sections, update icon selection.

### 3.3 Forms
- **Layout**: Use two-column grid on desktop, single column on mobile. Align labels top-left, helper text below input.
- **Controls**: Text input, textarea, select, multi-select, toggle, radio, checkbox, slider, file upload.
- **Validation**: Provide inline error message, icon, border color shift to `--color-danger-500`. Provide success state with checkmark.
- **Accessibility**: Associate labels via `for`/`id`, provide `aria-describedby` for helper text.
- **Admin controls**: Create form templates, manage custom fields, set conditional logic, preview states.

## 4. Logo and Asset Management in Admin Panel
- **Current**: Single upload field for logo.
- **Upgraded**:
  - Asset manager with folders (Logos, Icons, Backgrounds). Support drag-and-drop, version history, alt text metadata.
  - Logo upload wizard: Upload light/dark variants, favicon, app icon (512px). Provide cropping and background removal tool.
  - Automatic asset optimization (WebP, AVIF) with fallback. Provide CDN URLs.
  - Theme preview: Show logos on dark/light backgrounds, header, footer, mobile splash.
  - Permissions: Restrict branding changes to `Brand Manager` role; require approval workflow.

## 5. Typography Controls
- **Font sourcing**: Integrate with Adobe Fonts/Google Fonts API. Allow custom font upload (WOFF2) with license acknowledgement.
- **Fallback stacks**: `"Inter", "-apple-system", "BlinkMacSystemFont", "Segoe UI", "Roboto", "Helvetica Neue", sans-serif`.
- **Scaling**: Provide slider to adjust base font size (14–18px) with preview. Save per community.
- **Line height & letter spacing**: Provide defaults per heading level; allow adjustments with guardrails to maintain readability.
- **Mobile typography**: Provide responsive `clamp` values to ensure readability on small screens.

## 6. Branding Governance
- **Brand board**: Central dashboard summarizing current assets, last update, responsible owner.
- **Approval workflow**: Submit brand change → Reviewer approves/requests edits → Publish with change log.
- **Audit log**: Track asset uploads, color changes, typography updates with timestamp, user, before/after preview.
- **Versioning**: Snapshot brand configuration per release. Allow rollback to previous version.
- **Guidelines distribution**: Auto-generate PDF/Notion export of brand guidelines for partners.

## 7. Mobile Branding Implementation
- **Splash screen**: Dynamic background gradient based on primary color. Logo scaling to 60% of width, fallback to icon for narrow devices.
- **App icon**: Provide layered vector assets for iOS/Android. Manage icon updates in admin panel with release preview.
- **In-app theming**: Bind colors and typography to theme extension tokens. Provide toggles for dark mode accent levels.
- **Branding sync**: On brand update, push config via remote config. Provide manual refresh option in app settings.

## 8. Landing Page Content Controls
- **Content blocks**: Manage hero copy, feature list, testimonials, case studies via CMS. Provide inline editing with preview.
- **Dynamic inserts**: Support personalization tokens ({{first_name}}, {{industry}}). Provide fallback content.
- **Localization**: Manage translations with language-specific assets. Provide translation memory.
- **SEO controls**: Edit meta title, description, OG tags per landing variant. Provide structured data snippets.

