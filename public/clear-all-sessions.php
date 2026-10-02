<?php
// Clear all sessions - run this to ensure fresh login
// Access: https://kasira.id/clear-all-sessions.php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CLEAR ALL SESSIONS ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

try {
    $count = \DB::table('sessions')->count();
    echo "Sessions found: $count\n";

    if ($count > 0) {
        \DB::table('sessions')->truncate();
        echo "All sessions cleared successfully!\n";
    } else {
        echo "No sessions to clear.\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== DONE ===\n";
echo "Please clear your browser cookies and try logging in again.\n";
