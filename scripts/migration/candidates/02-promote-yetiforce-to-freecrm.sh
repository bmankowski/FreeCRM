#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
# shellcheck source=lib/common.sh
source "${SCRIPT_DIR}/lib/common.sh"
load_sync_env

require_docker_db

SRC="${STAGING_DB_NAME}"
TGT="${LOCAL_DB_NAME}"
FB="${IMPORT_FALLBACK_USER_ID}"

log "promote" "before: ${TGT} $(recruitment_counts "${TGT}")"
log "promote" "source: ${SRC} $(recruitment_counts "${SRC}")"

if [[ "${SYNC_DRY_RUN}" == "1" ]]; then
  log "dry-run" "would build temp ID tables, delete scoped rows in ${TGT}, copy from ${SRC}, transform documents"
  exit 0
fi

LEGACY_DOC_DELETE_SQL=""
if table_exists_in_db "$TGT" "vtiger_seattachmentsrel"; then
  LEGACY_DOC_DELETE_SQL+="
DELETE rel FROM \`${TGT}\`.vtiger_seattachmentsrel rel
  INNER JOIN \`${TGT}\`.tmp_imp_document_ids d ON d.notesid = rel.crmid;"
fi
if table_exists_in_db "$TGT" "vtiger_attachments"; then
  LEGACY_DOC_DELETE_SQL+="
DELETE att FROM \`${TGT}\`.vtiger_attachments att
  INNER JOIN \`${TGT}\`.tmp_imp_attachment_ids t ON t.attachmentsid = att.attachmentsid;"
fi

BUILD_SQL=$(cat <<SQL
SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS \`${TGT}\`.tmp_imp_candidates_ids;
DROP TABLE IF EXISTS \`${TGT}\`.tmp_imp_projekty_ids;
DROP TABLE IF EXISTS \`${TGT}\`.tmp_imp_related_to_ids;
DROP TABLE IF EXISTS \`${TGT}\`.tmp_imp_modcomments_ids;
DROP TABLE IF EXISTS \`${TGT}\`.tmp_imp_document_ids;
DROP TABLE IF EXISTS \`${TGT}\`.tmp_imp_attachment_ids;

CREATE TABLE \`${TGT}\`.tmp_imp_candidates_ids (crmid INT NOT NULL PRIMARY KEY) ENGINE=InnoDB;
INSERT INTO \`${TGT}\`.tmp_imp_candidates_ids (crmid)
  SELECT crmid FROM \`${SRC}\`.vtiger_crmentity
  WHERE setype IN ('Candidates', 'Kandydaci') AND deleted=0;

CREATE TABLE \`${TGT}\`.tmp_imp_projekty_ids (crmid INT NOT NULL PRIMARY KEY) ENGINE=InnoDB;
INSERT INTO \`${TGT}\`.tmp_imp_projekty_ids (crmid)
  SELECT crmid FROM \`${SRC}\`.vtiger_crmentity WHERE setype='ProjektyRekrutacyjne' AND deleted=0;

CREATE TABLE \`${TGT}\`.tmp_imp_related_to_ids (crmid INT NOT NULL PRIMARY KEY) ENGINE=InnoDB;
INSERT INTO \`${TGT}\`.tmp_imp_related_to_ids (crmid) SELECT crmid FROM \`${TGT}\`.tmp_imp_candidates_ids;
INSERT IGNORE INTO \`${TGT}\`.tmp_imp_related_to_ids (crmid) SELECT crmid FROM \`${TGT}\`.tmp_imp_projekty_ids;

CREATE TABLE \`${TGT}\`.tmp_imp_modcomments_ids (crmid INT NOT NULL PRIMARY KEY) ENGINE=InnoDB;
INSERT INTO \`${TGT}\`.tmp_imp_modcomments_ids (crmid)
  SELECT modcommentsid FROM \`${SRC}\`.vtiger_modcomments
  WHERE related_to IN (SELECT crmid FROM \`${TGT}\`.tmp_imp_related_to_ids);

CREATE TABLE \`${TGT}\`.tmp_imp_document_ids (notesid INT NOT NULL PRIMARY KEY) ENGINE=InnoDB;
INSERT INTO \`${TGT}\`.tmp_imp_document_ids (notesid)
  SELECT DISTINCT sn.notesid
  FROM \`${SRC}\`.vtiger_senotesrel sn
  INNER JOIN \`${SRC}\`.vtiger_crmentity ce
    ON ce.crmid = sn.notesid AND ce.setype = 'Documents' AND ce.deleted = 0
  WHERE sn.crmid IN (SELECT crmid FROM \`${TGT}\`.tmp_imp_related_to_ids);

CREATE TABLE \`${TGT}\`.tmp_imp_attachment_ids (attachmentsid INT NOT NULL PRIMARY KEY) ENGINE=InnoDB;
INSERT INTO \`${TGT}\`.tmp_imp_attachment_ids (attachmentsid)
  SELECT DISTINCT rel.attachmentsid FROM \`${SRC}\`.vtiger_seattachmentsrel rel
  WHERE rel.crmid IN (SELECT notesid FROM \`${TGT}\`.tmp_imp_document_ids);
${LEGACY_DOC_DELETE_SQL}

DELETE sn FROM \`${TGT}\`.vtiger_senotesrel sn
  WHERE sn.crmid IN (SELECT crmid FROM \`${TGT}\`.tmp_imp_related_to_ids)
     OR sn.notesid IN (SELECT notesid FROM \`${TGT}\`.tmp_imp_document_ids);

DELETE cf FROM \`${TGT}\`.vtiger_modcommentscf cf
  INNER JOIN \`${TGT}\`.tmp_imp_modcomments_ids m ON m.crmid = cf.modcommentsid;
DELETE mc FROM \`${TGT}\`.vtiger_modcomments mc
  INNER JOIN \`${TGT}\`.tmp_imp_modcomments_ids m ON m.crmid = mc.modcommentsid;
DELETE ce FROM \`${TGT}\`.vtiger_crmentity ce
  INNER JOIN \`${TGT}\`.tmp_imp_modcomments_ids m ON m.crmid = ce.crmid;
SQL
)

if table_exists_in_db "$TGT" "vtiger_notescf"; then
  BUILD_SQL+="
DELETE cf FROM \`${TGT}\`.vtiger_notescf cf
  INNER JOIN \`${TGT}\`.tmp_imp_document_ids d ON d.notesid = cf.notesid;"
fi

BUILD_SQL+="
DELETE n FROM \`${TGT}\`.vtiger_notes n
  INNER JOIN \`${TGT}\`.tmp_imp_document_ids d ON d.notesid = n.notesid;
DELETE ce FROM \`${TGT}\`.vtiger_crmentity ce
  INNER JOIN \`${TGT}\`.tmp_imp_document_ids d ON d.notesid = ce.crmid;

DELETE FROM \`${TGT}\`.u_yf_projekty_rekrutacyjne_relations_members_entity;

DELETE FROM \`${TGT}\`.u_yf_candidatescf;
DELETE FROM \`${TGT}\`.u_yf_candidates;
DELETE ce FROM \`${TGT}\`.vtiger_crmentity ce WHERE ce.setype='Candidates';

DELETE FROM \`${TGT}\`.u_yf_projektyrekrutacyjnecf;
DELETE FROM \`${TGT}\`.u_yf_projektyrekrutacyjne;
DELETE ce FROM \`${TGT}\`.vtiger_crmentity ce WHERE ce.setype='ProjektyRekrutacyjne';

DELETE ce FROM \`${TGT}\`.vtiger_crmentity ce
  INNER JOIN \`${TGT}\`.tmp_imp_projekty_ids t ON t.crmid = ce.crmid;
DELETE ce FROM \`${TGT}\`.vtiger_crmentity ce
  INNER JOIN \`${TGT}\`.tmp_imp_candidates_ids t ON t.crmid = ce.crmid;

DELETE lbl FROM \`${TGT}\`.u_yf_crmentity_label lbl
  WHERE lbl.crmid IN (SELECT crmid FROM \`${TGT}\`.tmp_imp_related_to_ids)
     OR lbl.crmid IN (SELECT crmid FROM \`${TGT}\`.tmp_imp_modcomments_ids)
     OR lbl.crmid IN (SELECT notesid FROM \`${TGT}\`.tmp_imp_document_ids);
DELETE sl FROM \`${TGT}\`.u_yf_crmentity_search_label sl
  WHERE sl.crmid IN (SELECT crmid FROM \`${TGT}\`.tmp_imp_related_to_ids)
     OR sl.crmid IN (SELECT crmid FROM \`${TGT}\`.tmp_imp_modcomments_ids)
     OR sl.crmid IN (SELECT notesid FROM \`${TGT}\`.tmp_imp_document_ids);
DELETE lc FROM \`${TGT}\`.u_yf_crmentity_last_changes lc
  WHERE lc.crmid IN (SELECT crmid FROM \`${TGT}\`.tmp_imp_related_to_ids)
     OR lc.crmid IN (SELECT crmid FROM \`${TGT}\`.tmp_imp_modcomments_ids)
     OR lc.crmid IN (SELECT notesid FROM \`${TGT}\`.tmp_imp_document_ids);

SET FOREIGN_KEY_CHECKS=1;
"

log "promote" "Building ID sets and removing old rows in ${TGT}..."
mariadb_exec "$BUILD_SQL"

"${SCRIPT_DIR}/02a-filter-missing-document-files.sh"

log "promote" "Copying projects..."
sql_copy_table "$TGT" "$SRC" vtiger_crmentity "WHERE crmid IN (SELECT crmid FROM \`${TGT}\`.tmp_imp_projekty_ids)"
sql_copy_table "$TGT" "$SRC" u_yf_projektyrekrutacyjne "WHERE projektyrekrutacyjneid IN (SELECT crmid FROM \`${TGT}\`.tmp_imp_projekty_ids)"
sql_copy_table "$TGT" "$SRC" u_yf_projektyrekrutacyjnecf "WHERE projektyrekrutacyjneid IN (SELECT crmid FROM \`${TGT}\`.tmp_imp_projekty_ids)"

log "promote" "Copying candidates (Kandydaci → Candidates when needed)..."
copy_candidates_from_source "$TGT" "$SRC"

log "promote" "Copying relations..."
sql_copy_table "$TGT" "$SRC" u_yf_projekty_rekrutacyjne_relations_members_entity \
  "WHERE relcrmid IN (SELECT crmid FROM \`${TGT}\`.tmp_imp_candidates_ids) OR crmid IN (SELECT crmid FROM \`${TGT}\`.tmp_imp_projekty_ids)"

log "promote" "Copying comments..."
sql_copy_table "$TGT" "$SRC" vtiger_crmentity "WHERE crmid IN (SELECT crmid FROM \`${TGT}\`.tmp_imp_modcomments_ids)"
sql_copy_table "$TGT" "$SRC" vtiger_modcomments "WHERE modcommentsid IN (SELECT crmid FROM \`${TGT}\`.tmp_imp_modcomments_ids)"
sql_copy_table "$TGT" "$SRC" vtiger_modcommentscf "WHERE modcommentsid IN (SELECT crmid FROM \`${TGT}\`.tmp_imp_modcomments_ids)"

log "promote" "Copying documents..."
sql_copy_table "$TGT" "$SRC" vtiger_crmentity "WHERE crmid IN (SELECT notesid FROM \`${TGT}\`.tmp_imp_document_ids)"
sql_copy_table "$TGT" "$SRC" vtiger_notes "WHERE notesid IN (SELECT notesid FROM \`${TGT}\`.tmp_imp_document_ids)"
if table_exists_in_db "$TGT" "vtiger_notescf"; then
  sql_copy_table "$TGT" "$SRC" vtiger_notescf "WHERE notesid IN (SELECT notesid FROM \`${TGT}\`.tmp_imp_document_ids)"
fi
sql_copy_table "$TGT" "$SRC" vtiger_senotesrel \
  "WHERE crmid IN (SELECT crmid FROM \`${TGT}\`.tmp_imp_related_to_ids) OR notesid IN (SELECT notesid FROM \`${TGT}\`.tmp_imp_document_ids)"
if table_exists_in_db "$TGT" "vtiger_attachments"; then
  sql_copy_table "$TGT" "$SRC" vtiger_attachments \
    "WHERE attachmentsid IN (SELECT attachmentsid FROM \`${TGT}\`.tmp_imp_attachment_ids)"
fi
if table_exists_in_db "$TGT" "vtiger_seattachmentsrel"; then
  sql_copy_table "$TGT" "$SRC" vtiger_seattachmentsrel \
    "WHERE crmid IN (SELECT notesid FROM \`${TGT}\`.tmp_imp_document_ids)"
fi

if column_exists_in_db "$TGT" "vtiger_notes" "storage_path" \
   && column_exists_in_db "$SRC" "vtiger_notes" "filelocationtype"; then
  log "promote" "Transforming documents to storage_path schema..."
  mariadb_exec "
UPDATE \`${TGT}\`.vtiger_notes n
INNER JOIN \`${SRC}\`.vtiger_notes yn ON yn.notesid = n.notesid
INNER JOIN \`${TGT}\`.tmp_imp_document_ids d ON d.notesid = n.notesid
SET n.location_type = CASE WHEN yn.filelocationtype = 'E' THEN 'external' ELSE 'internal' END,
    n.mime_type = NULLIF(yn.filetype, ''),
    n.size_bytes = COALESCE(yn.filesize, 0),
    n.download_count = COALESCE(yn.filedownloadcount, 0),
    n.active = COALESCE(yn.filestatus, 1),
    n.external_url = CASE WHEN yn.filelocationtype = 'E' THEN yn.filename ELSE NULL END,
    n.original_name = CASE WHEN yn.filelocationtype = 'E' THEN NULL ELSE yn.filename END;
"
  if table_exists_in_db "$SRC" "vtiger_seattachmentsrel" && table_exists_in_db "$SRC" "vtiger_attachments"; then
    mariadb_exec "
UPDATE \`${TGT}\`.vtiger_notes n
INNER JOIN \`${SRC}\`.vtiger_seattachmentsrel s ON s.crmid = n.notesid
INNER JOIN \`${SRC}\`.vtiger_attachments a ON a.attachmentsid = s.attachmentsid
INNER JOIN \`${TGT}\`.tmp_imp_document_ids d ON d.notesid = n.notesid
SET n.storage_path = CONCAT(a.path, a.attachmentsid)
WHERE n.location_type = 'internal'
  AND (n.storage_path IS NULL OR n.storage_path = '');
"
  fi
fi

log "promote" "Fixing missing owners (fallback user ${FB})..."
mariadb_exec "
UPDATE \`${TGT}\`.vtiger_crmentity ce
LEFT JOIN \`${TGT}\`.vtiger_users u ON u.id = ce.smownerid
SET ce.smownerid = ${FB}
WHERE (ce.crmid IN (SELECT crmid FROM \`${TGT}\`.tmp_imp_related_to_ids)
    OR ce.crmid IN (SELECT crmid FROM \`${TGT}\`.tmp_imp_modcomments_ids)
    OR ce.crmid IN (SELECT notesid FROM \`${TGT}\`.tmp_imp_document_ids))
  AND u.id IS NULL;
UPDATE \`${TGT}\`.vtiger_crmentity ce
LEFT JOIN \`${TGT}\`.vtiger_users u ON u.id = ce.smcreatorid
SET ce.smcreatorid = ${FB}
WHERE (ce.crmid IN (SELECT crmid FROM \`${TGT}\`.tmp_imp_related_to_ids)
    OR ce.crmid IN (SELECT crmid FROM \`${TGT}\`.tmp_imp_modcomments_ids)
    OR ce.crmid IN (SELECT notesid FROM \`${TGT}\`.tmp_imp_document_ids))
  AND u.id IS NULL;
"

mariadb_exec "
DROP TABLE IF EXISTS \`${TGT}\`.tmp_imp_candidates_ids;
DROP TABLE IF EXISTS \`${TGT}\`.tmp_imp_projekty_ids;
DROP TABLE IF EXISTS \`${TGT}\`.tmp_imp_related_to_ids;
DROP TABLE IF EXISTS \`${TGT}\`.tmp_imp_modcomments_ids;
DROP TABLE IF EXISTS \`${TGT}\`.tmp_imp_document_ids;
DROP TABLE IF EXISTS \`${TGT}\`.tmp_imp_attachment_ids;
"

log "promote" "after: ${TGT} $(recruitment_counts "${TGT}")"
