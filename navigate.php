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
    'manage_users.php',
    'create_playlist.php'
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caricamento...</title>
    <meta http-equiv="refresh" content="0.3;url=' . htmlspecialchars($target, ENT_QUOTES, 'UTF-8') . '">
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 50px;
            background: #f3f4f6;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .loading {
            font-size: 24px;
            color: #ea580c;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #ea580c;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div>
        <div class="spinner"></div>
        <div class="loading">Caricamento...</div>
    </div>
</body>
</html>';
exit;
?>
