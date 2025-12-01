<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (!is_logged_in() || !is_admin()) {
    $_SESSION['message'] = 'Solo gli Admin possono accedere a questa pagina';
    $_SESSION['message_type'] = 'error';
    header('Location: manage_users.php');
    exit;
}

$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? 'info';
if ($message) {
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

$edit_utente = null;
$page_title = 'Aggiungi utente';

// Check if editing existing user
if (isset($_GET['id'])) {
    $edit_id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM Utenti WHERE Id = ?");
    $stmt->bind_param('i', $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_utente = $result->fetch_assoc();
    if ($edit_utente) {
        $page_title = 'Modifica utente';
    }
}
?>

<?php include 'includes/header.php'; ?>
<div class="max-w-2xl mx-auto mt-4 lg:mt-0">
    <div class="flex items-center mb-4">
        <a href="navigate.php?to=manage_users.php" class="mr-3 text-gray-600 hover:text-gray-900">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </a>
        <svg class="h-6 w-6 text-orange-600 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
        </svg>
        <h1 class="text-xl font-bold text-gray-900"><?php echo $page_title; ?></h1>
    </div>

    <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'; ?>">
            <p class="<?php echo $message_type === 'success' ? 'text-green-800' : 'text-red-800'; ?> text-sm md:text-base"><?php echo sanitize($message); ?></p>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow-lg border border-gray-200 p-4 md:p-6">
        <form method="post" action="loading.php">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="action" value="manage_users">
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
                <?php else: ?>
                    <button type="submit" name="add"
                        class="flex-1 md:flex-initial flex items-center justify-center px-4 py-3 md:py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg md:rounded-md font-medium transition-colors min-h-[44px] min-w-[44px] select-none">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        <span>Aggiungi</span>
                    </button>
                <?php endif; ?>
                <a href="navigate.php?to=manage_users.php"
                    class="flex-1 md:flex-initial flex items-center justify-center px-4 py-3 md:py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg md:rounded-md font-medium transition-colors min-h-[44px] min-w-[44px] select-none text-center">
                    Annulla
                </a>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
