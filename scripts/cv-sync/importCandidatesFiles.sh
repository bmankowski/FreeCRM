#!/usr/bin/env bash
# Pull CV application files into import/cv/pending for CRM import (CvImportTask).
# Configure via cv-import.conf on each server (see cv-import.conf.example).
set -euo pipefail

CONFIG="${CV_IMPORT_CONFIG:-/etc/freecrm/cv-import.conf}"

usage() {
	echo "Usage: $0 [--config /path/to/cv-import.conf]" >&2
	exit 2
}

while [[ $# -gt 0 ]]; do
	case "$1" in
		--config)
			[[ $# -ge 2 ]] || usage
			CONFIG="$2"
			shift 2
			;;
		-h | --help)
			usage
			;;
		*)
			echo "Unknown argument: $1" >&2
			usage
			;;
	esac
done

if [[ ! -f "$CONFIG" ]]; then
	echo "Config not found: $CONFIG" >&2
	exit 1
fi

# shellcheck source=/dev/null
source "$CONFIG"

: "${SOURCE:?SOURCE must be set in $CONFIG (cyberfolks or prod-backup)}"
: "${IMPORT_BASE:?IMPORT_BASE must be set in $CONFIG}"

ENABLED="${ENABLED:-1}"
[[ "$ENABLED" == "1" ]] || exit 0

DOWNLOADED="${IMPORT_BASE}/downloaded"
PENDING="${IMPORT_BASE}/pending"
BACKUP="${IMPORT_BASE}/backup"
LOG_FILE="${LOG_FILE:-${IMPORT_BASE}/cv-sync.log}"
CV_SYNC_DAYS="${CV_SYNC_DAYS:-14}"
REMOVE_SOURCE="${REMOVE_SOURCE:-0}"
PHP_BIN="${PHP_BIN:-php}"

mkdir -p "$DOWNLOADED" "$PENDING" "$BACKUP" "${IMPORT_BASE}/processed" "${IMPORT_BASE}/failed" "$(dirname "$LOG_FILE")"

log() {
	printf '[%s] %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$*" | tee -a "$LOG_FILE"
}

SSH_TMP=""
RSYNC_SSH=""
SSH_CMD=()

cleanup() {
	[[ -n "$SSH_TMP" && -d "$SSH_TMP" ]] && rm -rf "$SSH_TMP"
}
trap cleanup EXIT

setup_ssh() {
	local key_dir="${SSH_KEY_DIR:-}"
	local known_hosts="${KNOWN_HOSTS:-}"

	if [[ -z "$key_dir" ]]; then
		local port_opt=()
		[[ -n "${SSH_PORT:-}" ]] && port_opt=(-p "$SSH_PORT")
		RSYNC_SSH="ssh ${port_opt[*]} -o BatchMode=yes"
		SSH_CMD=(ssh "${port_opt[@]}" -o BatchMode=yes)
		if [[ -n "$known_hosts" && -f "$known_hosts" ]]; then
			RSYNC_SSH+=" -o StrictHostKeyChecking=yes -o UserKnownHostsFile=${known_hosts}"
			SSH_CMD+=(-o StrictHostKeyChecking=yes -o UserKnownHostsFile="$known_hosts")
		fi
		return
	fi

	local ssh_key=""
	for candidate in "${key_dir}/id_ed25519" "${key_dir}/id_rsa"; do
		[[ -f "$candidate" ]] && ssh_key="$candidate" && break
	done
	if [[ -z "$ssh_key" ]]; then
		log "no SSH key in ${key_dir}"
		exit 1
	fi

	SSH_TMP=$(mktemp -d)
	cp "$ssh_key" "${SSH_TMP}/id_key"
	chmod 700 "$SSH_TMP" && chmod 600 "${SSH_TMP}/id_key"

	local port_opt=()
	[[ -n "${SSH_PORT:-}" ]] && port_opt=(-p "$SSH_PORT")

	SSH_CMD=(ssh -i "${SSH_TMP}/id_key" "${port_opt[@]}" -o BatchMode=yes)
	RSYNC_SSH="ssh -i ${SSH_TMP}/id_key ${port_opt[*]} -o BatchMode=yes"

	if [[ -n "$known_hosts" && -f "$known_hosts" ]]; then
		SSH_CMD+=(-o StrictHostKeyChecking=yes -o UserKnownHostsFile="$known_hosts")
		RSYNC_SSH+=" -o StrictHostKeyChecking=yes -o UserKnownHostsFile=${known_hosts}"
	fi
}

resolve_cv_attachment_name() {
	local json_path="$1"
	"$PHP_BIN" -r '
		$data = json_decode(file_get_contents($argv[1]), true);
		if (!is_array($data)) {
			exit(0);
		}
		if (!empty($data["cv_saved_filename"])) {
			echo $data["cv_saved_filename"];
			exit(0);
		}
		if (!empty($data["file_uploads"]["cv-zalacz-cv"][0]["name"])) {
			echo $data["file_uploads"]["cv-zalacz-cv"][0]["name"];
			exit(0);
		}
	' "$json_path" 2>/dev/null || true
}

application_already_queued() {
	local base="$1"
	local subdir
	for subdir in pending processed failed; do
		[[ -f "${IMPORT_BASE}/${subdir}/${base}" ]] && return 0
	done
	return 1
}

queue_from_backup() {
	local json_dir="$1"
	local names_ref="$2"
	local -n names=$names_ref
	local queued=0

	for name in "${names[@]}"; do
		[[ "$name" == *.json ]] || continue
		local json="${json_dir}/${name}"
		application_already_queued "$name" && continue
		[[ -f "$json" ]] || continue

		cp "$json" "${PENDING}/"
		local cv
		cv=$(resolve_cv_attachment_name "$json")
		if [[ -n "$cv" && -f "${json_dir}/${cv}" ]]; then
			cp "${json_dir}/${cv}" "${PENDING}/"
		fi
		queued=$((queued + 1))
	done

	echo "$queued"
}

pull_cyberfolks() {
	: "${CYBERFOLKS_SSH_USER:?CYBERFOLKS_SSH_USER required for SOURCE=cyberfolks}"
	: "${CYBERFOLKS_SSH_HOST:?CYBERFOLKS_SSH_HOST required for SOURCE=cyberfolks}"
	: "${CYBERFOLKS_REMOTE_PATH:?CYBERFOLKS_REMOTE_PATH required for SOURCE=cyberfolks}"

	SSH_PORT="${CYBERFOLKS_SSH_PORT:-222}"
	setup_ssh

	local remote="${CYBERFOLKS_SSH_USER}@${CYBERFOLKS_SSH_HOST}"
	local rsync_opts=(-avz)
	local remove_flag=()
	[[ "$REMOVE_SOURCE" == "1" ]] && remove_flag=(--remove-source-files)

	if [[ -n "${RSYNC_CHOWN:-}" ]]; then
		rsync_opts+=(--chown="$RSYNC_CHOWN")
	fi
	if [[ -n "${RSYNC_CHMOD:-}" ]]; then
		rsync_opts+=(--chmod="$RSYNC_CHMOD")
	fi
	if [[ -n "${CYBERFOLKS_RSYNC_INCLUDES:-}" ]]; then
		# shellcheck disable=SC2206
		local include_opts=(${CYBERFOLKS_RSYNC_INCLUDES})
		rsync_opts+=("${include_opts[@]}")
	else
		rsync_opts+=(--include '*/' --include '*' --exclude '*')
	fi

	log "rsync from ${remote}:${CYBERFOLKS_REMOTE_PATH}/ -> ${DOWNLOADED}/ (remove_source=${REMOVE_SOURCE})"
	rsync -e "$RSYNC_SSH" "${rsync_opts[@]}" "${remove_flag[@]}" \
		"${remote}:${CYBERFOLKS_REMOTE_PATH}/" "${DOWNLOADED}/"

	shopt -s nullglob
	local staged=( "$DOWNLOADED"/* )
	if (( ${#staged[@]} == 0 )); then
		log "nothing new from Cyberfolks"
		return 0
	fi

	cp -a "$DOWNLOADED"/* "$PENDING"/
	cp -a "$PENDING"/* "$BACKUP"/
	rm -rf "${DOWNLOADED:?}"/*
	log "staged ${#staged[@]} file(s) from Cyberfolks into pending"
}

pull_prod_backup() {
	: "${PROD_SSH_USER:?PROD_SSH_USER required for SOURCE=prod-backup}"
	: "${PROD_SSH_HOST:?PROD_SSH_HOST required for SOURCE=prod-backup}"
	: "${PROD_BACKUP_PATH:?PROD_BACKUP_PATH required for SOURCE=prod-backup}"

	SSH_PORT="${PROD_SSH_PORT:-22}"
	setup_ssh

	local remote="${PROD_SSH_USER}@${PROD_SSH_HOST}"
	local last_sync="${IMPORT_BASE}/.last_sync"
	local find_args=()

	# .last_sync is local only — remote find must use mtime epoch, not a path on prod.
	if [[ -f "$last_sync" ]]; then
		local sync_epoch
		sync_epoch=$(stat -c %Y "$last_sync" 2>/dev/null || stat -f %m "$last_sync")
		find_args=(-maxdepth 1 -type f -newermt "@${sync_epoch}")
		log "list on ${remote}:${PROD_BACKUP_PATH} (since last sync @${sync_epoch})"
	else
		find_args=(-maxdepth 1 -type f -mtime "-${CV_SYNC_DAYS}")
		log "list on ${remote}:${PROD_BACKUP_PATH} (last ${CV_SYNC_DAYS} days)"
	fi

	local remote_names=()
	while IFS= read -r path; do
		[[ -n "$path" ]] && remote_names+=("$(basename "$path")")
	done < <("${SSH_CMD[@]}" "$remote" find "$PROD_BACKUP_PATH" "${find_args[@]}" 2>/dev/null || true)

	if [[ ${#remote_names[@]} -eq 0 ]]; then
		log "nothing new on prod backup"
		exit 0
	fi

	log "rsync ${#remote_names[@]} file(s) from ${remote}:${PROD_BACKUP_PATH}/"
	printf '%s\n' "${remote_names[@]}" | rsync -rlt --no-perms --no-owner --no-group --files-from=- -e "$RSYNC_SSH" \
		"${remote}:${PROD_BACKUP_PATH}/" "${BACKUP}/"

	local queued
	queued=$(queue_from_backup "$BACKUP" remote_names)
	touch "$last_sync"
	log "queued ${queued} new application(s)"
}

log "cv-import source=${SOURCE} config=${CONFIG}"
case "$SOURCE" in
	cyberfolks)
		pull_cyberfolks
		;;
	prod-backup)
		pull_prod_backup
		;;
	*)
		log "unknown SOURCE=${SOURCE} (expected cyberfolks or prod-backup)"
		exit 1
		;;
esac

if [[ -n "${AFTER_SYNC_CMD:-}" ]]; then
	log "running AFTER_SYNC_CMD"
	# shellcheck disable=SC2086
	eval "$AFTER_SYNC_CMD"
fi
