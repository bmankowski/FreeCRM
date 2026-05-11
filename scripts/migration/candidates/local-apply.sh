#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../../.." && pwd)"
cd "$ROOT_DIR"

LOCAL_DB_NAME="${LOCAL_DB_NAME:-freecrm}"
LOCAL_DB_USER="${LOCAL_DB_USER:-root}"
LOCAL_DB_PASS="${LOCAL_DB_PASS:-root}"
VERBOSE="${VERBOSE:-1}"

if [ "${VERBOSE}" = "1" ] || [ "${VERBOSE}" = "true" ]; then
  set -x
fi

TMP_DIR="$ROOT_DIR/tmp/sync"

for f in "$TMP_DIR/projekty_core.sql" "$TMP_DIR/kandydaci_core.sql" "$TMP_DIR/kandydaci_relations.sql" "$TMP_DIR/modcomments_core.sql"; do
  if [ ! -f "$f" ]; then
    echo "[apply] missing artifact: $f" >&2
    exit 1
  fi
done

echo "[apply] using artifacts from: $TMP_DIR"
ls -lah "$TMP_DIR"/*.sql "$TMP_DIR"/*.tsv 2>/dev/null || true

cid="$(sudo docker compose ps -q db)"
echo "[apply] db container: $cid"
sudo docker cp "$TMP_DIR/kandydaci_core.sql" "$cid:/tmp/kandydaci_core.sql"
sudo docker cp "$TMP_DIR/projekty_core.sql" "$cid:/tmp/projekty_core.sql"
sudo docker cp "$TMP_DIR/kandydaci_relations.sql" "$cid:/tmp/kandydaci_relations.sql"
sudo docker cp "$TMP_DIR/modcomments_core.sql" "$cid:/tmp/modcomments_core.sql"

echo "[apply] replacing local Kandydaci (FULL), projects, relations, comments"
sudo docker compose exec -T db sh -lc "
set -e
if [ \"${VERBOSE}\" = \"1\" ] || [ \"${VERBOSE}\" = \"true\" ]; then set -x; fi

DB_ARGS=\"-u${LOCAL_DB_USER} -p${LOCAL_DB_PASS} ${LOCAL_DB_NAME} --show-warnings --verbose\"

echo \"[apply] pre-cleanup row counts\"
mariadb \$DB_ARGS -e \"
  SELECT 'vtiger_crmentity' AS t, COUNT(*) AS c FROM vtiger_crmentity
  UNION ALL SELECT 'vtiger_modcomments', COUNT(*) FROM vtiger_modcomments
  UNION ALL SELECT 'vtiger_modcommentscf', COUNT(*) FROM vtiger_modcommentscf
  UNION ALL SELECT 'u_yf_kandydaci', COUNT(*) FROM u_yf_kandydaci
  UNION ALL SELECT 'u_yf_projektyrekrutacyjne', COUNT(*) FROM u_yf_projektyrekrutacyjne
  UNION ALL SELECT 'u_yf_projekty_rekrutacyjne_relations_members_entity', COUNT(*) FROM u_yf_projekty_rekrutacyjne_relations_members_entity;
\"

echo \"[apply] cleanup\"
mariadb \$DB_ARGS -e '
  SET FOREIGN_KEY_CHECKS=0;

  -- User requested: wipe ALL entity rows to avoid PK collisions.
  -- This is intentionally destructive and will remove *all* entities (not just candidates/projects).
  DELETE FROM vtiger_crmentity;

  -- Clear derived entity label/search tables to avoid massive orphaned data.
  DELETE FROM u_yf_crmentity_label;
  DELETE FROM u_yf_crmentity_search_label;
  DELETE FROM u_yf_crmentity_last_changes;

  -- comments: full replace (related_to can point to candidates OR projects)
  DELETE FROM vtiger_modcommentscf;
  DELETE FROM vtiger_modcomments;

  -- relations first (candidate ↔ project)
  -- User asked for full replace; safest is to clear the whole relation table.
  DELETE FROM u_yf_projekty_rekrutacyjne_relations_members_entity;

  -- candidates: FULL replace
  DELETE FROM u_yf_kandydacicf;
  DELETE FROM u_yf_kandydaci;
  DELETE FROM vtiger_crmentity WHERE setype=\"Kandydaci\";

  -- projects: full replace
  DELETE FROM u_yf_projektyrekrutacyjnecf WHERE projektyrekrutacyjneid IN (SELECT projektyrekrutacyjneid FROM u_yf_projektyrekrutacyjne);
  DELETE FROM u_yf_projektyrekrutacyjne;
  DELETE FROM vtiger_crmentity WHERE setype=\"ProjektyRekrutacyjne\";

  SET FOREIGN_KEY_CHECKS=1;
';

echo \"[apply] import projekty_core.sql\"
mariadb \$DB_ARGS < /tmp/projekty_core.sql
echo \"[apply] import kandydaci_core.sql\"
mariadb \$DB_ARGS < /tmp/kandydaci_core.sql
echo \"[apply] import kandydaci_relations.sql\"
mariadb \$DB_ARGS < /tmp/kandydaci_relations.sql
echo \"[apply] import modcomments_core.sql\"
mariadb \$DB_ARGS < /tmp/modcomments_core.sql

echo \"[apply] post-import row counts\"
mariadb \$DB_ARGS -e \"
  SELECT 'vtiger_crmentity' AS t, COUNT(*) AS c FROM vtiger_crmentity
  UNION ALL SELECT 'vtiger_modcomments', COUNT(*) FROM vtiger_modcomments
  UNION ALL SELECT 'vtiger_modcommentscf', COUNT(*) FROM vtiger_modcommentscf
  UNION ALL SELECT 'u_yf_kandydaci', COUNT(*) FROM u_yf_kandydaci
  UNION ALL SELECT 'u_yf_projektyrekrutacyjne', COUNT(*) FROM u_yf_projektyrekrutacyjne
  UNION ALL SELECT 'u_yf_projekty_rekrutacyjne_relations_members_entity', COUNT(*) FROM u_yf_projekty_rekrutacyjne_relations_members_entity;
\"
"

echo "[apply] done"

