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
    <header class="hidden lg:block text-white shadow-lg sticky top-0 z-30 will-change-transform" style="background-color: #F97D27;">
        <nav class="container mx-auto px-4 py-3 flex justify-between items-center">
            <a href="navigate.php?to=index.php" class="text-lg md:text-xl font-bold flex items-center space-x-3">
                <img src="images/logo-orange.svg" alt="WakeWorship Logo" class="w-8 h-8 md:w-10 md:h-10">
                <span class="inline">Brani Frequenti - WakeWorship</span>
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
                    <?php if (can_access_backup()): ?>
                        <a href="navigate.php?to=backup.php" class="flex items-center space-x-1 hover:bg-orange-700 px-3 py-2 rounded transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            <span>Backup DB</span>
                        </a>
                    <?php endif; ?>
                    <div class="relative group">
                        <button class="flex items-center space-x-1 hover:bg-orange-700 px-3 py-2 rounded transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                            </svg>
                            <span>Scalette</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                            </svg>
                        </button>
                        <div class="absolute left-0 mt-0 w-48 bg-orange-700 rounded shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                            <a href="navigate.php?to=create_playlist.php" class="block px-4 py-3 text-white hover:bg-orange-800 rounded-t transition-colors">
                                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                Crea Scaletta
                            </a>
                            <a href="navigate.php?to=edit_playlist.php" class="block px-4 py-3 text-white hover:bg-orange-800 rounded-b transition-colors">
                                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                Modifica Scaletta
                            </a>
                        </div>
                    </div>
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

    <!-- Mobile Header -->
    <div class="lg:hidden">
        <?php if (basename($_SERVER['PHP_SELF']) === 'index.php'): ?>
            <!-- Full header for index.php -->
            <div class="container mx-auto px-4 py-5">
                <div class="flex items-center space-x-3">
                    <img src="images/logo-orange.svg" alt="WakeWorship Logo" class="w-12 h-12 rounded-lg shadow-sm">
                    <div>
                        <h1 class="text-xl font-bold" style="color: #F97D27;">Brani Frequenti</h1>
                        <p class="text-sm text-gray-600">WakeWorship</p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Compact header for other pages -->
            <div class="bg-white border-b border-gray-200">
                <div class="container mx-auto px-4 py-3">
                    <a href="navigate.php?to=index.php" class="flex items-center space-x-2">
                        <img src="images/logo-orange.svg" alt="WakeWorship Logo" class="w-8 h-8 rounded-md">
                        <span class="text-sm font-semibold text-gray-700">Brani Frequenti</span>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>


    <!-- Mobile Bottom Tab Navigation - Always Visible -->
    <nav class="lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 z-40 will-change-transform">
        <div class="flex justify-around h-24">
            <a href="navigate.php?to=index.php" class="nav-tab flex flex-col items-center justify-center flex-1 text-gray-600 hover:bg-gray-50 transition-colors <?php echo (basename($_SERVER['PHP_SELF']) === 'index.php' || basename($_SERVER['PHP_SELF']) === '') ? 'text-orange-600 border-t-2 border-orange-600' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>

                <span class="text-xs mt-1">Home</span>
            </a>

            <?php if (is_logged_in()): ?>
                <a href="navigate.php?to=create_playlist.php" class="nav-tab flex flex-col items-center justify-center flex-1 text-gray-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) === 'create_playlist.php' ? 'text-orange-600 border-t-2 border-orange-600' : ''; ?>">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span class="text-xs mt-1">Crea</span>
                </a>

                <a href="navigate.php?to=edit_playlist.php" class="nav-tab flex flex-col items-center justify-center flex-1 text-gray-600 hover:bg-gray-50 transition-colors <?php echo basename($_SERVER['PHP_SELF']) === 'edit_playlist.php' ? 'text-orange-600 border-t-2 border-orange-600' : ''; ?>">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    <span class="text-xs mt-1">Modifica</span>
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
        <?php
        // Alert backup mensile - solo il primo giorno del mese per Admin e Developer
        if (is_logged_in() && can_access_backup() && date('j') == 1):
        ?>
            <div class="max-w-6xl mx-auto mb-6 p-4 rounded-lg bg-yellow-50 border border-yellow-200">
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                    <div class="flex items-start space-x-3 flex-1">
                        <svg class="w-6 h-6 text-yellow-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <div class="flex-1">
                            <h3 class="text-yellow-900 font-semibold mb-1">Promemoria Backup Mensile</h3>
                            <p class="text-yellow-800 text-sm">
                                È il primo giorno del mese! Ricordati di effettuare un backup del database per garantire la sicurezza dei dati.
                            </p>
                        </div>
                    </div>
                    <a href="navigate.php?to=backup.php" 
                       class="w-full md:w-auto flex items-center justify-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg font-medium transition-colors min-h-[44px] select-none">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Vai al Backup
                    </a>
                </div>
            </div>
        <?php endif; ?>