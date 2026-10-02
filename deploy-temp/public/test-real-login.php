<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Models\User;

echo "=== REAL LOGIN TEST ===\n\n";

// Check config
echo "Config:\n";
echo "  SESSION_DRIVER: " . config('session.driver') . "\n";
echo "  SESSION_SECURE: " . (config('session.secure') ? 'true' : 'false') . "\n";
echo "  APP_ENV: " . config('app.env') . "\n";
echo "  APP_KEY: " . (config('app.key') ? 'SET' : 'NOT SET') . "\n\n";

// Try to login
$user = User::first();
echo "User found: " . ($user ? $user->email : 'NO') . "\n";
echo "User ID: " . ($user ? $user->id : 'N/A') . "\n";
echo "Has super_admin role: " . ($user && $user->hasRole('super_admin') ? 'YES' : 'NO') . "\n\n";

// Check tenants
$tenants = \App\Models\Tenant::all();
echo "Total tenants: " . $tenants->count() . "\n";
foreach ($tenants as $tenant) {
    $canAccess = $user ? $user->canAccessTenant($tenant) : false;
    echo "  - {$tenant->slug} (ID:{$tenant->id}) - User can access: " . ($canAccess ? 'YES' : 'NO') . "\n";
}
echo "\n";

// Simulate login manually
echo "Simulating login...\n";
Auth::login($user);
echo "Auth::check(): " . (Auth::check() ? 'YES' : 'NO') . "\n";
echo "Auth::id(): " . (Auth::id() ?? 'NULL') . "\n";

// Get Filament auth
$panel = \Filament\Facades\Filament::getCurrentOrDefaultPanel();
$filamentUser = $panel->auth()->user();
echo "Filament user: " . ($filamentUser ? $filamentUser->email : 'NULL') . "\n";

// Check canAccessPanel
if ($filamentUser) {
    $canAccessPanel = $filamentUser->canAccessPanel($panel);
    echo "canAccessPanel: " . ($canAccessPanel ? 'YES' : 'NO') . "\n";
}

// Start session and check
Session::start();
$sessionId = Session::getId();
echo "\nSession ID: $sessionId\n";
echo "Session has user: " . (Session::has('login_web_59ba36addc2b2f940158f014504963b711ad89cd') ? 'YES' : 'NO') . "\n";
echo "Session has Filament auth: " . (Session::has('_filament_auth') ? 'YES' : 'NO') . "\n";

// Save session
Session::save();

// Check database
$dbSession = DB::table('sessions')->where('id', $sessionId)->first();
if ($dbSession) {
    echo "\nSession saved to database!\n";
    echo "User ID in DB: " . ($dbSession->user_id ?? 'NULL') . "\n";

    // Decode payload
    $payload = json_decode(base64_decode($dbSession->payload), true);
    if ($payload) {
        echo "Payload keys: " . implode(', ', array_keys($payload)) . "\n";
    }
} else {
    echo "\nSession NOT saved to database!\n";
}

echo "\n=== END TEST ===\n";
