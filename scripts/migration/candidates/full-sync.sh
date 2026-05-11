#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../../.." && pwd)"
cd "$ROOT_DIR"

SCRIPT_DIR="$ROOT_DIR/script/migration/candidates"
TMP_DIR="$ROOT_DIR/tmp/sync"
BACKUP_DIR="$ROOT_DIR/tmp/db-backups"

REMOTE_HOST="${REMOTE_HOST:-10.0.0.220}"
REMOTE_DB_HOST="${REMOTE_DB_HOST:-localhost}"
REMOTE_DB_NAME="${REMOTE_DB_NAME:-yetiforce2}"
REMOTE_DB_USER="${REMOTE_DB_USER:-yetiforceuser}"
REMOTE_DB_PASS="${REMOTE_DB_PASS:-yetiforcepassword}"
REMOTE_WEB_ROOT="${REMOTE_WEB_ROOT:-/var/www/yetiforce}"

LOCAL_DB_NAME="${LOCAL_DB_NAME:-freecrm}"
LOCAL_DB_USER="${LOCAL_DB_USER:-root}"
LOCAL_DB_PASS="${LOCAL_DB_PASS:-root}"

echo "[sync] repo: $ROOT_DIR"
echo "[sync] remote: $REMOTE_HOST (db=$REMOTE_DB_NAME)"

mkdir -p "$TMP_DIR" "$BACKUP_DIR"

echo "[sync] 1) Local DB backup (rollback point)"
"$SCRIPT_DIR/local-backup.sh"

echo "[sync] 2) Export on remote + copy artifacts"
"$SCRIPT_DIR/remote-export.sh"
"$SCRIPT_DIR/fetch-artifacts.sh"

echo "[sync] 3) Sync storage/ from remote"
"$SCRIPT_DIR/sync-storage.sh"

echo "[sync] 4) Apply DB replace on localhost"
"$SCRIPT_DIR/local-apply.sh"

echo "[sync] Done."

