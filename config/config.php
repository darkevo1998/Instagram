<?php
// Database configuration (if needed)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'instagram_app');

// Instagram API Configuration
define('INSTAGRAM_APP_ID', '1429645661731728');
define('INSTAGRAM_APP_SECRET', '439309216533436792b63eaae35cdbe7');
define('INSTAGRAM_REDIRECT_URI', 'http://localhost/instagram-api-integration/auth.php');

// Base configuration
define('APP_BASE_PATH', '');
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