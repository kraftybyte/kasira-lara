<?php

/**
 * Standalone Session Test - No Laravel dependencies
 * Tests if PHP sessions work correctly on Hostinger
 */
$results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION,
    'tests' => [],
];

// Test 1: Basic session start
$results['tests']['session_start'] = [
    'attempted' => true,
];

// Try to start session
$sessionStarted = false;
$sessionError = null;

try {
    // Set session save path explicitly
    $sessionPath = sys_get_temp_dir();
    session_save_path($sessionPath);

    // Configure session
    ini_set('session.save_handler', 'files');
    ini_set('session.save_path', $sessionPath);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);

    // Start session
    session_start();
    $sessionStarted = true;

    $results['tests']['session_start']['success'] = true;
    $results['tests']['session_start']['session_id'] = session_id();
    $results['tests']['session_start']['save_path'] = $sessionPath;

} catch (Exception $e) {
    $results['tests']['session_start']['success'] = false;
    $results['tests']['session_start']['error'] = $e->getMessage();
}

// Test 2: Set and read session data
if ($sessionStarted) {
    $results['tests']['session_data'] = [];

    // Set test data
    $_SESSION['test_value'] = 'hello_world';
    $_SESSION['user_id'] = 123;
    $_SESSION['auth_check'] = ['id' => 123, 'email' => 'test@example.com'];

    $results['tests']['session_data']['set'] = [
        'test_value' => $_SESSION['test_value'],
        'user_id' => $_SESSION['user_id'],
        'auth_check' => $_SESSION['auth_check'],
    ];

    // Write and close session
    session_write_close();

    // Start new session with same ID
    session_id(session_id());
    session_start();

    $results['tests']['session_data']['read'] = [
        'test_value' => $_SESSION['test_value'] ?? 'NOT_FOUND',
        'user_id' => $_SESSION['user_id'] ?? 'NOT_FOUND',
        'auth_check' => $_SESSION['auth_check'] ?? 'NOT_FOUND',
    ];

    // Check if data persisted
    $results['tests']['session_data']['persisted'] =
        ($_SESSION['test_value'] ?? null) === 'hello_world' &&
        ($_SESSION['user_id'] ?? null) === 123;
}

// Test 3: Cookie settings
$results['tests']['cookies'] = [
    'session_name' => session_name(),
    'session_cache_limiter' => session_cache_limiter(),
    'current_cookies' => $_COOKIE,
];

// Test 4: Check session file existence
if ($sessionStarted) {
    $sid = session_id();
    $sessionFile = $sessionPath.'/sess_'.$sid;
    $results['tests']['session_file'] = [
        'session_id' => $sid,
        'expected_path' => $sessionFile,
        'exists' => file_exists($sessionFile),
        'readable' => is_readable($sessionFile),
    ];

    if (file_exists($sessionFile)) {
        $content = file_get_contents($sessionFile);
        $results['tests']['session_file']['content_preview'] = substr($content, 0, 200);
        $results['tests']['session_file']['content_length'] = strlen($content);
    }
}

// Test 5: Environment info
$results['tests']['environment'] = [
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown',
    'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'unknown',
    'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown',
    'https' => ($_SERVER['HTTPS'] ?? '') === 'on',
    'request_scheme' => $_SERVER['REQUEST_SCHEME'] ?? 'unknown',
];

// Test 6: Try to check database connection if available
$results['tests']['database_check'] = 'not_tested';

// Output results
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
echo json_encode($results, JSON_PRETTY_PRINT);
