<?php

/**
 * Simple Laravel Bootstrap Test
 */
$results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION,
    'tests' => [],
];

// Get paths
$basePath = dirname(__DIR__);
$results['paths'] = [
    'base' => $basePath,
    'autoload' => $basePath.'/vendor/autoload.php',
    'bootstrap' => $basePath.'/bootstrap/app.php',
];

// Test 1: Check if files exist
$results['tests']['files_exist'] = [
    'autoload' => file_exists($basePath.'/vendor/autoload.php'),
    'bootstrap' => file_exists($basePath.'/bootstrap/app.php'),
    'env' => file_exists($basePath.'/.env'),
];

// Test 2: Load autoloader
try {
    require_once $basePath.'/vendor/autoload.php';
    $results['tests']['autoload'] = ['success' => true];
} catch (Exception $e) {
    $results['tests']['autoload'] = [
        'success' => false,
        'error' => $e->getMessage(),
    ];
}

// Test 3: Create application
try {
    $app = require_once $basePath.'/bootstrap/app.php';
    $results['tests']['bootstrap'] = [
        'success' => true,
        'app_class' => get_class($app),
    ];
} catch (Exception $e) {
    $results['tests']['bootstrap'] = [
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ];
}

// Output
header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT);
