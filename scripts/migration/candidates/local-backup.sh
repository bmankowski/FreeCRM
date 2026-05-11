#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../../.." && pwd)"
cd "$ROOT_DIR"

BACKUP_DIR="$ROOT_DIR/tmp/db-backups"
mkdir -p "$BACKUP_DIR"

LOCAL_DB_NAME="${LOCAL_DB_NAME:-freecrm}"
LOCAL_DB_USER="${LOCAL_DB_USER:-root}"
LOCAL_DB_PASS="${LOCAL_DB_PASS:-root}"

STAMP="$(date +%F_%H%M%S)"
OUT="$BACKUP_DIR/${LOCAL_DB_NAME}-${STAMP}.sql"

echo "[backup] dumping local db to $OUT"
sudo docker compose exec -T db sh -lc "mariadb-dump -u${LOCAL_DB_USER} -p${LOCAL_DB_PASS} --single-transaction --routines --triggers ${LOCAL_DB_NAME}" > "$OUT"
echo "[backup] ok"

