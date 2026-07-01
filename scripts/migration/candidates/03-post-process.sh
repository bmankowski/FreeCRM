#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
# shellcheck source=lib/common.sh
source "${SCRIPT_DIR}/lib/common.sh"
load_sync_env

require_docker_db

if [[ "${SYNC_DRY_RUN}" == "1" ]]; then
  log "dry-run" "would rebuild labels, clear Smarty cache, verify counts"
  exit 0
fi

log "verify" "staging ${STAGING_DB_NAME}: $(recruitment_counts "${STAGING_DB_NAME}")"
log "verify" "target ${LOCAL_DB_NAME}: $(recruitment_counts "${LOCAL_DB_NAME}")"
log "verify" "target ${LOCAL_DB_NAME}: $(accounts_contacts_counts "${LOCAL_DB_NAME}")"

log "post" "Rebuilding record labels (LBL_RECORD_LABEL_UPDATER)..."
for _ in $(seq 1 30); do
  docker_compose exec -T app php cron/vtigercron.php service=LBL_RECORD_LABEL_UPDATER 2>/dev/null || true
  remaining="$(mariadb_query "
    SELECT COUNT(*) FROM \`${LOCAL_DB_NAME}\`.vtiger_crmentity ce
    INNER JOIN \`${LOCAL_DB_NAME}\`.vtiger_tab t ON t.name = ce.setype AND t.presence = 0
    LEFT JOIN \`${LOCAL_DB_NAME}\`.u_yf_crmentity_label l ON l.crmid = ce.crmid
    WHERE ce.deleted = 0 AND ce.setype IN ('Candidates','ProjektyRekrutacyjne','Documents','Accounts','Contacts','ModComments')
      AND (l.label IS NULL OR l.label = '');
  " 2>/dev/null || echo "1")"
  log "post" "labels remaining (imported modules): ${remaining:-?}"
  [[ "${remaining:-1}" == "0" ]] && break
done

log "post" "Clearing Smarty compiled templates..."
rm -f "${ROOT_DIR}/cache/templates_c/"*.php 2>/dev/null || true

SAMPLE_ID="$(sample_candidate_id)"
if [[ -n "${SAMPLE_ID}" ]]; then
  log "verify" "Sample: ${FREECRM_BASE_URL}/index.php?module=Candidates&view=Detail&record=${SAMPLE_ID}"
fi
