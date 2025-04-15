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

// Redirect logged-in users away from auth pages
$auth_pages = ['/login.php', '/auth.php', '/index.php', '/'];
if (isset($_SESSION['instagram_access_token']) && in_array($request_uri, $auth_pages)) {
    header('Location: ' . APP_BASE_PATH . '/profile.php');
    exit();
}

// Route to the appropriate page
switch ($request_uri) {
    case '/':
    case '/index.php':
        require_once __DIR__ . '/pages/home.php';
        break;
        
    case '/login.php':
        require_once __DIR__ . '/pages/login.php';
        break;
        
    case '/auth.php':
        require_once __DIR__ . '/includes/auth-handler.php';
        break;
        
    case '/profile.php':
        if (!isset($_SESSION['instagram_access_token'])) {
            header('Location: ' . APP_BASE_PATH . '/login.php');
            exit();
        }
        require_once __DIR__ . '/pages/profile.php';
        break;
        
    case '/media.php':
        if (!isset($_SESSION['instagram_access_token'])) {
            header('Location: ' . APP_BASE_PATH . '/login.php');
            exit();
        }
        require_once __DIR__ . '/pages/media.php';
        break;
        
    case '/logout.php':
        require_once __DIR__ . '/includes/logout.php';
        break;
        
    default:
        http_response_code(404);
        require_once __DIR__ . '/pages/404.php';
        break;
}