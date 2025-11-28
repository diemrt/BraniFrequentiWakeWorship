<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BraniFrequentiWakeWorship</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="bg-gray-50 text-gray-900 pb-20 md:pb-0">
    <?php session_start(); ?>
    <header class="bg-gradient-to-r from-orange-600 to-orange-800 text-white shadow-lg sticky top-0 z-30">
        <nav class="container mx-auto px-4 py-3 flex justify-between items-center">
            <a href="index.php" class="text-lg md:text-xl font-bold flex items-center space-x-2">
                <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                </svg>
                <span class="hidden sm:inline">BraniFrequenti</span>
            </a>
            <div class="hidden md:flex">
                <?php if (is_logged_in()): ?>
                    <a href="manage_brani.php" class="flex items-center space-x-1 hover:bg-orange-700 px-3 py-2 rounded transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        <span>Gestisci Brani</span>
                    </a>
                    <a href="manage_users.php" class="flex items-center space-x-1 hover:bg-orange-700 px-3 py-2 rounded transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <span>Gestisci Utenti</span>
                    </a>
                    <a href="create_playlist.php" class="flex items-center space-x-1 hover:bg-orange-700 px-3 py-2 rounded transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                        </svg>
                        <span>Crea Scaletta</span>
                    </a>
                    <a href="logout.php" class="flex items-center space-x-1 hover:bg-orange-700 px-3 py-2 rounded transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        <span>Logout</span>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="flex items-center space-x-1 hover:bg-orange-700 px-3 py-2 rounded transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        <span>Login</span>
                    </a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <!-- Mobile Bottom Tab Navigation -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 z-40">
        <div class="flex justify-around h-16">
            <a href="index.php" class="nav-tab flex flex-col items-center justify-center flex-1 text-gray-600 hover:bg-gray-50 transition-colors <?php echo (basename($_SERVER['PHP_SELF']) === 'index.php' || basename($_SERVER['PHP_SELF']) === '') ? 'text-orange-600 border-t-2 border-orange-600' : ''; ?>" data-section="home">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-3m0 0l7-4 7 4M5 9v10a1 1 0 001 1h12a1 1 0 001-1V9m-9 9l-3-3m0 0l3 3m-3-3V6"></path>
                </svg>
                <span class="text-xs mt-1">Home</span>
            </a>
            
            <?php if (is_logged_in()): ?>
                <a href="create_playlist.php" class="nav-tab flex flex-col items-center justify-center flex-1 text-gray-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) === 'create_playlist.php' ? 'text-orange-600 border-t-2 border-orange-600' : ''; ?>" data-section="playlist">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                    </svg>
                    <span class="text-xs mt-1">Scaletta</span>
                </a>
                
                <a href="manage_brani.php" class="nav-tab flex flex-col items-center justify-center flex-1 text-gray-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) === 'manage_brani.php' ? 'text-orange-600 border-t-2 border-orange-600' : ''; ?>" data-section="brani">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <span class="text-xs mt-1">Brani</span>
                </a>
                
                <a href="manage_users.php" class="nav-tab flex flex-col items-center justify-center flex-1 text-gray-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) === 'manage_users.php' ? 'text-orange-600 border-t-2 border-orange-600' : ''; ?>" data-section="users">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <span class="text-xs mt-1">Utenti</span>
                </a>
            <?php endif; ?>
            
            <div class="nav-tab flex flex-col items-center justify-center flex-1 text-gray-600 hover:bg-gray-50 transition-colors" id="user-menu-toggle">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                </svg>
                <span class="text-xs mt-1">Menu</span>
            </div>
        </div>
    </nav>

    <!-- Mobile User Menu (Hidden by default) -->
    <div class="md:hidden fixed bottom-16 left-0 right-0 bg-white border-t border-gray-200 shadow-lg hidden z-40" id="user-menu-mobile">
        <div class="max-h-60 overflow-y-auto">
            <?php if (is_logged_in()): ?>
                <a href="logout.php" class="block px-4 py-3 text-red-600 hover:bg-red-50 border-b border-gray-200">
                    <div class="flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        <span>Logout</span>
                    </div>
                </a>
            <?php else: ?>
                <a href="login.php" class="block px-4 py-3 text-orange-600 hover:bg-orange-50 border-b border-gray-200">
                    <div class="flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        <span>Login</span>
                    </div>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <main class="container mx-auto px-4 py-4 md:py-6">