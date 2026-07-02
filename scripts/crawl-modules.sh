#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

show_help() {
	cat <<'EOF'
FreeCRM module crawler
======================

Visits every active CRM module (ListView, ListPreview, DashBoard, Edit where
available), every DetailView tab on a sample record per entity module (summary,
details, comments, updates, related lists), logs in as admin, and watches
cache/logs/system.log for new PHP errors after each page load.

By default the crawl STOPS on the first failure (HTTP error or new log line
at the selected severity). A JSON report and console log are written when
finished or stopped early.

Requires: Docker Compose (app + db services), Node.js on the host, network
access to the target CRM URL.


QUICK START
-----------

  ./crawl.sh
      Export module URLs, crawl https://dev.itconnect.pl, stop on first error,
      watch system.log for Fatal/Parse/Uncaught lines only.

  ./crawl.sh --log-level warnings
      Also fail on PHP Warning and PHP Error log lines.

  ./crawl.sh --help
      Show this help.


WHAT THE SCRIPT DOES
--------------------

  1. docker compose exec app php scripts/export-crawl-urls.php
         Writes tests/e2e/.crawl-urls.json (module views + DetailView tabs).

  2. docker run … playwright … npx tsx scripts/module-crawler.ts
         Playwright Chromium logs in and visits each URL sequentially.
         Reads cache/logs/system.log before/after each request.

  3. Output
         tests/e2e/.crawl-run.log     — full console output (tee)
         tests/e2e/.crawl-report.json — structured report (findings, counts)


ENVIRONMENT VARIABLES (shell wrapper)
-------------------------------------

  FREECRM_PLAYWRIGHT_IMAGE
      Docker image with Playwright + Chromium preinstalled.
      Default: mcr.microsoft.com/playwright:v1.56.1-noble

  FREECRM_LOG_LEVEL
      Default log severity when --log-level is not passed on the command line.
      Values: errors | warnings | all
      Default: errors

  All FREECRM_* variables below are forwarded to the Node crawler unchanged.


ENVIRONMENT VARIABLES (Node crawler)
------------------------------------

  FREECRM_URL
      Base URL of the CRM instance (no trailing slash required).
      Default: https://dev.itconnect.pl

  FREECRM_USER
      Login username. Fields are filled individually in the browser.
      Default: admin

  FREECRM_PASS
      Login password.
      Default: NewArgon2idAdmin#42

  FREECRM_LOG_LEVEL
      Which system.log lines count as failures.
        errors   — [error], PHP Fatal error, PHP Parse error, Uncaught
        warnings — above + [warning], PHP Warning, PHP Error
        all      — above + [info], [trace], PHP Deprecated, PHP Notice
      Default: warnings (overridden to errors by this shell script unless set)

  FREECRM_LOG_PATH
      Path to system.log on the host (mounted into the Playwright container).
      Default: cache/logs/system.log (relative to repo root)

  FREECRM_CONTINUE_ON_FAIL
      Set to 1 to keep crawling after failures (same as --continue-on-fail).
      Default: unset (stop on first failure)

  FREECRM_START_FROM
      Begin at URL N (1-based). Same as --start-from.
      Default: 1


CLI OPTIONS (passed to module-crawler.ts)
-----------------------------------------

  -h, --help
      Show module-crawler help (Node script options only).

  --url URL
      CRM base URL. Overrides FREECRM_URL.
      Example: --url https://dev.itconnect.pl

  --log-level LEVEL
      Log severity filter: errors | warnings | all
      Overrides FREECRM_LOG_LEVEL and the shell default (errors).
      Example: --log-level all

  --log-path PATH
      system.log path inside the mounted repo (use /work/… in Docker).
      This script sets --log-path /work/cache/logs/system.log automatically;
      override only for custom layouts.
      Example: --log-path /work/cache/logs/system.log

  --urls-file PATH
      JSON file produced by export-crawl-urls.php.
      Default: tests/e2e/.crawl-urls.json
      Example: --urls-file /work/tests/e2e/.crawl-urls-smoke.json

  --report-file PATH
      Where to write the JSON crawl report.
      Default: tests/e2e/.crawl-report.json
      Example: --report-file /work/tests/e2e/.crawl-report-smoke.json

  --stop-on-fail
      Stop crawling after the first HTTP or log failure (default behaviour).

  --continue-on-fail
      Visit all URLs even when failures occur; report everything at the end.

  --start-from N
      Begin crawling at URL N (1-based index, same as [N/total] in progress output).
      Skips URLs 1 through N-1. Useful after fixing a failure to resume where you left off.
      Example: ./crawl.sh --start-from 84
      Env: FREECRM_START_FROM


LOG LEVEL REFERENCE
-------------------

  errors
      Matches: [error] in system.log, PHP Fatal error, PHP Parse error, Uncaught
      Use for: smoke runs, CI gates, finding hard breakages only.

  warnings
      Matches: errors + [warning], PHP Warning, PHP Error
      Use for: routine regression checks after code changes.

  all
      Matches: warnings + [info], [trace], PHP Deprecated, PHP Notice
      Use for: modernization sweeps (noisy — many known deprecations exist).


FAILURE CONDITIONS
------------------

  A URL is reported as FAIL when any of these occur:

  • Navigation timeout or Playwright error
  • HTTP status >= 400
  • New line(s) in system.log matching the selected --log-level filter

  With default --stop-on-fail the crawl exits immediately after the first FAIL,
  prints failure details (URL, module, view, log lines), and writes a partial
  report.


EXIT CODES
----------

  0   No failures at the selected log level (all visited URLs returned HTTP
      2xx/3xx and produced no matching log lines).

  1   At least one failure, or script error (missing URLs file, Docker failure,
      login failure, etc.).


OUTPUT FILES (gitignored)
-------------------------

  tests/e2e/.crawl-urls.json   — exported URL list (regenerated each run)
  tests/e2e/.crawl-report.json — JSON report with findings array
  tests/e2e/.crawl-run.log       — full stdout/stderr from the crawl


EXAMPLES
--------

  # Default smoke crawl (errors only, stop on first problem)
  ./crawl.sh

  # Stricter: catch PHP warnings too
  ./crawl.sh --log-level warnings

  # Full audit including deprecations (slow, noisy)
  ./crawl.sh --log-level all --continue-on-fail

  # Custom credentials and target
  FREECRM_USER=admin FREECRM_PASS='MySecret#42' \
    ./crawl.sh --url https://dev.itconnect.pl

  # Resume after fixing a failure at URL 84
  ./crawl.sh --start-from 84

  # Smoke-test a subset (export full list first, then slice manually)
  node -e "const u=require('./tests/e2e/.crawl-urls.json'); \
    require('fs').writeFileSync('tests/e2e/.crawl-urls-smoke.json', \
    JSON.stringify(u.slice(0,20)))"
  ./crawl.sh --urls-file tests/e2e/.crawl-urls-smoke.json \
             --report-file tests/e2e/.crawl-report-smoke.json

  # Re-export URLs only (without crawling)
  docker compose exec -T app php scripts/export-crawl-urls.php \
    > tests/e2e/.crawl-urls.json

  # Run crawler directly (after export), e.g. inside Playwright container
  docker run --rm --network host -v "$PWD:/work" -w /work/tests/e2e \
    mcr.microsoft.com/playwright:v1.56.1-noble \
    npx tsx scripts/module-crawler.ts --help


NOTES
-----

  • system.log must be the same file the web app writes to. When crawling
    https://dev.itconnect.pl served by local Docker, cache/logs/system.log on
    the host is correct. Remote servers need their log synced separately.

  • Playwright runs in Docker because local browser install may fail on some
    WSL/Linux versions. Override FREECRM_PLAYWRIGHT_IMAGE if needed.

  • Settings modules (parent=Settings) are not included — only modules from
    vtiger_tab with presence 0 or 2.

  • Equivalent npm command: npm run crawl:modules -- [options]

EOF
}

for arg in "$@"; do
	case "$arg" in
		-h|--help)
			show_help
			exit 0
			;;
	esac
done

cd "$ROOT"

PLAYWRIGHT_IMAGE="${FREECRM_PLAYWRIGHT_IMAGE:-mcr.microsoft.com/playwright:v1.56.1-noble}"
LOG_LEVEL="${FREECRM_LOG_LEVEL:-errors}"

EXTRA_ARGS=()
CRAWL_LOG_LEVEL="$LOG_LEVEL"
HAS_LOG_LEVEL=0
args=("$@")
for ((i = 0; i < ${#args[@]}; i++)); do
	if [[ "${args[i]}" == "--log-level" ]]; then
		HAS_LOG_LEVEL=1
		if [[ -n "${args[i + 1]:-}" ]]; then
			CRAWL_LOG_LEVEL="${args[i + 1]}"
		fi
	fi
done

if [[ "$HAS_LOG_LEVEL" -eq 0 ]]; then
	EXTRA_ARGS=(--log-level "$LOG_LEVEL")
fi
EXTRA_ARGS+=("$@")

echo "==> Exporting module URLs..."
docker compose exec -T app php scripts/export-crawl-urls.php > tests/e2e/.crawl-urls.json
URL_COUNT="$(node -e "console.log(require('./tests/e2e/.crawl-urls.json').length)")"
echo "    $URL_COUNT URLs ready"
echo ""
echo "==> Crawling (stop on fail, log level: ${CRAWL_LOG_LEVEL})..."
echo "    Report: tests/e2e/.crawl-report.json"
echo "    Log:    tests/e2e/.crawl-run.log"
echo "    Help:   ./crawl.sh --help"
echo ""

docker run --rm --network host \
	-v "$ROOT:/work" \
	-w /work/tests/e2e \
	-e FREECRM_URL="${FREECRM_URL:-https://dev.itconnect.pl}" \
	-e FREECRM_USER="${FREECRM_USER:-admin}" \
	-e FREECRM_PASS="${FREECRM_PASS:-NewArgon2idAdmin#42}" \
	-e FREECRM_LOG_PATH="${FREECRM_LOG_PATH:-/work/cache/logs/system.log}" \
	-e FREECRM_CONTINUE_ON_FAIL="${FREECRM_CONTINUE_ON_FAIL:-}" \
	-e FREECRM_START_FROM="${FREECRM_START_FROM:-}" \
	"$PLAYWRIGHT_IMAGE" \
	npx tsx scripts/module-crawler.ts \
		--log-path /work/cache/logs/system.log \
		--report-file /work/tests/e2e/.crawl-report.json \
		"${EXTRA_ARGS[@]}" \
	2>&1 | tee tests/e2e/.crawl-run.log
