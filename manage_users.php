<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? 'info';
if ($message) {
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Handle delete confirmation request
if (isset($_GET['confirm_delete'])) {
    $id = (int)$_GET['confirm_delete'];
    $stmt = $conn->prepare("SELECT Username FROM Utenti WHERE Id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $confirm_utente = $result->fetch_assoc();
}

// Get utente for editing if edit_id is set
$edit_utente = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM Utenti WHERE Id = ?");
    $stmt->bind_param('i', $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_utente = $result->fetch_assoc();
}

$result = $conn->query("SELECT COUNT(*) FROM Utenti");
$total = $result->fetch_row()[0];
$total_pages = ceil($total / $limit);

$stmt = $conn->prepare("SELECT Id, Username, Ruolo FROM Utenti ORDER BY Username LIMIT ? OFFSET ?");
$stmt->bind_param('ii', $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
$utenti = $result->fetch_all(MYSQLI_ASSOC);
?>

<?php include 'includes/header.php'; ?>
<div class="max-w-6xl mx-auto mt-4 lg:mt-0">
    <div class="flex items-center mb-4">
        <svg class="h-6 w-6 text-orange-600 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
        </svg>
        <h1 class="text-xl font-bold text-gray-900">Gestione utenti</h1>
    </div>
    <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'; ?>">
            <p class="<?php echo $message_type === 'success' ? 'text-green-800' : 'text-red-800'; ?> text-sm md:text-base"><?php echo sanitize($message); ?></p>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['confirm_delete']) && $confirm_utente): ?>
        <div class="fixed inset-0 bg-black/50 z-50 flex items-end md:items-center md:justify-center" id="delete-modal">
            <div class="bg-white w-full md:w-96 rounded-t-2xl md:rounded-2xl p-6 md:p-8 space-y-6 max-h-96 overflow-y-auto">
                <h2 class="text-xl md:text-2xl font-bold text-center text-gray-900">Eliminare l'utente?</h2>
                <p class="text-gray-700 text-sm md:text-base">
                    Sei sicuro di voler eliminare l'utente "<?php echo sanitize($confirm_utente['Username']); ?>"?
                </p>
                <div class="flex gap-4">
                    <a href="manage_users.php?page=<?php echo $page; ?>"
                        class="flex-1 px-4 py-3 md:py-4 bg-gray-100 hover:bg-gray-200 rounded-lg md:rounded-xl font-medium text-gray-900 text-center transition-colors">
                        Annulla
                    </a>
                    <a href="loading.php?action=delete_utente&delete=<?php echo $_GET['confirm_delete']; ?>&confirmed=1&page=<?php echo $page; ?>"
                        class="flex-1 px-4 py-3 md:py-4 bg-red-600 hover:bg-red-700 text-white rounded-lg md:rounded-xl font-medium text-center transition-colors">
                        Elimina
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Utenti List - Mobile Optimized Cards -->
    <div class="space-y-3 md:space-y-0 md:grid md:grid-cols-2 md:gap-6 mb-8">
        <?php if (empty($utenti)): ?>
            <div class="col-span-2 text-center py-12">
                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 15c-2.34 0-4.29-.98-5.5-2.5"></path>
                </svg>
                <p class="text-gray-500 text-lg">Nessun utente trovato.</p>
            </div>
        <?php else: ?>
            <?php foreach ($utenti as $utente): ?>
                <div class="bg-white rounded-lg border border-gray-200 p-4 md:p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm md:text-base font-semibold text-gray-900 break-words"><?php echo sanitize($utente['Username']); ?></h3>
                            <span class="inline-block mt-2 px-2 py-1 text-xs font-medium rounded-full 
                                <?php 
                                    echo $utente['Ruolo'] === 'Admin' ? 'bg-purple-100 text-purple-800' : 
                                        ($utente['Ruolo'] === 'Developer' ? 'bg-blue-100 text-blue-800' : 
                                        'bg-gray-100 text-gray-800'); 
                                ?>">
                                <?php echo sanitize($utente['Ruolo']); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Action Buttons - Always Visible -->
                    <div class="flex gap-2 mt-3">
                        <a href="?edit_id=<?php echo $utente['Id']; ?>&page=<?php echo $page; ?>"
                            class="flex-1 flex items-center justify-center space-x-1 px-3 py-2 bg-orange-100 text-orange-700 rounded-lg hover:bg-orange-200 transition-colors text-sm min-h-[44px] select-none">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            <span>Modifica</span>
                        </a>
                        <a href="?confirm_delete=<?php echo $utente['Id']; ?>&page=<?php echo $page; ?>"
                            class="flex-1 flex items-center justify-center space-x-1 px-3 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors text-sm min-h-[44px] select-none">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            <span>Elimina</span>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="flex justify-center gap-2 mb-8 flex-wrap">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>" class="flex items-center px-3 py-2 md:px-4 md:py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    <span class="hidden md:inline">Precedente</span>
                </a>
            <?php endif; ?>

            <div class="flex items-center gap-1 md:gap-2">
                <span class="text-xs md:text-sm text-gray-600">Pagina <?php echo $page; ?> di <?php echo $total_pages; ?></span>
            </div>

            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>" class="flex items-center px-3 py-2 md:px-4 md:py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    <span class="hidden md:inline">Successivo</span>
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Add/Edit Form -->
    <div class="bg-white rounded-lg shadow-lg border border-gray-200 p-4 md:p-6" id="utenteForm">
        <div class="flex items-center mb-4">
            <svg class="h-6 w-6 text-orange-600 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
            </svg>


            <h2 class="text-xl font-bold text-gray-900"><?php echo $edit_utente ? 'Modifica' : 'Aggiungi'; ?> utente</h2>
        </div>
        <form method="post" action="loading.php">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="action" value="manage_users">
            <input type="hidden" name="page" value="<?php echo $page; ?>">
            <?php if ($edit_utente): ?>
                <input type="hidden" name="id" value="<?php echo $edit_utente['Id']; ?>">
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                    <input type="text" name="username" id="username" placeholder="Username"
                        value="<?php echo $edit_utente ? sanitize($edit_utente['Username']) : ''; ?>"
                        class="w-full px-3 py-2 md:py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500 text-base min-h-[44px]"
                        required>
                </div>
                <div>
                    <label for="ruolo" class="block text-sm font-medium text-gray-700 mb-2">Ruolo</label>
                    <select name="ruolo" id="ruolo"
                        class="w-full px-3 py-2 md:py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500 text-base min-h-[44px]"
                        required>
                        <option value="User" <?php echo ($edit_utente && $edit_utente['Ruolo'] === 'User') ? 'selected' : ''; ?>>User</option>
                        <option value="Developer" <?php echo ($edit_utente && $edit_utente['Ruolo'] === 'Developer') ? 'selected' : ''; ?>>Developer</option>
                        <option value="Admin" <?php echo ($edit_utente && $edit_utente['Ruolo'] === 'Admin') ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password<?php echo $edit_utente ? ' (lascia vuoto per non modificare)' : ''; ?></label>
                    <input type="password" name="password" id="password" placeholder="Password"
                        class="w-full px-3 py-2 md:py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500 text-base min-h-[44px]"
                        <?php echo $edit_utente ? '' : 'required'; ?>>
                </div>
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Conferma Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Conferma Password"
                        class="w-full px-3 py-2 md:py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500 text-base min-h-[44px]"
                        <?php echo $edit_utente ? '' : 'required'; ?>>
                </div>
            </div>

            <div class="flex flex-col md:flex-row gap-3">
                <?php if ($edit_utente): ?>
                    <button type="submit" name="edit"
                        class="flex-1 md:flex-initial flex items-center justify-center px-4 py-3 md:py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg md:rounded-md font-medium transition-colors min-h-[44px] min-w-[44px] select-none">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Salva Modifiche</span>
                    </button>
                    <a href="manage_users.php?page=<?php echo $page; ?>"
                        class="flex-1 md:flex-initial flex items-center justify-center px-4 py-3 md:py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg md:rounded-md font-medium transition-colors min-h-[44px] min-w-[44px] select-none text-center">
                        Annulla
                    </a>
                <?php else: ?>
                    <button type="submit" name="add"
                        class="flex-1 md:flex-initial flex items-center justify-center px-4 py-3 md:py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg md:rounded-md font-medium transition-colors min-h-[44px] min-w-[44px] select-none">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        <span>Aggiungi</span>
                    </button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>