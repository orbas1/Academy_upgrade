# Section 6.3 – Analytics Dashboards & Reporting

This artifact defines the dashboards, tiles, KPIs, and operational workflows powering analytics visibility for community admins and internal stakeholders.

## Dashboard Stack
- **Metabase** for community-facing dashboards embedded in admin portal.
- **Looker (internal)** for growth, finance, and retention analytics with governed explores.
- **Superset** for ad-hoc querying by data analysts.

## Community Admin Dashboard (Embedded)
- **Overview Tab**
  - Active members (7d, 28d)
  - New posts/comments per day with sparkline
  - Engagement score (weighted sum of posts, comments, reactions)
  - Paywall conversion rate
  - Churn vs. acquisition trend
- **Content Performance Tab**
  - Top posts by reactions, saves, dwell time
  - Content heatmap (day vs. hour)
  - Media mix distribution (text/image/video)
- **Monetization Tab**
  - MRR, ARR, ARPU segmented by tier
  - Subscription funnel drop-off (view → start → activate)
  - Failed payments & recovery queue
- **Member Insights Tab**
  - Cohort retention chart
  - Power user leaderboard (posts, replies, reactions)
  - Onboarding completion funnel
- **Operations Tab**
  - Open moderation flags by status
  - SLA compliance (time to review)
  - Automation job health (digest send rates, leaderboard recalculations)

### Filters & Controls
- Date range presets (7d, 28d, 90d, custom)
- Community selector (RBAC restricts to accessible communities)
- Member segment filter (role, tier, geography)
- Experiment variant filter

## Internal Executive Dashboard
- Company-wide MRR, churn, LTV, CAC
- Conversion funnel from marketing site to paid membership
- Feature adoption (notifications enabled, map usage, classroom engagement)
- Support tickets vs. feature usage correlation

## Reporting Automation
- Scheduled PDF/CSV exports every Monday 08:00 UTC to community admins.
- Slack alerts (`#community-insights`) for threshold breaches (e.g., engagement score -20% WoW).
- API endpoint `GET /admin/analytics/export` generating signed download links with expiry.

## Data Sources
- Snowflake models maintained in dbt project (`models/communities/*.sql`).
- Fact tables: `f_community_events`, `f_subscription_revenue`, `f_engagement_scores`.
- Dimensions: `d_user`, `d_community`, `d_membership_tier`, `d_content`.

## QA & Validation
- Dashboard regression tests using Looker’s content validator and Metabase API snapshots.
- Acceptance checklist before publishing:
  - Visual QA on desktop & tablet breakpoints.
  - Filter interactions validated with sample scenarios.
  - Data freshness indicator shows < 1 hour for real-time cards.

## Security & Access
- Embedded dashboards served via signed JWT tokens tied to admin RBAC scope.
- Row-level security in Snowflake ensures tenant isolation.
- Audit logging for export/download actions (see Section 7.5).

## Documentation & Training
- Playbook stored in Confluence with video walkthrough.
- Quarterly training for community managers on interpreting metrics.
