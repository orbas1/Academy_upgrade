#!/usr/bin/env python3
"""Enforce minimum coverage for Flutter lcov reports."""

import json
import os
import sys
from datetime import datetime, timezone
from typing import Tuple


def parse_lcov(path: str) -> Tuple[int, int]:
    total = 0
    covered = 0
    with open(path, "r", encoding="utf-8") as handle:
        for raw_line in handle:
            line = raw_line.strip()
            if line.startswith("DA:"):
                try:
                    _, payload = line.split(":", 1)
                    _, hits = payload.split(",")
                    total += 1
                    if int(hits) > 0:
                        covered += 1
                except ValueError as exc:
                    raise RuntimeError(f"Invalid DA entry in {path}: '{line}'") from exc
    return covered, total


def enforce(path: str, minimum: float, output: str) -> None:
    if not os.path.isfile(path):
        raise FileNotFoundError(f"Coverage file '{path}' not found")
    if minimum <= 0 or minimum > 100:
        raise ValueError("Minimum coverage must be within (0, 100]")

    covered, total = parse_lcov(path)
    coverage = (covered / total * 100) if total else 0.0

    summary = {
        "generated_at": datetime.now(tz=timezone.utc).isoformat(),
        "thresholds": {"line": round(minimum, 2)},
        "coverage": {
            "line": round(coverage, 2),
            "covered_lines": covered,
            "total_lines": total,
        },
    }

    os.makedirs(os.path.dirname(output), exist_ok=True)
    with open(output, "w", encoding="utf-8") as handle:
        json.dump(summary, handle, indent=2)

    if coverage < minimum:
        raise SystemExit(
            f"Flutter coverage {coverage:.2f}% is below required threshold {minimum:.2f}%"
        )


if __name__ == "__main__":
    if len(sys.argv) != 4:
        print(
            "Usage: enforce_lcov_coverage.py <lcov-file> <min-percentage> <summary-output>",
            file=sys.stderr,
        )
        raise SystemExit(1)

    _, coverage_file, min_percentage, summary_file = sys.argv
    enforce(coverage_file, float(min_percentage), summary_file)
