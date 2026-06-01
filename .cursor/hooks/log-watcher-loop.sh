#!/usr/bin/env bash
set -euo pipefail

ROOT="${CURSOR_PROJECT_DIR:-$(cd "$(dirname "$0")/../.." && pwd)}"
LOG="$ROOT/cache/logs/system.log"
QUEUE="$ROOT/.cursor/log-warnings.queue"
STATE="$ROOT/.cursor/log-warnings.seen"
LOCK="$ROOT/.cursor/log-warnings.lock"
WATCHER_LOG="$ROOT/.cursor/log-watcher.log"
KEY_SCRIPT="$ROOT/.cursor/hooks/log-warning-key.sh"

mkdir -p "$ROOT/cache/logs" "$ROOT/.cursor"
touch "$LOG" "$QUEUE" "$STATE" "$WATCHER_LOG"

watcher_log() {
	printf '%s %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$1" >>"$WATCHER_LOG"
}

queue_log_line() {
	local line="$1"
	local key=""

	if [[ "$line" =~ PHP\ (Warning|Deprecated|Fatal\ error) ]] && [[ "$line" == *"/vendor/"* ]]; then
		watcher_log "skip vendor: ${line:0:120}"
		return 0
	fi

	key="$(CURSOR_PROJECT_DIR="$ROOT" bash "$KEY_SCRIPT" "$line" 2>/dev/null || true)"
	if [[ -z "$key" ]]; then
		if [[ "$line" =~ PHP\ (Warning|Deprecated|Fatal\ error) ]] && [[ "$line" == *"/cache/templates_c/"* ]]; then
			watcher_log "skip templates_c (no source .tpl): ${line:0:120}"
		fi
		return 0
	fi

	(
		flock -x 200
		if grep -qxF "$key" "$STATE" 2>/dev/null; then
			watcher_log "skip dedupe: $key"
			exit 0
		fi
		echo "$key" >>"$STATE"
		printf '%s\n' "$line" >>"$QUEUE"
		if [[ "$line" =~ PHP\ (Warning|Deprecated|Fatal\ error) ]]; then
			watcher_log "queued php: $key"
		elif [[ "$line" =~ \[(error|warning)\]\ -\  ]]; then
			watcher_log "queued app: $key"
		fi
	) 200>>"$LOCK"
}

watcher_log "watcher started (pid $$)"

tail -n 500 "$LOG" 2>/dev/null | while IFS= read -r line; do
	queue_log_line "$line" || true
done

tail -n 0 -F "$LOG" 2>/dev/null | while IFS= read -r line; do
	queue_log_line "$line" || true
done
