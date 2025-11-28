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

$stmt = $conn->prepare("SELECT Id, Username FROM Utenti ORDER BY Username LIMIT ? OFFSET ?");
$stmt->bind_param('ii', $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
$utenti = $result->fetch_all(MYSQLI_ASSOC);
?>

<?php include 'includes/header.php'; ?>
<div class="max-w-6xl mx-auto">
    <h1 class="text-3xl md:text-4xl font-bold text-center mb-6 md:mb-8 text-gray-800">Gestione Utenti</h1>
    
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

    <div class="bg-white shadow-lg rounded-lg overflow-hidden mb-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">
            <?php foreach ($utenti as $utente): ?>
                <div class="bg-gray-50 rounded-lg p-4 shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2"><?php echo sanitize($utente['Username']); ?></h3>
                    <div class="flex space-x-2">
                        <a href="?edit_id=<?php echo $utente['Id']; ?>&page=<?php echo $page; ?>" class="inline-flex items-center px-3 py-1 text-sm font-medium text-orange-600 bg-orange-100 rounded-md hover:bg-orange-200 focus:outline-none focus:ring-2 focus:ring-orange-500 min-h-[44px] min-w-[44px] select-none">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Modifica
                        </a>
                        <a href="?confirm_delete=<?php echo $utente['Id']; ?>&page=<?php echo $page; ?>" class="inline-flex items-center px-3 py-1 text-sm font-medium text-red-600 bg-red-100 rounded-md hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-red-500 min-h-[44px] min-w-[44px] select-none">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            Elimina
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="flex justify-center space-x-2 mb-8">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>" class="flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Precedente
            </a>
        <?php endif; ?>
        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
            <a href="?page=<?php echo $i; ?>" class="px-4 py-2 text-sm font-medium rounded-md <?php echo $i == $page ? 'text-orange-600 bg-orange-50 border border-orange-500' : 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-50'; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
        <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?>" class="flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                Successivo
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </a>
        <?php endif; ?>
    </div>

    <div class="bg-white shadow-lg rounded-lg p-6" id="utenteForm">
        <h2 class="text-xl md:text-2xl font-bold mb-6 text-gray-800"><?php echo $edit_utente ? 'Modifica' : 'Aggiungi'; ?> Utente</h2>
        <form method="post" action="loading.php">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="action" value="manage_users">
            <input type="hidden" name="page" value="<?php echo $page; ?>">
            <?php if ($edit_utente): ?>
                <input type="hidden" name="id" value="<?php echo $edit_utente['Id']; ?>">
            <?php endif; ?>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" name="username" id="username" placeholder="Username" 
                           value="<?php echo $edit_utente ? sanitize($edit_utente['Username']) : ''; ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500 text-base min-h-[44px]" required>
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password<?php echo $edit_utente ? ' (lascia vuoto per non modificare)' : ''; ?></label>
                    <input type="password" name="password" id="password" placeholder="Password" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500 text-base min-h-[44px]" 
                           <?php echo $edit_utente ? '' : 'required'; ?>>
                </div>
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Conferma Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Conferma Password" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500 text-base min-h-[44px]" 
                           <?php echo $edit_utente ? '' : 'required'; ?>>
                </div>
            </div>
            <div class="flex gap-3">
                <?php if ($edit_utente): ?>
                    <button type="submit" name="edit" 
                            class="flex-1 md:flex-initial inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 min-h-[44px] min-w-[44px] select-none">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Salva Modifiche
                    </button>
                    <a href="manage_users.php?page=<?php echo $page; ?>"
                       class="flex-1 md:flex-initial inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-gray-800 bg-gray-300 hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 min-h-[44px] min-w-[44px] select-none">
                        Annulla
                    </a>
                <?php else: ?>
                    <button type="submit" name="add" 
                            class="flex-1 md:flex-initial inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 min-h-[44px] min-w-[44px] select-none">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Aggiungi
                    </button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>