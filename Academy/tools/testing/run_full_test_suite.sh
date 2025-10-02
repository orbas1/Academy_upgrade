#!/usr/bin/env bash
# Orchestrates the major automated checks that exist in the repository so that
# engineers can surface issues quickly from a single entry point.
#
# The script is intentionally defensive: it attempts to bootstrap dependencies
# when they appear to be missing, gracefully skips steps when prerequisite
# tooling is unavailable, and always prints a run summary. Individual step
# outputs are streamed to STDOUT/STDERR and stored under tools/testing/logs/ so
# that historical runs can be audited.

set -o errexit
set -o pipefail
set -o nounset

if [[ ${BASH_VERSINFO[0]} -lt 4 ]]; then
  echo "[test-suite] Bash 4.0 or newer is required." >&2
  exit 1
fi

ROOT_DIR="$(git rev-parse --show-toplevel 2>/dev/null || true)"
if [[ -z "$ROOT_DIR" ]]; then
  echo "[test-suite] Unable to determine repository root. Run inside a Git checkout." >&2
  exit 1
fi

if [[ ! -d "$ROOT_DIR/Web_Application/Academy-LMS" && -d "$ROOT_DIR/Academy/Web_Application/Academy-LMS" ]]; then
  ROOT_DIR="$ROOT_DIR/Academy"
fi

cd "$ROOT_DIR"

# Resolve project directories (repository contains an intermediate "Academy" folder).
if [[ -d "Web_Application/Academy-LMS" ]]; then
  LARAVEL_DIR="Web_Application/Academy-LMS"
elif [[ -d "Academy/Web_Application/Academy-LMS" ]]; then
  LARAVEL_DIR="Academy/Web_Application/Academy-LMS"
else
  LARAVEL_DIR=""
fi

if [[ -d "Student Mobile APP/academy_lms_app" ]]; then
  FLUTTER_DIR="Student Mobile APP/academy_lms_app"
elif [[ -d "Academy/Student Mobile APP/academy_lms_app" ]]; then
  FLUTTER_DIR="Academy/Student Mobile APP/academy_lms_app"
else
  FLUTTER_DIR=""
fi

PROJECT_ROOT_REL="."
if [[ -n "$LARAVEL_DIR" ]]; then
  PROJECT_ROOT_REL="$(dirname "$(dirname "$LARAVEL_DIR")")"
elif [[ -n "$FLUTTER_DIR" ]]; then
  PROJECT_ROOT_REL="$(dirname "$(dirname "$FLUTTER_DIR")")"
fi

LOG_ROOT="$ROOT_DIR/$PROJECT_ROOT_REL/tools/testing/logs"
mkdir -p "$LOG_ROOT"
RUN_STAMP="$(date -u +"%Y%m%dT%H%M%SZ")"
RUN_LOG_DIR="$LOG_ROOT/$RUN_STAMP"
mkdir -p "$RUN_LOG_DIR"

declare -A STEP_STATUS=()
declare -A STEP_LOG=()

declare -a STEPS=()

# Utilities -----------------------------------------------------------------

log_name() {
  local name="$1"
  echo "${name//[^A-Za-z0-9_]/_}" | tr '[:upper:]' '[:lower:]'
}

command_exists() {
  command -v "$1" >/dev/null 2>&1
}

should_bootstrap_laravel() {
  local base="$LARAVEL_DIR"
  [[ -n "$base" ]] || return 1
  [[ -d "$base" ]] || return 1
  [[ -f "$base/vendor/autoload.php" ]] || return 0
  return 1
}

should_bootstrap_flutter() {
  local base="$FLUTTER_DIR"
  [[ -n "$base" ]] || return 1
  [[ -d "$base" ]] || return 1
  [[ -d "$base/.dart_tool" ]] || return 0
  [[ -f "$base/.dart_tool/package_config.json" ]] || return 0
  return 1
}

run_step() {
  local name="$1"
  local workdir="$2"
  local cmd="$3"

  local short_name
  short_name="$(log_name "$name")"
  local logfile="$RUN_LOG_DIR/${short_name}.log"

  printf '\n[test-suite] ▶ %s\n' "$name"
  printf '[test-suite]    working directory: %s\n' "${workdir:-$ROOT_DIR}"
  printf '[test-suite]    command: %s\n' "$cmd"

  local first_word
  first_word="${cmd%% *}"
  if [[ "$first_word" == */* ]]; then
    local check_dir="$ROOT_DIR"
    if [[ -n "$workdir" ]]; then
      check_dir="$workdir"
    fi
    if ! (cd "$check_dir" && [[ -x "$first_word" || -x "${first_word#./}" ]]); then
      STEP_STATUS["$name"]="SKIPPED (missing '$first_word')"
      STEP_LOG["$name"]="$logfile"
      printf '[test-suite] ⚠ Skipping — required command "%s" not executable in %s.\n' "$first_word" "$check_dir" | tee "$logfile"
      return
    fi
  elif ! command_exists "$first_word"; then
    STEP_STATUS["$name"]="SKIPPED (missing '$first_word')"
    STEP_LOG["$name"]="$logfile"
    printf '[test-suite] ⚠ Skipping — required command "%s" not found.\n' "$first_word" | tee "$logfile"
    return
  fi

  local previous_dir
  previous_dir="$PWD"
  if [[ -n "$workdir" ]]; then
    cd "$workdir"
  fi

  set +o errexit
  bash -o pipefail -c "$cmd" \
    | tee "$logfile"
  local exit_code="${PIPESTATUS[0]}"
  set -o errexit

  cd "$previous_dir"

  if [[ "$exit_code" -eq 0 ]]; then
    STEP_STATUS["$name"]="PASSED"
  else
    STEP_STATUS["$name"]="FAILED (exit $exit_code)"
  fi
  STEP_LOG["$name"]="$logfile"
}

# Step registration ----------------------------------------------------------

if should_bootstrap_laravel; then
  STEPS+=("Bootstrap Laravel dependencies|$LARAVEL_DIR|composer install --no-interaction --prefer-dist")
fi

if [[ -n "$LARAVEL_DIR" ]]; then
  laravel_test_cmd="php artisan test --group=data-protection"
  if [[ -f "$LARAVEL_DIR/vendor/bin/paratest" ]]; then
    laravel_test_cmd="php artisan test --parallel --group=data-protection"
  fi

  STEPS+=(
    "Laravel PHPUnit suite|$LARAVEL_DIR|$laravel_test_cmd"
    "Laravel static analysis (PHPStan)|$LARAVEL_DIR|composer phpstan:data-protection"
    "Laravel Pint (format check)|$LARAVEL_DIR|./vendor/bin/pint --test"
  )
fi

if should_bootstrap_flutter; then
  STEPS+=("Bootstrap Flutter dependencies|$FLUTTER_DIR|flutter pub get")
fi

if [[ -n "$FLUTTER_DIR" ]]; then
  STEPS+=(
    "Flutter analyze|$FLUTTER_DIR|flutter analyze"
    "Flutter tests|$FLUTTER_DIR|flutter test"
  )
fi

if [[ ${#STEPS[@]} -eq 0 ]]; then
  echo "[test-suite] No known projects detected. Nothing to do." >&2
  exit 1
fi

# Execution -----------------------------------------------------------------

for entry in "${STEPS[@]}"; do
  IFS='|' read -r name workdir cmd <<<"$entry"
  run_step "$name" "$workdir" "$cmd"

done

printf '\n[test-suite] ▶ Summary\n'
exit_code=0
mapfile -t sorted_names < <(printf '%s\n' "${!STEP_STATUS[@]}" | sort)
for name in "${sorted_names[@]}"; do
  status="${STEP_STATUS[$name]}"
  log_path="${STEP_LOG[$name]}"
  printf '  - %-35s %s\n' "$name" "$status"
  printf '      log: %s\n' "${log_path#$ROOT_DIR/}"
  if [[ "$status" == FAILED* ]]; then
    exit_code=1
  fi
  if [[ "$status" == "" ]]; then
    exit_code=1
  fi
  printf '\n'

done

printf '[test-suite] Logs saved to %s\n' "${RUN_LOG_DIR#$ROOT_DIR/}"

exit "$exit_code"
