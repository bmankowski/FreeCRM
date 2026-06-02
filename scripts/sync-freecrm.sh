#!/usr/bin/env bash
set -euo pipefail

# Simple FreeCRM localhost <-> server sync helper.

ROOT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd -P)"

REMOTE_HOST="${FREECRM_SYNC_REMOTE_HOST:-local.itconnect.pl}"
REMOTE_USER="${FREECRM_SYNC_REMOTE_USER:-bmankowski}"
REMOTE_PATH="${FREECRM_SYNC_REMOTE_PATH:-/opt/freecrm}"
REMOTE="${REMOTE_USER}@${REMOTE_HOST}"

DB_SERVICE="${FREECRM_SYNC_DB_SERVICE:-db}"
DB_NAME="${FREECRM_SYNC_DB_NAME:-freecrm}"
DB_USER="${FREECRM_SYNC_DB_USER:-freecrm}"
DB_PASS="${FREECRM_SYNC_DB_PASS:-freecrm}"
DB_ROOT_USER="${FREECRM_SYNC_DB_ROOT_USER:-root}"
DB_ROOT_PASS="${FREECRM_SYNC_DB_ROOT_PASS:-root}"

usage() {
  cat <<'EOF'
FreeCRM sync helper.

Usage:
  scripts/sync-freecrm.sh help
  scripts/sync-freecrm.sh pushsrc
  scripts/sync-freecrm.sh pushdb
  scripts/sync-freecrm.sh pushall
  scripts/sync-freecrm.sh pulldb

Commands:
  help      Show this help.
  pushsrc   Send source changes from localhost to local.itconnect.pl.
  pushdb    Dump localhost DB and import it on local.itconnect.pl.
  pushall   Run pushsrc and pushdb.
  pulldb    Dump DB from local.itconnect.pl and import it on localhost.

Defaults can be changed with environment variables:
  FREECRM_SYNC_REMOTE_HOST       default: local.itconnect.pl
  FREECRM_SYNC_REMOTE_USER       default: bmankowski
  FREECRM_SYNC_REMOTE_PATH       default: /opt/freecrm
  FREECRM_SYNC_DB_SERVICE        default: db
  FREECRM_SYNC_DB_NAME           default: freecrm
  FREECRM_SYNC_DB_USER           default: freecrm
  FREECRM_SYNC_DB_PASS           default: freecrm
  FREECRM_SYNC_DB_ROOT_USER      default: root
  FREECRM_SYNC_DB_ROOT_PASS      default: root
  FREECRM_SYNC_YES=1             skip DB overwrite confirmation

Examples:
  scripts/sync-freecrm.sh pushsrc
  scripts/sync-freecrm.sh pushdb
  FREECRM_SYNC_YES=1 scripts/sync-freecrm.sh pulldb
EOF
}

die() {
  echo "ERROR: $*" >&2
  exit 1
}

require_cmd() {
  command -v "$1" >/dev/null 2>&1 || die "Missing required command: $1"
}

confirm_db_overwrite() {
  local target="$1"

  if [[ "${FREECRM_SYNC_YES:-0}" == "1" ]]; then
    return 0
  fi

  echo "This will overwrite database '${DB_NAME}' on ${target}."
  read -r -p "Continue? [y/N] " answer
  case "${answer}" in
    y|Y|yes|YES|tak|TAK) ;;
    *) die "Cancelled." ;;
  esac
}

remote_run() {
  ssh "${REMOTE}" "$@"
}

remote_bash() {
  ssh "${REMOTE}" bash -s -- "$@"
}

local_compose() {
  (cd "${ROOT_DIR}" && docker compose "$@")
}

dump_local_db() {
  local output_file="$1"

  echo "Creating local DB dump: ${output_file}"
  local_compose exec -T \
    -e SYNC_DB_NAME="${DB_NAME}" \
    -e SYNC_DB_USER="${DB_USER}" \
    -e SYNC_DB_PASS="${DB_PASS}" \
    "${DB_SERVICE}" sh -lc '
      if command -v mariadb-dump >/dev/null 2>&1; then
        dump_cmd=mariadb-dump
      elif command -v mysqldump >/dev/null 2>&1; then
        dump_cmd=mysqldump
      else
        echo "Neither mariadb-dump nor mysqldump found in DB container." >&2
        exit 1
      fi
      "$dump_cmd" -u"$SYNC_DB_USER" -p"$SYNC_DB_PASS" --single-transaction --quick --routines --triggers "$SYNC_DB_NAME"
    ' | gzip -c > "${output_file}"
}

dump_remote_db() {
  local remote_dump="$1"

  echo "Creating remote DB dump: ${REMOTE}:${remote_dump}"
  remote_bash "${REMOTE_PATH}" "${DB_SERVICE}" "${DB_NAME}" "${DB_USER}" "${DB_PASS}" "${remote_dump}" <<'REMOTE_SCRIPT'
set -euo pipefail
remote_path="$1"
db_service="$2"
db_name="$3"
db_user="$4"
db_pass="$5"
remote_dump="$6"

cd "${remote_path}"
docker compose exec -T \
  -e SYNC_DB_NAME="${db_name}" \
  -e SYNC_DB_USER="${db_user}" \
  -e SYNC_DB_PASS="${db_pass}" \
  "${db_service}" sh -lc '
    if command -v mariadb-dump >/dev/null 2>&1; then
      dump_cmd=mariadb-dump
    elif command -v mysqldump >/dev/null 2>&1; then
      dump_cmd=mysqldump
    else
      echo "Neither mariadb-dump nor mysqldump found in DB container." >&2
      exit 1
    fi
    "$dump_cmd" -u"$SYNC_DB_USER" -p"$SYNC_DB_PASS" --single-transaction --quick --routines --triggers "$SYNC_DB_NAME"
  ' | gzip -c > "${remote_dump}"
REMOTE_SCRIPT
}

import_local_db() {
  local dump_file="$1"

  confirm_db_overwrite "localhost"
  echo "Importing DB dump into localhost..."
  gzip -dc "${dump_file}" | local_compose exec -T \
    -e SYNC_DB_NAME="${DB_NAME}" \
    -e SYNC_DB_ROOT_USER="${DB_ROOT_USER}" \
    -e SYNC_DB_ROOT_PASS="${DB_ROOT_PASS}" \
    "${DB_SERVICE}" sh -lc '
      if command -v mariadb >/dev/null 2>&1; then
        client_cmd=mariadb
      elif command -v mysql >/dev/null 2>&1; then
        client_cmd=mysql
      else
        echo "Neither mariadb nor mysql found in DB container." >&2
        exit 1
      fi
      "$client_cmd" -u"$SYNC_DB_ROOT_USER" -p"$SYNC_DB_ROOT_PASS" -e "DROP DATABASE IF EXISTS \`$SYNC_DB_NAME\`; CREATE DATABASE \`$SYNC_DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
      "$client_cmd" -u"$SYNC_DB_ROOT_USER" -p"$SYNC_DB_ROOT_PASS" "$SYNC_DB_NAME"
    '
}

import_remote_db() {
  local remote_dump="$1"

  confirm_db_overwrite "${REMOTE}"
  echo "Importing DB dump into ${REMOTE}..."
  remote_bash "${REMOTE_PATH}" "${DB_SERVICE}" "${DB_NAME}" "${DB_ROOT_USER}" "${DB_ROOT_PASS}" "${remote_dump}" <<'REMOTE_SCRIPT'
set -euo pipefail
remote_path="$1"
db_service="$2"
db_name="$3"
db_root_user="$4"
db_root_pass="$5"
remote_dump="$6"

cd "${remote_path}"
gzip -dc "${remote_dump}" | docker compose exec -T \
  -e SYNC_DB_NAME="${db_name}" \
  -e SYNC_DB_ROOT_USER="${db_root_user}" \
  -e SYNC_DB_ROOT_PASS="${db_root_pass}" \
  "${db_service}" sh -lc '
    if command -v mariadb >/dev/null 2>&1; then
      client_cmd=mariadb
    elif command -v mysql >/dev/null 2>&1; then
      client_cmd=mysql
    else
      echo "Neither mariadb nor mysql found in DB container." >&2
      exit 1
    fi
    "$client_cmd" -u"$SYNC_DB_ROOT_USER" -p"$SYNC_DB_ROOT_PASS" -e "DROP DATABASE IF EXISTS \`$SYNC_DB_NAME\`; CREATE DATABASE \`$SYNC_DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    "$client_cmd" -u"$SYNC_DB_ROOT_USER" -p"$SYNC_DB_ROOT_PASS" "$SYNC_DB_NAME"
  '
REMOTE_SCRIPT
}

pushsrc() {
  require_cmd rsync
  require_cmd ssh

  echo "Syncing sources to ${REMOTE}:${REMOTE_PATH}"
  remote_run "mkdir -p '${REMOTE_PATH}'"
  rsync -az --delete --human-readable --info=name1,del1,stats2,progress2 \
    --exclude '.git/' \
    --exclude '.cursor/' \
    --exclude 'cache/' \
    --exclude 'storage/' \
    --exclude 'tmp/' \
    --exclude '_migration/' \
    --exclude 'docker/nginx/letsencrypt/accounts/' \
    --exclude 'docker/nginx/letsencrypt/archive/' \
    --exclude 'docker/nginx/letsencrypt/live/' \
    "${ROOT_DIR}/" "${REMOTE}:${REMOTE_PATH}/"
}

pushdb() {
  require_cmd docker
  require_cmd gzip
  require_cmd scp
  require_cmd ssh

  local local_dump
  local remote_dump
  local status

  local_dump="$(mktemp "/tmp/freecrm-local-${DB_NAME}.XXXXXX.sql.gz")"
  remote_dump="/tmp/freecrm-push-${DB_NAME}-$(date +%Y%m%d%H%M%S).sql.gz"

  dump_local_db "${local_dump}"
  echo "Copying DB dump to ${REMOTE}:${remote_dump}"
  scp "${local_dump}" "${REMOTE}:${remote_dump}"
  rm -f "${local_dump}"

  set +e
  import_remote_db "${remote_dump}"
  status=$?
  set -e

  remote_run "rm -f '${remote_dump}'" || true
  return "${status}"
}

pulldb() {
  require_cmd docker
  require_cmd gzip
  require_cmd scp
  require_cmd ssh

  local local_dump
  local remote_dump
  local status

  local_dump="$(mktemp "/tmp/freecrm-remote-${DB_NAME}.XXXXXX.sql.gz")"
  remote_dump="/tmp/freecrm-pull-${DB_NAME}-$(date +%Y%m%d%H%M%S).sql.gz"

  dump_remote_db "${remote_dump}"
  echo "Copying DB dump to localhost: ${local_dump}"
  scp "${REMOTE}:${remote_dump}" "${local_dump}"
  remote_run "rm -f '${remote_dump}'" || true

  set +e
  import_local_db "${local_dump}"
  status=$?
  set -e

  rm -f "${local_dump}"
  return "${status}"
}

main() {
  local command="${1:-help}"

  case "${command}" in
    help|-h|--help)
      usage
      ;;
    pushsrc)
      pushsrc
      ;;
    pushdb)
      pushdb
      ;;
    pushall)
      pushsrc
      pushdb
      ;;
    pulldb)
      pulldb
      ;;
    *)
      usage >&2
      die "Unknown command: ${command}"
      ;;
  esac
}

main "$@"
