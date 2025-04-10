<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/instagram-auth.php';

$instagramAuth = new InstagramAuth();
?>

<div class="max-w-md mx-auto my-12 bg-white rounded-xl shadow-md overflow-hidden p-8 text-center">
    <div class="instagram-gradient p-4 rounded-full w-24 h-24 flex items-center justify-center mx-auto mb-6">
        <i class="fab fa-instagram text-white text-4xl"></i>
    </div>
    
    <h2 class="text-2xl font-bold text-gray-800 mb-2">Welcome to Empathy Technologies</h2>
    <p class="text-gray-600 mb-8">Connect with your Instagram account to view your profile and media.</p>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    
    <a href="<?= $instagramAuth->getLoginUrl() ?>" class="instagram-gradient text-white font-semibold py-3 px-6 rounded-lg inline-flex items-center hover:opacity-90 transition">
        <i class="fab fa-instagram mr-2"></i> Login with Instagram
    </a>
    
    <div class="mt-8 pt-6 border-t border-gray-200">
        <p class="text-gray-500 text-sm">Full Stack Developer Assessment</p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>