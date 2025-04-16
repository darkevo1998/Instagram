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
$instagramId = $userProfile['id']; // Get Instagram user ID from session

// Get media ID from URL
$mediaId = $_GET['id'] ?? '';
if (empty($mediaId)) {
    header('Location: '. APP_BASE_PATH . '/media.php');
    exit();
}

// Get media details - updated to use getUserMedia
$limit = 20; // Increased from 1 to improve chances of finding the media
$mediaData = $instagramAuth->getUserMedia($instagramId, $accessToken, $limit);
$instagramAuth->logMessage('mediaData: ' . json_encode($mediaData), 'info');

$media = null;

// Find the specific media item from the returned data
if (!empty($mediaData['data'])) {
    foreach ($mediaData['data'] as $item) {
        if ($item['id'] === $mediaId) {
            $media = $item;
            break;
        }
    }
}

$comments = $instagramAuth->getMediaComments($mediaId, $accessToken);

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['comment_text'])) {
        // Verify access token first
        if (empty($accessToken) || !$instagramAuth->validateAccessToken($accessToken)) {
            $instagramAuth->logMessage("Invalid access token attempt - Media ID: $mediaId", 'error');
            $_SESSION['error_message'] = 'Your session has expired. Please log in again.';
            header('Location: ' . APP_BASE_PATH . '/logout.php');
            exit();
        }
        
        // Log comment attempt
        $instagramAuth->logMessage("Attempting to post comment to media $mediaId", 'info');
        
        // Handle new comment
        $commentResponse = $instagramAuth->postComment(
            $mediaId,
            $_POST['comment_text'],
            $accessToken
        );
        
        if (isset($commentResponse['id'])) {
            // Log successful comment
            $instagramAuth->logMessage("Successfully posted comment to media $mediaId. Comment ID: {$commentResponse['id']}", 'info');
            
            // Refresh comments after successful comment
            $comments = $instagramAuth->getMediaComments($mediaId, $accessToken);
            $_SESSION['success_message'] = 'Comment posted successfully!';
        } else {
            $errorMsg = $commentResponse['error'] ?? 'Failed to post comment';
            
            // Log failed comment attempt
            $instagramAuth->logMessage("Failed to post comment to media $mediaId. Error: $errorMsg", 'error');
            
            $_SESSION['error_message'] = $errorMsg;
            
            // If token is invalid, redirect to logout
            if (strpos($errorMsg, 'access token') !== false || 
                (isset($commentResponse['http_code']) && $commentResponse['http_code'] == 400)) {
                $instagramAuth->logMessage("Invalid access token detected - forcing logout", 'error');
                header('Location: ' . APP_BASE_PATH . '/logout.php');
                exit();
            }
        }
    }
    
    // Log redirect
    $instagramAuth->logMessage("Redirecting after comment submission to media $mediaId", 'debug');
    
    // Redirect to prevent form resubmission
    header("Location: " . APP_BASE_PATH . "/media/details/$mediaId");
    exit();
}
?>

<div class="max-w-4xl mx-auto min-h-screen px-4 py-8">
    <!-- Back button -->
    <a href="<?= APP_BASE_PATH ?>/media.php" class="flex items-center text-blue-500 mb-6 hover:text-blue-700 transition-colors">
        <i class="fas fa-arrow-left mr-2"></i> Back to Media
    </a>

    <!-- Success/Error messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            <?= $_SESSION['success_message'] ?>
            <?php unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <?= $_SESSION['error_message'] ?>
            <?php unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>

    <!-- Media content -->
    <div class="media-wrapper bg-white rounded-xl shadow-md overflow-hidden mb-8">
        <?php if ($media['media_type'] === 'IMAGE'): ?>
            <img src="<?= htmlspecialchars($media['media_url']) ?>" alt="Post image" class="w-full">
        <?php elseif ($media['media_type'] === 'VIDEO'): ?>
            <video controls class="w-full">
                <source src="<?= htmlspecialchars($media['media_url']) ?>" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        <?php elseif ($media['media_type'] === 'CAROUSEL_ALBUM'): ?>
            <!-- Handle carousel posts - showing first media item -->
            <?php 
            $carouselMedia = $instagramAuth->getMediaChildren($mediaId, $accessToken);
            if (!empty($carouselMedia['data'])): ?>
                <img src="<?= htmlspecialchars($carouselMedia['data'][0]['media_url']) ?>" alt="Post image" class="w-full">
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <div class="flex items-center space-x-4">
                    <img src="<?= htmlspecialchars($userProfile['profile_picture']) ?>" alt="Profile" class="w-10 h-10 rounded-full">
                    <span class="font-semibold"><?= htmlspecialchars($userProfile['username']) ?></span>
                </div>
                <span class="text-gray-500 text-sm"><?= formatTimestamp($media['timestamp'] ?? '') ?></span>
            </div>
            
            <?php if (!empty($media['caption'])): ?>
                <p class="text-gray-800 mb-4"><?= htmlspecialchars($media['caption']) ?></p>
            <?php endif; ?>
            
            <div class="flex items-center space-x-6 text-gray-700 mb-6">
                <span class="flex items-center">
                    <i class="far fa-heart mr-2"></i> <?= $media['like_count'] ?? 0 ?> likes
                </span>
                <span class="flex items-center">
                    <i class="far fa-comment mr-2"></i> <?= $media['comments_count'] ?? 0 ?> comments
                </span>
                <?php if (!empty($media['permalink'])): ?>
                    <a href="<?= htmlspecialchars($media['permalink']) ?>" 
                       target="_blank"
                       class="text-blue-500 hover:underline flex items-center">
                        <i class="fas fa-external-link-alt mr-2"></i> View on Instagram
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add comment form -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
        <div class="p-6">
            <form method="POST">
                <div class="flex items-center space-x-3">
                    <img src="<?= htmlspecialchars($userProfile['profile_picture']) ?>" alt="Profile" class="w-10 h-10 rounded-full">
                    <div class="flex-1 flex space-x-2">
                        <input type="text" name="comment_text" placeholder="Add a comment..." required
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-full hover:bg-blue-600 transition-colors">
                            Post
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Comments section -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Comments (<?= count($comments['data']) ?>)</h3>
        </div>
        
        <div class="divide-y divide-gray-200">
            <?php if (empty($comments['data'])): ?>
                <div class="p-6 text-center text-gray-500">
                    No comments yet. Be the first to comment!
                </div>
            <?php else: ?>
                <?php foreach ($comments['data'] as $comment): ?>
                    <div class="p-6">
                        <div class="flex items-start space-x-3">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($comment['username']) ?>&background=random" alt="Commenter" class="w-10 h-10 rounded-full">
                            
                            <div class="flex-1">
                                <div class="flex items-center space-x-2">
                                    <span class="font-semibold"><?= htmlspecialchars($comment['username']) ?></span>
                                </div>
                                <!--
                                <p class="text-gray-800 mt-1"><?= htmlspecialchars($comment['text']) ?></p>
                -->
                                <!-- Reply button and form -->
                                <div class="mt-2">
                                    <button onclick="toggleReplyForm('<?= $comment['id'] ?>')" 
                                            class="text-blue-500 text-sm hover:underline flex items-center">
                                        <i class="fas fa-reply mr-1"></i> Reply
                                    </button>
                                    
                                    <form id="reply-form-<?= $comment['id'] ?>" method="POST" class="hidden mt-3">
                                        <input type="hidden" name="parent_comment_id" value="<?= $comment['id'] ?>">
                                        <div class="flex items-center space-x-3 pl-10">
                                            <img src="<?= htmlspecialchars($userProfile['profile_picture']) ?>" alt="Profile" class="w-8 h-8 rounded-full">
                                            <div class="flex-1 flex space-x-2">
                                                <input type="text" name="reply_text" placeholder="Write a reply..." required
                                                    class="flex-1 px-3 py-2 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-full hover:bg-blue-600 transition-colors">
                                                    Post
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                
                                <!-- Replies -->
                                <?php if (!empty($comment['replies']['data'])): ?>
                                    <div class="mt-4 pl-10 border-l-2 border-gray-200 space-y-4">
                                        <?php foreach ($comment['replies']['data'] as $reply): ?>
                                            <div class="flex items-start space-x-3">
                                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($reply['username']) ?>&background=random" alt="Replier" class="w-8 h-8 rounded-full">
                                                
                                                <div class="flex-1">
                                                    <div class="flex items-center space-x-2">
                                                        <span class="font-semibold"><?= htmlspecialchars($reply['username']) ?></span>
                                                        <span class="text-gray-500 text-sm"><?= formatTimestamp($reply['timestamp']) ?></span>
                                                    </div>
                                                    <p class="text-gray-800 mt-1"><?= htmlspecialchars($reply['text']) ?></p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function toggleReplyForm(commentId) {
        const form = document.getElementById(`reply-form-${commentId}`);
        form.classList.toggle('hidden');
        
        // Scroll to the form if it's being shown
        if (!form.classList.contains('hidden')) {
            form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }
    
    // Focus on comment input when page loads if there's an error
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (isset($_SESSION['error_message'])): ?>
            const commentInput = document.querySelector('input[name="comment_text"]');
            if (commentInput) commentInput.focus();
        <?php endif; ?>
    });

    const allComments = <?= json_encode($comments, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    console.log("All Comments:", allComments);
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>