<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/instagram-auth.php';

if (!isset($_SESSION['instagram_access_token'])) {
    header('Location: index.php');
    exit();
}

$instagramAuth = new InstagramAuth();
$userProfile = $_SESSION['instagram_user'];
?>

<div class="max-w-4xl mx-auto bg-white rounded-xl shadow-md overflow-hidden mt-8 mb-8 p-4 min-h-screen">
    <div class="md:flex">
        <div class="md:w-1/3 p-8 flex flex-col items-center">
            <div class="w-32 h-32 rounded-full bg-gray-200 overflow-hidden mb-4">
                <img src="https://via.placeholder.com/150" alt="Profile" class="w-full h-full object-cover">
            </div>
            <h2 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($userProfile['username']) ?></h2>
            
            <div class="bg-gray-100 rounded-lg p-4 w-full text-center mt-4">
                <div class="flex justify-around">
                    <div>
                        <p class="font-bold text-gray-800"><?= $userProfile['media_count'] ?></p>
                        <p class="text-gray-600 text-sm">Posts</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="md:w-2/3 p-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Profile Information</h3>
            
            <div class="space-y-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Username</p>
                    <p class="text-gray-800"><?= htmlspecialchars($userProfile['username']) ?></p>
                </div>
                
                <div>
                    <p class="text-sm font-medium text-gray-500">Account ID</p>
                    <p class="text-gray-800"><?= htmlspecialchars($userProfile['id']) ?></p>
                </div>
            
            </div>
            
            <div class="mt-8">
                <a href="media.php" class="inline-flex items-center px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition">
                    View My Media <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
console.log(<?= json_encode($userProfile, JSON_PRETTY_PRINT) ?>);
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>