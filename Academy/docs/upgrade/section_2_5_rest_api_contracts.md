# Section 2.5 – REST API Contracts Progress Log

## Purpose
This document captures the current REST API contract for the communities module so the Laravel implementation team can wire controllers, transformers, and policies consistently. It is a living artifact that will be refined as additional endpoints and error cases are implemented.

## Base Path
All endpoints are namespaced under `/api/v1` and must return JSON with the standard envelope:

```json
{
  "data": {},
  "meta": {
    "request_id": "uuid",
    "cursor": {
      "next": "opaque-cursor",
      "prev": null
    }
  },
  "errors": []
}
```

* `data` contains the response payload (object or array).
* `meta.cursor` is included for keyset pagination aware endpoints.
* `errors` is an array of `{ "code": "string", "message": "string", "field": "string|null" }`.

All authenticated requests require the header `Authorization: Bearer <token>` and should respond with RFC 7807 problem documents on validation failures.

## Community Directory

### `GET /api/v1/communities`
Returns a paginated list of communities the user can see.

Query params:

| Name | Type | Description |
| --- | --- | --- |
| `filter` | enum(`all`,`joined`,`recommended`) | Optional filter toggle |
| `page_size` | integer ≤ 50 | Page length |
| `after` | string | Keyset cursor |

Response `data` is an array of:

```json
{
  "id": 42,
  "slug": "founders-lounge",
  "name": "Founders Lounge",
  "tagline": "Weekly AMAs with growth leaders",
  "member_count": 1284,
  "joined": true,
  "visibility": "public"
}
```

### `POST /api/v1/communities`
Restricted to admins. Creates a community with base metadata.

Payload:

```json
{
  "name": "Design Ops",
  "slug": "design-ops",
  "tagline": "",
  "category_id": 12,
  "visibility": "public",
  "paywall_tier_id": null
}
```

### `GET /api/v1/communities/{community}/feed`
Returns feed entries with markdown + rendered HTML summary.

Query params: `filter` (`new`,`top`,`announcements`), `after`, `page_size`.

Feed item representation:

```json
{
  "id": 593,
  "type": "post",
  "author": {
    "id": 77,
    "display_name": "Jessie Chen",
    "avatar_url": "https://cdn..."
  },
  "body_md": "**Welcome** to the cohort!",
  "body_html": "<p><strong>Welcome</strong> to the cohort!</p>",
  "like_count": 18,
  "comment_count": 4,
  "is_liked": false,
  "visibility": "community",
  "created_at": "2024-03-21T17:02:12Z",
  "paywall_tier_id": null
}
```

### `POST /api/v1/communities/{community}/posts`
Creates a post. Requires `body_md`. Optional `paywall_tier_id` to mark premium posts. Returns the hydrated feed item.

### `POST /api/v1/communities/{community}/members`
Joins the community. Returns membership resource:

```json
{
  "id": 999,
  "user_id": 14,
  "role": "member",
  "status": "active",
  "joined_at": "2024-03-18T09:33:45Z",
  "points": 125,
  "level": 2
}
```

### `DELETE /api/v1/communities/{community}/membership`
Leaves the community. Responds with `204 No Content`.

## Moderation + Governance

### `GET /api/v1/communities/{community}/reports`
Paginated list of content flags. Fields include `reporter`, `reason_code`, `status`, and `submitted_at`.

### `POST /api/v1/communities/{community}/reports/{report}/resolve`
Updates report status (`resolved`, `dismissed`, `escalated`) and records audit log metadata.

### `GET /api/v1/communities/{community}/members`
Admin-only membership search with filters (`role`, `status`, `joined_after`, `joined_before`). Supports CSV export via `Accept: text/csv`.

## Notifications & Preferences

### `GET /api/v1/communities/{community}/notifications`
Delivers unread + recent notifications. Supports pagination.

### `GET /api/v1/communities/{community}/notification-preferences`
Returns user preferences with flags for mentions, replies, digests, and marketing.

### `PUT /api/v1/communities/{community}/notification-preferences`
Updates the preference object. Must validate boolean toggles and allowed digest cadence (`daily`,`weekly`,`off`).

## Error Codes

| Code | Description |
| --- | --- |
| `community_not_found` | Community slug or id missing |
| `membership_required` | User is not a member for restricted views |
| `paywall_required` | Content gated by tier |
| `validation_failed` | Standard validation error |
| `rate_limited` | Too many requests |

## Next Steps
- Wire Laravel API controllers/services to follow this contract.
- Add automated tests to assert response envelopes and authorization paths.
- Expand contract to cover scheduled posts, automation, and geo tooling endpoints.
