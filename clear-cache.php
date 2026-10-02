<?php

/**
 * Cache Clearing Script for Laravel
 *
 * Upload ke root project dan akses via browser:
 * https://kasira.id/clear-cache.php?secret=YOUR_SECRET_KEY
 *
 * Ganti YOUR_SECRET_KEY dengan random string yang aman
 */
$secret = 'kasira-clear-2024'; // GANTI INI DENGAN SECRET KEY ANDA

// Simple security check
if (! isset($_GET['secret']) || $_GET['secret'] !== $secret) {
    http_response_code(403);
    exit('Access denied. Provide correct secret key.');
}

// Set headers
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store, no-cache');

echo "Laravel Cache Clearing Script\n";
echo "=============================\n\n";

$root = __DIR__;
$artisan = $root.'/artisan';

// Helper function
function runCommand($desc, $cmd)
{
    echo "[$desc]\n";
    echo "Command: $cmd\n";
    $output = [];
    $returnCode = 0;
    exec($cmd.' 2>&1', $output, $returnCode);
    echo implode("\n", $output)."\n";
    echo "Return code: $returnCode\n";
    echo $returnCode === 0 ? "✓ Success\n" : "✗ Failed\n";
    echo "\n";

    return $returnCode === 0;
}

$results = [];

echo "Root: $root\n";
echo "Artisan: $artisan\n\n";

// Clear caches
$results['config:clear'] = runCommand('Config Clear', "php $artisan config:clear");
$results['route:clear'] = runCommand('Route Clear', "php $artisan route:clear");
$results['view:clear'] = runCommand('View Clear', "php $artisan view:clear");
$results['cache:clear'] = runCommand('Cache Clear', "php $artisan cache:clear");
$results['clear-compiled'] = runCommand('Clear Compiled', "php $artisan clear-compiled");
$results['optimize:clear'] = runCommand('Optimize Clear', "php $artisan optimize:clear");

// Set permissions
echo "[Permissions]\n";
$dirs = [
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'bootstrap/cache',
];
foreach ($dirs as $dir) {
    $path = $root.'/'.$dir;
    if (is_dir($path)) {
        chmod($path, 0755);
        echo "chmod 0755 $dir\n";
    }
}
echo "✓ Permissions set\n\n";

echo "=============================\n";
echo "Cache clearing complete!\n\n";

$success = array_filter($results);
if (count($success) === count($results)) {
    echo "✓ All caches cleared successfully!\n";
} else {
    $failed = array_keys(array_filter($results, fn ($r) => ! $r));
    echo '⚠ Some commands failed: '.implode(', ', $failed)."\n";
}

echo "\nNext steps:\n";
echo "1. Refresh your browser (Ctrl+Shift+R or Cmd+Shift+R)\n";
echo "2. Clear browser cookies for kasira.id\n";
echo "3. Try logging in again\n";
echo "\n";
echo "Delete this file after use for security!\n";
