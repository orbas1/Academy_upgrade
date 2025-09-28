# Upgrade Communication Template

**Subject:** [NOTICE] Academy Platform Upgrade – Laravel 11 Cutover

**Audience:** Internal stakeholders, support, customer success, leadership.

**Timeline:**
- Start: {{start_time_utc}}
- End: {{end_time_utc}}
- Impact: Intermittent read-only mode during data sync; no downtime expected.

**Summary:**
We are performing the Laravel 11 + PHP 8.3 upgrade to enable the new Communities experience. All traffic will transition from the blue stack to the green stack during the window.

**Customer Impact:**
- Sessions will remain active; new logins may be throttled for ~5 minutes.
- Media uploads paused while AV scanners redeploy.

**Actions for Support:**
1. Monitor #academy-status for live updates.
2. Escalate anomalies via PagerDuty `Academy - Upgrade` service.
3. Do not initiate manual cache clears during the window.

**Rollback Plan:** If issues arise, traffic will revert to the blue stack within 5 minutes and the upgrade rescheduled.

**Point of Contact:**
- Engineering Lead: {{engineer_name}} (Slack: @{{handle}})
- Support Lead: {{support_name}}

Thank you for your cooperation.
