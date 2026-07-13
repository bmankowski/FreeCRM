#!/usr/bin/env bash
# Deprecated wrapper — use scripts/cv-sync/importCandidatesFiles.sh directly.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
exec "${ROOT}/scripts/cv-sync/importCandidatesFiles.sh" --config "${CV_IMPORT_CONFIG:-${ROOT}/docker/cv-sync/cv-import.from-prod-backup.conf}"
