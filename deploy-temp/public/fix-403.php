<?php
// Force fix script - updates IdentifyTenant middleware
// Run this by accessing: https://kasira.id/fix-403.php

echo "=== FORCE FIX FOR 403 ERROR ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

// Define the updated IdentifyTenant middleware content
$middlewareContent = '<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Filament\Facades\Filament;
use Filament\Models\Contracts\HasTenants;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IdentifyTenant
{
    private const PUBLIC_PATHS = [
        \'admin/login\',
        \'admin/register\',
        \'admin/forgot-password\',
    ];

    public function handle(Request $request, Closure $next): mixed
    {
        $path = $request->path();

        // Early return for public/auth pages
        if ($this->isPublicPath($path)) {
            return $next($request);
        }

        // Get current panel
        $panel = Filament::getCurrentOrDefaultPanel();

        // Skip if panel doesn\'t have tenancy
        if (! $panel->hasTenancy()) {
            return $next($request);
        }

        // Skip if route doesn\'t have tenant parameter
        if (! $request->route() || ! $request->route()->hasParameter(\'tenant\')) {
            return $next($request);
        }

        $tenantParam = $request->route()->parameter(\'tenant\');

        // Handle empty tenant parameter
        if (empty($tenantParam)) {
            $user = $this->getUser($panel);
            if (! $user) {
                return $next($request);
            }
            return $next($request);
        }

        // Ensure session is valid
        if ($request->hasSession()) {
            try {
                if (!$request->session()->isStarted()) {
                    $request->session()->start();
                }
            } catch (\\Throwable $e) {
                Log::warning(\'Session start failed, regenerating\', [\'error\' => $e->getMessage()]);
                $request->session()->regenerate();
            }
        }

        // Get authenticated user
        $user = $this->getUser($panel);

        if (! $user) {
            Log::info(\'IdentifyTenant: No authenticated user, redirecting to login\');
            return redirect()->to(Filament::getLoginUrl());
        }

        // Verify user implements HasTenants
        if (! $user instanceof HasTenants) {
            abort(404, \'User tidak memiliki akses multi-tenant.\');
        }

        // Resolve tenant
        $tenant = $this->resolveTenant($tenantParam);

        if (! $tenant) {
            abort(404, \'Tenant tidak ditemukan.\');
        }

        // Check access permission
        if (! $user->canAccessTenant($tenant)) {
            Log::warning(\'User denied tenant access\', [
                \'user_id\' => $user->id,
                \'tenant\' => $tenant->slug
            ]);
            abort(403, \'Anda tidak memiliki akses ke tenant ini.\');
        }

        // Set tenant context
        Filament::setTenant($tenant);

        return $next($request);
    }

    private function isPublicPath(string $path): bool
    {
        $normalized = trim($path, \'/\');';
        $middlewareContent .= "\n";
        $middlewareContent .= '        foreach (self::PUBLIC_PATHS as $publicPath) {
            if ($normalized === $publicPath || str_starts_with($normalized, $publicPath)) {
                return true;
            }
        }' . "\n";
        $middlewareContent .= '        return false;
    }

    private function getUser($panel): ?Model
    {
        try {
            return $panel->auth()->user();
        } catch (\\Throwable $e) {
            Log::debug(\'IdentifyTenant: Error getting user\', [\'error\' => $e->getMessage()]);
            return null;
        }
    }

    private function resolveTenant(mixed $tenantParam): ?Tenant
    {
        if (! is_string($tenantParam)) {
            $tenantParam = (string) $tenantParam;
        }

        try {
            $tenant = Filament::getCurrentOrDefaultPanel()->getTenant($tenantParam);
            if ($tenant) {
                return $tenant;
            }
        } catch (ModelNotFoundException) {}

        $decoded = rawurldecode($tenantParam);
        $normalized = strtolower(trim($decoded));

        $tenant = Tenant::whereRaw(\'LOWER(slug) = ?\', [$normalized])->first();
        if ($tenant) {
            return $tenant;
        }

        if (is_numeric($tenantParam)) {
            return Tenant::find((int) $tenantParam);
        }

        return null;
    }
}
';

$middlewarePath = __DIR__ . '/../app/Http/Middleware/IdentifyTenant.php';
$backupPath = __DIR__ . '/../app/Http/Middleware/IdentifyTenant.php.bak';

echo "Step 1: Creating backup of current IdentifyTenant.php...\n";
if (file_exists($middlewarePath)) {
    copy($middlewarePath, $backupPath);
    echo "  [OK] Backup created at: IdentifyTenant.php.bak\n";
} else {
    echo "  [WARN] Original file not found, skipping backup\n";
}

echo "\nStep 2: Writing updated IdentifyTenant.php...\n";
$result = file_put_contents($middlewarePath, $middlewareContent);
if ($result !== false) {
    echo "  [OK] Updated IdentifyTenant.php ({$result} bytes)\n";
} else {
    echo "  [ERROR] Failed to write IdentifyTenant.php\n";
    exit(1);
}

echo "\nStep 3: Verifying file was written correctly...\n";
clearstatcache();
$writtenContent = file_get_contents($middlewarePath);
if (strpos($writtenContent, 'private function isPublicPath') !== false) {
    echo "  [OK] File content verified\n";
} else {
    echo "  [ERROR] File content verification failed\n";
    exit(1);
}

echo "\nStep 4: Clearing Laravel caches...\n";
$basePath = dirname(__DIR__);
chdir($basePath);

$commands = [
    'php artisan config:clear 2>&1',
    'php artisan cache:clear 2>&1',
    'php artisan route:clear 2>&1',
    'php artisan view:clear 2>&1',
];

foreach ($commands as $cmd) {
    echo "  Running: {$cmd}\n";
    $output = [];
    exec($cmd, $output, $return);
    if ($return === 0) {
        echo "    [OK]\n";
    } else {
        echo "    [WARN] Command returned: " . implode("\n", $output) . "\n";
    }
}

echo "\nStep 5: Checking file permissions...\n";
if (is_readable($middlewarePath)) {
    echo "  [OK] File is readable\n";
} else {
    echo "  [WARN] File may not be readable\n";
}

echo "\n=== FIX COMPLETE ===\n";
echo "Completed at: " . date('Y-m-d H:i:s') . "\n";
echo "\nPlease clear your browser cookies and try logging in again.\n";
echo "If you still get 403, please restore the backup:\n";
echo "  cp app/Http/Middleware/IdentifyTenant.php.bak app/Http/Middleware/IdentifyTenant.php\n";
