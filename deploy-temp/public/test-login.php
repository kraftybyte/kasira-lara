<?php

use Illuminate\Contracts\Http\Kernel;

/**
 * Laravel Login Session Test for Hostinger
 *
 * This script tests if Laravel sessions can be saved to the database
 * and if user authentication state persists correctly.
 */

// Define base path
$basePath = __DIR__.'/../';

// Load Laravel bootstrap if available
$bootstrapPath = $basePath.'bootstrap/app.php';
$vendorPath = $basePath.'vendor/autoload.php';

$results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'tests' => [],
];

if (file_exists($bootstrapPath) && file_exists($vendorPath)) {
    try {
        require_once $vendorPath;
        $app = require_once $bootstrapPath;

        $results['laravel_loaded'] = true;
        $results['bootstrap_path'] = $bootstrapPath;

        // Get session config
        $sessionConfig = config('session');
        $results['tests']['session_config'] = [
            'driver' => $sessionConfig['driver'] ?? 'unknown',
            'lifetime' => $sessionConfig['lifetime'] ?? 'unknown',
            'expire_on_close' => $sessionConfig['expire_on_close'] ?? 'unknown',
            'encrypt' => $sessionConfig['encrypt'] ?? 'unknown',
            'cookie' => $sessionConfig['cookie'] ?? 'unknown',
            'path' => $sessionConfig['path'] ?? 'unknown',
            'domain' => $sessionConfig['domain'] ?? 'unknown',
            'secure' => $sessionConfig['secure'] ?? 'unknown',
            'same_site' => $sessionConfig['same_site'] ?? 'unknown',
        ];

        // Try to start session and set user data
        $kernel = $app->make(Kernel::class);

        // Bootstrap the application
        $kernel->bootstrap();

        // Start session
        $session = app('session');
        $driver = $session->getDefaultDriver();

        $results['tests']['session_driver'] = $driver;

        // Set simulated user data as Laravel would after login
        $simulatedUserId = 1;
        $simulatedUserData = [
            'id' => $simulatedUserId,
            'email' => 'test@example.com',
            'name' => 'Test User',
        ];

        // Set session data like login typically does
        session(['_token' => Str::random(40)]);
        session(['_flash' => ['old' => [], 'new' => []]]);
        session(['auth.password_confirmed_at' => time()]);

        // Set the typical Laravel session structure for web guard
        $results['tests']['session_before'] = [
            'has_user_id' => session()->has('login_web_'.sha1('Kasira')),
            'session_id' => session()->getId(),
        ];

        // Simulate login as Laravel's SessionGuard does
        $results['tests']['simulating_login'] = true;
        session(['auth' => ['defaults' => ['guard' => 'web', 'provider' => null]]]);
        session(['auth.single' => ['id' => $simulatedUserId, 'remember' => false]]);
        session(['auth.password_confirmed_at' => time()]);

        $results['tests']['session_after'] = [
            'auth_data' => session('auth'),
            'password_confirmed' => session('auth.password_confirmed_at'),
            'session_id' => session()->getId(),
        ];

        // Try to save session
        $session->save();

        $results['tests']['session_saved'] = true;
        $results['tests']['session_id_after_save'] = session()->getId();

        // Check if sessions table exists and try to retrieve
        try {
            $sessionsTable = config('session.table', 'sessions');
            $connection = config('session.connection');

            // Try direct query if sessions table exists
            $pdo = DB::connection($connection)->getPdo();

            $results['tests']['sessions_table'] = $sessionsTable;
            $results['tests']['database_connected'] = true;

            // Try to insert a test session
            $sessionId = session()->getId();
            $payload = session()->serialize();

            $results['tests']['session_insert_test'] = [
                'session_id' => $sessionId,
                'payload_length' => strlen($payload),
                'payload_preview' => substr($payload, 0, 200),
            ];

            // Check if session already exists
            $existing = DB::connection($connection)->table($sessionsTable)
                ->where('id', $sessionId)
                ->first();

            if ($existing) {
                $results['tests']['existing_session'] = [
                    'found' => true,
                    'last_activity' => $existing->last_activity,
                    'payload_length' => strlen($existing->payload ?? ''),
                ];
            } else {
                $results['tests']['existing_session'] = ['found' => false];
            }

        } catch (Exception $e) {
            $results['tests']['database_error'] = [
                'message' => $e->getMessage(),
                'class' => get_class($e),
            ];
        }

        // Check cookies
        $results['tests']['cookies'] = [
            'session_cookie_name' => config('session.cookie'),
            'current_cookies' => $_COOKIE,
        ];

    } catch (Exception $e) {
        $results['error'] = [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => array_slice($e->getTrace(), 0, 5),
        ];
    }
} else {
    $results['laravel_loaded'] = false;
    $results['error'] = 'Laravel bootstrap files not found';
    $results['checked_paths'] = [
        'bootstrap' => $bootstrapPath,
        'vendor' => $vendorPath,
    ];
}

// Output results
header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT);
