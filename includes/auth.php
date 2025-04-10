<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/instagram-auth.php';

$instagramAuth = new InstagramAuth();

if (isset($_GET['code'])) {
    // User has authenticated, get access token
    $code = $_GET['code'];
    $tokenData = $instagramAuth->getAccessToken($code);
    
    if (isset($tokenData['access_token'])) {
        $_SESSION['instagram_access_token'] = $tokenData['access_token'];
        $_SESSION['instagram_user_id'] = $tokenData['user_id'];
        
        // Get user profile
        $userProfile = $instagramAuth->getUserProfile($tokenData['access_token']);
        $_SESSION['instagram_user'] = [
            'id' => $userProfile['id'],
            'username' => $userProfile['username'],
            'account_type' => $userProfile['account_type'],
            'media_count' => $userProfile['media_count']
        ];
        
        header('Location: profile.php');
        exit();
    } else {
        // Handle error
        $_SESSION['error'] = 'Failed to authenticate with Instagram.';
        header('Location: index.php');
        exit();
    }
} elseif (isset($_GET['error'])) {
    // User denied authentication
    $_SESSION['error'] = 'Access denied. Please authorize the app to continue.';
    header('Location: index.php');
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}
?>