#!/bin/bash
# =========================================================================
# KASIRA LARAVEL - HOSTINGER 403 FIX & DIAGNOSTICS
# Diagnose and fix 403 Forbidden errors after login
# =========================================================================

set -e

echo "=========================================="
echo "KASIRA - 403 FORBIDDEN DIAGNOSTICS"
echo "=========================================="
echo ""

# Check database
echo "Checking database connection..."
php artisan tinker --execute="
\$users = DB::table('users')->count();
\$tenants = DB::table('tenants')->count();
\$relations = DB::table('tenant_user')->count();

echo 'Users: ' . \$users . PHP_EOL;
echo 'Tenants: ' . \$tenants . PHP_EOL;
echo 'User-Tenant Relations: ' . \$relations . PHP_EOL;
" 2>/dev/null

echo ""
echo "Checking for users without tenants..."
php artisan tinker --execute="
\$orphaned = DB::table('users')
    ->whereNotExists(function (\$query) {
        \$query->select(DB::raw(1))
            ->from('tenant_user')
            ->whereRaw('tenant_user.user_id = users.id');
    })
    ->count();

echo 'Users WITHOUT any tenant assignment: ' . \$orphaned . PHP_EOL;

if (\$orphaned > 0) {
    echo PHP_EOL . 'PROBLEM FOUND!' . PHP_EOL;
    echo 'These users cannot access any admin pages.' . PHP_EOL;
}
" 2>/dev/null

echo ""
echo "Fixing: Assigning all users to all active tenants..."
php artisan tinker --execute="
\$users = App\Models\User::all();
\$tenants = App\Models\Tenant::where('is_active', true)->get();

foreach (\$users as \$user) {
    foreach (\$tenants as \$tenant) {
        \$user->tenants()->attach(\$tenant->id, ['status' => 'active']);
    }
}

echo 'Fixed: All users now have tenant access' . PHP_EOL;
" 2>/dev/null

echo ""
echo "Clearing all caches..."
php artisan config:clear --no-interaction
php artisan cache:clear --no-interaction
php artisan route:clear --no-interaction
php artisan view:clear --no-interaction
echo "✓ Caches cleared"

echo ""
echo "=========================================="
echo "FIX COMPLETE"
echo "=========================================="
echo ""
echo "NEXT: Clear browser cookies and try login again"
echo ""
