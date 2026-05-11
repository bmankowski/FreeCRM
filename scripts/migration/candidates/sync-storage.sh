#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../../.." && pwd)"
cd "$ROOT_DIR"

REMOTE_HOST="${REMOTE_HOST:-10.0.0.220}"
REMOTE_WEB_ROOT="${REMOTE_WEB_ROOT:-/var/www/yetiforce}"

echo "[storage] rsync ${REMOTE_HOST}:${REMOTE_WEB_ROOT}/storage/ -> ./storage/"
# On some hosts (e.g. WSL2/Windows mounts), setting mtime/ownership/perms can fail with EPERM.
# We only need file contents for FreeCRM to serve attachments correctly.
rsync -rltD --delete --no-perms --no-owner --no-group --omit-dir-times --no-times -e ssh "${REMOTE_HOST}:${REMOTE_WEB_ROOT}/storage/" "$ROOT_DIR/storage/"
echo "[storage] ok"

