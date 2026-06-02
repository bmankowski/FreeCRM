#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
# shellcheck source=lib/common.sh
source "${SCRIPT_DIR}/lib/common.sh"
load_sync_env

require_docker_db
if [[ "${SYNC_SKIP_DUMP}" != "1" ]]; then
  require_ssh
fi

mkdir -p "$TMP_DIR"

STAMP="$(date +%F_%H%M%S)"
DUMP_FILE="${TMP_DIR}/yetiforce2-${STAMP}.sql.gz"

if [[ "${SYNC_SKIP_DUMP}" == "1" ]]; then
  existing="$(latest_yetiforce_dump)"
  [[ -n "$existing" ]] || die "SYNC_SKIP_DUMP=1 but no tmp/sync/yetiforce2-*.sql.gz found"
  log "dump" "Reusing ${existing}"
  DUMP_FILE="$existing"
else
  log "dump" "Exporting ${REMOTE_DB_NAME} from ${REMOTE_HOST} (this may take a while)..."
  if [[ "${SYNC_DRY_RUN}" == "1" ]]; then
    log "dry-run" "would ssh ${REMOTE_HOST} mysqldump | gzip > ${DUMP_FILE}"
  else
    ssh "${REMOTE_HOST}" \
      "mysqldump -h'${REMOTE_DB_HOST}' -u'${REMOTE_DB_USER}' -p'${REMOTE_DB_PASS}' \
        --single-transaction --routines --triggers --no-tablespaces '${REMOTE_DB_NAME}' | gzip -c" \
      > "${DUMP_FILE}"
    log "dump" "Saved $(du -h "${DUMP_FILE}" | awk '{print $1}') → ${DUMP_FILE}"
  fi
fi

if [[ "${SYNC_DRY_RUN}" == "1" ]]; then
  log "dry-run" "would import ${DUMP_FILE} into ${STAGING_DB_NAME}"
  exit 0
fi

log "dump" "Restoring into local ${STAGING_DB_NAME} (--drop-first)"
DB_ROOT_PASS="${LOCAL_DB_PASS}" DB_ROOT_USER=root "${ROOT_DIR}/scripts/import-db.sh" "${DUMP_FILE}" --root --drop-first \
  --db-name "${STAGING_DB_NAME}" --db-pass "${LOCAL_DB_PASS}"

log "verify" "${STAGING_DB_NAME}: $(recruitment_counts "${STAGING_DB_NAME}")"
