<?php

use Illuminate\Contracts\Http\Kernel;

/**
 * Laravel Session Config Test - Direct check without triggering handlers
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $basePath = dirname(__DIR__);
    require_once $basePath.'/vendor/autoload.php';

    $app = require_once $basePath.'/bootstrap/app.php';
    $kernel = $app->make(Kernel::class);
    $kernel->bootstrap();

    $results = [
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION,
        'laravel_version' => app()->version(),
        'tests' => [],
    ];

    // Test 1: Session configuration (read directly, don't trigger handlers)
    $results['tests']['session_config'] = [
        'driver' => config('session.driver'),
        'lifetime' => config('session.lifetime'),
        'encrypt' => config('session.encrypt'),
        'cookie' => config('session.cookie'),
        'path' => config('session.path'),
        'domain' => config('session.domain') ?? 'null',
        'secure' => config('session.secure') ?? 'null',
        'same_site' => config('session.same_site'),
        'http_only' => config('session.http_only'),
        'table' => config('session.table'),
        'connection' => config('session.connection') ?? 'null',
        'serialization' => config('session.serialization', 'json'),
    ];

    // Test 2: Database config
    $results['tests']['database_config'] = [
        'default' => config('database.default'),
        'host' => config('database.connections.mysql.host') ?? 'not_mysql',
        'port' => config('database.connections.mysql.port') ?? 'not_mysql',
        'database' => config('database.connections.mysql.database') ?? 'not_mysql',
    ];

    // Test 3: Database connection
    try {
        DB::connection()->getPdo();
        $results['tests']['database_connection'] = [
            'success' => true,
            'driver' => DB::connection()->getDriverName(),
        ];
    } catch (Exception $e) {
        $results['tests']['database_connection'] = [
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }

    // Test 4: Sessions table
    try {
        $tableName = config('session.table');
        $count = DB::connection()->table($tableName)->count();
        $results['tests']['sessions_table'] = [
            'exists' => true,
            'record_count' => $count,
        ];
    } catch (Exception $e) {
        $results['tests']['sessions_table'] = [
            'exists' => false,
            'error' => $e->getMessage(),
        ];
    }

    // Test 5: Check APP_KEY
    $results['tests']['app_key'] = [
        'exists' => ! empty(config('app.key')),
        'prefix' => substr(config('app.key'), 0, 7) ?? 'missing',
    ];

    // Test 6: Environment
    $results['tests']['environment'] = [
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
        'https' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'request_scheme' => $_SERVER['REQUEST_SCHEME'] ?? 'unknown',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown',
    ];

    // Test 7: Cookie info
    $results['tests']['cookies'] = [
        'session_cookie' => config('session.cookie'),
        'current_cookies' => array_keys($_COOKIE),
    ];

    // Test 8: Direct file-based session test (avoids database handler)
    // This tests if PHP sessions work in general
    $results['tests']['file_session_test'] = [];

    // Set a custom session path
    $testPath = sys_get_temp_dir();
    ini_set('session.save_path', $testPath);
    ini_set('session.use_cookies', '1');
    ini_set('session.cookie_httponly', '1');

    if (! session_start()) {
        $results['tests']['file_session_test'] = ['started' => false];
    } else {
        $_SESSION['test'] = 'works';
        $sid = session_id();
        session_write_close();

        $results['tests']['file_session_test'] = [
            'started' => true,
            'session_id' => $sid,
            'saved' => true,
        ];

        // Check if session file exists
        $sessionFile = $testPath.'/sess_'.$sid;
        $results['tests']['file_session_test']['file_exists'] = file_exists($sessionFile);
    }

    header('Content-Type: application/json');
    echo json_encode($results, JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => array_slice($e->getTrace(), 0, 10),
    ], JSON_PRETTY_PRINT);
}
