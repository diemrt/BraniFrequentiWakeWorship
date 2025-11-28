<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$message = '';
$title_search = $_GET['title'] ?? '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Check if associated
    $stmt = $conn->prepare("SELECT COUNT(*) FROM BraniSuonati WHERE IdBrano = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_row()[0];
    if ($count == 0) {
        $stmt = $conn->prepare("DELETE FROM Brani WHERE Id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $message = 'Brano eliminato';
    } else {
        $message = 'Impossibile eliminare, associato a registrazioni';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Token CSRF invalido';
    } elseif (isset($_POST['add'])) {
        $titolo = sanitize($_POST['titolo']);
        $tipologia = sanitize($_POST['tipologia']);
        if (!empty($titolo) && in_array($tipologia, ['Lode', 'Adorazione'])) {
            $stmt = $conn->prepare("INSERT INTO Brani (titolo, tipologia) VALUES (?, ?)");
            $stmt->bind_param('ss', $titolo, $tipologia);
            $stmt->execute();
            $message = 'Brano aggiunto';
        } else {
            $message = 'Dati invalidi';
        }
    } elseif (isset($_POST['edit'])) {
        $id = (int)$_POST['id'];
        $titolo = sanitize($_POST['titolo']);
        $tipologia = sanitize($_POST['tipologia']);
        if (!empty($titolo) && in_array($tipologia, ['Lode', 'Adorazione'])) {
            $stmt = $conn->prepare("UPDATE Brani SET titolo = ?, tipologia = ? WHERE id = ?");
            $stmt->bind_param('ssi', $titolo, $tipologia, $id);
            $stmt->execute();
            $message = 'Brano aggiornato';
        } else {
            $message = 'Dati invalidi';
        }
    }
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
?>

<?php include 'includes/header.php'; ?>
<div class="max-w-6xl mx-auto">
    <h1 class="text-3xl md:text-4xl font-bold text-center mb-6 md:mb-8 text-gray-800">Gestione Brani</h1>
    
    <?php if ($message): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                new Toast('<?php echo htmlspecialchars($message, ENT_QUOTES); ?>', '<?php echo strpos($message, 'eliminato') !== false || strpos($message, 'aggiunto') !== false || strpos($message, 'aggiornato') !== false ? 'success' : 'error'; ?>');
            });
        </script>
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
                        <button onclick="editBrano(<?php echo $brano['Id']; ?>, '<?php echo addslashes($brano['Titolo']); ?>', '<?php echo addslashes($brano['Tipologia']); ?>')" 
                                class="flex-1 flex items-center justify-center space-x-1 px-3 py-2 bg-orange-100 text-orange-700 rounded-lg hover:bg-orange-200 transition-colors text-sm min-h-[44px] select-none">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            <span>Modifica</span>
                        </button>
                        <button onclick="deleteBrano(<?php echo $brano['Id']; ?>, '<?php echo addslashes($brano['Titolo']); ?>')" 
                                class="flex-1 flex items-center justify-center space-x-1 px-3 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors text-sm min-h-[44px] select-none">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            <span>Elimina</span>
                        </button>
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
    <div class="bg-white rounded-lg shadow-lg border border-gray-200 p-4 md:p-6">
        <h2 class="text-xl md:text-2xl font-bold mb-6 text-gray-800">Aggiungi/Modifica Brano</h2>
        <form method="post" id="branoForm">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="id" id="branoId">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label for="titolo" class="block text-sm font-medium text-gray-700 mb-2">Titolo</label>
                    <input type="text" name="titolo" id="titolo" placeholder="Titolo del brano" 
                           class="w-full px-3 py-2 md:py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500 text-base min-h-[44px]" 
                           required>
                </div>
                <div>
                    <label for="tipologia" class="block text-sm font-medium text-gray-700 mb-2">Tipologia</label>
                    <select name="tipologia" id="tipologia" 
                            class="w-full px-3 py-2 md:py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500 text-base min-h-[44px]" 
                            required>
                        <option value="Lode">Lode</option>
                        <option value="Adorazione">Adorazione</option>
                    </select>
                </div>
            </div>

            <div class="flex flex-col md:flex-row gap-3">
                <button type="submit" name="add" id="addBtn" 
                        class="flex-1 md:flex-initial flex items-center justify-center px-4 py-3 md:py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg md:rounded-md font-medium transition-colors min-h-[44px] min-w-[44px] select-none">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    <span>Aggiungi</span>
                </button>
                <button type="submit" name="edit" id="editBtn" 
                        class="hidden flex-1 md:flex-initial flex items-center justify-center px-4 py-3 md:py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg md:rounded-md font-medium transition-colors min-h-[44px] min-w-[44px] select-none">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>Salva Modifiche</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function editBrano(id, titolo, tipologia) {
    document.getElementById('branoId').value = id;
    document.getElementById('titolo').value = titolo;
    document.getElementById('tipologia').value = tipologia;
    document.getElementById('addBtn').classList.add('hidden');
    document.getElementById('editBtn').classList.remove('hidden');
    document.getElementById('titolo').focus();
    // Scroll to form
    document.getElementById('branoForm').scrollIntoView({ behavior: 'smooth' });
}

function deleteBrano(id, title) {
    showDeleteModal('Eliminare il brano?', 
        `Sei sicuro di voler eliminare "${title}"?`,
        () => {
            const params = new URLSearchParams(window.location.search);
            params.set('delete', id);
            window.location.href = 'manage_brani.php?' + params.toString();
        });
}
</script>

<?php include 'includes/footer.php'; ?>