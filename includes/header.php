<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['instagram_access_token']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instagram App</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .instagram-bg {
            background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888);
        }
        body {
            font-family: Arial, sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Simple Header -->
    <header class="bg-white shadow">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <!-- Logo -->
            <div class="flex items-center">
                <i class="fab fa-instagram text-2xl instagram-bg bg-clip-text text-transparent mr-2"></i>
                <span class="font-bold">Instagram App</span>
            </div>
            
            <!-- Navigation -->
            <?php if ($isLoggedIn): ?>
                <div class="flex items-center space-x-4">
                    <a href="<?= APP_BASE_PATH ?>/profile.php" class="text-gray-600 hover:text-pink-600">
                        <i class="fas fa-user"></i>
                    </a>
                    <a href="<?= APP_BASE_PATH ?>/media.php" class="text-gray-600 hover:text-pink-600">
                        <i class="fas fa-images"></i>
                    </a>
                    <a href="<?= APP_BASE_PATH ?>/logout.php" class="text-gray-600 hover:text-pink-600">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            <?php else: ?>
                <a href="login.php" class="bg-pink-600 text-white px-3 py-1 rounded text-sm">
                    Login
                </a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="container mx-auto px-4 py-6">
        <!-- Display flash messages if they exist -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-4">
                <?= $_SESSION['message'] ?>
                <?php unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>
        </main>

<!-- Basic JavaScript for mobile menu (if needed) -->
<script>
    // Simple toggle function example
    function toggleMenu() {
        const menu = document.getElementById('mobile-menu');
        menu.classList.toggle('hidden');
    }
</script>
</body>
</html>