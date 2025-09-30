# Dummy Content, Placeholder Management, and Data Reset Guide

## Overview
This guide covers how dummy text, seed content, placeholders, and wipes are handled across the current platform and the upgraded system. It details admin controls for inserting, editing, and purging sample data across web and mobile experiences to support demos, QA, and onboarding.

## 1. Current Dummy Content Practices
- **Seed data**: Laravel seeders populate demo courses, instructors, and testimonials. No community-specific content. Text stored in English only.
- **Placeholders**: Landing page uses lorem ipsum blocks within Blade templates. Mobile app includes fallback copy within Dart widgets.
- **Media assets**: Default hero images stored in `public/uploads/demo`. No CDN integration. File naming inconsistent.
- **Reset controls**: Admin panel lacks dedicated reset options. Developers run artisan commands manually (`php artisan db:seed`).
- **User accounts**: Demo accounts manually created; passwords shared via email. No automated rotation.

## 2. Upgraded Dummy Content Strategy

### 2.1 Content Taxonomy
- **Content categories**: Communities, Posts, Events, Courses, Modules, Automations, Members, Announcements.
- **Locale support**: Provide base dummy content in English (en), Spanish (es), French (fr). Each locale has translations for major flows.
- **Persona-based copy**: Define personas (Creator, Community Manager, Instructor, Learner). Provide tailored copy segments for each persona’s experience.

### 2.2 Content Library
- **Central repository**: Store dummy content JSON in `/storage/app/dummy-content`. Structure by module (e.g., `feed/posts.json`, `events/calendar.json`).
- **Versioning**: Each file includes metadata (version, created_at, updated_by). Use Git for history; expose in admin UI.
- **Asset management**: Store images/videos in S3 `dummy-assets` bucket with lifecycle policy. Provide responsive variants.
- **Rich media**: Include sample polls, quizzes, attachments. Provide alt text and captions.

### 2.3 Generation & Rotation
- **Seeder scripts**: Artisan commands `academy:dummy:load`, `academy:dummy:reset`, `academy:dummy:rotate`.
  - `load`: Inserts baseline content for selected modules.
  - `reset`: Clears module data (respecting dependencies), reseeds defaults.
  - `rotate`: Swaps content sets (Seasonal, Industry-specific) without wiping user data.
- **Scheduling**: Cron job to rotate spotlight posts weekly for demo environments.
- **Randomization**: Provide template variables ({{first_name}}, {{community_name}}) resolved at load time using faker.

## 3. Admin Panel Controls
- **Dummy content dashboard**:
  - Overview cards showing modules seeded, last update, next rotation.
  - Toggle per module (Communities, Posts, Events, Automation). Each toggle shows record counts.
  - Preview pane with rendered sample data (web + mobile views).
- **Import/export**:
  - Upload JSON/CSV to update dummy content. Validate schema before commit.
  - Export current dummy dataset for external editing. Provide diff viewer.
- **Editing**:
  - Inline editor with markdown support, translation tabs, persona variants.
  - Bulk edit using spreadsheet-like grid (Handsontable integration).
  - Media picker for attaching images/videos. Provide cropping and caption fields.
- **Reset & wipe actions**:
  - Soft wipe: Archive dummy data (mark hidden) without deletion.
  - Hard wipe: Delete dummy records and associated media. Confirmation modal with double-entry of environment name.
  - Scheduled wipe: Configure automatic reset on environment teardown.
- **Permissions**:
  - Roles: `Content Demo Manager`, `QA Lead`, `Product Marketing`. Each has scoped permissions (view, edit, reset, approve).
  - Approval workflow: Edits require second reviewer before publish to demo environments.

## 4. Mobile App Integration
- **Remote config**: Fetch dummy content toggles via Firebase Remote Config. Allows enabling demo mode without app update.
- **Demo mode switch**: Profile → Settings → Enable Demo Content (requires admin role). Switch triggers data fetch and caches locally.
- **Offline handling**: Dummy content packaged in app bundle for offline demo. Provide asset manifest.
- **Reset flows**: Option to clear dummy data via Settings → Storage → Reset Demo Data. Prompts confirmation and restarts app state.

## 5. Data Isolation & Safety
- **Environment segmentation**: Dummy content available only in staging, demo, sandbox environments. Production requires explicit enablement and is audited.
- **Identifier namespaces**: Use prefixed IDs (`demo_community_001`) to prevent collisions. Ensure analytics filters exclude demo data.
- **Privacy**: No PII in dummy content. Media uses generated personas.
- **Audit logging**: Record actions (load, reset, edit) with user, timestamp, environment. Expose in admin audit log.

## 6. Automation & Tooling
- **CLI tools**: Provide `academy:dummy:status` to display counts, next rotation, errors.
- **API endpoints**: `/api/admin/dummy/preview`, `/api/admin/dummy/reset`, `/api/admin/dummy/export`. Secure via token + role.
- **CI integration**: On deploy to demo environment, run `academy:dummy:load --module=communities,feed` as part of pipeline.
- **Testing**: Automated tests ensure dummy content loads without validation errors. Provide contract tests for JSON schema.

## 7. Documentation & Governance
- **Playbook**: Document procedures for enabling/disabling dummy content, rotating sets, troubleshooting failures.
- **Change log**: Maintain `docs/dummy-content/changelog.md` tracking updates, owners, rationale.
- **Access control**: Quarterly review of demo content permissions.
- **Training**: Provide video walkthrough for sales and marketing teams on using dummy content scenarios.

