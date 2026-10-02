<?php
// Test script to verify login flow works correctly
// Run this by accessing: https://kasira.id/test-login-fix.php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== LOGIN FIX VERIFICATION ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

// 1. Check session configuration
echo "1. Session Configuration:\n";
echo "   Driver: " . config('session.driver') . "\n";
echo "   Lifetime: " . config('session.lifetime') . " minutes\n";
echo "   Secure: " . (config('session.secure') ? 'true' : 'false') . "\n";
echo "   SameSite: " . config('session.same_site') . "\n";
echo "   Domain: " . (config('session.domain') ?? 'null') . "\n\n";

// 2. Check IdentifyTenant middleware
echo "2. IdentifyTenant Middleware:\n";
$middlewarePath = __DIR__ . '/../app/Http/Middleware/IdentifyTenant.php';
if (file_exists($middlewarePath)) {
    $content = file_get_contents($middlewarePath);
    echo "   File exists: YES\n";
    echo "   File size: " . filesize($middlewarePath) . " bytes\n";
    if (strpos($content, 'isPublicPath') !== false) {
        echo "   Has PUBLIC_PATHS: YES\n";
    } else {
        echo "   Has PUBLIC_PATHS: NO\n";
    }
    if (strpos($content, 'session()->start()') !== false) {
        echo "   Has session handling: YES\n";
    } else {
        echo "   Has session handling: NO\n";
    }
} else {
    echo "   File exists: NO\n";
}
echo "\n";

// 3. Check if trustedproxy exists (should be deleted)
echo "3. TrustedProxy Config:\n";
$trustedProxyPath = __DIR__ . '/../config/trustedproxy.php';
if (file_exists($trustedProxyPath)) {
    echo "   Exists: YES (SHOULD BE DELETED!)\n";
} else {
    echo "   Exists: NO (CORRECT)\n";
}
echo "\n";

// 4. Check routes
echo "4. Admin Routes:\n";
$routes = route('filament.admin.auth.login');
echo "   Login URL: $routes\n";
echo "   Accessible: " . (Str::startsWith($routes, 'http') ? 'YES' : 'NO') . "\n\n";

// 5. Check tenants
echo "5. Tenants:\n";
try {
    $tenants = \App\Models\Tenant::all();
    echo "   Total tenants: " . $tenants->count() . "\n";
    foreach ($tenants->take(5) as $tenant) {
        echo "   - " . $tenant->slug . "\n";
    }
} catch (\Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}
echo "\n";

// 6. Check users
echo "6. Users:\n";
try {
    $users = \App\Models\User::with(['tenants', 'roles'])->get();
    echo "   Total users: " . $users->count() . "\n";
    foreach ($users->take(3) as $user) {
        echo "   - " . $user->email . "\n";
        echo "     Roles: " . $user->getRoleNames()->implode(', ') . "\n";
        echo "     Tenants: " . $user->tenants->pluck('slug')->implode(', ') . "\n";
        if ($user->hasRole('super_admin')) {
            echo "     Is super_admin: YES (can access all tenants)\n";
        }
    }
} catch (\Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}
echo "\n";

// 7. Simulate login test
echo "7. Login Simulation Test:\n";
try {
    $testUser = \App\Models\User::first();
    if ($testUser) {
        echo "   Test user: " . $testUser->email . "\n";

        // Check if user can access a tenant
        $firstTenant = \App\Models\Tenant::first();
        if ($firstTenant) {
            echo "   Test tenant: " . $firstTenant->slug . "\n";

            // Test canAccessTenant
            if ($testUser->canAccessTenant($firstTenant)) {
                echo "   canAccessTenant: PASS\n";
            } else {
                echo "   canAccessTenant: FAIL (user may not have access)\n";
            }

            // Check if super_admin bypass
            if ($testUser->hasRole('super_admin')) {
                echo "   super_admin bypass: PASS\n";
            } else {
                echo "   super_admin bypass: N/A (not super_admin)\n";
            }
        }
    }
} catch (\Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}
echo "\n";

// 8. Summary
echo "=== SUMMARY ===\n";
echo "If all checks pass, the login should work:\n";
echo "1. Session is configured correctly\n";
echo "2. IdentifyTenant middleware has public path handling\n";
echo "3. TrustedProxy is not causing issues\n";
echo "4. Users have tenant access\n";
echo "\nNext steps:\n";
echo "- Clear browser cookies\n";
echo "- Go to https://kasira.id/admin/login\n";
echo "- Login and verify redirect to tenant URL works\n";
echo "- Dashboard should load without 403 error\n";
