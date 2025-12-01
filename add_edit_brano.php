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

$edit_brano = null;
$page_title = 'Aggiungi brano';

// Check if editing existing song
if (isset($_GET['id'])) {
    $edit_id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM Brani WHERE Id = ?");
    $stmt->bind_param('i', $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_brano = $result->fetch_assoc();
    if ($edit_brano) {
        $page_title = 'Modifica brano';
    }
}
?>

<?php include 'includes/header.php'; ?>
<div class="max-w-2xl mx-auto mt-4 lg:mt-0">
    <div class="flex items-center mb-4">
        <a href="navigate.php?to=manage_brani.php" class="mr-3 text-gray-600 hover:text-gray-900">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </a>
        <svg class="h-6 w-6 text-orange-600 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
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
            <input type="hidden" name="action" value="manage_brani">
            <?php if ($edit_brano): ?>
                <input type="hidden" name="id" value="<?php echo $edit_brano['Id']; ?>">
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label for="titolo" class="block text-sm font-medium text-gray-700 mb-2">Titolo</label>
                    <input type="text" name="titolo" id="titolo" placeholder="Titolo del brano"
                        value="<?php echo $edit_brano ? sanitize($edit_brano['Titolo']) : ''; ?>"
                        class="w-full px-3 py-2 md:py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500 text-base min-h-[44px]"
                        required>
                </div>
                <div>
                    <label for="tipologia" class="block text-sm font-medium text-gray-700 mb-2">Tipologia</label>
                    <select name="tipologia" id="tipologia"
                        class="w-full px-3 py-2 md:py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500 text-base min-h-[44px]"
                        required>
                        <option value="Lode" <?php echo ($edit_brano && $edit_brano['Tipologia'] == 'Lode') ? 'selected' : ''; ?>>Lode</option>
                        <option value="Adorazione" <?php echo ($edit_brano && $edit_brano['Tipologia'] == 'Adorazione') ? 'selected' : ''; ?>>Adorazione</option>
                    </select>
                </div>
            </div>

            <div class="flex flex-col md:flex-row gap-3">
                <?php if ($edit_brano): ?>
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
                <a href="navigate.php?to=manage_brani.php"
                    class="flex-1 md:flex-initial flex items-center justify-center px-4 py-3 md:py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg md:rounded-md font-medium transition-colors min-h-[44px] min-w-[44px] select-none text-center">
                    Annulla
                </a>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
