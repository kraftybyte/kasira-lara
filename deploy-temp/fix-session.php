#!/usr/bin/env php
<?php

/**
 * Fix session configuration for Hostinger (LiteSpeed) hosting
 *
 * Run this from public_html directory:
 *   php fix-session.php
 */
echo "=== Fixing Session Configuration for Hostinger ===\n\n";

$envFile = __DIR__.'/.env';

if (! file_exists($envFile)) {
    echo 'ERROR: .env file not found in '.__DIR__."\n";
    exit(1);
}

$content = file_get_contents($envFile);
$originalContent = $content;

// Fix SESSION_DOMAIN
$content = fixEnvVariable($content, 'SESSION_DOMAIN', 'kasira.id');
$content = fixEnvVariable($content, 'SESSION_SECURE_COOKIE', 'false');
$content = fixEnvVariable($content, 'SESSION_DRIVER', 'file');
$content = fixEnvVariable($content, 'SESSION_PATH', '/');

if ($content !== $originalContent) {
    file_put_contents($envFile, $content);
    echo "SUCCESS: .env updated with session fixes\n";
    echo "\nUpdated settings:\n";
    echo "  SESSION_DOMAIN=kasira.id\n";
    echo "  SESSION_SECURE_COOKIE=false\n";
    echo "  SESSION_DRIVER=file\n";
    echo "  SESSION_PATH=/\n";
} else {
    echo "No changes needed - settings already correct\n";
}

echo "\nNow run:\n";
echo "  php artisan config:clear\n";
echo "  php artisan optimize:clear\n";
echo "  php artisan cache:clear\n";
echo "\n=== Done ===\n";

/**
 * Fix or add an ENV variable in .env file
 */
function fixEnvVariable(string $content, string $key, string $value): string
{
    $pattern = "/^{$key}=.*$/m";
    $replacement = "{$key}={$value}";

    if (preg_match($pattern, $content)) {
        return preg_replace($pattern, $replacement, $content);
    } else {
        // Add new variable
        return $content."\n{$replacement}";
    }
}
