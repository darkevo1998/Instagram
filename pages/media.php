<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/instagram-auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['instagram_access_token'])) {
    header('Location: index.php');
    exit();
}

$instagramAuth = new InstagramAuth();
$accessToken = $_SESSION['instagram_access_token'];
$userProfile = $_SESSION['instagram_user'];

// Get user media
$mediaData = $instagramAuth->getUserMedia($userProfile['id'], $accessToken, 12);
$mediaItems = $mediaData['data'] ?? [];
?>

<div class="max-w-4xl mx-auto min-h-screen">
    <div class="flex justify-between items-center mb-8">
        <h2 class="text-2xl font-bold text-gray-800">My Instagram Media</h2>
        <span class="text-gray-600"><?= count($mediaItems) ?> posts</span>
    </div>
    
    <?php if (empty($mediaItems)): ?>
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <i class="fas fa-camera text-4xl text-gray-400 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">No Media Found</h3>
            <p class="text-gray-500">You haven't posted any media yet.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
            <?php foreach ($mediaItems as $media): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <?php if ($media['media_type'] === 'IMAGE' || $media['media_type'] === 'CAROUSEL_ALBUM'): ?>
                        <img src="<?= htmlspecialchars($media['media_url']) ?>" alt="Post" class="w-full h-48 object-cover">
                    <?php elseif ($media['media_type'] === 'VIDEO'): ?>
                        <video class="w-full h-48 object-cover" poster="<?= htmlspecialchars($media['thumbnail_url'] ?? '') ?>">
                            <source src="<?= htmlspecialchars($media['media_url']) ?>" type="video/mp4">
                        </video>
                    <?php endif; ?>
                    
                    <div class="p-4">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-gray-700 flex items-center">
                                <i class="far fa-heart mr-1"></i> <?= $media['like_count'] ?? 0 ?>
                            </span>
                            <span class="text-gray-700 flex items-center">
                                <i class="far fa-comment mr-1"></i> <?= $media['comments_count'] ?? 0 ?>
                            </span>
                        </div>
                        
                        <p class="text-gray-800 text-sm mb-2 truncate"><?= htmlspecialchars($media['caption'] ?? 'No caption') ?></p>
                        
                        <div class="flex justify-between items-center">
                            <a href="<?= APP_BASE_PATH ?>/media/details/<?= $media['id'] ?>" class="text-blue-500 text-sm hover:underline">View Details</a>
                            <span class="text-gray-500 text-xs"><?= formatTimestamp($media['timestamp']) ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>