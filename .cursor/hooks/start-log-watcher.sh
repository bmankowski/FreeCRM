#!/usr/bin/env bash
set -euo pipefail

ROOT="${CURSOR_PROJECT_DIR:-$(cd "$(dirname "$0")/../.." && pwd)}"
PIDFILE="$ROOT/.cursor/log-watcher.pid"
WATCHER="$ROOT/.cursor/hooks/log-watcher-loop.sh"
LOGFILE="$ROOT/.cursor/log-watcher.log"
STARTUP_LOCK="$ROOT/.cursor/log-watcher.start.lock"

mkdir -p "$ROOT/.cursor/hooks" "$ROOT/cache/logs"
touch "$ROOT/cache/logs/system.log"

exec 201>>"$STARTUP_LOCK"
flock -x 201

if [[ -f "$PIDFILE" ]]; then
	pid="$(cat "$PIDFILE")"
	if kill -0 "$pid" 2>/dev/null; then
		exit 0
	fi
	rm -f "$PIDFILE"
fi

# Orphaned loops (e.g. after Cursor reload) are not always tracked in the pid file.
while IFS= read -r orphan; do
	[[ -z "$orphan" ]] && continue
	kill "$orphan" 2>/dev/null || true
done < <(pgrep -f "$WATCHER" 2>/dev/null || true)
pkill -f "tail -n 0 -F ${ROOT}/cache/logs/system.log" 2>/dev/null || true

sleep 1

nohup bash "$WATCHER" >>"$LOGFILE" 2>&1 &
echo $! >"$PIDFILE"
exit 0
