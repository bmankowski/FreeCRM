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

copy_module_rows() {
  local id_table="$1"
  shift
  local -a specs=("$@")
  local spec table pk
  for spec in "${specs[@]}"; do
    table="${spec%%:*}"
    pk="${spec##*:}"
    if ! intersection_columns "$TGT" "$SRC" "$table" >/dev/null 2>&1; then
      log "refs" "skip ${table} (no shared columns)"
      continue
    fi
    sql_copy_table "$TGT" "$SRC" "$table" \
      "WHERE \`${pk}\` IN (SELECT crmid FROM \`${TGT}\`.${id_table})"
  done
}

log "refs" "Syncing all Accounts/Contacts from ${SRC}..."

if [[ "${SYNC_DRY_RUN}" == "1" ]]; then
  log "dry-run" "would sync all Accounts (kontrahenci) and Contacts from ${SRC}"
  exit 0
fi

mariadb_exec "
SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS \`${TGT}\`.tmp_imp_account_ids;
DROP TABLE IF EXISTS \`${TGT}\`.tmp_imp_contact_ids;

CREATE TABLE \`${TGT}\`.tmp_imp_account_ids (crmid INT NOT NULL PRIMARY KEY) ENGINE=InnoDB;
INSERT INTO \`${TGT}\`.tmp_imp_account_ids (crmid)
  SELECT crmid FROM \`${SRC}\`.vtiger_crmentity
  WHERE setype = 'Accounts' AND deleted = 0;

CREATE TABLE \`${TGT}\`.tmp_imp_contact_ids (crmid INT NOT NULL PRIMARY KEY) ENGINE=InnoDB;
INSERT INTO \`${TGT}\`.tmp_imp_contact_ids (crmid)
  SELECT crmid FROM \`${SRC}\`.vtiger_crmentity
  WHERE setype = 'Contacts' AND deleted = 0;
"

ACCOUNTS="$(mariadb_query "SELECT COUNT(*) FROM \`${TGT}\`.tmp_imp_account_ids;")"
CONTACTS="$(mariadb_query "SELECT COUNT(*) FROM \`${TGT}\`.tmp_imp_contact_ids;")"
log "refs" "account_ids=${ACCOUNTS} contact_ids=${CONTACTS}"

mariadb_exec "
SET FOREIGN_KEY_CHECKS=0;

DELETE s FROM \`${TGT}\`.vtiger_entity_stats s
  INNER JOIN \`${TGT}\`.tmp_imp_account_ids t ON t.crmid = s.crmid;
DELETE s FROM \`${TGT}\`.vtiger_entity_stats s
  INNER JOIN \`${TGT}\`.tmp_imp_contact_ids t ON t.crmid = s.crmid;

DELETE cf FROM \`${TGT}\`.vtiger_accountscf cf
  INNER JOIN \`${TGT}\`.tmp_imp_account_ids t ON t.crmid = cf.accountid;
DELETE ad FROM \`${TGT}\`.vtiger_accountaddress ad
  INNER JOIN \`${TGT}\`.tmp_imp_account_ids t ON t.crmid = ad.accountaddressid;
DELETE a FROM \`${TGT}\`.vtiger_account a
  INNER JOIN \`${TGT}\`.tmp_imp_account_ids t ON t.crmid = a.accountid;
DELETE ce FROM \`${TGT}\`.vtiger_crmentity ce
  INNER JOIN \`${TGT}\`.tmp_imp_account_ids t ON t.crmid = ce.crmid;

DELETE cd FROM \`${TGT}\`.vtiger_customerdetails cd
  INNER JOIN \`${TGT}\`.tmp_imp_contact_ids t ON t.crmid = cd.customerid;
DELETE sd FROM \`${TGT}\`.vtiger_contactsubdetails sd
  INNER JOIN \`${TGT}\`.tmp_imp_contact_ids t ON t.crmid = sd.contactsubscriptionid;
DELETE cf FROM \`${TGT}\`.vtiger_contactscf cf
  INNER JOIN \`${TGT}\`.tmp_imp_contact_ids t ON t.crmid = cf.contactid;
DELETE ad FROM \`${TGT}\`.vtiger_contactaddress ad
  INNER JOIN \`${TGT}\`.tmp_imp_contact_ids t ON t.crmid = ad.contactaddressid;
DELETE c FROM \`${TGT}\`.vtiger_contactdetails c
  INNER JOIN \`${TGT}\`.tmp_imp_contact_ids t ON t.crmid = c.contactid;
DELETE ce FROM \`${TGT}\`.vtiger_crmentity ce
  INNER JOIN \`${TGT}\`.tmp_imp_contact_ids t ON t.crmid = ce.crmid;

DELETE lbl FROM \`${TGT}\`.u_yf_crmentity_label lbl
  WHERE lbl.crmid IN (SELECT crmid FROM \`${TGT}\`.tmp_imp_account_ids)
     OR lbl.crmid IN (SELECT crmid FROM \`${TGT}\`.tmp_imp_contact_ids);
DELETE sl FROM \`${TGT}\`.u_yf_crmentity_search_label sl
  WHERE sl.crmid IN (SELECT crmid FROM \`${TGT}\`.tmp_imp_account_ids)
     OR sl.crmid IN (SELECT crmid FROM \`${TGT}\`.tmp_imp_contact_ids);
DELETE lc FROM \`${TGT}\`.u_yf_crmentity_last_changes lc
  WHERE lc.crmid IN (SELECT crmid FROM \`${TGT}\`.tmp_imp_account_ids)
     OR lc.crmid IN (SELECT crmid FROM \`${TGT}\`.tmp_imp_contact_ids);

SET FOREIGN_KEY_CHECKS=1;
"

log "refs" "Copying Accounts..."
copy_module_rows tmp_imp_account_ids \
  vtiger_crmentity:crmid \
  vtiger_account:accountid \
  vtiger_accountaddress:accountaddressid \
  vtiger_accountscf:accountid \
  vtiger_entity_stats:crmid

log "refs" "Copying Contacts..."
copy_module_rows tmp_imp_contact_ids \
  vtiger_crmentity:crmid \
  vtiger_contactdetails:contactid \
  vtiger_contactaddress:contactaddressid \
  vtiger_contactsubdetails:contactsubscriptionid \
  vtiger_contactscf:contactid \
  vtiger_customerdetails:customerid \
  vtiger_entity_stats:crmid

mariadb_exec "
UPDATE \`${TGT}\`.vtiger_crmentity ce
LEFT JOIN \`${TGT}\`.vtiger_users u ON u.id = ce.smownerid
SET ce.smownerid = ${FB}
WHERE (ce.crmid IN (SELECT crmid FROM \`${TGT}\`.tmp_imp_account_ids)
    OR ce.crmid IN (SELECT crmid FROM \`${TGT}\`.tmp_imp_contact_ids))
  AND u.id IS NULL;
UPDATE \`${TGT}\`.vtiger_crmentity ce
LEFT JOIN \`${TGT}\`.vtiger_users u ON u.id = ce.smcreatorid
SET ce.smcreatorid = ${FB}
WHERE (ce.crmid IN (SELECT crmid FROM \`${TGT}\`.tmp_imp_account_ids)
    OR ce.crmid IN (SELECT crmid FROM \`${TGT}\`.tmp_imp_contact_ids))
  AND u.id IS NULL;

DROP TABLE IF EXISTS \`${TGT}\`.tmp_imp_account_ids;
DROP TABLE IF EXISTS \`${TGT}\`.tmp_imp_contact_ids;
"

BROKEN_ACC="$(mariadb_query "
  SELECT COUNT(*) FROM \`${TGT}\`.u_yf_projektyrekrutacyjne p
  INNER JOIN \`${TGT}\`.vtiger_crmentity pe ON pe.crmid=p.projektyrekrutacyjneid AND pe.deleted=0
  WHERE p.kontrahent > 0 AND NOT EXISTS (
    SELECT 1 FROM \`${TGT}\`.vtiger_crmentity ae
    WHERE ae.crmid=p.kontrahent AND ae.setype='Accounts' AND ae.deleted=0
  );
")"
BROKEN_CON="$(mariadb_query "
  SELECT COUNT(*) FROM \`${TGT}\`.u_yf_projektyrekrutacyjne p
  INNER JOIN \`${TGT}\`.vtiger_crmentity pe ON pe.crmid=p.projektyrekrutacyjneid AND pe.deleted=0
  WHERE p.contact_person > 0 AND NOT EXISTS (
    SELECT 1 FROM \`${TGT}\`.vtiger_crmentity ce
    WHERE ce.crmid=p.contact_person AND ce.setype='Contacts' AND ce.deleted=0
  );
")"
log "refs" "broken kontrahent=${BROKEN_ACC} broken contact_person=${BROKEN_CON}"

SAMPLE="$(mariadb_query "
  SELECT a.accountname FROM \`${TGT}\`.u_yf_projektyrekrutacyjne p
  INNER JOIN \`${TGT}\`.vtiger_account a ON a.accountid=p.kontrahent
  WHERE p.projektyrekrutacyjneid=1442309;
")"
log "refs" "project 1442309 kontrahent=${SAMPLE:-'(none)'}"
