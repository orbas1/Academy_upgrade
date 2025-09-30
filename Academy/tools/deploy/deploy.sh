#!/usr/bin/env bash
set -euo pipefail

APP_ENVIRONMENT="production"
OUTPUT_DIR="build/releases"
INCLUDE_MOBILE=1
INCLUDE_IOS=1
INCLUDE_ANDROID=1

print_usage() {
  cat <<USAGE
Usage: $0 [--env=<environment>] [--output=<dir>] [--skip-mobile] [--skip-ios] [--skip-android]

Builds a production-ready release bundle for the Laravel backend and Flutter
mobile applications. The script executes composer/npm installs, optimises the
framework caches, compiles assets, and assembles mobile binaries for
TestFlight/Play distribution.
USAGE
}

for arg in "$@"; do
  case "$arg" in
    --env=*)
      APP_ENVIRONMENT="${arg#*=}"
      shift
      ;;
    --output=*)
      OUTPUT_DIR="${arg#*=}"
      shift
      ;;
    --skip-mobile)
      INCLUDE_MOBILE=0
      shift
      ;;
    --skip-ios)
      INCLUDE_IOS=0
      shift
      ;;
    --skip-android)
      INCLUDE_ANDROID=0
      shift
      ;;
    --help)
      print_usage
      exit 0
      ;;
    *)
      echo "Unknown argument: $arg" >&2
      print_usage
      exit 1
      ;;
  esac
done

BACKEND_DIR="Web_Application/Academy-LMS"
MOBILE_DIR="Student Mobile APP/academy_lms_app"

if [[ ! -d "$BACKEND_DIR" ]]; then
  echo "Backend directory $BACKEND_DIR not found" >&2
  exit 2
fi

mkdir -p "$OUTPUT_DIR"

pushd "$BACKEND_DIR" >/dev/null

if [[ ! -f composer.lock ]]; then
  echo "composer.lock missing; run composer install once before packaging" >&2
  exit 3
fi

echo "[deploy] Installing PHP dependencies"
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

echo "[deploy] Installing Node dependencies"
npm ci --no-audit --progress false

echo "[deploy] Building production assets"
APP_ENV="$APP_ENVIRONMENT" npm run build -- --mode=production

echo "[deploy] Optimising Laravel caches"
php artisan config:clear
php artisan event:cache
php artisan route:cache
php artisan view:cache

echo "[deploy] Running database migrations"
php artisan migrate --force --env=$APP_ENVIRONMENT

if command -v php >/dev/null; then
  echo "[deploy] Refreshing horizon to pick up new code"
  php artisan horizon:terminate --wait || true
fi

BACKEND_RELEASE_DIR="../../${OUTPUT_DIR}/backend"
rm -rf "$BACKEND_RELEASE_DIR"
mkdir -p "$BACKEND_RELEASE_DIR"

echo "[deploy] Staging backend release artifacts"
rsync -a --delete \
  --exclude='.env' \
  --exclude='node_modules' \
  --exclude='storage/logs/*.log' \
  --exclude='tests' \
  ./ "$BACKEND_RELEASE_DIR"/

popd >/dev/null

if [[ $INCLUDE_MOBILE -eq 1 ]]; then
  if [[ ! -d "$MOBILE_DIR" ]]; then
    echo "Mobile directory $MOBILE_DIR not found" >&2
    exit 4
  fi

  pushd "$MOBILE_DIR" >/dev/null
  echo "[deploy] Fetching Flutter dependencies"
  flutter pub get

  if [[ $INCLUDE_ANDROID -eq 1 ]]; then
    echo "[deploy] Building Android appbundle"
    flutter build appbundle --flavor $APP_ENVIRONMENT --dart-define=ACADEMY_APP_ENV=$APP_ENVIRONMENT
  fi

  if [[ $INCLUDE_IOS -eq 1 ]]; then
    if [[ "$(uname -s)" == "Darwin" ]]; then
      echo "[deploy] Building iOS IPA"
      flutter build ipa --flavor $APP_ENVIRONMENT --no-codesign --dart-define=ACADEMY_APP_ENV=$APP_ENVIRONMENT
    else
      echo "[deploy] Skipping iOS build (host OS is not macOS)" >&2
    fi
  fi

  MOBILE_RELEASE_DIR="../../${OUTPUT_DIR}/mobile"
  rm -rf "$MOBILE_RELEASE_DIR"
  mkdir -p "$MOBILE_RELEASE_DIR"

  if [[ $INCLUDE_ANDROID -eq 1 ]]; then
    find build/app/outputs -name '*.aab' -print -exec cp {} "$MOBILE_RELEASE_DIR"/ \;
  fi
  if [[ $INCLUDE_IOS -eq 1 && "$(uname -s)" == "Darwin" ]]; then
    find build/ios/ipa -name '*.ipa' -print -exec cp {} "$MOBILE_RELEASE_DIR"/ \;
  fi

  popd >/dev/null
fi

pushd "$OUTPUT_DIR" >/dev/null
RELEASE_ARCHIVE="academy-${APP_ENVIRONMENT}-$(date -u +%Y%m%d%H%M%S).tar.gz"

tar_components=(backend)
if [[ $INCLUDE_MOBILE -eq 1 ]]; then
  tar_components+=(mobile)
fi

echo "[deploy] Creating release archive $RELEASE_ARCHIVE"
tar -czf "$RELEASE_ARCHIVE" "${tar_components[@]}"

popd >/dev/null

echo "[deploy] Release artifacts available in $OUTPUT_DIR"
