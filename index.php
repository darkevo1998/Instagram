<?php
// config.php should define APP_BASE_PATH like:
// define('APP_BASE_PATH', '/instagram-app'); // Adjust based on your installation

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

// Start session securely
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => APP_BASE_PATH ?? '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Route handling
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$script_name = dirname($_SERVER['SCRIPT_NAME']);

// Remove script directory from request URI if needed
if ($script_name !== '/') {
    $request_uri = str_replace($script_name, '', $request_uri);
}

// Clean the URI
$request_uri = '/' . trim($request_uri, '/');

// Define protected routes that require authentication
$protected_routes = [
    '/profile.php',
    '/media.php',
    '/media/details' // Keep this without .php for clean URLs
];

// Define auth routes that should redirect if already authenticated
$auth_routes = [
    '/index.php',
    '/login.php',
    '/auth.php'
];

// Redirect logged-in users away from auth pages
if (isset($_SESSION['instagram_access_token']) && in_array($request_uri, $auth_routes)) {
    header('Location: ' . APP_BASE_PATH . '/profile.php');
    exit();
}

// Check if route is protected and requires authentication
$is_protected = false;
foreach ($protected_routes as $protected_route) {
    if (strpos($request_uri, $protected_route) === 0) {
        $is_protected = true;
        break;
    }
}

// Redirect to login if trying to access protected route without auth
if ($is_protected && !isset($_SESSION['instagram_access_token'])) {
    header('Location: ' . APP_BASE_PATH . '/login.php');
    exit();
}

// Function to safely include files with error handling
function safe_require($path) {
    if (file_exists($path)) {
        require_once $path;
    } else {
        http_response_code(404);
        if (file_exists(__DIR__ . '/pages/404.php')) {
            require_once __DIR__ . '/pages/404.php';
        } else {
            die('404 Page Not Found');
        }
        exit();
    }
}

// Route to the appropriate page
switch (true) {
    case $request_uri === '/':
    case $request_uri === '/index.php':
        safe_require(__DIR__ . '/pages/home.php');
        break;
        
    case $request_uri === '/login.php':
        safe_require(__DIR__ . '/pages/login.php');
        break;
        
    case $request_uri === '/auth.php':
        safe_require(__DIR__ . '/includes/auth-handler.php');
        break;
        
    case $request_uri === '/profile.php':
        safe_require(__DIR__ . '/pages/profile.php');
        break;
        
    case $request_uri === '/media.php':
        safe_require(__DIR__ . '/pages/media.php');
        break;
        
    case preg_match('@^/media/details/(\d+)$@', $request_uri, $matches):
        $_GET['id'] = $matches[1]; // Capture the media ID from URL
        safe_require(__DIR__ . '/pages/media-detail.php');
        break;
        
    case $request_uri === '/logout.php':
        safe_require(__DIR__ . '/logout.php');
        break;
        
    default:
        http_response_code(404);
        safe_require(__DIR__ . '/pages/404.php');
        break;
}