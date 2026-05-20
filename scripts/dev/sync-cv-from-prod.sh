#!/usr/bin/env bash
# DEV ONLY — pull new CV applications from prod (10.0.0.220) into local pending/.
# Prod root cron stores backups in /var/www/import/cv/backup; ScheduledImport reads pending/.
set -euo pipefail

[[ "${FREECRM_DEV_CV_SYNC:-0}" == "1" ]] || exit 0

REMOTE_HOST="${REMOTE_HOST:-10.0.0.220}"
REMOTE_USER="${REMOTE_USER:-bmankowski}"
REMOTE="${REMOTE_USER}@${REMOTE_HOST}"
REMOTE_BACKUP="/var/www/import/cv/backup"
IMPORT_CV="${IMPORT_CV:-/var/www/import/cv}"
LOCAL_BACKUP="${IMPORT_CV}/backup"
LOCAL_PENDING="${IMPORT_CV}/pending"
LOG="${ROOT_DIRECTORY:-/var/www/html}/cache/logs/cv-sync.log"
KNOWN_HOSTS="${KNOWN_HOSTS:-/var/www/html/docker/dev/ssh/known_hosts}"
SSH_KEY_DIR="${SSH_KEY_DIR:-/root/.ssh}"
# First run: only recent files (full backup is huge). Override with CV_SYNC_DAYS=365 if needed.
CV_SYNC_DAYS="${CV_SYNC_DAYS:-14}"

mkdir -p "$LOCAL_BACKUP" "$LOCAL_PENDING" "${IMPORT_CV}/processed" "${IMPORT_CV}/failed" "$(dirname "$LOG")"

log() { printf '[%s] %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$*" | tee -a "$LOG"; }

ssh_key=""
for candidate in "${SSH_KEY_DIR}/id_ed25519" "${SSH_KEY_DIR}/id_rsa"; do
	[[ -f "$candidate" ]] && ssh_key="$candidate" && break
done
if [[ -z "$ssh_key" ]]; then
	log "no SSH key in ${SSH_KEY_DIR}"
	exit 1
fi
SSH_TMP=$(mktemp -d)
trap 'rm -rf "$SSH_TMP"' EXIT
cp "$ssh_key" "${SSH_TMP}/id_key"
chmod 700 "$SSH_TMP" && chmod 600 "${SSH_TMP}/id_key"

SSH_CMD=(ssh -i "${SSH_TMP}/id_key" -o BatchMode=yes -o StrictHostKeyChecking=yes
	-o UserKnownHostsFile="${KNOWN_HOSTS}")
RSYNC_SSH="ssh -i ${SSH_TMP}/id_key -o BatchMode=yes -o StrictHostKeyChecking=yes -o UserKnownHostsFile=${KNOWN_HOSTS}"

LAST_SYNC="${IMPORT_CV}/.last_sync"
if [[ -f "$LAST_SYNC" ]]; then
	find_args=(-maxdepth 1 -type f -newer "${LAST_SYNC}")
	log "list on ${REMOTE}:${REMOTE_BACKUP} (since last sync)"
else
	find_args=(-maxdepth 1 -type f -mtime "-${CV_SYNC_DAYS}")
	log "list on ${REMOTE}:${REMOTE_BACKUP} (last ${CV_SYNC_DAYS} days)"
fi

remote_names=()
while IFS= read -r path; do
	[[ -n "$path" ]] && remote_names+=("$(basename "$path")")
done < <("${SSH_CMD[@]}" "$REMOTE" find "$REMOTE_BACKUP" "${find_args[@]}" 2>/dev/null || true)

if [[ ${#remote_names[@]} -eq 0 ]]; then
	log "nothing new on prod"
	exit 0
fi

log "rsync ${#remote_names[@]} file(s) from ${REMOTE}:${REMOTE_BACKUP}/"
printf '%s\n' "${remote_names[@]}" | rsync -rlt --no-perms --no-owner --no-group --files-from=- -e "$RSYNC_SSH" \
	"${REMOTE}:${REMOTE_BACKUP}/" "${LOCAL_BACKUP}/"

queued=0
for name in "${remote_names[@]}"; do
	[[ "$name" == *.json ]] || continue
	base="$name"
	json="${LOCAL_BACKUP}/${base}"

	skip=0
	for subdir in pending processed failed; do
		if [[ -f "${IMPORT_CV}/${subdir}/${base}" ]]; then
			skip=1
			break
		fi
	done
	[[ "$skip" -eq 1 ]] || [[ ! -f "$json" ]] && continue

	cp "$json" "${LOCAL_PENDING}/"
	cv=$(php -r 'echo json_decode(file_get_contents($argv[1]), true)["cv_saved_filename"] ?? "";' "$json")
	if [[ -n "$cv" && -f "${LOCAL_BACKUP}/${cv}" ]]; then
		cp "${LOCAL_BACKUP}/${cv}" "${LOCAL_PENDING}/"
	fi
	queued=$((queued + 1))
done

touch "$LAST_SYNC"
log "queued ${queued} new application(s)"
