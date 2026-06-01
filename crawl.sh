#!/usr/bin/env bash
# FreeCRM module crawler launcher.
# Run ./crawl.sh --help for full documentation.
exec "$(dirname "$0")/scripts/crawl-modules.sh" "$@"
