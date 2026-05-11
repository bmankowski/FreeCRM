#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../../.." && pwd)"
cd "$ROOT_DIR"

REMOTE_HOST="${REMOTE_HOST:-10.0.0.220}"

TMP_DIR="$ROOT_DIR/tmp/sync"
mkdir -p "$TMP_DIR"

echo "[fetch] copying dump artifacts to $TMP_DIR"
scp "$REMOTE_HOST:/tmp/kandydaci_core.sql" "$TMP_DIR/kandydaci_core.sql"
scp "$REMOTE_HOST:/tmp/projekty_core.sql" "$TMP_DIR/projekty_core.sql"
scp "$REMOTE_HOST:/tmp/kandydaci_relations.sql" "$TMP_DIR/kandydaci_relations.sql"
scp "$REMOTE_HOST:/tmp/modcomments_core.sql" "$TMP_DIR/modcomments_core.sql"
scp "$REMOTE_HOST:/tmp/kandydaci_ids.tsv" "$TMP_DIR/kandydaci_ids.tsv"
scp "$REMOTE_HOST:/tmp/projekty_ids.tsv" "$TMP_DIR/projekty_ids.tsv"
scp "$REMOTE_HOST:/tmp/modcomments_ids.tsv" "$TMP_DIR/modcomments_ids.tsv"

echo "[fetch] ok"

