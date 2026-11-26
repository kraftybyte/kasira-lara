#!/bin/bash
# Deploy satu commit dari GitHub ke folder situs di Hostinger.
# Pakai: bash deploy-from-github.sh <folder_situs> <commit_sha>
# Hanya menimpa file yang dilacak git; .env, storage/, vendor/ di server tidak disentuh.
set -euo pipefail

SITE="$1"
COMMIT="$2"
REPO="kraftybyte/kasira-lara"
ARCHIVE="https://github.com/${REPO}/archive/${COMMIT}.tar.gz"
BACKUP="$HOME/kasira-backup-${COMMIT}.tgz"
TMP="$(mktemp -d)"

echo "== Unduh ${ARCHIVE}"
curl -fsSL "$ARCHIVE" -o "$TMP/src.tgz"

echo "== Backup file yang akan ditimpa ke ${BACKUP}"
tar -tzf "$TMP/src.tgz" | sed 's#^[^/]*/##' | grep -v '/$' \
  | grep -vE '^(storage/|deploy-temp/|\.DS_Store$|.*/\.DS_Store$)' > "$TMP/files.txt"
(cd "$SITE" && tar -czf "$BACKUP" --ignore-failed-read -T "$TMP/files.txt" 2>/dev/null || true)

echo "== Ekstrak ke ${SITE}"
tar -xzf "$TMP/src.tgz" -C "$SITE" --strip-components=1 \
  --exclude='storage' --exclude='deploy-temp' --exclude='.DS_Store'

cd "$SITE"
echo "== Migrasi"
php artisan migrate --force --no-interaction
echo "== Bersihkan cache"
php artisan optimize:clear --no-interaction
php artisan filament:optimize-clear --no-interaction || true
rm -rf "$TMP"
echo "== Selesai: ${COMMIT}"
