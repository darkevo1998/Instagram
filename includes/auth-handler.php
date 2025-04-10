<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/instagram-auth.php';

try {
    $instagramAuth = new InstagramAuth();

    if (isset($_GET['error'])) {
        throw new Exception('Instagram login error: ' . $_GET['error']);
    }

    if (!isset($_GET['code']) || !isset($_GET['state'])) {
        throw new Exception('Missing required parameters');
    }

    $tokenData = $instagramAuth->getAccessToken($_GET['code'], $_GET['state']);
    
    if (!isset($tokenData['access_token'])) {
        throw new Exception('No access token received');
    }

    // Store the access token and user data in session
    $_SESSION['instagram_access_token'] = $tokenData['access_token'];
    $_SESSION['instagram_user'] = [
        'id' => $tokenData['user_id'],
        'username' => 'temp_username' // Will be updated with real API call
    ];

    // Redirect to profile page
    header('Location: ' . APP_BASE_PATH . '/profile.php');
    exit();

} catch (Exception $e) {
    $_SESSION['auth_error'] = $e->getMessage();
    header('Location: ' . APP_BASE_PATH . '/login.php?error=1');
    exit();
}