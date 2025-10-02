#!/usr/bin/env python3
"""
Generate deterministic community test fixtures for the Laravel and Flutter test suites.

The generator consumes the curated dataset under
`docs/upgrade/testing/fixtures/community_base_dataset.json` and produces:

1. `Web_Application/Academy-LMS/tests/Fixtures/community_fixture_set.json`
   – JSON document consumed by PHPUnit feature tests and Artisan commands.
2. `Student Mobile APP/academy_lms_app/test/fixtures/community_fixture_set.dart`
   – Const Dart structures for Flutter widget/integration tests.
3. `docs/upgrade/testing/fixtures/fixture_manifest.json` – metadata including
   checksums, record counts, and freshness timestamps.

The script performs non-trivial enrichment (leaderboards, engagement scoring,
role/timezone distributions, paywall slicing, and tier summaries) to ensure the
fixtures accurately represent production-like behaviour.
"""
from __future__ import annotations

import json
import hashlib
from dataclasses import dataclass
from datetime import datetime, timedelta, timezone
from pathlib import Path
from typing import Dict, Iterable, List

REPO_ROOT = Path(__file__).resolve().parents[2]
BASE_DATASET_PATH = REPO_ROOT / "docs" / "upgrade" / "testing" / "fixtures" / "community_base_dataset.json"
BACKEND_OUTPUT_PATH = REPO_ROOT / "Web_Application" / "Academy-LMS" / "tests" / "Fixtures" / "community_fixture_set.json"
MOBILE_OUTPUT_PATH = (
    REPO_ROOT
    / "Student Mobile APP"
    / "academy_lms_app"
    / "test"
    / "fixtures"
    / "community_fixture_set.dart"
)
MANIFEST_PATH = REPO_ROOT / "docs" / "upgrade" / "testing" / "fixtures" / "fixture_manifest.json"

ONLINE_DEFAULT_MINUTES = 10


@dataclass(frozen=True)
class Engagement:
    reactions: int
    comments: int
    shares: int

    @classmethod
    def from_dict(cls, payload: Dict[str, int]) -> "Engagement":
        return cls(
            reactions=int(payload.get("reactions", 0)),
            comments=int(payload.get("comments", 0)),
            shares=int(payload.get("shares", 0)),
        )

    def score(self) -> int:
        # Weighted scoring: reactions=1, comments=2, shares=3 to emphasise active participation.
        return self.reactions + (self.comments * 2) + (self.shares * 3)


@dataclass
class Member:
    member_id: int
    name: str
    role: str
    status: str
    timezone: str
    points: int
    level: int
    joined_at: datetime
    last_seen_at: datetime
    badges: List[str]

    @classmethod
    def from_dict(cls, payload: Dict[str, object]) -> "Member":
        return cls(
            member_id=int(payload["id"]),
            name=str(payload["name"]),
            role=str(payload["role"]),
            status=str(payload["status"]),
            timezone=str(payload["timezone"]),
            points=int(payload["points"]),
            level=int(payload["level"]),
            joined_at=_parse_datetime(payload["joined_at"]),
            last_seen_at=_parse_datetime(payload["last_seen_at"]),
            badges=list(payload.get("badges", [])),
        )

    @property
    def is_active(self) -> bool:
        return self.status == "active"


@dataclass
class Post:
    post_id: int
    author_id: int
    post_type: str
    visibility: str
    created_at: datetime
    engagement: Engagement
    tags: List[str]
    title: str
    excerpt: str
    paywall_tier_id: int | None

    @classmethod
    def from_dict(cls, payload: Dict[str, object]) -> "Post":
        paywall = payload.get("paywall") or None
        tier_id = int(paywall.get("tier_id")) if isinstance(paywall, dict) and "tier_id" in paywall else None
        return cls(
            post_id=int(payload["id"]),
            author_id=int(payload["author_id"]),
            post_type=str(payload["type"]),
            visibility=str(payload["visibility"]),
            created_at=_parse_datetime(payload["created_at"]),
            engagement=Engagement.from_dict(payload.get("engagement", {})),
            tags=list(payload.get("tags", [])),
            title=str(payload["title"]),
            excerpt=str(payload["body_excerpt"]),
            paywall_tier_id=tier_id,
        )

    def engagement_score(self, now: datetime) -> float:
        # Recency bonus tapers linearly across 48 hours to reward freshness.
        hours_since = max((now - self.created_at).total_seconds() / 3600.0, 0.0)
        recency_bonus = max(48.0 - hours_since, 0.0) * 1.5
        return round(self.engagement.score() + recency_bonus, 2)


@dataclass
class Event:
    event_id: int
    title: str
    start_at: datetime
    duration_minutes: int
    rsvp_count: int
    hosts: List[int]
    location: str
    visibility: str

    @classmethod
    def from_dict(cls, payload: Dict[str, object]) -> "Event":
        return cls(
            event_id=int(payload["id"]),
            title=str(payload["title"]),
            start_at=_parse_datetime(payload["start_at"]),
            duration_minutes=int(payload["duration_minutes"]),
            rsvp_count=int(payload["rsvp_count"]),
            hosts=[int(h) for h in payload.get("hosts", [])],
            location=str(payload["location"]),
            visibility=str(payload["visibility"]),
        )


@dataclass
class Tier:
    tier_id: int
    name: str
    price: float
    currency: str
    billing_interval: str
    benefits: List[str]

    @classmethod
    def from_dict(cls, payload: Dict[str, object]) -> "Tier":
        return cls(
            tier_id=int(payload["id"]),
            name=str(payload["name"]),
            price=float(payload["price"]),
            currency=str(payload["currency"]),
            billing_interval=str(payload["billing_interval"]),
            benefits=list(payload.get("benefits", [])),
        )


def _parse_datetime(value: str) -> datetime:
    # Accept ISO strings with Z suffix.
    if isinstance(value, str):
        value = value.replace("Z", "+00:00")
    return datetime.fromisoformat(value).astimezone(timezone.utc)


def _ensure_directory(path: Path) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)


def _role_counts(members: Iterable[Member]) -> List[Dict[str, object]]:
    counts: Dict[str, int] = {}
    for member in members:
        counts[member.role] = counts.get(member.role, 0) + 1
    return [
        {"role": role, "count": counts[role]}
        for role in sorted(counts.keys())
    ]


def _timezone_counts(members: Iterable[Member]) -> List[Dict[str, object]]:
    counts: Dict[str, int] = {}
    for member in members:
        counts[member.timezone] = counts.get(member.timezone, 0) + 1
    return [
        {"timezone": tz, "members": counts[tz]}
        for tz in sorted(counts.keys())
    ]


def _leaderboard(members: Iterable[Member], limit: int = 5) -> List[Dict[str, object]]:
    sorted_members = sorted(
        (m for m in members if m.is_active),
        key=lambda m: (m.points, m.level, -m.member_id),
        reverse=True,
    )
    top_members = sorted_members[:limit]
    return [
        {
            "user_id": member.member_id,
            "display_name": member.name,
            "role": member.role,
            "points": member.points,
            "level": member.level,
            "badges": member.badges,
        }
        for member in top_members
    ]


def _post_payload(post: Post, now: datetime) -> Dict[str, object]:
    return {
        "id": post.post_id,
        "author_id": post.author_id,
        "type": post.post_type,
        "visibility": post.visibility,
        "tags": post.tags,
        "title": post.title,
        "excerpt": post.excerpt,
        "created_at": post.created_at.isoformat(),
        "engagement": {
            "reactions": post.engagement.reactions,
            "comments": post.engagement.comments,
            "shares": post.engagement.shares,
            "score": post.engagement_score(now),
        },
        "paywall_tier_id": post.paywall_tier_id,
    }


def _event_payload(event: Event) -> Dict[str, object]:
    return {
        "id": event.event_id,
        "title": event.title,
        "starts_at": event.start_at.isoformat(),
        "duration_minutes": event.duration_minutes,
        "rsvp_count": event.rsvp_count,
        "hosts": event.hosts,
        "location": event.location,
        "visibility": event.visibility,
    }


def _tier_payload(tier: Tier) -> Dict[str, object]:
    return {
        "id": tier.tier_id,
        "name": tier.name,
        "price": tier.price,
        "currency": tier.currency,
        "billing_interval": tier.billing_interval,
        "benefits": tier.benefits,
    }


def load_dataset() -> Dict[str, object]:
    if not BASE_DATASET_PATH.exists():
        raise FileNotFoundError(f"Base dataset missing at {BASE_DATASET_PATH}")
    with BASE_DATASET_PATH.open("r", encoding="utf-8") as handle:
        return json.load(handle)


def build_backend_payload(dataset: Dict[str, object]) -> Dict[str, object]:
    generated_at = _parse_datetime(dataset["generated_at"])
    online_window_minutes = int(dataset.get("online_window_minutes") or ONLINE_DEFAULT_MINUTES)
    online_window = timedelta(minutes=online_window_minutes)

    backend_communities: List[Dict[str, object]] = []
    total_active_members = 0
    total_paywalled_posts = 0
    engagement_scores: List[float] = []

    for raw_community in dataset.get("communities", []):
        members = [Member.from_dict(entry) for entry in raw_community.get("members", [])]
        posts = [Post.from_dict(entry) for entry in raw_community.get("posts", [])]
        events = [Event.from_dict(entry) for entry in raw_community.get("events", [])]
        tiers = [Tier.from_dict(entry) for entry in raw_community.get("tiers", [])]

        active_members = [member for member in members if member.is_active]
        online_members = [
            member
            for member in active_members
            if generated_at - member.last_seen_at <= online_window
        ]

        paywalled_posts = [post for post in posts if post.paywall_tier_id is not None]
        post_payloads = [_post_payload(post, generated_at) for post in posts]

        trending_posts = sorted(
            post_payloads,
            key=lambda payload: payload["engagement"]["score"],
            reverse=True,
        )[:3]

        recent_posts = sorted(
            post_payloads,
            key=lambda payload: payload["created_at"],
            reverse=True,
        )[:5]

        upcoming_events = [
            _event_payload(event)
            for event in sorted(events, key=lambda e: e.start_at)
            if event.start_at >= generated_at
        ]

        backend_communities.append(
            {
                "id": int(raw_community["id"]),
                "slug": raw_community["slug"],
                "name": raw_community["name"],
                "category": raw_community["category"],
                "visibility": raw_community["visibility"],
                "timezone": raw_community["timezone"],
                "created_at": raw_community["created_at"],
                "member_counts": {
                    "total": len(members),
                    "active": len(active_members),
                    "online": len(online_members),
                    "pending": sum(1 for m in members if m.status == "pending"),
                    "banned": sum(1 for m in members if m.status == "banned"),
                },
                "role_distribution": _role_counts(members),
                "timezone_distribution": _timezone_counts(members),
                "leaderboard": _leaderboard(members),
                "recent_posts": recent_posts,
                "trending_posts": trending_posts,
                "paywalled_posts": [payload for payload in post_payloads if payload["paywall_tier_id"]],
                "upcoming_events": upcoming_events,
                "tiers": [_tier_payload(tier) for tier in tiers],
                "points_rules": raw_community.get("points_rules", []),
            }
        )

        total_active_members += len(active_members)
        total_paywalled_posts += len(paywalled_posts)
        engagement_scores.extend(payload["engagement"]["score"] for payload in post_payloads)

    aggregate_engagement = {
        "average_score": round(sum(engagement_scores) / len(engagement_scores), 2) if engagement_scores else 0.0,
        "max_score": max(engagement_scores) if engagement_scores else 0.0,
        "min_score": min(engagement_scores) if engagement_scores else 0.0,
    }

    return {
        "generated_at": generated_at.isoformat(),
        "communities": backend_communities,
        "global_metrics": {
            "communities": len(backend_communities),
            "active_members": total_active_members,
            "paywalled_posts": total_paywalled_posts,
            "engagement": aggregate_engagement,
        },
    }


def build_mobile_payload(backend_payload: Dict[str, object]) -> str:
    generated_at = backend_payload["generated_at"]
    dart_lines: List[str] = []
    dart_lines.append("// GENERATED CODE - DO NOT MODIFY BY HAND")
    dart_lines.append("// Generated by tools/testing/generate_test_fixtures.py")
    dart_lines.append(f"// Timestamp: {generated_at}")
    dart_lines.append("\nconst String fixtureGeneratedAt = '" + generated_at + "';")
    dart_lines.append("\nconst List<Map<String, dynamic>> communityFixtureSet = [")

    for community in backend_payload["communities"]:
        trending_posts = community["trending_posts"]
        upcoming_events = community["upcoming_events"]
        leaderboard = community["leaderboard"]
        timezone_dist = community["timezone_distribution"]
        tiers = community["tiers"]

        dart_lines.append("  {")
        dart_lines.append(f"    'id': {community['id']},")
        dart_lines.append(f"    'slug': '{community['slug']}',")
        dart_lines.append(f"    'name': '{community['name']}',")
        dart_lines.append(f"    'category': '{community['category']}',")
        dart_lines.append(f"    'visibility': '{community['visibility']}',")
        dart_lines.append(f"    'timezone': '{community['timezone']}',")
        counts = community["member_counts"]
        dart_lines.append(f"    'memberCount': {counts['total']},")
        dart_lines.append(f"    'activeMembers': {counts['active']},")
        dart_lines.append(f"    'onlineMembers': {counts['online']},")
        dart_lines.append("    'leaderboard': [")
        for entry in leaderboard:
            dart_lines.append("      {")
            dart_lines.append(f"        'userId': {entry['user_id']},")
            dart_lines.append(f"        'displayName': '{entry['display_name']}',")
            dart_lines.append(f"        'role': '{entry['role']}',")
            dart_lines.append(f"        'points': {entry['points']},")
            dart_lines.append(f"        'level': {entry['level']},")
            dart_lines.append("        'badges': [" + ", ".join(f"'{badge}'" for badge in entry["badges"]) + "],")
            dart_lines.append("      },")
        dart_lines.append("    ],")
        dart_lines.append("    'trendingPosts': [")
        for post in trending_posts:
            dart_lines.append("      {")
            dart_lines.append(f"        'id': {post['id']},")
            dart_lines.append(f"        'title': '{post['title']}',")
            dart_lines.append(f"        'type': '{post['type']}',")
            dart_lines.append(f"        'visibility': '{post['visibility']}',")
            dart_lines.append("        'tags': [" + ", ".join(f"'{tag}'" for tag in post["tags"]) + "],")
            dart_lines.append(f"        'engagementScore': {post['engagement']['score']},")
            dart_lines.append(
                f"        'isPaywalled': {'true' if post['paywall_tier_id'] else 'false'},"
            )
            dart_lines.append("      },")
        dart_lines.append("    ],")
        dart_lines.append("    'upcomingEvents': [")
        for event in upcoming_events:
            dart_lines.append("      {")
            dart_lines.append(f"        'id': {event['id']},")
            dart_lines.append(f"        'title': '{event['title']}',")
            dart_lines.append(f"        'startsAt': '{event['starts_at']}',")
            dart_lines.append(f"        'durationMinutes': {event['duration_minutes']},")
            dart_lines.append(f"        'rsvpCount': {event['rsvp_count']},")
            dart_lines.append("        'hosts': [" + ", ".join(str(host) for host in event["hosts"]) + "],")
            dart_lines.append(f"        'location': '{event['location']}',")
            dart_lines.append(f"        'visibility': '{event['visibility']}',")
            dart_lines.append("      },")
        dart_lines.append("    ],")
        dart_lines.append("    'subscriptionTiers': [")
        for tier in tiers:
            dart_lines.append("      {")
            dart_lines.append(f"        'id': {tier['id']},")
            dart_lines.append(f"        'name': '{tier['name']}',")
            dart_lines.append(f"        'price': {tier['price']},")
            dart_lines.append(f"        'currency': '{tier['currency']}',")
            dart_lines.append(f"        'billingInterval': '{tier['billing_interval']}',")
            dart_lines.append("        'benefits': [" + ", ".join(f"'{benefit}'" for benefit in tier["benefits"]) + "],")
            dart_lines.append("      },")
        dart_lines.append("    ],")
        dart_lines.append("    'timezoneDistribution': [")
        for entry in timezone_dist:
            dart_lines.append("      {")
            dart_lines.append(f"        'timezone': '{entry['timezone']}',")
            dart_lines.append(f"        'members': {entry['members']},")
            dart_lines.append("      },")
        dart_lines.append("    ],")
        dart_lines.append("    'pointsRules': [")
        for rule in community["points_rules"]:
            dart_lines.append("      {")
            dart_lines.append(f"        'action': '{rule['action']}',")
            dart_lines.append(f"        'points': {rule['points']},")
            dart_lines.append(f"        'cooldownHours': {rule['cooldown_hours']},")
            dart_lines.append("      },")
        dart_lines.append("    ],")
        dart_lines.append("  },")
    dart_lines.append("];")

    return "\n".join(dart_lines) + "\n"


def write_backend_fixture(payload: Dict[str, object]) -> None:
    _ensure_directory(BACKEND_OUTPUT_PATH)
    with BACKEND_OUTPUT_PATH.open("w", encoding="utf-8") as handle:
        json.dump(payload, handle, indent=2, sort_keys=True)
        handle.write("\n")


def write_mobile_fixture(dart_source: str) -> None:
    _ensure_directory(MOBILE_OUTPUT_PATH)
    with MOBILE_OUTPUT_PATH.open("w", encoding="utf-8") as handle:
        handle.write(dart_source)


def write_manifest(payloads: Dict[str, Dict[str, object]]) -> None:
    manifest_entries = []
    for path, metadata in payloads.items():
        manifest_entries.append({
            "path": path,
            **metadata,
        })
    manifest = {
        "generated_at": datetime.now(timezone.utc).isoformat(),
        "artifacts": manifest_entries,
    }
    with MANIFEST_PATH.open("w", encoding="utf-8") as handle:
        json.dump(manifest, handle, indent=2, sort_keys=True)
        handle.write("\n")


def _hash_file(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as handle:
        for chunk in iter(lambda: handle.read(65536), b""):
            digest.update(chunk)
    return digest.hexdigest()


def main() -> None:
    dataset = load_dataset()
    backend_payload = build_backend_payload(dataset)
    dart_source = build_mobile_payload(backend_payload)

    write_backend_fixture(backend_payload)
    write_mobile_fixture(dart_source)

    manifest_payload = {
        str(BACKEND_OUTPUT_PATH.relative_to(REPO_ROOT)): {
            "sha256": _hash_file(BACKEND_OUTPUT_PATH),
            "record_counts": {
                "communities": len(backend_payload["communities"]),
                "posts": sum(len(c["recent_posts"]) for c in backend_payload["communities"]),
                "upcoming_events": sum(len(c["upcoming_events"]) for c in backend_payload["communities"]),
            },
        },
        str(MOBILE_OUTPUT_PATH.relative_to(REPO_ROOT)): {
            "sha256": _hash_file(MOBILE_OUTPUT_PATH),
            "line_count": sum(1 for _ in MOBILE_OUTPUT_PATH.open("r", encoding="utf-8")),
        },
    }
    write_manifest(manifest_payload)
    print("Generated fixtures for", len(backend_payload["communities"]), "communities.")


if __name__ == "__main__":
    main()
