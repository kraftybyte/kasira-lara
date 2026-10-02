<?php

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Test Login Flow Script
 *
 * Simulates a complete login flow to diagnose session issues.
 * Tests:
 * 1. User authentication
 * 2. Session creation and storage
 * 3. Session data preservation
 * 4. Cookie settings
 *
 * Run: https://kasira.id/test-login-flow.php
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== LOGIN FLOW TEST ===\n";
echo 'Timestamp: '.date('Y-m-d H:i:s')."\n";
echo 'PHP Version: '.PHP_VERSION."\n";
echo 'Laravel: '.app()->version()."\n\n";

// ============================================
// TEST 1: Environment & HTTPS Detection
// ============================================
echo "TEST 1: Environment & HTTPS Detection\n";
echo str_repeat('-', 40)."\n";
echo 'HTTPS: '.(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'YES' : 'NO')."\n";
echo 'HTTPS Header: '.($_SERVER['HTTPS'] ?? 'not set')."\n";
echo 'Server Port: '.($_SERVER['SERVER_PORT'] ?? 'not set')."\n";
echo 'APP_URL: '.config('app.url')."\n";
echo 'APP_ENV: '.config('app.env')."\n\n";

// ============================================
// TEST 2: Session Configuration
// ============================================
echo "TEST 2: Session Configuration\n";
echo str_repeat('-', 40)."\n";
echo 'Driver: '.config('session.driver')."\n";
$secure = config('session.secure');
echo 'Secure: '.($secure === true ? 'true' : ($secure === false ? 'false' : 'null'))."\n";
echo 'Cookie: '.config('session.cookie')."\n";
echo 'Domain: '.(config('session.domain') ?? 'null')."\n";
echo 'Same Site: '.config('session.same_site')."\n";
echo 'HTTP Only: '.(config('session.http_only') ? 'true' : 'false')."\n";
echo 'Table: '.config('session.table', 'sessions')."\n";
echo 'Lifetime: '.config('session.lifetime')." minutes\n\n";

// ============================================
// TEST 3: Find a test user
// ============================================
echo "TEST 3: Test User\n";
echo str_repeat('-', 40)."\n";

try {
    $user = User::first();
    if ($user) {
        echo "[OK] User found:\n";
        echo '  ID: '.$user->id."\n";
        echo '  Email: '.$user->email."\n";
    } else {
        echo "[WARN] No users found in database\n";
    }
} catch (Exception $e) {
    echo '[ERROR] '.$e->getMessage()."\n";
}
echo "\n";

// ============================================
// TEST 4: Start Session
// ============================================
echo "TEST 4: Session Start\n";
echo str_repeat('-', 40)."\n";

try {
    Session::start();
    $sessionId = Session::getId();
    echo "[OK] Session started\n";
    echo "Session ID: $sessionId\n\n";
} catch (Exception $e) {
    echo '[ERROR] '.$e->getMessage()."\n\n";
    $sessionId = null;
}

// ============================================
// TEST 5: Simulate Login - Set Auth Data
// ============================================
echo "TEST 5: Set Auth Session Data\n";
echo str_repeat('-', 40)."\n";

try {
    if ($sessionId) {
        // Clear any existing session data
        Session::flush();

        // Set Laravel's standard auth session data
        Session::put('_token', Str::random(40));
        Session::put('_flash', ['old' => [], 'new' => []]);
        Session::put('auth.password_confirmed_at', time());

        if (isset($user)) {
            Session::put('auth.single', ['id' => $user->id]);
            Session::put('login_web_'.md5(config('app.url', 'laravel')), $user->id);
        }

        echo "[OK] Auth session data set:\n";
        echo '  Has _token: '.(Session::has('_token') ? 'YES' : 'NO')."\n";
        echo '  Has _flash: '.(Session::has('_flash') ? 'YES' : 'NO')."\n";
        echo '  Has password_confirmed_at: '.(Session::has('auth.password_confirmed_at') ? 'YES' : 'NO')."\n";
        echo '  Has auth.single: '.(Session::has('auth.single') ? 'YES' : 'NO')."\n";
    }
} catch (Exception $e) {
    echo '[ERROR] '.$e->getMessage()."\n";
}
echo "\n";

// ============================================
// TEST 6: Save Session to Database
// ============================================
echo "TEST 6: Save Session to Database\n";
echo str_repeat('-', 40)."\n";

try {
    if ($sessionId) {
        Session::save();
        echo "[OK] Session saved\n";
        echo 'Session ID after save: '.Session::getId()."\n";

        // Check if session was saved to database
        $tableName = config('session.table', 'sessions');
        $savedSession = DB::table($tableName)->where('id', $sessionId)->first();

        if ($savedSession) {
            echo "[OK] Session found in database!\n";
            echo '  Payload length: '.strlen($savedSession->payload ?? '')."\n";
            echo '  Last activity: '.date('Y-m-d H:i:s', $savedSession->last_activity)."\n";
        } else {
            echo "[ERROR] Session NOT found in database!\n";
        }

        $totalSessions = DB::table($tableName)->count();
        echo "  Total sessions in DB: $totalSessions\n";
    }
} catch (Exception $e) {
    echo '[ERROR] '.$e->getMessage()."\n";
    echo $e->getTraceAsString()."\n";
}
echo "\n";

// ============================================
// TEST 7: Cookie Settings Check
// ============================================
echo "TEST 7: Cookie Security Check\n";
echo str_repeat('-', 40)."\n";

$siteUsesHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
$secureConfigured = config('session.secure') === true;

if ($siteUsesHttps && ! $secureConfigured) {
    echo "⚠️  WARNING: Site uses HTTPS but SESSION_SECURE_COOKIE is not true!\n";
    echo "    This WILL cause login loops!\n";
    echo "    Fix: Set SESSION_SECURE_COOKIE=true in .env\n";
} elseif ($siteUsesHttps && $secureConfigured) {
    echo "✅ OK: SESSION_SECURE_COOKIE is set correctly for HTTPS\n";
} else {
    echo "ℹ️  Site does not appear to use HTTPS\n";
}
echo "\n";

// ============================================
// Summary
// ============================================
echo str_repeat('=', 40)."\n";
echo "SUMMARY\n";
echo str_repeat('-', 40)."\n";

$issues = [];

if ($siteUsesHttps && ! $secureConfigured) {
    $issues[] = 'SESSION_SECURE_COOKIE must be true on HTTPS';
}

if (! $sessionId) {
    $issues[] = 'Session failed to start';
}

if (empty($issues)) {
    echo "✅ All tests passed! Login should work.\n";
    echo "\nTry logging in at: https://kasira.id/admin/login\n";
} else {
    echo "⚠️  Issues found:\n";
    foreach ($issues as $issue) {
        echo "  - $issue\n";
    }
    echo "\nRun https://kasira.id/fix-login.php to fix these issues!\n";
}
