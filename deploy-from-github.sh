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
echo "== Hapus skrip debug publik lama"
(cd public && rm -f clear-all-sessions.php deploy-extract.php fix-403.php fix-login.php laravel-bootstrap-test.php laravel-session-config-test.php laravel-session-test.php session-test.php test-login-fix.php test-login-flow.php test-login.php test-real-login.php test-session.php debug-session.php public-sync-hostinger.php test.html)
echo "== Hapus skrip perbaikan lama di root"
rm -f sync-hostinger.php test-login.php test-login-flow.php fix-login.php fix-403.php fix-session.php debug-session.php session-test.php test-modal.blade.php root-test.html clear-cache.php clear-cache.sh fix-hostinger.sh deploy-hostinger.sh fix-403-forbidden.sh hostinger-fix.sh HOSTINGER-403-FIX.md PLAN_RBAC.md PLAN_MODIFIERS.md deploy.zip
echo "== Migrasi"
php artisan migrate --force --no-interaction
echo "== Storage publik"
# Server lama menyimpan upload di storage_old; satukan ke storage lalu buat ulang symlink public/storage.
if [ -d storage_old/app/public ]; then cp -Rn storage_old/app/public/. storage/app/public/ && rm -rf storage_old; fi
php artisan storage:link --force --no-interaction
echo "== Bersihkan cache"
php artisan optimize:clear --no-interaction
php artisan filament:optimize-clear --no-interaction || true
rm -rf "$TMP"
echo "== Selesai: ${COMMIT}"
