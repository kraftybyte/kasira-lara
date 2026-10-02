<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Str;

/**
 * Laravel Database Session Test
 * Tests if Laravel's database session driver works correctly
 */
define('LARAVEL_START', microtime(true));

// Get the base path
$basePath = dirname(__DIR__);

// Load Composer autoloader
$autoloadPath = $basePath.'/vendor/autoload.php';
if (! file_exists($autoloadPath)) {
    exit(json_encode([
        'error' => 'Vendor autoload not found at: '.$autoloadPath,
        'base_path' => $basePath,
    ], JSON_PRETTY_PRINT));
}

require_once $autoloadPath;

// Bootstrap Laravel
$app = require_once $basePath.'/bootstrap/app.php';

// Get the kernel and bootstrap
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION,
    'laravel_version' => app()->version(),
    'tests' => [],
];

// Test 1: Session configuration
$results['tests']['session_config'] = [
    'driver' => config('session.driver'),
    'lifetime' => config('session.lifetime'),
    'encrypt' => config('session.encrypt'),
    'cookie' => config('session.cookie'),
    'path' => config('session.path'),
    'domain' => config('session.domain'),
    'secure' => config('session.secure'),
    'same_site' => config('session.same_site'),
    'http_only' => config('session.http_only'),
    'table' => config('session.table'),
    'connection' => config('session.connection'),
    'serialization' => config('session.serialization', 'json'),
];

// Test 2: Session driver initialization
try {
    $session = app('session');
    $driverName = $session->getDefaultDriver();
    $results['tests']['session_driver'] = [
        'default_driver' => $driverName,
        'driver_set' => true,
    ];
} catch (Exception $e) {
    $results['tests']['session_driver'] = [
        'error' => $e->getMessage(),
        'driver_set' => false,
    ];
}

// Test 3: Start session
try {
    $sessionId = session()->getId();
    $results['tests']['session_start'] = [
        'success' => true,
        'session_id' => $sessionId,
    ];
} catch (Exception $e) {
    $results['tests']['session_start'] = [
        'success' => false,
        'error' => $e->getMessage(),
    ];
}

// Test 4: Set auth-like session data
try {
    // Simulate what Laravel's auth does
    $userId = 1;

    session(['_token' => Str::random(40)]);
    session(['_flash' => ['old' => [], 'new' => []]]);
    session(['auth.password_confirmed_at' => time()]);
    session(['auth.single' => ['id' => $userId, 'remember' => false]]);

    $results['tests']['session_data_set'] = [
        'success' => true,
        'has_token' => session()->has('_token'),
        'has_flash' => session()->has('_flash'),
        'has_password_confirmed' => session()->has('auth.password_confirmed_at'),
        'has_auth' => session()->has('auth.single'),
        'auth_data' => session('auth.single'),
    ];
} catch (Exception $e) {
    $results['tests']['session_data_set'] = [
        'success' => false,
        'error' => $e->getMessage(),
    ];
}

// Test 5: Serialize and check session
try {
    $serialized = session()->serialize();
    $results['tests']['session_serialize'] = [
        'success' => true,
        'length' => strlen($serialized),
        'preview' => substr($serialized, 0, 300),
    ];
} catch (Exception $e) {
    $results['tests']['session_serialize'] = [
        'success' => false,
        'error' => $e->getMessage(),
    ];
}

// Test 6: Save session
try {
    session()->save();
    $savedSessionId = session()->getId();
    $results['tests']['session_save'] = [
        'success' => true,
        'session_id' => $savedSessionId,
    ];
} catch (Exception $e) {
    $results['tests']['session_save'] = [
        'success' => false,
        'error' => $e->getMessage(),
    ];
}

// Test 7: Database session table check
try {
    $tableName = config('session.table', 'sessions');
    $connection = config('session.connection');

    // Get connection info
    $dbConnection = DB::connection($connection);
    $driver = $dbConnection->getDriverName();

    $results['tests']['database'] = [
        'connection' => $connection ?? 'default',
        'driver' => $driver,
        'database' => $dbConnection->getDatabaseName(),
        'table_name' => $tableName,
    ];

    // Check if table exists and has records
    $count = DB::connection($connection)->table($tableName)->count();
    $results['tests']['database']['session_count'] = $count;

    // Try to find our session
    if (! empty($savedSessionId)) {
        $sessionRecord = DB::connection($connection)->table($tableName)
            ->where('id', $savedSessionId)
            ->first();

        if ($sessionRecord) {
            $results['tests']['database']['our_session'] = [
                'found' => true,
                'last_activity' => $sessionRecord->last_activity,
                'payload_length' => strlen($sessionRecord->payload ?? ''),
                'user_id_in_payload' => strpos($sessionRecord->payload ?? '', '"id";i:'.$userId) !== false,
            ];
        } else {
            $results['tests']['database']['our_session'] = ['found' => false];
        }
    }

} catch (Exception $e) {
    $results['tests']['database'] = [
        'error' => $e->getMessage(),
        'error_class' => get_class($e),
    ];
}

// Test 8: Check cookies
$results['tests']['cookies'] = [
    'session_cookie' => config('session.cookie'),
    'current_cookies' => $_COOKIE,
];

// Output
header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT);
