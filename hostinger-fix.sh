#!/bin/bash
# =================================================================
# HOSTINGER LARAVEL SESSION FIX SCRIPT
# Run this on Hostinger terminal in public_html directory
# =================================================================

echo "=== HOSTINGER LARAVEL SESSION FIX ==="
echo ""

# 1. Generate NEW clean APP_KEY
NEW_KEY="base64:Wn+RmgSu503JSV/Zq6K+NYkKKQfjqUoISlb3VPSMZIY="

echo "Step 1: Backing up .env..."
cp .env .env.backup

echo "Step 2: Creating clean .env..."
grep -v "^APP_KEY=" .env > .env.tmp
grep -v "^SESSION" .env.tmp > .env.clean

cat >> .env.clean << 'EOF'
APP_KEY=base64:Wn+RmgSu503JSV/Zq6K+NYkKKQfjqUoISlb3VPSMZIY=
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=false
EOF

mv .env.clean .env
rm .env.tmp

echo "Step 3: Clearing all caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
php artisan optimize

echo "Step 4: Clearing all sessions in database..."
php artisan tinker --execute="DB::table('sessions')->truncate();"

echo "Step 5: Verifying configuration..."
echo ""
echo "=== VERIFICATION ==="
grep "^APP_KEY=" .env
grep "^SESSION_DRIVER=" .env
grep "^SESSION_DOMAIN=" .env
grep "^SESSION_SECURE_COOKIE=" .env
echo ""

# Test database
echo "Testing database connection..."
php artisan tinker --execute="
try {
    \$count = DB::table('sessions')->count();
    echo 'Sessions table: OK (count: ' . \$count . ')' . PHP_EOL;
} catch (\Exception \$e) {
    echo 'Sessions table: ERROR - ' . \$e->getMessage() . PHP_EOL;
}
"

echo ""
echo "=== FIX COMPLETE ==="
echo ""
echo "NEXT STEPS:"
echo "1. Clear ALL browser cookies for kasira.id"
echo "2. Try login at https://kasira.id/admin/login"
echo "3. If still failing, check storage/logs/laravel.log"
echo ""
