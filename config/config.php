<?php
// Database configuration (if needed)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'instagram_app');

// Instagram API Configuration
define('INSTAGRAM_APP_ID', '1429645661731728');
define('INSTAGRAM_APP_SECRET', '439309216533436792b63eaae35cdbe7');
define('INSTAGRAM_REDIRECT_URI', 'http://localhost:8000/auth.php');

define('FACEBOOK_APP_ID', '566178249247692');
define('FACEBOOK_APP_SECRET', '32e43ac60d19e3fb2d93d055f6a30989');

// Base configuration
define('APP_BASE_PATH', '/instagram-testing');
define('APP_ENV', 'development'); // or 'production'

// Session configuration
session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => APP_ENV === 'production',
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>