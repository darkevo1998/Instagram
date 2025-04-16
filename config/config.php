<?php
// Database configuration (if needed)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'instagram_app');

// Instagram API Configuration
define('INSTAGRAM_APP_ID', '9517033638379055');
define('INSTAGRAM_APP_SECRET', '12245f7cc4e0e9cf00102f5a567c5c32');
define('INSTAGRAM_REDIRECT_URI', 'http://localhost/instagram-testing/auth.php');

define('FACEBOOK_APP_ID', '653499260727590');
define('FACEBOOK_APP_SECRET', 'd6cef6b30f0072398c6153f21120b037');

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