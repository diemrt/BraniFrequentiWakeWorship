<?php
session_start();

// Get the target URL from query parameter
$target = $_GET['to'] ?? 'index.php';

// Validate that the target is a local page (security measure)
$allowed_pages = [
    'index.php',
    'login.php',
    'logout.php',
    'manage_brani.php',
    'add_edit_brano.php',
    'manage_users.php',
    'create_playlist.php',
    'edit_playlist.php',
    'backup.php'
];

// Extract base page without query string
$base_page = parse_url($target, PHP_URL_PATH);
if ($base_page === null) {
    $base_page = $target;
}

// If it's not an allowed page or contains suspicious characters, default to index
if (!in_array($base_page, $allowed_pages) && strpos($target, '?') === false) {
    $target = 'index.php';
} elseif (strpos($target, 'http') === 0 || strpos($target, '//') === 0) {
    // Prevent external redirects
    $target = 'index.php';
}

// Output loading page with meta refresh
echo '<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Caricamento...</title>
    <meta http-equiv="refresh" content="0.3;url=' . htmlspecialchars($target, ENT_QUOTES, 'UTF-8') . '">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        @keyframes pulse-slow {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .animate-spin { animation: spin 0.8s linear infinite; }
        .animate-pulse-slow { animation: pulse-slow 1.5s ease-in-out infinite; }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="flex flex-col items-center justify-center space-y-6">
        <!-- Spinner -->
        <div class="w-16 h-16 border-4 border-gray-200 border-t-orange-600 rounded-full animate-spin"></div>
        <!-- Loading Text -->
        <p class="text-2xl font-semibold text-orange-600">Caricamento...</p>
    </div>
</body>
</html>';
exit;
?>
