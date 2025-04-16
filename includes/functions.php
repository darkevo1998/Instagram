<?php
function formatTimestamp($timestamp) {
    $date = new DateTime($timestamp);
    return $date->format('M j, Y \a\t g:i a');
}

function displayMediaItem($media) {
    $mediaType = $media['media_type'];
    $mediaUrl = $media['media_url'] ?? ($media['thumbnail_url'] ?? '');
    $caption = $media['caption'] ?? 'No caption';
    $permalink = $media['permalink'] ?? '#';
    $likeCount = $media['like_count'] ?? 0;
    $commentsCount = $media['comments_count'] ?? 0;
    
    echo '<div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">';
    echo '    <div class="p-4 flex items-center">';
    echo '        <img src="https://via.placeholder.com/150" alt="User" class="w-10 h-10 rounded-full mr-3">';
    echo '        <div>';
    echo '            <h3 class="font-semibold text-gray-800">' . htmlspecialchars($media['username']) . '</h3>';
    echo '            <p class="text-xs text-gray-500">' . formatTimestamp($media['timestamp']) . '</p>';
    echo '        </div>';
    echo '    </div>';
    
    // Media content
    if ($mediaType === 'IMAGE' || $mediaType === 'CAROUSEL_ALBUM') {
        echo '    <img src="' . htmlspecialchars($mediaUrl) . '" alt="Post" class="w-full">';
    } elseif ($mediaType === 'VIDEO') {
        echo '    <video controls class="w-full">';
        echo '        <source src="' . htmlspecialchars($mediaUrl) . '" type="video/mp4">';
        echo '        Your browser does not support the video tag.';
        echo '    </video>';
    }
    
    // Caption and stats
    echo '    <div class="p-4">';
    echo '        <div class="flex space-x-4 mb-2">';
    echo '            <span class="flex items-center text-gray-700"><i class="far fa-heart mr-1"></i> ' . $likeCount . '</span>';
    echo '            <span class="flex items-center text-gray-700"><i class="far fa-comment mr-1"></i> ' . $commentsCount . '</span>';
    echo '        </div>';
    echo '        <p class="text-gray-800 mb-2"><span class="font-semibold">' . htmlspecialchars($media['username']) . '</span> ' . htmlspecialchars($caption) . '</p>';
    echo '        <a href="' . htmlspecialchars($permalink) . '" target="_blank" class="text-blue-500 text-sm">View on Instagram</a>';
    echo '    </div>';
    echo '</div>';
}

function display_instagram_media(array $media = []) {
    // Set safe defaults
    $media = array_merge([
        'media_type' => 'IMAGE',
        'media_url' => '',
        'thumbnail_url' => '',
        'caption' => '',
        'id' => 'unknown'
    ], $media);

    // Validate media URL
    if (!filter_var($media['media_url'], FILTER_VALIDATE_URL)) {
        error_log("Invalid media URL for post {$media['id']}");
        $media['media_url'] = '';
    }

    // Main display switch
    ob_start(); ?>
    <div class="instagram-media-container">
        <?php switch(strtoupper($media['media_type'])) {
            case 'IMAGE':
            case 'CAROUSEL_ALBUM':
                if ($media['media_url']): ?>
                    <img src="<?= htmlspecialchars($media['media_url']) ?>" 
                         alt="<?= htmlspecialchars($media['caption'] ?: 'Instagram post') ?>"
                         class="w-full max-h-[600px] object-contain">
                <?php else: ?>
                    <?= media_error_state('Image not available') ?>
                <?php endif;
                break;

            case 'VIDEO':
                if ($media['media_url']): ?>
                    <video controls 
                           class="w-full max-h-[600px] object-contain"
                           <?= $media['thumbnail_url'] ? 'poster="' . htmlspecialchars($media['thumbnail_url']) . '"' : '' ?>>
                        <source src="<?= htmlspecialchars($media['media_url']) ?>" type="video/mp4">
                        Your browser doesn't support HTML5 video.
                    </video>
                <?php else: ?>
                    <?= media_error_state('Video not available') ?>
                <?php endif;
                break;

            default: ?>
                <?= media_error_state('Unsupported media type: ' . htmlspecialchars($media['media_type'])) ?>
        <?php } ?>
    </div>
    <?php return ob_get_clean();
}

function media_error_state($message) {
    return <<<HTML
    <div class="media-error bg-gray-100 w-full h-64 flex flex-col items-center justify-center gap-2 p-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <p class="text-gray-500 text-center">$message</p>
    </div>
    HTML;
}
?>
