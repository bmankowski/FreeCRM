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
  echo "This replaces Candidates, ProjektyRekrutacyjne, their relations, comments,"
  echo "linked Documents, Accounts (kontrahenci), and Contacts in local database '${LOCAL_DB_NAME}'."
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

table_exists_in_db() {
  local db="$1"
  local table="$2"
  local count
  count="$(mariadb_query "
    SELECT COUNT(*) FROM information_schema.tables
    WHERE table_schema='${db}' AND table_name='${table}';
  " 2>/dev/null || echo "0")"
  [[ "${count:-0}" != "0" ]]
}

column_exists_in_db() {
  local db="$1"
  local table="$2"
  local column="$3"
  local count
  count="$(mariadb_query "
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema='${db}' AND table_name='${table}' AND column_name='${column}';
  " 2>/dev/null || echo "0")"
  [[ "${count:-0}" != "0" ]]
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

# Prod Kandydaci → local Candidates (m260609_000001_rename_kandydaci_to_candidates).
candidates_main_column_pairs() {
  cat <<'MAP'
kandydaciid:candidatesid
status_kandydata:candidate_status
telefon_extra:phone_extra
telefon:phone
rekrutowany_stanowisko:recruited_position
dostepnosc:availability
wymiar_czasu_pracy:work_time_type
polec_znajomego:referrer_consultant_id
MAP
}

candidates_cf_column_pairs() {
  cat <<'MAP'
kandydaciid:candidatesid
ilosc_dokumentow_kandydata:documents_count
ilosc_dokumentow:documents_count_legacy
projekt_na_ktory_ostatnio_wysl:last_sent_to_project_id
data_maksymalny_kontakt_rodo:gdpr_max_contact_date
oczekiwania_finansowe_brutto:salary_expectation_gross
data_ostatniego_wyslania:last_sent_to_project_date
email_prywatny:email_private
email_firmowy:email_business
zrodlo_aplikacji:application_source
tresc_cv:cv_text
komunikator:messenger
MAP
}

sql_copy_table_mapped() {
  local target_db="$1"
  local source_db="$2"
  local source_table="$3"
  local target_table="$4"
  local where_clause="${5:-}"

  declare -A src_for_tgt=()
  local old_col new_col
  while IFS=: read -r old_col new_col; do
    [[ -z "${old_col}" || -z "${new_col}" ]] && continue
    src_for_tgt["${new_col}"]="${old_col}"
  done < <(candidates_main_column_pairs)

  if [[ "${source_table}" == "u_yf_kandydacicf" ]]; then
    src_for_tgt=()
    while IFS=: read -r old_col new_col; do
      [[ -z "${old_col}" || -z "${new_col}" ]] && continue
      src_for_tgt["${new_col}"]="${old_col}"
    done < <(candidates_cf_column_pairs)
  fi

  local tgt_cols insert_cols="" select_exprs="" tgt_col src_col bt=$'\x60'
  local -a tgt_col_arr=()
  tgt_cols="$(mariadb_query "
    SELECT COLUMN_NAME FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA='${target_db}' AND TABLE_NAME='${target_table}'
    ORDER BY ORDINAL_POSITION;
  ")"
  mapfile -t tgt_col_arr <<< "$tgt_cols"

  for tgt_col in "${tgt_col_arr[@]}"; do
    [[ -z "${tgt_col}" ]] && continue
    src_col="${src_for_tgt[$tgt_col]:-$tgt_col}"
    column_exists_in_db "$source_db" "$source_table" "$src_col" \
      || die "Missing ${source_db}.${source_table}.${src_col} for ${target_table}.${tgt_col}"
    insert_cols+=$bt$tgt_col$bt,
    select_exprs+=$bt$src_col$bt,
  done

  insert_cols="${insert_cols%,}"
  select_exprs="${select_exprs%,}"

  local sql=
  sql+='INSERT INTO `'
  sql+=$target_db
  sql+='`.`'
  sql+=$target_table
  sql+='` ('
  sql+=$insert_cols
  sql+=') SELECT '
  sql+=$select_exprs
  sql+=' FROM `'
  sql+=$source_db
  sql+='`.`'
  sql+=$source_table
  sql+='`'
  if [[ -n "$where_clause" ]]; then
    sql+=" ${where_clause}"
  fi
  mariadb_exec "${sql};"
}

copy_candidates_from_source() {
  local target_db="$1"
  local source_db="$2"
  local ids_where="(SELECT crmid FROM \`${target_db}\`.tmp_imp_candidates_ids)"

  sql_copy_table "$target_db" "$source_db" vtiger_crmentity "WHERE crmid IN ${ids_where}"
  mariadb_exec "
UPDATE \`${target_db}\`.vtiger_crmentity
SET setype = 'Candidates'
WHERE crmid IN ${ids_where} AND setype = 'Kandydaci';
"

  if table_exists_in_db "$source_db" "u_yf_candidates"; then
    sql_copy_table "$target_db" "$source_db" u_yf_candidates "WHERE candidatesid IN ${ids_where}"
    sql_copy_table "$target_db" "$source_db" u_yf_candidatescf "WHERE candidatesid IN ${ids_where}"
    return 0
  fi

  if table_exists_in_db "$source_db" "u_yf_kandydaci"; then
    sql_copy_table_mapped "$target_db" "$source_db" u_yf_kandydaci u_yf_candidates \
      "WHERE kandydaciid IN ${ids_where}"
    sql_copy_table_mapped "$target_db" "$source_db" u_yf_kandydacicf u_yf_candidatescf \
      "WHERE kandydaciid IN ${ids_where}"
    return 0
  fi

  die "No candidates module tables in ${source_db}"
}

accounts_contacts_counts() {
  local db="$1"
  mariadb_query "
    SELECT CONCAT(
      'accounts=', (SELECT COUNT(*) FROM \`${db}\`.vtiger_crmentity
        WHERE setype='Accounts' AND deleted=0),
      ' contacts=', (SELECT COUNT(*) FROM \`${db}\`.vtiger_crmentity
        WHERE setype='Contacts' AND deleted=0)
    );
  " 2>/dev/null || echo "(counts unavailable)"
}

recruitment_counts() {
  local db="$1"
  local candidates_count_sql="NULL"

  if table_exists_in_db "$db" "u_yf_candidates"; then
    candidates_count_sql="(SELECT COUNT(*) FROM \`${db}\`.u_yf_candidates k
      INNER JOIN \`${db}\`.vtiger_crmentity e ON e.crmid=k.candidatesid AND e.setype='Candidates' AND e.deleted=0)"
  elif table_exists_in_db "$db" "u_yf_kandydaci"; then
    candidates_count_sql="(SELECT COUNT(*) FROM \`${db}\`.u_yf_kandydaci k
      INNER JOIN \`${db}\`.vtiger_crmentity e ON e.crmid=k.kandydaciid AND e.setype='Kandydaci' AND e.deleted=0)"
  else
    echo "(counts unavailable)"
    return 0
  fi

  mariadb_query "
    SELECT CONCAT(
      'candidates=', ${candidates_count_sql},
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
  mariadb_query "SELECT MAX(candidatesid) FROM \`${LOCAL_DB_NAME}\`.u_yf_candidates LIMIT 1;" 2>/dev/null || echo ""
}

document_storage_file_exists() {
  local storage_path="$1"
  local original_name="${2:-}"
  [[ -n "$storage_path" ]] || return 1

  local candidates=("$storage_path")
  local base="${storage_path##*/}"
  local dir="${storage_path%/*}"
  if [[ "$base" =~ ^([0-9]+)_(.+)$ ]]; then
    candidates+=("${dir}/${BASH_REMATCH[1]}")
  elif [[ "$base" =~ ^([0-9]+)$ && -n "$original_name" ]]; then
    candidates+=("${dir}/${BASH_REMATCH[1]}_${original_name}")
    local sanitized="${original_name// /_}"
    if [[ "$sanitized" != "$original_name" ]]; then
      candidates+=("${dir}/${BASH_REMATCH[1]}_${sanitized}")
    fi
  fi

  local candidate
  for candidate in "${candidates[@]}"; do
    if [[ "${SYNC_SKIP_STORAGE:-0}" != "1" ]]; then
      if [[ -f "${ROOT_DIR}/${candidate}" ]]; then
        return 0
      fi
    else
      require_ssh
      if ssh -o BatchMode=yes -o ConnectTimeout=10 "${REMOTE_HOST}" \
        "test -f '${REMOTE_WEB_ROOT}/${candidate}'" >/dev/null 2>&1; then
        return 0
      fi
    fi
  done

  return 1
}
