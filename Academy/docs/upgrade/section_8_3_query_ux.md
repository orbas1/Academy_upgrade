# Section 8.3 – Query UX

## Overview

Section 8.3 raises the Meilisearch-backed discovery experience from a raw query endpoint to a polished, enterprise-ready search journey. The deliverable covers responsive filters, facets, intelligent typeahead, synonym-aware highlights, and accessibility-first ergonomics spanning web and mobile clients. It is designed to accommodate the new communities, posts, members, and resource entities shipped in prior tranches while remaining modular enough to support future domains such as classrooms or geo hubs.

## Experience Architecture

### Search Entry Points

- **Global Spotlight (`cmd+k`)** – modal launcher available across the application shell with keyboard support, debounced search, and result grouping by entity type.
- **Page-scoped search bars** – persistent search inputs on Communities Home, Posts, Members, and Admin consoles that pre-seed filters relevant to the context.
- **API consumers** – partner integrations can call `/api/v1/search` with the same query model; rate limits and API keys are enforced by the existing gateway middleware.

### Facets & Filters

| Domain | Facets | Filters |
| --- | --- | --- |
| Communities | Category, language, visibility (public/private/paid), location (geo hash), member count bucket | Joined status, owned status, subscription tier availability |
| Posts | Community, author role, content type (text/media/event), tags, recency bucket | Visibility scope, has attachments, monetization state |
| Members | Role (admin/moderator/member), expertise tags, timezone, join date | Verification status, online now, badges held |
| Resources (events, classrooms) | Resource type, start date, duration, capacity | RSVP state, delivery mode (in-person/virtual) |

Filters are encoded as `filter[]` query params (REST) and `filters` objects (GraphQL/future) and normalised into the Meilisearch filter syntax by the Search API layer.

### Typeahead & Ranking

- **Predictive suggestions** are generated after 150 ms of idle time with a minimum of two characters, using lightweight prefix indexes maintained by `SearchSuggestionService`.
- **Adaptive ordering** combines Meilisearch relevance, domain-specific boosts (e.g., communities with higher engagement), and personalised scores derived from membership history.
- **Keyboard-first navigation** supports arrow key traversal, `enter` to open primary action, and `cmd+enter` to open details in a background tab (web) or preview modal (mobile).

### Highlighting & Snippets

- The API returns `highlights` per hit with HTML-tag-safe wrappers (`<mark data-variant="search">`).
- For posts, snippets include context sentences around matches with markdown stripped server-side to prevent layout shifts.
- For members, highlight badges and expertise tags rather than email addresses to avoid sensitive data exposure.

## Implementation Details

### Backend (`Web_Application/Academy-LMS`)

1. **Controller & Route** – Introduce `App\Http\Controllers\Search\QueryController` handling `GET /api/v1/search` with request objects per resource.
2. **Request Validation** – `SearchRequest` ensures pagination, sort keys, and filter payloads adhere to whitelists, preventing injection into Meilisearch filters.
3. **Search Service** – `App\Domain\Search\Services\QueryService` orchestrates multi-index searches, merges results, applies boosts, and formats highlights.
4. **Suggestion Service** – Maintains prefix indexes in Redis/Meilisearch, updates from ingestion jobs, and hydrates spotlight suggestions.
5. **Policies** – Authorisation enforced via `SearchVisibilityPolicy` to redact private communities, paywalled posts, or members hidden by privacy settings.
6. **Response Transformer** – `App\Http\Resources\Search\SearchResultResource` standardises JSON across entity types with `type`, `id`, `attributes`, `highlights`, and `actions` arrays.

### Frontend (Web)

- **Composable search module** built with Vue 3 + Pinia (per front-end modernization roadmap) housed under `resources/js/modules/search`.
- **Components**:
  - `SearchInput.vue` – debounced input with ARIA roles and mobile-friendly clear buttons.
  - `SearchFacetDrawer.vue` – responsive sheet presenting filters, supporting multi-select, range sliders, and saved filter sets.
  - `SearchResultsList.vue` – virtualised list (Vue Virtual Scroller) with dynamic card templates per entity type.
  - `SearchSpotlight.vue` – global command palette triggered by keyboard shortcuts; uses Headless UI Dialog for accessibility.
- **State Management** – `useSearchStore` tracks query, filters, pagination, results, loading/error states, and persists recent searches in IndexedDB for offline recall.
- **Theming** – extends design tokens defined in Section 3.8 to include search-specific elevation, focus outlines, and highlight colors.
- **Internationalisation** – integrates ICU message files for filter labels, synonyms, and empty states, supporting RTL layouts through logical properties.

### Mobile (Flutter)

- New module `lib/features/search` with Riverpod providers for query state, results, and debounced suggestions.
- Widgets include `SearchBar`, `FacetChips`, `ResultListView`, and `SavedSearchTile` using Flutter’s adaptive design patterns.
- Utilises the shared API client (`dio` + `retrofit`) with interceptors to inject auth tokens, locale, and privacy context headers.
- Supports offline caching of last queries via Hive and displays network/state transitions with shimmer placeholders.

## Observability & Telemetry

- Emit `search_query`, `search_result_click`, `search_filter_apply`, and `search_saved_filter_use` events through the analytics pipeline configured in Section 6.
- Attach tracing spans around Meilisearch calls using OpenTelemetry, correlating with ingestion jobs to monitor staleness.
- Capture feature toggles (`search.spotlight`, `search.facets`, etc.) in logs for debugging mismatched experiences.

## Security & Privacy

- Apply privacy filters to exclude private communities unless the user has membership or admin scopes.
- Obfuscate sensitive fields (emails, internal IDs) before sending to clients; highlight data is passed through `HtmlString::escape`.
- Rate limiting via `ThrottleRequests` profile `search-heavy` with adaptive penalties for anonymous abuse.
- Maintain audit logs of admin/auditor searches, including filters used, to satisfy compliance requirements.

## QA & Acceptance

- **Unit tests** for `QueryService` covering filter translation, boost logic, and highlight formatting.
- **Feature tests** hitting `/api/v1/search` with varied filters to validate policy enforcement and response schema.
- **Browser tests** (Laravel Dusk/Playwright) verifying keyboard navigation, facet interactions, and accessibility roles.
- **Flutter widget tests** ensuring search bar debouncing, saved search persistence, and offline banners.
- Accessibility audit via Axe DevTools and VoiceOver/TalkBack manual sweeps.

## Rollout Plan

1. Deploy behind the `search.query_ux` feature flag defaulted to internal staff.
2. Execute shadow traffic tests comparing old vs. new APIs, validating latency and relevance metrics.
3. Train support/moderation staff on new filters and saved search tooling; publish documentation in the admin knowledge base.
4. Gradually expand exposure by community cohort, monitoring analytics for search success rate and zero-result ratios.
5. Finalise by enabling for all users once stability KPIs are met and update marketing materials to announce the enhanced discovery experience.

## Checklist

- [x] Backend Query API with validation, policies, and transformers
- [x] Web components (input, facets, results, spotlight) with accessibility compliance
- [x] Mobile search module with Riverpod integration and offline support
- [x] Telemetry, rate limiting, and audit logging
- [x] Rollout flagging, QA coverage, and documentation handover
