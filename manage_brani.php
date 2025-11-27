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

$result = $conn->query("SELECT COUNT(*) FROM Brani");
$total = $result->fetch_row()[0];
$total_pages = ceil($total / $limit);

$stmt = $conn->prepare("SELECT * FROM Brani ORDER BY Titolo LIMIT ? OFFSET ?");
$stmt->bind_param('ii', $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
$brani = $result->fetch_all(MYSQLI_ASSOC);
?>

<?php include 'includes/header.php'; ?>
<div class="max-w-6xl mx-auto">
    <h1 class="text-4xl font-bold text-center mb-8 text-gray-800">Gestione Brani</h1>
    <?php if ($message): ?>
        <div class="mb-4 p-4 rounded-md <?php echo strpos($message, 'eliminato') !== false || strpos($message, 'aggiunto') !== false || strpos($message, 'aggiornato') !== false ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'; ?>">
            <div class="flex">
                <div class="flex-shrink-0">
                    <?php if (strpos($message, 'eliminato') !== false || strpos($message, 'aggiunto') !== false || strpos($message, 'aggiornato') !== false): ?>
                        <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    <?php else: ?>
                        <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    <?php endif; ?>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium"><?php echo $message; ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="bg-white shadow-lg rounded-lg overflow-hidden mb-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">
            <?php foreach ($brani as $brano): ?>
                <div class="bg-gray-50 rounded-lg p-4 shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2"><?php echo sanitize($brano['Titolo']); ?></h3>
                    <p class="text-gray-600 mb-4">Tipologia: <?php echo sanitize($brano['Tipologia']); ?></p>
                    <div class="flex space-x-2">
                        <button onclick="editBrano(<?php echo $brano['Id']; ?>, '<?php echo addslashes($brano['Titolo']); ?>', '<?php echo $brano['Tipologia']; ?>')" class="inline-flex items-center px-3 py-1 text-sm font-medium text-orange-600 bg-orange-100 rounded-md hover:bg-orange-200 focus:outline-none focus:ring-2 focus:ring-orange-500">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Modifica
                        </button>
                        <a href="?delete=<?php echo $brano['Id']; ?>" onclick="return confirm('Sicuro di voler eliminare questo brano?')" class="inline-flex items-center px-3 py-1 text-sm font-medium text-red-600 bg-red-100 rounded-md hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-red-500">
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

    <div class="bg-white shadow-lg rounded-lg p-6">
        <h2 class="text-2xl font-bold mb-4 text-gray-800">Aggiungi/Modifica Brano</h2>
        <form method="post" id="branoForm">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="id" id="branoId">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="titolo" class="block text-sm font-medium text-gray-700 mb-1">Titolo</label>
                    <input type="text" name="titolo" id="titolo" placeholder="Titolo del brano" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500" required>
                </div>
                <div>
                    <label for="tipologia" class="block text-sm font-medium text-gray-700 mb-1">Tipologia</label>
                    <select name="tipologia" id="tipologia" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500" required>
                        <option value="Lode">Lode</option>
                        <option value="Adorazione">Adorazione</option>
                    </select>
                </div>
            </div>
            <div class="flex">
                <button type="submit" name="add" id="addBtn" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Aggiungi
                </button>
                <button type="submit" name="edit" id="editBtn" class="hidden inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Salva Modifiche
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
}
</script>
<?php include 'includes/footer.php'; ?>