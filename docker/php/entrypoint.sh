#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

# Some repo files are too restrictive (e.g. 0600) for www-data in containers.
# Make application code readable, while keeping ownership unchanged.
chmod -R a+rX src vendor config public index.php api.php webservice.php yii 2>/dev/null || true

# Ensure runtime dirs are writable in container
mkdir -p cache storage user_privileges
chown -R www-data:www-data cache storage || true

# Installer needs to write config/config.inc.php (bind mounts can be restrictive).
chmod -R a+rwX config cache storage user_privileges 2>/dev/null || true

# Installer also checks write access to these paths.
mkdir -p cache/addressBook cache/images cache/import cron/modules 2>/dev/null || true
chmod -R a+rwX cache cron modules libraries 2>/dev/null || true

exec "$@"

