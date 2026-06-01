#!/usr/bin/env bash
set -euo pipefail

input="$(cat)"
mode="$(printf '%s' "$input" | python3 -c 'import json,sys; print(json.load(sys.stdin).get("composer_mode",""))' 2>/dev/null || true)"

if [[ "$mode" != "agent" ]]; then
	printf '{}\n'
	exit 0
fi

ROOT="${CURSOR_PROJECT_DIR:-$(cd "$(dirname "$0")/../.." && pwd)}"
QUEUE="$ROOT/.cursor/log-warnings.queue"

python3 - "$QUEUE" <<'PY'
import json
import sys
from pathlib import Path

queue_path = Path(sys.argv[1])
items = []
if queue_path.is_file():
    items = [line for line in queue_path.read_text(encoding="utf-8").splitlines() if line.strip()]

base = (
    "FreeCRM log-watcher hooks are active for this workspace. "
    "cache/logs/system.log is tailed in the background; new PHP Warning, Deprecated, "
    "Fatal error, and application [error] and [warning] lines (excluding vendor/; "
    "templates_c mapped to layouts/ when possible) are queued in .cursor/log-warnings.queue. "
    "When an agent turn completes, the stop hook drains one queued warning and auto-submits "
    "a follow-up fix task. See log-warning-fixer.mdc."
)

if items:
    preview = items[0] if len(items[0]) <= 400 else items[0][:397] + "..."
    base += (
        f" Queue status: {len(items)} pending item(s). "
        f"First queued entry: {preview!r}. "
        "To start or continue the fix loop in this session, send any short message "
        "(e.g. 'fix queued log warnings' or 'go') and let the turn complete; "
        "the stop hook will auto-submit the next fix while items remain."
    )
else:
    base += (
        " Queue is currently empty. New warnings from system.log will be appended automatically; "
        "finish any agent turn while the queue has items to drain one fix at a time."
    )

print(json.dumps({"additional_context": base}))
PY
