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

$title_search = $_GET['title'] ?? '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Handle delete confirmation request
if (isset($_GET['confirm_delete'])) {
    $id = (int)$_GET['confirm_delete'];
    $stmt = $conn->prepare("SELECT Titolo FROM Brani WHERE Id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $confirm_brano = $result->fetch_assoc();
}

$count_query = "SELECT COUNT(*) FROM Brani";
$params = [];
$types = '';

if (!empty($title_search)) {
    $count_query .= " WHERE Titolo LIKE ?";
    $params[] = '%' . $title_search . '%';
    $types .= 's';
}

$stmt_count = $conn->prepare($count_query);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$total = $result_count->fetch_row()[0];
$total_pages = ceil($total / $limit);

$select_query = "SELECT * FROM Brani";
if (!empty($title_search)) {
    $select_query .= " WHERE Titolo LIKE ?";
}
$select_query .= " ORDER BY Titolo LIMIT ? OFFSET ?";
$select_params = array_merge($params, [$limit, $offset]);
$select_types = $types . 'ii';

$stmt = $conn->prepare($select_query);
$stmt->bind_param($select_types, ...$select_params);
$stmt->execute();
$result = $stmt->get_result();
$brani = $result->fetch_all(MYSQLI_ASSOC);

$query_string = http_build_query(['title' => $title_search]);

// Get brano for editing if edit_id is set
$edit_brano = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM Brani WHERE Id = ?");
    $stmt->bind_param('i', $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_brano = $result->fetch_assoc();
}
?>

<?php include 'includes/header.php'; ?>
<div class="max-w-6xl mx-auto">
    <h1 class="text-3xl md:text-4xl font-bold text-center mb-6 md:mb-8 text-gray-800">Gestione Brani</h1>
    
    <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'; ?>">
            <p class="<?php echo $message_type === 'success' ? 'text-green-800' : 'text-red-800'; ?> text-sm md:text-base"><?php echo sanitize($message); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['confirm_delete']) && $confirm_brano): ?>
        <div class="fixed inset-0 bg-black/50 z-50 flex items-end md:items-center md:justify-center" id="delete-modal">
            <div class="bg-white w-full md:w-96 rounded-t-2xl md:rounded-2xl p-6 md:p-8 space-y-6 max-h-96 overflow-y-auto">
                <h2 class="text-xl md:text-2xl font-bold text-center text-gray-900">Eliminare il brano?</h2>
                <p class="text-gray-700 text-sm md:text-base">
                    Sei sicuro di voler eliminare "<?php echo sanitize($confirm_brano['Titolo']); ?>"?
                </p>
                <div class="flex gap-4">
                    <a href="manage_brani.php?title=<?php echo urlencode($title_search); ?>&page=<?php echo $page; ?>" 
                       class="flex-1 px-4 py-3 md:py-4 bg-gray-100 hover:bg-gray-200 rounded-lg md:rounded-xl font-medium text-gray-900 text-center transition-colors">
                        Annulla
                    </a>
                    <a href="loading.php?action=delete_brano&delete=<?php echo $confirm_brano['Id'] ?? $_GET['confirm_delete']; ?>&confirmed=1&title=<?php echo urlencode($title_search); ?>&page=<?php echo $page; ?>" 
                       class="flex-1 px-4 py-3 md:py-4 bg-red-600 hover:bg-red-700 text-white rounded-lg md:rounded-xl font-medium text-center transition-colors">
                        Elimina
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Search Form -->
    <div class="mb-6 md:mb-8">
        <form method="GET" class="bg-gray-100 md:bg-transparent p-4 md:p-0 rounded-lg md:rounded-none space-y-4 md:space-y-0">
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Cerca per titolo</label>
                    <input type="text" id="title" name="title" value="<?php echo sanitize($title_search); ?>" 
                           placeholder="Es: Amazing Grace"
                           class="w-full px-3 py-2 md:py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500 text-base min-h-[44px]">
                </div>
                <div class="flex gap-2 md:items-end">
                    <button type="submit" class="flex-1 md:flex-initial px-4 py-2 md:py-3 bg-orange-600 hover:bg-orange-700 text-white rounded-md font-medium transition-colors min-h-[44px] min-w-[44px] inline-flex items-center justify-center select-none">Cerca</button>
                    <a href="manage_brani.php" class="flex-1 md:flex-initial text-center px-4 py-2 md:py-3 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-md font-medium transition-colors min-h-[44px] min-w-[44px] inline-flex items-center justify-center select-none">Reset</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Brani List - Mobile Optimized Cards -->
    <div class="space-y-3 md:space-y-0 md:grid md:grid-cols-2 md:gap-6 mb-8">
        <?php if (empty($brani)): ?>
            <div class="col-span-2 text-center py-12">
                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 15c-2.34 0-4.29-.98-5.5-2.5"></path>
                </svg>
                <p class="text-gray-500 text-lg">Nessun brano trovato.</p>
            </div>
        <?php else: ?>
            <?php foreach ($brani as $brano): ?>
                <div class="bg-white rounded-lg border border-gray-200 p-4 md:p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm md:text-base font-semibold text-gray-900 break-words"><?php echo sanitize($brano['Titolo']); ?></h3>
                            <p class="text-xs md:text-sm text-gray-600 mt-1">
                                <span class="inline-block px-2 py-1 bg-gray-100 rounded text-gray-700">
                                    <?php echo sanitize($brano['Tipologia']); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Action Buttons - Always Visible -->
                    <div class="flex gap-2 mt-3">
                        <a href="?edit_id=<?php echo $brano['Id']; ?>&title=<?php echo urlencode($title_search); ?>&page=<?php echo $page; ?>" 
                           class="flex-1 flex items-center justify-center space-x-1 px-3 py-2 bg-orange-100 text-orange-700 rounded-lg hover:bg-orange-200 transition-colors text-sm min-h-[44px] select-none">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            <span>Modifica</span>
                        </a>
                        <a href="?confirm_delete=<?php echo $brano['Id']; ?>&title=<?php echo urlencode($title_search); ?>&page=<?php echo $page; ?>" 
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
            <a href="?<?php echo $query_string; ?>&page=<?php echo $page - 1; ?>" class="flex items-center px-3 py-2 md:px-4 md:py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
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
            <a href="?<?php echo $query_string; ?>&page=<?php echo $page + 1; ?>" class="flex items-center px-3 py-2 md:px-4 md:py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                <span class="hidden md:inline">Successivo</span>
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Add/Edit Form -->
    <div class="bg-white rounded-lg shadow-lg border border-gray-200 p-4 md:p-6" id="branoForm">
        <h2 class="text-xl md:text-2xl font-bold mb-6 text-gray-800"><?php echo $edit_brano ? 'Modifica' : 'Aggiungi'; ?> Brano</h2>
        <form method="post" action="loading.php">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="action" value="manage_brani">
            <input type="hidden" name="title_search" value="<?php echo sanitize($title_search); ?>">
            <input type="hidden" name="page" value="<?php echo $page; ?>">
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
                    <a href="manage_brani.php?title=<?php echo urlencode($title_search); ?>&page=<?php echo $page; ?>"
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