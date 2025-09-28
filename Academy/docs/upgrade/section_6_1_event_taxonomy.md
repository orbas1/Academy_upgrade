# Section 6.1 – Analytics Event Taxonomy

This specification enumerates every analytics event required for the community platform upgrade, including trigger conditions, payload contracts, data lineage, and privacy annotations. The taxonomy is shared across web, mobile, and backend emitters to guarantee parity and consistent downstream modeling.

## Naming Convention
- `snake_case` event names prefixed by functional domain (`community_`, `post_`, `comment_`, `membership_`, `subscription_`, `calendar_`, `classroom_`, `search_`, `notification_`).
- Version suffix `_v1` appended when breaking changes to schema occur.
- All timestamps captured in ISO-8601 UTC.

## Core Entities
- **Community**: `community_id`, `slug`, `name`, `tier_id` (nullable).
- **User**: `user_id` (hashed for privacy), `role`, `membership_status`, `plan_type`.
- **Content**: `content_id`, `content_type` (post/comment/media), `visibility` (public/community/paid), `media_types`.
- **Device/Client**: `client` (`web`, `ios`, `android`), `app_version`, `locale`, `experiment_variants`.

## Event Catalog

| Event Name | Trigger | Payload Fields | Channel | Privacy Classification |
| --- | --- | --- | --- | --- |
| `community_join_v1` | User joins or is approved into a community | `community`, `user`, `source` (`invite_link`, `search`, `referral`), `join_type` (`self`, `admin_add`), `is_first_join` | Web, Mobile, Backend | PII-lite (hashed user id) |
| `community_leave_v1` | Membership ends voluntarily or by admin action | `community`, `user`, `leave_reason`, `initiated_by` (`user`, `admin`) | Backend | PII-lite |
| `post_create_v1` | Composer submit success | `community`, `user`, `content`, `attachments` (array), `scheduled_for` (nullable), `is_paid_content` | Web, Mobile | PII-lite |
| `post_publish_v1` | Scheduled post released | `community`, `user`, `content_id`, `published_at`, `schedule_id` | Backend job | PII-lite |
| `post_engage_v1` | Reaction (`like`, `celebrate`, `insight`), `share`, or `save` | `community`, `user`, `content_id`, `engagement_type`, `surface` (`feed`, `notification`, `profile`) | Web, Mobile | PII-lite |
| `comment_create_v1` | Comment submit success | `community`, `user`, `content_id`, `parent_comment_id`, `reply_depth`, `media_types` | Web, Mobile | PII-lite |
| `comment_resolve_v1` | Moderator resolves a flagged comment | `community`, `moderator_id`, `content_id`, `flag_reason`, `resolution` | Admin | Sensitive (moderator id) |
| `membership_tier_view_v1` | Paywall tier view | `community`, `user`, `tier_id`, `surface` (`paywall_page`, `cta_modal`) | Web, Mobile | PII-lite |
| `subscription_start_v1` | Successful Stripe subscription creation | `community`, `user`, `tier_id`, `plan_amount`, `currency`, `trial_days`, `promo_code` | Backend | Payment (restricted) |
| `subscription_cancel_v1` | Subscription cancellation | `community`, `user`, `tier_id`, `cancel_reason`, `initiated_by` | Backend | Payment (restricted) |
| `calendar_join_v1` | User RSVP to event | `community`, `user`, `event_id`, `event_type`, `event_start`, `event_location_type` (`virtual`, `in_person`) | Web, Mobile | PII-lite |
| `classroom_link_click_v1` | Classroom resource click-through | `community`, `user`, `resource_id`, `resource_type`, `surface` | Web, Mobile | PII-lite |
| `notification_open_v1` | Push/in-app notification opened | `user`, `notification_id`, `channel` (`push`, `email`, `in_app`), `cta_destination` | Mobile, Web | PII-lite |
| `search_execute_v1` | Search query submitted | `user`, `query_hash`, `filters`, `results_count`, `latency_ms`, `result_types` | Web, Mobile | Anonymized (query hashed) |
| `moderation_flag_create_v1` | User flags content | `community`, `user`, `content_id`, `flag_reason`, `source_surface` | Web, Mobile | Sensitive (moderation) |
| `onboarding_step_complete_v1` | Completion of onboarding step | `user`, `step_id`, `community`, `completion_time_ms`, `experiment_variants` | Web, Mobile | PII-lite |
| `presence_heartbeat_v1` | Presence ping for online indicator | `user`, `community`, `device_id`, `latency_ms`, `network_type` | Mobile | Device-level |
| `referral_invite_send_v1` | Member sends invite | `community`, `user`, `invite_method`, `invitee_email_hash`, `message_template` | Web | Sensitive (hashed email) |
| `report_export_v1` | Admin exports data/report | `admin_id`, `community`, `report_type`, `filter_set`, `rows_count` | Admin | Sensitive (admin id) |

## Attribution Metadata
Every event is enriched with:
- `session_id`
- `utm_source`, `utm_medium`, `utm_campaign`, `utm_term`, `utm_content`
- `referrer`
- `page_name` / `screen_name`
- `experiments` (map of experiment name → variant)

## Data Quality Requirements
- 95th percentile event delivery latency < 2 minutes.
- Event loss < 0.1% measured via warehouse reconciliation vs. client send counts.
- Schemas validated at CI via JSON schema tests.

## Privacy & Consent
- Respect `analytics_opt_in` per user; drop or anonymize events until consent is granted.
- Differential privacy noise applied to aggregated community metrics for tenants with < 20 members.

## Change Management
- Changes to taxonomy require RFC review, schema version bump, and communication to downstream dashboard owners.
