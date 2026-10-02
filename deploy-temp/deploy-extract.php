<?php
/**
 * Deploy Extract Script
 * Run this via browser: https://kasira.id/deploy-extract.php
 * Or via CLI: php deploy-extract.php
 *
 * WARNING: This script will overwrite existing files!
 */

$zipFile = __DIR__ . '/deploy.zip';
$extractTo = __DIR__;

echo "=== Kasira Laravel Deploy Extract ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

if (!file_exists($zipFile)) {
    echo "ERROR: deploy.zip not found in " . __DIR__ . "\n";
    echo "Please upload deploy.zip first.\n";
    exit(1);
}

$zip = new ZipArchive();
$result = $zip->open($zipFile);

if ($result !== TRUE) {
    echo "ERROR: Could not open zip file (error code: $result)\n";
    exit(1);
}

echo "Zip file opened successfully.\n";
echo "Files in archive: " . $zip->numFiles . "\n";
echo "Extracting to: $extractTo\n\n";

// Extract all files
$extracted = 0;
$errors = 0;

for ($i = 0; $i < $zip->numFiles; $i++) {
    $stat = $zip->statIndex($i);
    $filename = $stat['name'];

    // Skip .env files (keep server version)
    if (preg_match('/^\.env/', $filename)) {
        echo "SKIP (keeping server version): $filename\n";
        continue;
    }

    // Skip backup files
    if (strpos($filename, '.backup_') !== false || strpos($filename, '.bak') !== false) {
        echo "SKIP (backup file): $filename\n";
        continue;
    }

    // Extract the file
    if ($zip->extractTo($extractTo, $filename)) {
        $extracted++;
        if ($extracted <= 50 || $extracted % 500 === 0) {
            echo "EXTRACTED: $filename\n";
        }
    } else {
        $errors++;
        echo "ERROR extracting: $filename\n";
    }
}

$zip->close();

echo "\n=== Extraction Complete ===\n";
echo "Extracted: $extracted files\n";
echo "Errors: $errors\n";
echo "Skipped: .env files and backups\n\n";

// Delete the zip file after successful extraction
if ($errors === 0 && file_exists($zipFile)) {
    unlink($zipFile);
    echo "Deleted deploy.zip after successful extraction.\n";
}

// Clear Laravel caches
echo "\n=== Clearing Laravel Caches ===\n";
$artisan = __DIR__ . '/artisan';

if (file_exists($artisan)) {
    $commands = [
        'config:clear',
        'cache:clear',
        'route:clear',
        'view:clear',
        'optimize:clear',
        'event:clear',
    ];

    foreach ($commands as $command) {
        $output = [];
        $returnCode = 0;
        exec("php $artisan $command 2>&1", $output, $returnCode);
        $status = $returnCode === 0 ? 'OK' : 'ERROR';
        echo "[$status] php artisan $command\n";
        if ($returnCode !== 0 && !empty($output)) {
            foreach ($output as $line) {
                echo "   $line\n";
            }
        }
    }

    // Regenerate optimized files
    echo "\n=== Regenerating Optimized Files ===\n";
    exec("php $artisan optimize 2>&1", $output, $returnCode);
    echo "[OK] php artisan optimize\n";
} else {
    echo "WARNING: artisan not found, skipping cache clear.\n";
}

echo "\n=== Deploy Complete ===\n";
echo "Please test your application at https://kasira.id\n";
