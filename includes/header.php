<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BraniFrequentiWakeWorship</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="bg-gray-100">
    <?php session_start(); ?>
    <header class="bg-blue-600 text-white p-4">
        <nav class="container mx-auto flex justify-between">
            <a href="index.php" class="text-xl font-bold">BraniFrequenti</a>
            <div>
                <?php if (is_logged_in()): ?>
                    <a href="manage_brani.php" class="mr-4">Gestisci Brani</a>
                    <a href="register_frequency.php" class="mr-4">Registra Frequenza</a>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>
    <main class="container mx-auto p-4">