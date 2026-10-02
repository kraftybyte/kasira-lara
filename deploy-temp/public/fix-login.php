<?php

use Illuminate\Support\Str;

/**
 * Fix Login Loop - Session Configuration Fix
 *
 * This script:
 * 1. Fixes SESSION_SECURE_COOKIE setting in .env
 * 2. Clears all database sessions
 * 3. Clears all Laravel caches
 * 4. Tests session functionality
 *
 * Run: https://kasira.id/fix-login.php
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== FIX LOGIN LOOP ===\n";
echo 'Timestamp: '.date('Y-m-d H:i:s')."\n\n";

// ============================================
// STEP 1: Fix .env SESSION_SECURE_COOKIE
// ============================================
echo "STEP 1: Fix SESSION_SECURE_COOKIE in .env\n";
echo str_repeat('-', 40)."\n";

$envFile = base_path('.env');

if (! file_exists($envFile)) {
    echo "[ERROR] .env file not found at: $envFile\n";
    echo 'Current base_path: '.base_path()."\n";
    exit(1);
}

$envContent = file_get_contents($envFile);
$originalContent = $envContent;

// Check if SESSION_SECURE_COOKIE is set
if (preg_match('/^SESSION_SECURE_COOKIE=/m', $envContent)) {
    // Update existing value
    $envContent = preg_replace(
        '/^SESSION_SECURE_COOKIE=.*$/m',
        'SESSION_SECURE_COOKIE=true',
        $envContent
    );
    echo "Updated existing SESSION_SECURE_COOKIE\n";
} else {
    // Add new setting after SESSION_DOMAIN
    $envContent = preg_replace(
        '/^(SESSION_DOMAIN=.*)$/m',
        "$1\nSESSION_SECURE_COOKIE=true",
        $envContent
    );
    echo "Added SESSION_SECURE_COOKIE=true\n";
}

// Write updated content
if ($envContent !== $originalContent) {
    file_put_contents($envFile, $envContent);
    echo "[OK] .env updated successfully\n";
} else {
    echo "[OK] SESSION_SECURE_COOKIE already set to true\n";
}

// ============================================
// STEP 2: Clear all database sessions
// ============================================
echo "\nSTEP 2: Clear all database sessions\n";
echo str_repeat('-', 40)."\n";

try {
    $tableName = config('session.table', 'sessions');

    // Get session count before
    $countBefore = DB::table($tableName)->count();
    echo "Sessions before: $countBefore\n";

    // Truncate sessions table
    DB::table($tableName)->truncate();

    // Verify
    $countAfter = DB::table($tableName)->count();
    echo "Sessions after: $countAfter\n";
    echo '[OK] Cleared '.($countBefore - $countAfter)." sessions\n";

} catch (Exception $e) {
    echo '[ERROR] '.$e->getMessage()."\n";
}

// ============================================
// STEP 3: Clear Laravel caches
// ============================================
echo "\nSTEP 3: Clear Laravel caches\n";
echo str_repeat('-', 40)."\n";

$cleared = [];

// Clear config cache
try {
    Artisan::call('config:clear');
    $cleared[] = 'config';
} catch (Exception $e) {
    echo '[WARN] config: '.$e->getMessage()."\n";
}

// Clear route cache
try {
    Artisan::call('route:clear');
    $cleared[] = 'routes';
} catch (Exception $e) {
    echo '[WARN] routes: '.$e->getMessage()."\n";
}

// Clear view cache
try {
    Artisan::call('view:clear');
    $cleared[] = 'views';
} catch (Exception $e) {
    echo '[WARN] views: '.$e->getMessage()."\n";
}

// Clear cache
try {
    Artisan::call('cache:clear');
    $cleared[] = 'cache';
} catch (Exception $e) {
    echo '[WARN] cache: '.$e->getMessage()."\n";
}

// Clear compiled classes
try {
    Artisan::call('clear-compiled');
    $cleared[] = 'compiled';
} catch (Exception $e) {
    echo '[WARN] compiled: '.$e->getMessage()."\n";
}

// Manually clear bootstrap/cache
$bootstrapCache = base_path('bootstrap/cache');
if (is_dir($bootstrapCache)) {
    $files = glob("$bootstrapCache/*.php");
    foreach ($files as $file) {
        if (is_file($file)) {
            @unlink($file);
        }
    }
    $cleared[] = 'bootstrap/cache';
}

// Clear storage framework cache
$frameworkCache = storage_path('framework/cache');
if (is_dir($frameworkCache)) {
    $files = glob("$frameworkCache/*");
    foreach ($files as $file) {
        if (is_file($file)) {
            @unlink($file);
        }
    }
    $cleared[] = 'storage/framework/cache';
}

echo 'Cleared: '.implode(', ', $cleared)."\n";

// ============================================
// STEP 4: Test session functionality
// ============================================
echo "\nSTEP 4: Test session functionality\n";
echo str_repeat('-', 40)."\n";

echo "Session Config:\n";
echo '  Driver: '.config('session.driver')."\n";
echo '  Secure: '.(config('session.secure') ? 'true' : 'false/null')."\n";
echo '  Cookie: '.config('session.cookie')."\n";
echo '  Same Site: '.config('session.same_site')."\n\n";

// Start a fresh session
try {
    Session::start();
    $sessionId = Session::getId();
    echo "Session started: $sessionId\n";

    // Set test data (simulating login)
    Session::put('test_user_id', 99999);
    Session::put('_token', Str::random(40));
    Session::put('auth.single', ['id' => 99999]);
    Session::put('auth.password_confirmed_at', time());

    // Save session
    Session::save();

    // Verify in database
    $savedSession = DB::table($tableName)->where('id', $sessionId)->first();

    if ($savedSession) {
        echo "[OK] Session saved to database!\n";
        echo '  Payload length: '.strlen($savedSession->payload ?? '')."\n";
    } else {
        echo "[ERROR] Session NOT saved to database!\n";
    }

    // Check total sessions
    $totalSessions = DB::table($tableName)->count();
    echo "Total sessions in DB: $totalSessions\n";

} catch (Exception $e) {
    echo '[ERROR] '.$e->getMessage()."\n";
    echo $e->getTraceAsString()."\n";
}

// ============================================
// Summary
// ============================================
echo "\n".str_repeat('=', 40)."\n";
echo "FIX COMPLETED!\n";
echo "Try logging in at: https://kasira.id/admin/login\n";
echo "\nIMPORTANT: Clear your browser cookies and try again!\n";
