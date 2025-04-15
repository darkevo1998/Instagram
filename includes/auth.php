<?php
// Set up logging function
function logMessage($message) {
    $logFile = __DIR__ . '/../logs/auth_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Start logging
logMessage('Script started processing Instagram callback');

// Ensure this is included only once
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/instagram-auth.php';

// Session handling with logging
if (session_status() == PHP_SESSION_NONE) {
    session_start();
    logMessage('Session started. Session ID: ' . session_id());
} else {
    logMessage('Session already active. Session ID: ' . session_id());
}

// Log session data (be careful with sensitive info in production)
logMessage('Session data at start: ' . json_encode($_SESSION));

$instagramAuth = new InstagramAuth();

// Check if 'code' and 'state' are present in the URL
if (isset($_GET['code']) && isset($_GET['state'])) {
    logMessage('Received code and state parameters from Instagram');
    logMessage('Received state: ' . $_GET['state']);
    
    // Check for state in session
    $sessionState = $_SESSION['instagram_auth_state'] ?? null;
    logMessage('Expected state (instagram_auth_state): ' . ($sessionState ?? 'NOT SET'));

    // Validate the state parameter
    if (!$sessionState) {
        logMessage('ERROR: No state parameter found in session');
        $_SESSION['error'] = 'Session expired. Please try logging in again.';
        header('Location: index.php');
        exit();
    }

    if ($_GET['state'] !== $sessionState) {
        logMessage('ERROR: State mismatch - Possible CSRF attack');
        logMessage('Session state: ' . $sessionState);
        logMessage('Returned state: ' . $_GET['state']);
        
        $_SESSION['error'] = 'Invalid state parameter. Possible CSRF attack.';
        header('Location: index.php');
        exit();
    }

    try {
        logMessage('State validation successful - proceeding with token exchange');
    
        
        $code = $_GET['code'];
        $state = $_GET['state'];
    
        // Get the access token - pass the validated state explicitly
        logMessage('Attempting to get access token with code and validated state');
        $tokenData = $instagramAuth->getAccessToken($code, $state);
        logMessage('Token data received: ' . json_encode($tokenData));
        
        if (isset($tokenData['access_token'])) {
            $_SESSION['instagram_access_token'] = $tokenData['access_token'];
            logMessage('Short-lived access token stored in session');
            
            // Get long-lived token
            logMessage('Attempting to get long-lived token');
            $longLivedToken = $instagramAuth->getLongLivedToken($tokenData['access_token']);
            logMessage('Long-lived token data: ' . json_encode($longLivedToken));
            
            if (isset($longLivedToken['access_token'])) {
                $_SESSION['instagram_long_token'] = $longLivedToken['access_token'];
                logMessage('Long-lived token stored in session');
                
                // Get Instagram business account info
                logMessage('Attempting to get Instagram account info');
                $instagramAccount = $instagramAuth->getInstagramAccount($longLivedToken['access_token']);
                logMessage('Instagram account data: ' . json_encode($instagramAccount));
                
                if (isset($instagramAccount['instagram_id'])) {
                    $_SESSION['instagram_user_id'] = $instagramAccount['instagram_id'];
                    logMessage('Instagram user ID stored in session: ' . $instagramAccount['instagram_id']);
                    
                    // Get user profile
                    logMessage('Attempting to get user profile');
                    $userProfile = $instagramAuth->getUserProfile(
                        $instagramAccount['instagram_id'], 
                        $longLivedToken['access_token']
                    );
                    logMessage('User profile data: ' . json_encode($userProfile));
                    
                    $_SESSION['instagram_user'] = [
                        'id' => $userProfile['id'],
                        'username' => $userProfile['username'],
                        'name' => $userProfile['name'] ?? '',
                        'profile_picture' => $userProfile['profile_picture_url'] ?? '',
                        'media_count' => $userProfile['media_count'] ?? 0,
                        'followers_count' => $userProfile['followers_count'] ?? 0
                    ];
                    logMessage('Full user profile stored in session');

                    logMessage('Redirecting to profile.php');
                    header('Location: profile.php');
                    exit();
                } else {
                    logMessage('ERROR: Instagram account ID not found in response');
                    throw new Exception('Instagram account ID not found');
                }
            } else {
                logMessage('ERROR: Long-lived token not found in response');
                throw new Exception('Long-lived token not received');
            }
        } else {
            logMessage('ERROR: Access token not found in response');
            throw new Exception('Access token not received');
        }
    } catch (Exception $e) {
        logMessage('EXCEPTION: ' . $e->getMessage());
        logMessage('Stack trace: ' . $e->getTraceAsString());
        
        $_SESSION['error'] = 'Failed to authenticate with Instagram: ' . $e->getMessage();
        header('Location: index.php');
        exit();
    }
} elseif (isset($_GET['error'])) {
    logMessage('User denied authentication. Error: ' . $_GET['error']);
    $_SESSION['error'] = 'Access denied. Please authorize the app to continue.';
    header('Location: index.php');
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    logMessage('User initiated logout. Destroying session.');
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit();
}

logMessage('Script completed without matching any condition');
?>
