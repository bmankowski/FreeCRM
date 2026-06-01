#!/usr/bin/env bash
# Print dedupe key for a system.log line (stdout). Exit 0 if queueable, 1 otherwise.
set -euo pipefail

line="${1:-}"
[[ -n "$line" ]] || exit 1

ROOT="${CURSOR_PROJECT_DIR:-$(cd "$(dirname "$0")/../.." && pwd)}"

source_file_exists() {
	[[ -f "$ROOT/$1" ]]
}

resolve_templates_c_path() {
	local tpl_line="$1"
	if [[ "$tpl_line" =~ _0\.file\.([A-Za-z0-9_]+)\.tpl\.php ]]; then
		local tpl_name="${BASH_REMATCH[1]}.tpl"
		local found
		found="$(find "$ROOT/layouts" -name "$tpl_name" -print -quit 2>/dev/null || true)"
		if [[ -n "$found" ]]; then
			echo "${found#"$ROOT/"}"
			return 0
		fi
	fi
	return 1
}

if [[ "$line" =~ PHP\ (Warning|Deprecated|Fatal\ error) ]]; then
	[[ "$line" == *"/vendor/"* ]] && exit 1

	if [[ "$line" =~ in\ (.+)\ on\ line\ ([0-9]+) ]]; then
		filepath="${BASH_REMATCH[1]}"
		filepath="${filepath#/var/www/html/}"
		if [[ "$filepath" == cache/templates_c/* ]]; then
			if source_path="$(resolve_templates_c_path "$line")"; then
				filepath="$source_path"
			else
				exit 1
			fi
		fi
		if ! source_file_exists "$filepath"; then
			exit 1
		fi
		printf '%s:%s\n' "$filepath" "${BASH_REMATCH[2]}"
		exit 0
	fi

	printf '%s\n' "${line:0:200}"
	exit 0
fi

if [[ "$line" =~ \[(error|warning)\]\ -\  ]]; then
	level="${BASH_REMATCH[1]}"
	if [[ "$line" =~ \=\>\ (.+):([0-9]+)$ ]]; then
		filepath="${BASH_REMATCH[1]}"
		filepath="${filepath#/var/www/html/}"
		if ! source_file_exists "$filepath"; then
			exit 1
		fi
		printf '%s:%s\n' "$filepath" "${BASH_REMATCH[2]}"
		exit 0
	fi
	key="${line#*\[$level\] - }"
	printf '%s\n' "${key:0:200}"
	exit 0
fi

exit 1
