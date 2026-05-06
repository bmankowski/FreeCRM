#!/usr/bin/env bash
set -euo pipefail

usage() {
  cat <<'EOF'
Import a FreeCRM database dump into the Docker "db" service.

Usage:
  scripts/import-db.sh /path/to/dump.sql[.gz] [--db-name NAME] [--db-user USER] [--db-pass PASS] [--root] [--drop-first]

Defaults (can be overridden by flags or env vars):
  DB_NAME: freecrm
  DB_USER: freecrm
  DB_PASS: freecrm

Options:
  --db-name NAME   Target database name (or env DB_NAME)
  --db-user USER   Target database user (or env DB_USER)
  --db-pass PASS   Target database password (or env DB_PASS)
  --root           Import as root user (uses DB_ROOT_USER/DB_ROOT_PASS or root with empty password)
  --drop-first     Drop and recreate the target database before import (destructive)
  -h, --help       Show this help

Examples:
  scripts/import-db.sh ./freecrm.sql
  scripts/import-db.sh ./freecrm.sql.gz
  DB_NAME=freecrm DB_USER=freecrm DB_PASS=freecrm scripts/import-db.sh ./freecrm.sql

Notes:
  - Requires Docker Compose and access to the Docker daemon (often via sudo).
  - The script detects the container ID for the compose service named "db".
  - MySQL 8 dumps may contain collations not supported by MariaDB (e.g. utf8mb4_0900_ai_ci).
    This script rewrites known MySQL 8 utf8mb4 collations to MariaDB-compatible ones on the fly.
EOF
}

die() {
  echo "ERROR: $*" >&2
  exit 1
}

require_cmd() {
  command -v "$1" >/dev/null 2>&1 || die "Missing required command: $1"
}

is_gzip() {
  case "${1,,}" in
    *.gz) return 0 ;;
    *) return 1 ;;
  esac
}

rewrite_mysql8_collations() {
  # MySQL 8 default collations (utf8mb4_0900_*) are not supported by MariaDB.
  # Rewrite them to closest MariaDB equivalents.
  sed \
    -e 's/utf8mb4_0900_ai_ci/utf8mb4_unicode_ci/g' \
    -e 's/utf8mb4_0900_as_ci/utf8mb4_unicode_ci/g' \
    -e 's/utf8mb4_0900_bin/utf8mb4_bin/g'
}

project_root() {
  local script_dir
  script_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
  cd "${script_dir}/.."
  pwd -P
}

main() {
  local dump_path=""
  local use_root="false"
  local drop_first="false"

  local db_name="${DB_NAME:-freecrm}"
  local db_user="${DB_USER:-freecrm}"
  local db_pass="${DB_PASS:-freecrm}"
  local db_root_user="${DB_ROOT_USER:-root}"
  local db_root_pass="${DB_ROOT_PASS:-}"

  while [[ $# -gt 0 ]]; do
    case "$1" in
      -h|--help)
        usage
        exit 0
        ;;
      --db-name)
        [[ $# -ge 2 ]] || die "--db-name requires a value"
        db_name="$2"
        shift 2
        ;;
      --db-user)
        [[ $# -ge 2 ]] || die "--db-user requires a value"
        db_user="$2"
        shift 2
        ;;
      --db-pass)
        [[ $# -ge 2 ]] || die "--db-pass requires a value"
        db_pass="$2"
        shift 2
        ;;
      --root)
        use_root="true"
        shift
        ;;
      --drop-first)
        drop_first="true"
        shift
        ;;
      -*)
        die "Unknown option: $1"
        ;;
      *)
        if [[ -z "$dump_path" ]]; then
          dump_path="$1"
          shift
        else
          die "Unexpected argument: $1"
        fi
        ;;
    esac
  done

  [[ -n "$dump_path" ]] || { usage; exit 2; }
  [[ -f "$dump_path" ]] || die "Dump file not found: $dump_path"

  require_cmd docker
  require_cmd sed

  local root
  root="$(project_root)"
  cd "$root"

  local container_id=""
  if container_id="$(docker compose ps -q db 2>/dev/null)"; then
    :
  else
    die "Failed to run 'docker compose'. Is Compose installed and are you in the project root?"
  fi

  [[ -n "$container_id" ]] || die "Could not detect the 'db' service container. Is it running? Try: sudo docker compose up -d"

  local client_cmd="mariadb"
  if docker exec "$container_id" sh -lc 'command -v mariadb >/dev/null 2>&1'; then
    client_cmd="mariadb"
  elif docker exec "$container_id" sh -lc 'command -v mysql >/dev/null 2>&1'; then
    client_cmd="mysql"
  else
    die "Neither 'mariadb' nor 'mysql' client found inside the db container."
  fi

  echo "Importing dump into container: $container_id"
  echo "Target DB: $db_name"

  local auth_user=""
  local auth_pass=""
  if [[ "$use_root" == "true" ]]; then
    auth_user="$db_root_user"
    auth_pass="$db_root_pass"
  else
    auth_user="$db_user"
    auth_pass="$db_pass"
  fi

  local pass_flag=()
  if [[ -n "$auth_pass" ]]; then
    pass_flag=(-p"$auth_pass")
  else
    pass_flag=()
  fi

  if [[ "$drop_first" == "true" ]]; then
    echo "Dropping and recreating database (destructive)..."
    docker exec -i "$container_id" sh -lc \
      "$client_cmd -u\"$auth_user\" ${pass_flag[*]} -e \"DROP DATABASE IF EXISTS \\\`$db_name\\\`; CREATE DATABASE \\\`$db_name\\\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\""
  else
    echo "Creating database if missing..."
    docker exec -i "$container_id" sh -lc \
      "$client_cmd -u\"$auth_user\" ${pass_flag[*]} -e \"CREATE DATABASE IF NOT EXISTS \\\`$db_name\\\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\""
  fi

  echo "Importing..."
  if is_gzip "$dump_path"; then
    require_cmd gzip
    gzip -dc "$dump_path" | rewrite_mysql8_collations | docker exec -i "$container_id" sh -lc \
      "$client_cmd -u\"$auth_user\" ${pass_flag[*]} \"$db_name\""
  else
    rewrite_mysql8_collations < "$dump_path" | docker exec -i "$container_id" sh -lc \
      "$client_cmd -u\"$auth_user\" ${pass_flag[*]} \"$db_name\""
  fi

  echo "Done."
  echo "If the UI looks odd, clear caches / restart containers:"
  echo "  sudo docker compose restart"
}

main "$@"

