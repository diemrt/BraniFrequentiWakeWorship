<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BraniFrequenti - WakeWorship</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/styles.css">
    <script src="js/scripts.js" defer></script>
    <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
    <link rel="shortcut icon" href="/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
    <link rel="manifest" href="/site.webmanifest" />
    </head>

<body class="bg-gray-50 text-gray-900 pb-20 md:pb-0 scroll-smooth text-base">
    <?php session_start(); ?>
    <header class="bg-gradient-to-r from-orange-600 to-orange-800 text-white shadow-lg sticky top-0 z-30 will-change-transform">
        <nav class="container mx-auto px-4 py-3 flex justify-between items-center">
            <a href="navigate.php?to=index.php" class="text-lg md:text-xl font-bold flex items-center space-x-2">
                <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                </svg>
                <span class="hidden sm:inline">BraniFrequenti</span>
            </a>
            <div class="hidden md:flex">
                <?php if (is_logged_in()): ?>
                    <a href="navigate.php?to=manage_brani.php" class="flex items-center space-x-1 hover:bg-orange-700 px-3 py-2 rounded transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        <span>Gestisci Brani</span>
                    </a>
                    <a href="navigate.php?to=manage_users.php" class="flex items-center space-x-1 hover:bg-orange-700 px-3 py-2 rounded transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <span>Gestisci Utenti</span>
                    </a>
                    <a href="navigate.php?to=create_playlist.php" class="flex items-center space-x-1 hover:bg-orange-700 px-3 py-2 rounded transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                        </svg>
                        <span>Crea Scaletta</span>
                    </a>
                    <a href="navigate.php?to=logout.php" class="flex items-center space-x-1 hover:bg-orange-700 px-3 py-2 rounded transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        <span>Logout</span>
                    </a>
                <?php else: ?>
                    <a href="navigate.php?to=login.php" class="flex items-center space-x-1 hover:bg-orange-700 px-3 py-2 rounded transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        <span>Login</span>
                    </a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <!-- Mobile Bottom Tab Navigation - Always Visible -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 z-40 will-change-transform">
        <div class="flex justify-around h-16">
            <a href="navigate.php?to=index.php" class="nav-tab flex flex-col items-center justify-center flex-1 text-gray-600 hover:bg-gray-50 transition-colors <?php echo (basename($_SERVER['PHP_SELF']) === 'index.php' || basename($_SERVER['PHP_SELF']) === '') ? 'text-orange-600 border-t-2 border-orange-600' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>

                <span class="text-xs mt-1">Home</span>
            </a>

            <?php if (is_logged_in()): ?>
                <a href="navigate.php?to=create_playlist.php" class="nav-tab flex flex-col items-center justify-center flex-1 text-gray-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) === 'create_playlist.php' ? 'text-orange-600 border-t-2 border-orange-600' : ''; ?>">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                    </svg>
                    <span class="text-xs mt-1">Scaletta</span>
                </a>

                <a href="navigate.php?to=manage_brani.php" class="nav-tab flex flex-col items-center justify-center flex-1 text-gray-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) === 'manage_brani.php' ? 'text-orange-600 border-t-2 border-orange-600' : ''; ?>">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <span class="text-xs mt-1">Brani</span>
                </a>

                <a href="navigate.php?to=manage_users.php" class="nav-tab flex flex-col items-center justify-center flex-1 text-gray-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) === 'manage_users.php' ? 'text-orange-600 border-t-2 border-orange-600' : ''; ?>">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <span class="text-xs mt-1">Utenti</span>
                </a>

                <a href="navigate.php?to=logout.php" class="nav-tab flex flex-col items-center justify-center flex-1 text-gray-600 hover:bg-gray-50 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    <span class="text-xs mt-1">Logout</span>
                </a>
            <?php else: ?>
                <a href="navigate.php?to=login.php" class="nav-tab flex flex-col items-center justify-center flex-1 text-gray-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) === 'login.php' ? 'text-orange-600 border-t-2 border-orange-600' : ''; ?>">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    <span class="text-xs mt-1">Login</span>
                </a>
            <?php endif; ?>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-4 md:py-6">