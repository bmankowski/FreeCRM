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

# Ensure optional libraries are present (bind-mount overwrites image contents).
# mPDF is required for PDF export/generation.
if [ ! -f "libraries/mPDF/mpdf.php" ]; then
  echo "[freecrm-entrypoint] mPDF library missing; installing..."
  mpdf_ver="$(php -r '$v=require "config/version.php"; echo $v["lib_mPDF"] ?? "0.0.2";')"
  repo="https://github.com/YetiForceCompany/lib_mPDF.git"
  tmp_dir="/tmp/lib_mPDF_${mpdf_ver}"

  rm -rf "$tmp_dir" || true
  if git clone --depth 1 --branch "$mpdf_ver" "$repo" "$tmp_dir"; then
    rm -rf libraries/mPDF || true
    mkdir -p libraries
    mv "$tmp_dir" libraries/mPDF
    chmod -R a+rX,a+rwX libraries/mPDF 2>/dev/null || true
    echo "[freecrm-entrypoint] mPDF library installed into libraries/mPDF/"
  else
    echo "[freecrm-entrypoint] Failed to clone mPDF (${mpdf_ver}) from ${repo}"
    rm -rf "$tmp_dir" || true
  fi
fi

exec "$@"

