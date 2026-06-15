#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
# shellcheck source=lib/common.sh
source "${SCRIPT_DIR}/lib/common.sh"
load_sync_env

require_docker_db

SRC="${STAGING_DB_NAME}"
TGT="${LOCAL_DB_NAME}"

if [[ "${SYNC_DRY_RUN}" == "1" ]]; then
  log "filter" "would drop internal documents whose files are missing on storage"
  exit 0
fi

if ! table_exists_in_db "$TGT" "tmp_imp_document_ids"; then
  log "filter" "tmp_imp_document_ids missing — skip"
  exit 0
fi

if ! column_exists_in_db "$SRC" "vtiger_notes" "filelocationtype"; then
  log "filter" "source has no legacy document columns — skip"
  exit 0
fi

mariadb_exec "
DROP TABLE IF EXISTS \`${TGT}\`.tmp_imp_document_meta;
CREATE TABLE \`${TGT}\`.tmp_imp_document_meta (
  notesid INT NOT NULL PRIMARY KEY,
  is_external TINYINT(1) NOT NULL DEFAULT 0,
  storage_path VARCHAR(500) DEFAULT NULL
) ENGINE=InnoDB;
INSERT INTO \`${TGT}\`.tmp_imp_document_meta (notesid, is_external, storage_path)
SELECT d.notesid,
       CASE WHEN yn.filelocationtype = 'E' THEN 1 ELSE 0 END,
       CASE
         WHEN yn.filelocationtype = 'E' THEN NULL
         WHEN a.attachmentsid IS NULL THEN NULL
         ELSE CONCAT(a.path, a.attachmentsid, '_', a.name)
       END
FROM \`${TGT}\`.tmp_imp_document_ids d
INNER JOIN \`${SRC}\`.vtiger_notes yn ON yn.notesid = d.notesid
LEFT JOIN \`${SRC}\`.vtiger_seattachmentsrel s ON s.crmid = d.notesid
LEFT JOIN \`${SRC}\`.vtiger_attachments a ON a.attachmentsid = s.attachmentsid;
"

total="$(mariadb_query "SELECT COUNT(*) FROM \`${TGT}\`.tmp_imp_document_ids;" 2>/dev/null || echo 0)"
checked=0
skipped_external=0
dropped=0

mariadb_exec "
DROP TABLE IF EXISTS \`${TGT}\`.tmp_imp_document_drop;
CREATE TABLE \`${TGT}\`.tmp_imp_document_drop (notesid INT NOT NULL PRIMARY KEY) ENGINE=InnoDB;
"

while IFS=$'\t' read -r notesid is_external storage_path; do
  [[ -z "${notesid}" || ! "${notesid}" =~ ^[0-9]+$ ]] && continue
  if [[ "${is_external}" == "1" ]]; then
    skipped_external=$((skipped_external + 1))
    continue
  fi
  checked=$((checked + 1))
  if document_storage_file_exists "${storage_path}"; then
    continue
  fi
  mariadb_exec "INSERT IGNORE INTO \`${TGT}\`.tmp_imp_document_drop (notesid) VALUES (${notesid});"
  dropped=$((dropped + 1))
done < <(mariadb_query "
  SELECT notesid, is_external, IFNULL(storage_path, '')
  FROM \`${TGT}\`.tmp_imp_document_meta
  ORDER BY notesid;
")

if [[ "${dropped}" -gt 0 ]]; then
  mariadb_exec "
DELETE d FROM \`${TGT}\`.tmp_imp_document_ids d
  INNER JOIN \`${TGT}\`.tmp_imp_document_drop x ON x.notesid = d.notesid;
DELETE a FROM \`${TGT}\`.tmp_imp_attachment_ids a
  INNER JOIN \`${SRC}\`.vtiger_seattachmentsrel s ON s.attachmentsid = a.attachmentsid
  INNER JOIN \`${TGT}\`.tmp_imp_document_drop x ON x.notesid = s.crmid;
"
fi

mariadb_exec "
DROP TABLE IF EXISTS \`${TGT}\`.tmp_imp_document_meta;
DROP TABLE IF EXISTS \`${TGT}\`.tmp_imp_document_drop;
"

remaining="$(mariadb_query "SELECT COUNT(*) FROM \`${TGT}\`.tmp_imp_document_ids;" 2>/dev/null || echo 0)"
log "filter" "documents: total=${total} external=${skipped_external} checked=${checked} dropped=${dropped} remaining=${remaining}"
