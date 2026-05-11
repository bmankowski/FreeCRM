#!/usr/bin/env bash
set -euo pipefail

REMOTE_HOST="${REMOTE_HOST:-10.0.0.220}"
REMOTE_DB_HOST="${REMOTE_DB_HOST:-localhost}"
REMOTE_DB_NAME="${REMOTE_DB_NAME:-yetiforce2}"
REMOTE_DB_USER="${REMOTE_DB_USER:-yetiforceuser}"
REMOTE_DB_PASS="${REMOTE_DB_PASS:-yetiforcepassword}"

echo "[remote-export] host=$REMOTE_HOST db=$REMOTE_DB_NAME"

ssh "$REMOTE_HOST" "set -euo pipefail
  # Create ID tables to avoid huge IN (...) argument lists.
  # We intentionally use regular tables and DROP them at the end so mysqldump can reference them.
  mysql -h\"$REMOTE_DB_HOST\" -u\"$REMOTE_DB_USER\" -p\"$REMOTE_DB_PASS\" \"$REMOTE_DB_NAME\" -e \"
    DROP TABLE IF EXISTS tmp_sync_kandydaci_ids;
    DROP TABLE IF EXISTS tmp_sync_projekty_ids;
    DROP TABLE IF EXISTS tmp_sync_related_to_ids;
    DROP TABLE IF EXISTS tmp_sync_modcomments_ids;

    CREATE TABLE tmp_sync_kandydaci_ids (crmid INT NOT NULL PRIMARY KEY) ENGINE=InnoDB;
    INSERT INTO tmp_sync_kandydaci_ids (crmid)
      SELECT crmid FROM vtiger_crmentity WHERE setype='Kandydaci' AND deleted=0;

    CREATE TABLE tmp_sync_projekty_ids (crmid INT NOT NULL PRIMARY KEY) ENGINE=InnoDB;
    INSERT INTO tmp_sync_projekty_ids (crmid)
      SELECT crmid FROM vtiger_crmentity WHERE setype='ProjektyRekrutacyjne' AND deleted=0;

    CREATE TABLE tmp_sync_related_to_ids (crmid INT NOT NULL PRIMARY KEY) ENGINE=InnoDB;
    INSERT INTO tmp_sync_related_to_ids (crmid) SELECT crmid FROM tmp_sync_kandydaci_ids;
    INSERT IGNORE INTO tmp_sync_related_to_ids (crmid) SELECT crmid FROM tmp_sync_projekty_ids;

    CREATE TABLE tmp_sync_modcomments_ids (crmid INT NOT NULL PRIMARY KEY) ENGINE=InnoDB;
    INSERT INTO tmp_sync_modcomments_ids (crmid)
      SELECT modcommentsid FROM vtiger_modcomments WHERE related_to IN (SELECT crmid FROM tmp_sync_related_to_ids);

    SELECT COUNT(*) AS kandydaci_ids FROM tmp_sync_kandydaci_ids;
    SELECT COUNT(*) AS projekty_ids FROM tmp_sync_projekty_ids;
    SELECT COUNT(*) AS modcomments_ids FROM tmp_sync_modcomments_ids;
  \"

  # For debugging/inspection: also export plain id lists.
  mysql -N -B -h\"$REMOTE_DB_HOST\" -u\"$REMOTE_DB_USER\" -p\"$REMOTE_DB_PASS\" \"$REMOTE_DB_NAME\" -e \
    \"SELECT crmid FROM tmp_sync_kandydaci_ids\" > /tmp/kandydaci_ids.tsv
  mysql -N -B -h\"$REMOTE_DB_HOST\" -u\"$REMOTE_DB_USER\" -p\"$REMOTE_DB_PASS\" \"$REMOTE_DB_NAME\" -e \
    \"SELECT crmid FROM tmp_sync_projekty_ids\" > /tmp/projekty_ids.tsv

  # NOTE: mysqldump's --where applies to ALL tables in the invocation, so dump each table separately.
  # --complete-insert is critical: it makes inserts resilient to schema drift (e.g. localhost has extra columns like closedtime).
  # --skip-extended-insert avoids extremely long INSERT lines (easier to debug, less likely to hit limits).
  DUMP_OPTS=\"--single-transaction --skip-lock-tables --no-tablespaces --no-create-info --skip-add-drop-table --complete-insert --skip-extended-insert\"

  mysqldump -h\"$REMOTE_DB_HOST\" -u\"$REMOTE_DB_USER\" -p\"$REMOTE_DB_PASS\" \"$REMOTE_DB_NAME\" \$DUMP_OPTS \
    vtiger_crmentity --where=\"crmid IN (SELECT crmid FROM tmp_sync_kandydaci_ids)\" \
    > /tmp/kandydaci_core.sql
  mysqldump -h\"$REMOTE_DB_HOST\" -u\"$REMOTE_DB_USER\" -p\"$REMOTE_DB_PASS\" \"$REMOTE_DB_NAME\" \$DUMP_OPTS \
    u_yf_kandydaci --where=\"kandydaciid IN (SELECT crmid FROM tmp_sync_kandydaci_ids)\" \
    >> /tmp/kandydaci_core.sql
  mysqldump -h\"$REMOTE_DB_HOST\" -u\"$REMOTE_DB_USER\" -p\"$REMOTE_DB_PASS\" \"$REMOTE_DB_NAME\" \$DUMP_OPTS \
    u_yf_kandydacicf --where=\"kandydaciid IN (SELECT crmid FROM tmp_sync_kandydaci_ids)\" \
    >> /tmp/kandydaci_core.sql

  mysqldump -h\"$REMOTE_DB_HOST\" -u\"$REMOTE_DB_USER\" -p\"$REMOTE_DB_PASS\" \"$REMOTE_DB_NAME\" \$DUMP_OPTS \
    vtiger_crmentity --where=\"crmid IN (SELECT crmid FROM tmp_sync_projekty_ids)\" \
    > /tmp/projekty_core.sql
  mysqldump -h\"$REMOTE_DB_HOST\" -u\"$REMOTE_DB_USER\" -p\"$REMOTE_DB_PASS\" \"$REMOTE_DB_NAME\" \$DUMP_OPTS \
    u_yf_projektyrekrutacyjne --where=\"projektyrekrutacyjneid IN (SELECT crmid FROM tmp_sync_projekty_ids)\" \
    >> /tmp/projekty_core.sql
  mysqldump -h\"$REMOTE_DB_HOST\" -u\"$REMOTE_DB_USER\" -p\"$REMOTE_DB_PASS\" \"$REMOTE_DB_NAME\" \$DUMP_OPTS \
    u_yf_projektyrekrutacyjnecf --where=\"projektyrekrutacyjneid IN (SELECT crmid FROM tmp_sync_projekty_ids)\" \
    >> /tmp/projekty_core.sql

  mysqldump -h\"$REMOTE_DB_HOST\" -u\"$REMOTE_DB_USER\" -p\"$REMOTE_DB_PASS\" \"$REMOTE_DB_NAME\" \$DUMP_OPTS \
    u_yf_projekty_rekrutacyjne_relations_members_entity \
    --where=\"relcrmid IN (SELECT crmid FROM tmp_sync_kandydaci_ids) OR crmid IN (SELECT crmid FROM tmp_sync_kandydaci_ids)\" \
    > /tmp/kandydaci_relations.sql

  mysql -N -B -h\"$REMOTE_DB_HOST\" -u\"$REMOTE_DB_USER\" -p\"$REMOTE_DB_PASS\" \"$REMOTE_DB_NAME\" -e \
    \"SELECT crmid FROM tmp_sync_modcomments_ids\" > /tmp/modcomments_ids.tsv

  if [ -s /tmp/modcomments_ids.tsv ]; then
    mysqldump -h\"$REMOTE_DB_HOST\" -u\"$REMOTE_DB_USER\" -p\"$REMOTE_DB_PASS\" \"$REMOTE_DB_NAME\" \$DUMP_OPTS \
      vtiger_crmentity --where=\"crmid IN (SELECT crmid FROM tmp_sync_modcomments_ids)\" \
      > /tmp/modcomments_core.sql
    mysqldump -h\"$REMOTE_DB_HOST\" -u\"$REMOTE_DB_USER\" -p\"$REMOTE_DB_PASS\" \"$REMOTE_DB_NAME\" \$DUMP_OPTS \
      vtiger_modcomments --where=\"modcommentsid IN (SELECT crmid FROM tmp_sync_modcomments_ids)\" \
      >> /tmp/modcomments_core.sql
    mysqldump -h\"$REMOTE_DB_HOST\" -u\"$REMOTE_DB_USER\" -p\"$REMOTE_DB_PASS\" \"$REMOTE_DB_NAME\" \$DUMP_OPTS \
      vtiger_modcommentscf --where=\"modcommentsid IN (SELECT crmid FROM tmp_sync_modcomments_ids)\" \
      >> /tmp/modcomments_core.sql
  else
    : > /tmp/modcomments_core.sql
  fi

  wc -l /tmp/kandydaci_ids.tsv /tmp/projekty_ids.tsv || true
  ls -la /tmp/kandydaci_core.sql /tmp/projekty_core.sql /tmp/kandydaci_relations.sql /tmp/modcomments_core.sql

  # Cleanup temp tables
  mysql -h\"$REMOTE_DB_HOST\" -u\"$REMOTE_DB_USER\" -p\"$REMOTE_DB_PASS\" \"$REMOTE_DB_NAME\" -e \"
    DROP TABLE IF EXISTS tmp_sync_kandydaci_ids;
    DROP TABLE IF EXISTS tmp_sync_projekty_ids;
    DROP TABLE IF EXISTS tmp_sync_related_to_ids;
    DROP TABLE IF EXISTS tmp_sync_modcomments_ids;
  \"
"

echo "[remote-export] done"

