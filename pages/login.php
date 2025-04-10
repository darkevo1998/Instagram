<?php
require_once 'config/config.php';
require_once 'includes/header.php';

// Initialize Instagram Auth
require_once 'config/instagram-auth.php';
$instagramAuth = new InstagramAuth();

// Handle errors from Instagram
if (isset($_GET['error'])) {
    $_SESSION['message'] = "Login cancelled or failed. Please try again.";
    header('Location: index.php');
    exit();
}

// Handle successful login callback
if (isset($_GET['code'])) {
    $code = $_GET['code'];
    $tokenData = $instagramAuth->getAccessToken($code);
    
    if (isset($tokenData['access_token'])) {
        $_SESSION['instagram_access_token'] = $tokenData['access_token'];
        
        // Get basic user info
        $userProfile = $instagramAuth->getUserProfile($tokenData['access_token']);
        $_SESSION['instagram_user'] = [
            'username' => $userProfile['username'],
            'profile_picture' => 'https://via.placeholder.com/150' // Placeholder - would get from API in real implementation
        ];
        
        header('Location: profile.php');
        exit();
    } else {
        $_SESSION['message'] = "Failed to authenticate with Instagram.";
        header('Location: index.php');
        exit();
    }
}
?>

<div class="max-w-md mx-auto my-12 bg-white rounded-lg shadow-md p-8">
    <div class="text-center mb-8">
        <div class="instagram-bg rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
            <i class="fab fa-instagram text-white text-2xl"></i>
        </div>
        <h2 class="text-2xl font-bold text-gray-800">Connect Your Instagram</h2>
        <p class="text-gray-600 mt-2">Sign in to access your profile and insights</p>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <?= $_SESSION['message']; unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>

    <div class="text-center">
        <a href="<?= $instagramAuth->getLoginUrl() ?>" 
           class="instagram-bg text-white font-medium py-2 px-6 rounded-md inline-flex items-center hover:opacity-90">
            <i class="fab fa-instagram mr-2"></i> Continue with Instagram
        </a>
        
        <div class="mt-6 text-sm text-gray-500">
            By continuing, you agree to our <a href="#" class="text-pink-600 hover:underline">Terms</a> 
            and <a href="#" class="text-pink-600 hover:underline">Privacy Policy</a>
        </div>
    </div>
</div>

<?php 
require_once 'includes/footer.php';
?>