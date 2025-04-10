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
?>