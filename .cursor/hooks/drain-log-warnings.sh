#!/usr/bin/env bash
set -euo pipefail

input="$(cat)"
status="$(printf '%s' "$input" | python3 -c 'import json,sys; print(json.load(sys.stdin).get("status","completed"))' 2>/dev/null || echo completed)"
loop_count="$(printf '%s' "$input" | python3 -c 'import json,sys; print(json.load(sys.stdin).get("loop_count",0))' 2>/dev/null || echo 0)"

if [[ "$status" != "completed" ]]; then
	printf '{}\n'
	exit 0
fi

ROOT="${CURSOR_PROJECT_DIR:-$(cd "$(dirname "$0")/../.." && pwd)}"
QUEUE="$ROOT/.cursor/log-warnings.queue"
STATE="$ROOT/.cursor/log-warnings.seen"
LOCK="$ROOT/.cursor/log-warnings.lock"
KEY_SCRIPT="$ROOT/.cursor/hooks/log-warning-key.sh"

if [[ ! -s "$QUEUE" ]]; then
	printf '{}\n'
	exit 0
fi

popped=""
{
	flock -x 200
	if [[ -s "$QUEUE" ]]; then
		popped="$(head -n 1 "$QUEUE")"
		tail -n +2 "$QUEUE" >"$QUEUE.tmp" && mv "$QUEUE.tmp" "$QUEUE"
	fi
} 200>>"$LOCK"

if [[ -z "$popped" ]]; then
	printf '{}\n'
	exit 0
fi

key="$(CURSOR_PROJECT_DIR="$ROOT" bash "$KEY_SCRIPT" "$popped" 2>/dev/null || true)"
if [[ -n "$key" && -f "$STATE" ]]; then
	grep -vxF "$key" "$STATE" >"$STATE.tmp" && mv "$STATE.tmp" "$STATE"
fi

python3 - "$popped" "$loop_count" <<'PY'
import json
import sys

warning = sys.argv[1]
loop_count = sys.argv[2]

msg = f"""Fix one runtime warning or application error from cache/logs/system.log (auto-queued by log watcher, loop #{loop_count}).

Log entry:
{warning}

Instructions:
- Fix the source file under src/ or layouts/ only. Never edit vendor/ or cache/templates_c/.
- Map Docker paths: /var/www/html/ is the project root.
- If the warning originates from compiled Smarty output, find and fix the matching .tpl under layouts/, then clear Smarty cache: rm -f cache/templates_c/*.php
- Apply a minimal fix for this one issue. Do not batch unrelated warnings.
- Follow log-warning-fixer.mdc, error-checking.mdc, and php-coding-standards.mdc.
- After fixing, stop. The stop hook will submit the next queued warning if any remain."""

print(json.dumps({"followup_message": msg}))
PY
