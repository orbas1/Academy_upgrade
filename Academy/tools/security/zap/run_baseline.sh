#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
OUTPUT_DIR="${ZAP_OUTPUT_DIR:-$(mktemp -d)}"
TARGET="${ZAP_BASELINE_TARGET:-}"

if [[ $# -gt 0 ]]; then
  TARGET="$1"
  shift
fi

if [[ -z "${TARGET}" ]]; then
  echo "ZAP baseline target URL must be provided via argument or ZAP_BASELINE_TARGET" >&2
  exit 64
fi

if ! command -v docker >/dev/null 2>&1; then
  echo "docker is required to run the OWASP ZAP container" >&2
  exit 127
fi

mkdir -p "${OUTPUT_DIR}"

ZAP_IMAGE="${ZAP_BASELINE_IMAGE:-ghcr.io/zaproxy/zaproxy:stable}"

echo "[INFO] Running OWASP ZAP baseline scan against ${TARGET}"
docker run --rm \
  -v "${OUTPUT_DIR}:/zap/wrk:Z" \
  -e "ZAP_AUTH_HEADER=${ZAP_AUTH_HEADER:-}" \
  -e "ZAP_AUTH_HEADER_VALUE=${ZAP_AUTH_HEADER_VALUE:-}" \
  -e "ZAP_AUTH_HEADER_SITE=${ZAP_AUTH_HEADER_SITE:-}" \
  "${ZAP_IMAGE}" \
  zap-baseline.py \
  -t "${TARGET}" \
  -J zap-report.json \
  -w zap-warnings.md \
  -r zap-report.html \
  -a \
  -m "${ZAP_MAX_DURATION:-5}" \
  -T "${ZAP_TIMEOUT:-60}" \
  "$@"

SUMMARY_JSON="${OUTPUT_DIR}/zap-summary.json"
SUMMARY_MD="${OUTPUT_DIR}/zap-summary.md"

python3 "${SCRIPT_DIR}/summarize_zap.py" \
  --input "${OUTPUT_DIR}/zap-report.json" \
  --output "${SUMMARY_JSON}" \
  --markdown "${SUMMARY_MD}" >/dev/null

echo "[INFO] OWASP ZAP reports written to ${OUTPUT_DIR}"
echo "[INFO] JSON summary: ${SUMMARY_JSON}"
echo "[INFO] Markdown summary: ${SUMMARY_MD}"
