#!/usr/bin/env bash
# FreeCRM — Verama job sync (fetch + import)
# @project FreeCRM
# @author bmankowski@gmail.com
#
# Runs inside the `cron` container (see docker/cron/crontab).

set -euo pipefail

ROOT="${FREECRM_ROOT:-/var/www/html}"
cd "$ROOT"

export PYTHONPATH="${PYTHONPATH:-$ROOT/scripts}"
export VERAMA_OUTPUT_DIR="${VERAMA_OUTPUT_DIR:-/var/www/import/jobs}"
export VERAMA_LOG_PATH="${VERAMA_LOG_PATH:-$ROOT/cache/logs/verama-job-sync.log}"

echo "[$(date -Is)] Verama job sync: fetch starting"
python3 -m verama_job_sync

echo "[$(date -Is)] Verama job sync: import starting"
gosu www-data php "$ROOT/src/Modules/ProjektyRekrutacyjne/scripts/importVeramaJobs.php"

echo "[$(date -Is)] Verama job sync: done"
