#!/usr/bin/env bash
set -euo pipefail

# Installs a rootless container runtime that is compatible with the Academy
# Symfony docker-compose stack. The script favours Podman because the execution
# environment used for automated reviews typically blocks privileged Docker
# daemons. Podman provides a Docker-compatible CLI while working without
# elevated kernel capabilities.

if [[ ${EUID:-$(id -u)} -ne 0 ]]; then
  echo "[runtime][error] This installer must be executed with sudo or as root." >&2
  exit 1
fi

if ! command -v apt-get >/dev/null 2>&1; then
  echo "[runtime][error] This script currently supports apt-based distributions only." >&2
  exit 1
fi

PACKAGES=(
  podman
  podman-docker
  podman-compose
  slirp4netns
  fuse-overlayfs
  uidmap
)

printf '[runtime] Updating apt repositories...\n'
apt-get update -y

printf '[runtime] Installing Podman toolchain (packages: %s)\n' "${PACKAGES[*]}"
apt-get install -y "${PACKAGES[@]}"

# Silence the Podman Docker compatibility warning so that existing scripts do
# not emit noisy hints for every docker CLI invocation.
touch /etc/containers/nodocker

ACTIVE_USER="${SUDO_USER:-root}"
if [[ -z "$ACTIVE_USER" ]]; then
  ACTIVE_USER="root"
fi

user_home() {
  local user="$1"
  if [[ "$user" = "root" ]]; then
    echo "/root"
  else
    getent passwd "$user" | cut -d: -f6
  fi
}

ensure_subid() {
  local file="$1" user="$2" range="$3"
  if ! grep -q "^${user}:" "$file" 2>/dev/null; then
    printf '[runtime] Configuring %s entry for %s\n' "${file##*/}" "$user"
    echo "${user}:${range}" >>"$file"
  fi
}

if [[ "$ACTIVE_USER" != "root" ]]; then
  ensure_subid /etc/subuid "$ACTIVE_USER" "100000:65536"
  ensure_subid /etc/subgid "$ACTIVE_USER" "100000:65536"
fi

HOME_DIR="$(user_home "$ACTIVE_USER")"
if [[ -n "$HOME_DIR" && -d "$HOME_DIR" ]]; then
  install -d -o "$ACTIVE_USER" -g "$ACTIVE_USER" "$HOME_DIR/.config/containers"
  cat <<'CONFIG' >"$HOME_DIR/.config/containers/containers.conf"
[engine]
cgroup_manager = "cgroupfs"
events_logger = "file"
CONFIG
  chown "$ACTIVE_USER":"$ACTIVE_USER" "$HOME_DIR/.config/containers/containers.conf"
fi

cat <<'NEXT_STEPS'
[runtime] Podman installation complete.
[runtime] Next steps:
  1. Re-open your shell so the docker->podman shim is active (provided by podman-docker).
  2. Run tools/setup_symfony_environment.sh to build and boot the stack.
  3. Use tools/screenshots/capture_symfony.sh to generate screenshots once the stack is healthy.
NEXT_STEPS
