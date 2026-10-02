<?php
/**
 * Deploy Extract Script for Kasira Laravel
 *
 * Run via browser: https://kasira.id/deploy-extract.php
 *
 * This script extracts deploy.zip and clears Laravel caches.
 * It will NOT overwrite the existing .env file.
 */

echo "=== Kasira Laravel Deploy Extract ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

$zipFile = __DIR__ . '/deploy.zip';
$extractTo = __DIR__;

// Check if zip file exists
if (!file_exists($zipFile)) {
    echo "ERROR: deploy.zip not found\n";
    echo "Please upload deploy.zip first.\n";
    exit(1);
}

// Open the zip file
$zip = new ZipArchive();
$result = $zip->open($zipFile);

if ($result !== TRUE) {
    echo "ERROR: Could not open zip file (error code: $result)\n";
    exit(1);
}

echo "Zip file opened successfully.\n";
echo "Files in archive: " . $zip->numFiles . "\n";
echo "Extracting to: $extractTo\n\n";

$extracted = 0;
$skipped = 0;
$errors = 0;

// Extract all files
for ($i = 0; $i < $zip->numFiles; $i++) {
    $stat = $zip->statIndex($i);
    $filename = $stat['name'];

    // Skip .env files (keep server version)
    if (preg_match('/^\.env/', $filename)) {
        $skipped++;
        continue;
    }

    // Skip backup files
    if (strpos($filename, '.backup_') !== false || strpos($filename, '.bak') !== false) {
        $skipped++;
        continue;
    }

    // Create directory if needed
    $targetPath = $extractTo . '/' . $filename;
    $dir = dirname($targetPath);

    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    // Skip directories
    if (substr($filename, -1) === '/') {
        continue;
    }

    // Extract the file
    if ($zip->extractTo($extractTo, $filename)) {
        $extracted++;
        if ($extracted <= 20) {
            echo "EXTRACTED: $filename\n";
        } elseif ($extracted % 200 === 0) {
            echo "  ... $extracted files extracted\n";
        }
    } else {
        $errors++;
        echo "ERROR extracting: $filename\n";
    }
}

$zip->close();

echo "\n=== Extraction Complete ===\n";
echo "Extracted: $extracted files\n";
echo "Skipped: $skipped (.env and backups)\n";
echo "Errors: $errors\n";

// Delete the zip file after successful extraction
if ($errors === 0 && file_exists($zipFile)) {
    unlink($zipFile);
    echo "\nDeleted deploy.zip after successful extraction.\n";
}

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
        $status = $returnCode === 0 ? '[OK]' : '[ERROR]';
        echo "$status php artisan $command\n";
        if ($returnCode !== 0 && !empty($output)) {
            foreach (array_slice($output, 0, 3) as $line) {
                echo "   $line\n";
            }
        }
    }

    // Regenerate optimized files
    echo "\n=== Regenerating Optimized Files ===\n";
    $output = [];
    exec("php $artisan optimize 2>&1", $output, $returnCode);
    echo ($returnCode === 0 ? '[OK]' : '[ERROR]') . " php artisan optimize\n";
} else {
    echo "[WARNING] artisan not found, skipping cache clear.\n";
}

echo "\n=== Deploy Complete ===\n";
echo "Please test your application at https://kasira.id\n";
