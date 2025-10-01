#!/usr/bin/env python3
"""Utility to condense OWASP ZAP baseline reports into actionable summaries."""
from __future__ import annotations

import argparse
import datetime as _dt
from datetime import timezone as _timezone
import json
import pathlib
import sys
from typing import Any, Dict, List, Tuple

RiskCounts = Dict[str, int]

_RISK_ORDER: Dict[str, int] = {
    "High": 3,
    "Medium": 2,
    "Low": 1,
    "Informational": 0,
}

_RISK_BY_CODE: Dict[str, str] = {
    "3": "High",
    "2": "Medium",
    "1": "Low",
    "0": "Informational",
}


def _normalise_risk(alert: Dict[str, Any]) -> str:
    """Return a canonical risk label for a ZAP alert."""
    risk_code = str(alert.get("riskcode", "")).strip()
    if risk_code in _RISK_BY_CODE:
        return _RISK_BY_CODE[risk_code]

    risk_desc = str(alert.get("riskdesc", "")).strip()
    if risk_desc:
        head = risk_desc.split("(")[0].strip()
        if head:
            return head
    return "Informational"


def _collect_alerts(report: Dict[str, Any]) -> Tuple[List[Dict[str, Any]], RiskCounts, str]:
    alerts: List[Dict[str, Any]] = []
    counts: RiskCounts = {key: 0 for key in _RISK_ORDER}
    target = ""

    sites = report.get("site")
    if isinstance(sites, dict):
        sites = [sites]

    if not isinstance(sites, list):
        raise ValueError("ZAP report JSON must contain a 'site' array")

    for site in sites:
        if not isinstance(site, dict):
            continue
        target = target or str(site.get("@name") or site.get("name") or "")
        site_alerts = site.get("alerts", [])
        if isinstance(site_alerts, dict):
            site_alerts = site_alerts.get("alert", [])
        if not isinstance(site_alerts, list):
            continue
        for alert in site_alerts:
            if not isinstance(alert, dict):
                continue
            risk = _normalise_risk(alert)
            counts.setdefault(risk, 0)
            counts[risk] += 1
            alerts.append({
                "risk": risk,
                "name": str(alert.get("alert") or alert.get("name") or ""),
                "confidence": str(alert.get("confidence", "")),
                "instances": alert.get("instances", []),
                "cweid": str(alert.get("cweid", "")),
                "wascid": str(alert.get("wascid", "")),
                "solution": str(alert.get("solution", "")),
                "reference": str(alert.get("reference", "")),
                "pluginid": str(alert.get("pluginid", "")),
                "riskdesc": str(alert.get("riskdesc", "")),
                "desc": str(alert.get("desc", "")),
                "evidence": str(alert.get("evidence", "")),
                "otherinfo": str(alert.get("otherinfo", "")),
            })

    return alerts, counts, target


def _top_alerts(alerts: List[Dict[str, Any]], limit: int = 5) -> List[Dict[str, Any]]:
    def _sort_key(item: Dict[str, Any]) -> Tuple[int, int, str]:
        risk = item.get("risk", "Informational")
        instances = item.get("instances") or []
        return (
            _RISK_ORDER.get(risk, 0),
            len(instances),
            item.get("name", ""),
        )

    ordered = sorted(alerts, key=_sort_key, reverse=True)
    condensed: List[Dict[str, Any]] = []
    for alert in ordered[:limit]:
        instance_urls: List[str] = []
        for inst in alert.get("instances") or []:
            if isinstance(inst, dict):
                uri = inst.get("uri") or inst.get("url") or inst.get("URI")
                if uri:
                    instance_urls.append(str(uri))
        condensed.append({
            "risk": alert.get("risk", "Informational"),
            "name": alert.get("name", ""),
            "count": len(alert.get("instances") or []),
            "pluginId": alert.get("pluginid", ""),
            "cwe": alert.get("cweid", ""),
            "wasc": alert.get("wascid", ""),
            "exampleUrls": instance_urls[:5],
            "solution": alert.get("solution", ""),
            "reference": alert.get("reference", ""),
        })
    return condensed


def _generate_markdown(summary: Dict[str, Any]) -> str:
    lines = [
        "# OWASP ZAP Baseline Summary",
        "",
        f"Generated: {summary['generated_at']}",
    ]
    target = summary.get("target")
    if target:
        lines.append(f"Target: `{target}`")
    lines.extend([
        "",
        "## Alert Totals",
        "",
    ])
    for risk in ("High", "Medium", "Low", "Informational"):
        count = summary["risk_counts"].get(risk, 0)
        lines.append(f"- **{risk}:** {count}")
    lines.extend(["", "## Top Alerts", ""])
    if not summary["top_alerts"]:
        lines.append("No alerts were reported.")
    else:
        for alert in summary["top_alerts"]:
            lines.append(f"### {alert['risk']} – {alert['name']}")
            lines.append("")
            lines.append(f"- Occurrences: {alert['count']}")
            if alert.get("pluginId"):
                lines.append(f"- Plugin: `{alert['pluginId']}`")
            if alert.get("cwe"):
                lines.append(f"- CWE: `{alert['cwe']}`")
            if alert.get("wasc"):
                lines.append(f"- WASC: `{alert['wasc']}`")
            if alert.get("exampleUrls"):
                lines.append("- Sample URLs:")
                for url in alert["exampleUrls"]:
                    lines.append(f"  - {url}")
            if alert.get("solution"):
                lines.append(f"- Recommended Fix: {alert['solution']}")
            if alert.get("reference"):
                lines.append(f"- References: {alert['reference']}")
            lines.append("")
    return "\n".join(lines).strip() + "\n"


def summarise(report_path: pathlib.Path, markdown_path: pathlib.Path | None, output_path: pathlib.Path | None) -> Dict[str, Any]:
    with report_path.open("r", encoding="utf-8") as handle:
        report = json.load(handle)

    alerts, counts, target = _collect_alerts(report)
    summary: Dict[str, Any] = {
        "generated_at": _dt.datetime.now(_timezone.utc).replace(microsecond=0).isoformat().replace("+00:00", "Z"),
        "target": target,
        "total_alerts": len(alerts),
        "risk_counts": counts,
        "top_alerts": _top_alerts(alerts),
    }

    if output_path:
        output_path.parent.mkdir(parents=True, exist_ok=True)
        with output_path.open("w", encoding="utf-8") as handle:
            json.dump(summary, handle, indent=2, sort_keys=True)

    if markdown_path:
        markdown_path.parent.mkdir(parents=True, exist_ok=True)
        markdown = _generate_markdown(summary)
        markdown_path.write_text(markdown, encoding="utf-8")

    return summary


def _parse_args(argv: List[str]) -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Summarise OWASP ZAP baseline JSON reports")
    parser.add_argument("--input", required=True, help="Path to the ZAP JSON report")
    parser.add_argument("--output", help="Where to write the JSON summary")
    parser.add_argument("--markdown", help="Optional Markdown report destination")
    return parser.parse_args(argv)


def main(argv: List[str] | None = None) -> int:
    ns = _parse_args(argv or sys.argv[1:])
    input_path = pathlib.Path(ns.input)
    if not input_path.is_file():
        raise FileNotFoundError(f"ZAP report not found: {input_path}")

    output_path = pathlib.Path(ns.output) if ns.output else None
    markdown_path = pathlib.Path(ns.markdown) if ns.markdown else None

    summarise(input_path, markdown_path, output_path)
    return 0


if __name__ == "__main__":
    sys.exit(main())
