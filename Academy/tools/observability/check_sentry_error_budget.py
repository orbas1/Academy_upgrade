#!/usr/bin/env python3
"""Validate Sentry error budget before promoting a release.

This script queries the Sentry Events Stats API to calculate the error rate over
an interval. It exits with a non-zero code if the observed error rate exceeds
the configured threshold, enabling CI/CD gating.
"""

import json
import os
import sys
import urllib.parse
import urllib.request

API_BASE = "https://sentry.io/api/0"


def env(name: str, *, required: bool = False, default: str | None = None) -> str:
    value = os.environ.get(name, default)
    if required and (value is None or value == ""):
        sys.stderr.write(f"Environment variable {name} is required.\n")
        sys.exit(2)
    assert value is not None
    return value


def fetch_series(*, organization: str, token: str, project: str | None, query: str, window_minutes: int) -> list:
    params: list[tuple[str, str]] = [
        ("query", query),
        ("statsPeriod", f"{window_minutes}m"),
        ("field", "count()"),
    ]

    if project:
        params.append(("project", project))

    url = f"{API_BASE}/organizations/{urllib.parse.quote(organization)}/events-stats/?{urllib.parse.urlencode(params)}"
    request = urllib.request.Request(url, headers={"Authorization": f"Bearer {token}"})
    with urllib.request.urlopen(request, timeout=30) as response:
        payload = response.read().decode("utf-8")
    return json.loads(payload).get("data", [])


def sum_series(data: list) -> float:
    total = 0.0
    for bucket in data:
        if not isinstance(bucket, list) or len(bucket) < 2:
            continue
        series = bucket[1]
        if isinstance(series, list):
            for entry in series:
                if isinstance(entry, dict):
                    for key in ("count", "sum", "value"):
                        if key in entry and isinstance(entry[key], (int, float)):
                            total += float(entry[key])
                            break
        elif isinstance(series, (int, float)):
            total += float(series)
    return total


def main() -> None:
    organization = env("SENTRY_ORG", required=True)
    token = env("SENTRY_AUTH_TOKEN", required=True)
    project = os.environ.get("SENTRY_PROJECT")
    window = int(env("SENTRY_ERROR_BUDGET_WINDOW_MINUTES", default="60"))
    threshold = float(env("SENTRY_ERROR_BUDGET_THRESHOLD", default="0.01"))
    error_query = env("SENTRY_ERROR_QUERY", default="event.type:error")
    total_query = env("SENTRY_TOTAL_QUERY", default="event.type:transaction")

    error_series = fetch_series(
        organization=organization,
        token=token,
        project=project,
        query=error_query,
        window_minutes=window,
    )
    total_series = fetch_series(
        organization=organization,
        token=token,
        project=project,
        query=total_query,
        window_minutes=window,
    )

    error_count = sum_series(error_series)
    total_count = sum_series(total_series)

    if total_count <= 0:
        sys.stderr.write("No transactions observed in the sampling window; treating as pass.\n")
        return

    error_rate = error_count / total_count
    summary = (
        "Sentry error budget check:\n"
        f"  Window: last {window} minutes\n"
        f"  Error query: {error_query}\n"
        f"  Total query: {total_query}\n"
        f"  Errors: {error_count:.0f}\n"
        f"  Transactions: {total_count:.0f}\n"
        f"  Error rate: {error_rate:.4%}\n"
        f"  Threshold: {threshold:.2%}\n"
    )
    sys.stdout.write(summary)

    if error_rate > threshold:
        sys.stderr.write("Error budget exceeded. Blocking promotion.\n")
        sys.exit(1)

    sys.stdout.write("Error budget within limits.\n")


if __name__ == "__main__":
    main()
