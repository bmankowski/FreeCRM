#!/usr/bin/env bash
# Shared helpers for prod → yetiforce → freecrm recruitment sync.

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd -P)"
ROOT_DIR="$(cd -- "${SCRIPT_DIR}/../../.." && pwd -P)"
TMP_DIR="${ROOT_DIR}/tmp/sync"
BACKUP_DIR="${ROOT_DIR}/tmp/db-backups"

STAGING_DB_NAME="${STAGING_DB_NAME:-yetiforce}"
LOCAL_DB_NAME="${LOCAL_DB_NAME:-freecrm}"
LOCAL_DB_USER="${LOCAL_DB_USER:-root}"
LOCAL_DB_PASS="${LOCAL_DB_PASS:-root}"

REMOTE_HOST="${REMOTE_HOST:-10.0.0.220}"
REMOTE_DB_HOST="${REMOTE_DB_HOST:-localhost}"
REMOTE_DB_NAME="${REMOTE_DB_NAME:-yetiforce2}"
REMOTE_DB_USER="${REMOTE_DB_USER:-yetiforceuser}"
REMOTE_DB_PASS="${REMOTE_DB_PASS:-yetiforcepassword}"
REMOTE_WEB_ROOT="${REMOTE_WEB_ROOT:-/var/www/yetiforce}"

IMPORT_FALLBACK_USER_ID="${IMPORT_FALLBACK_USER_ID:-1}"
FREECRM_BASE_URL="${FREECRM_BASE_URL:-http://localhost}"

SYNC_YES="${SYNC_YES:-0}"
SYNC_DRY_RUN="${SYNC_DRY_RUN:-0}"
SYNC_SKIP_DUMP="${SYNC_SKIP_DUMP:-0}"
SYNC_SKIP_STORAGE="${SYNC_SKIP_STORAGE:-0}"
SYNC_SKIP_BACKUP="${SYNC_SKIP_BACKUP:-0}"
SYNC_ONLY_PROMOTE="${SYNC_ONLY_PROMOTE:-0}"
SYNC_SKIP_REFERENCES="${SYNC_SKIP_REFERENCES:-0}"

load_sync_env() {
  local f
  for f in "${HOME}/.freecrm-sync.env" "${SCRIPT_DIR}/.env.local"; do
    if [[ -f "$f" ]]; then
      set -a
      # shellcheck disable=SC1090
      source "$f"
      set +a
    fi
  done
}

log() {
  echo "[${1}] ${*:2}"
}

die() {
  log "sync" "ERROR: $*" >&2
  exit 1
}

docker_compose() {
  if docker compose "$@" 2>/dev/null; then
    return 0
  fi
  sudo docker compose "$@"
}

require_docker_db() {
  local cid
  cid="$(docker_compose ps -q db 2>/dev/null || true)"
  [[ -n "$cid" ]] || die "Docker db service is not running. Start with: docker compose up -d db"
}

require_ssh() {
  ssh -o BatchMode=yes -o ConnectTimeout=10 "${REMOTE_HOST}" 'echo ok' >/dev/null 2>&1 \
    || die "SSH to ${REMOTE_HOST} failed. Copy .env.example to ~/.freecrm-sync.env and ensure your key works."
}

confirm_destructive() {
  if [[ "${SYNC_YES}" == "1" ]]; then
    return 0
  fi
  echo
  echo "This replaces Kandydaci, ProjektyRekrutacyjne, their relations, comments,"
  echo "and linked Documents in local database '${LOCAL_DB_NAME}'."
  read -r -p "Continue? [y/N] " ans
  [[ "${ans,,}" == "y" || "${ans,,}" == "yes" ]] || die "Aborted."
}

mariadb_exec() {
  local sql="$1"
  if [[ "${SYNC_DRY_RUN}" == "1" ]]; then
    log "dry-run" "${sql//$'\n'/; }"
    return 0
  fi
  docker_compose exec -T db mariadb -u"${LOCAL_DB_USER}" -p"${LOCAL_DB_PASS}" "${LOCAL_DB_NAME}" -e "$sql"
}

mariadb_query() {
  local sql="$1"
  if [[ "${SYNC_DRY_RUN}" == "1" ]]; then
    return 0
  fi
  docker_compose exec -T db mariadb -N -B -u"${LOCAL_DB_USER}" -p"${LOCAL_DB_PASS}" "${LOCAL_DB_NAME}" -e "$sql"
}

intersection_columns() {
  local target_db="$1"
  local source_db="$2"
  local table="$3"
  mariadb_query "
    SELECT t.COLUMN_NAME
    FROM information_schema.COLUMNS t
    INNER JOIN information_schema.COLUMNS s
      ON s.TABLE_SCHEMA='${source_db}' AND s.TABLE_NAME='${table}' AND s.COLUMN_NAME=t.COLUMN_NAME
    WHERE t.TABLE_SCHEMA='${target_db}' AND t.TABLE_NAME='${table}'
    ORDER BY t.ORDINAL_POSITION;
  "
}

sql_copy_table() {
  local target_db="$1"
  local source_db="$2"
  local table="$3"
  local where_clause="${4:-}"

  local cols quoted="" col
  cols="$(intersection_columns "$target_db" "$source_db" "$table")"
  [[ -n "$cols" ]] || die "No shared columns for ${source_db}.${table} → ${target_db}.${table}"

  while IFS= read -r col; do
    [[ -z "$col" ]] && continue
    quoted+="\`${col}\`,"
  done <<< "$cols"
  quoted="${quoted%,}"

  local sql="INSERT INTO \`${target_db}\`.\`${table}\` (${quoted}) SELECT ${quoted} FROM \`${source_db}\`.\`${table}\`"
  if [[ -n "$where_clause" ]]; then
    sql+=" ${where_clause}"
  fi
  mariadb_exec "${sql};"
}

recruitment_counts() {
  local db="$1"
  mariadb_query "
    SELECT CONCAT(
      'kandydaci=', (SELECT COUNT(*) FROM \`${db}\`.u_yf_kandydaci k
        INNER JOIN \`${db}\`.vtiger_crmentity e ON e.crmid=k.kandydaciid AND e.setype='Kandydaci' AND e.deleted=0),
      ' projekty=', (SELECT COUNT(*) FROM \`${db}\`.u_yf_projektyrekrutacyjne p
        INNER JOIN \`${db}\`.vtiger_crmentity e ON e.crmid=p.projektyrekrutacyjneid AND e.setype='ProjektyRekrutacyjne' AND e.deleted=0),
      ' relations=', (SELECT COUNT(*) FROM \`${db}\`.u_yf_projekty_rekrutacyjne_relations_members_entity)
    );
  " 2>/dev/null || echo "(counts unavailable)"
}

latest_yetiforce_dump() {
  ls -1t "${TMP_DIR}"/yetiforce2-*.sql.gz 2>/dev/null | head -1 || true
}

sample_candidate_id() {
  mariadb_query "SELECT MAX(kandydaciid) FROM \`${LOCAL_DB_NAME}\`.u_yf_kandydaci LIMIT 1;" 2>/dev/null || echo ""
}
