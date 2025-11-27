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
    header('Location: manage_brani.php?page=' . $page);
    exit;
}

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
<h1 class="text-3xl mb-4">Gestione Brani</h1>
<?php if ($message): ?>
    <p class="text-green-500"><?php echo $message; ?></p>
<?php endif; ?>

<table class="w-full bg-white shadow rounded">
    <thead>
        <tr class="bg-gray-200">
            <th class="p-2">Titolo</th>
            <th class="p-2">Tipologia</th>
            <th class="p-2">Azioni</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($brani as $brano): ?>
            <tr>
                <td class="p-2"><?php echo sanitize($brano['Titolo']); ?></td>
                <td class="p-2"><?php echo sanitize($brano['Tipologia']); ?></td>
                <td class="p-2">
                    <button onclick="editBrano(<?php echo $brano['Id']; ?>, '<?php echo addslashes($brano['Titolo']); ?>', '<?php echo $brano['Tipologia']; ?>')">Modifica</button>
                    <a href="?delete=<?php echo $brano['Id']; ?>" onclick="return confirm('Sicuro?')">Elimina</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="mt-4 flex justify-center space-x-2">
    <?php if ($page > 1): ?>
        <a href="?page=<?php echo $page - 1; ?>" class="bg-blue-600 text-white px-4 py-2 rounded">Precedente</a>
    <?php endif; ?>
    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
        <a href="?page=<?php echo $i; ?>" class="px-4 py-2 rounded <?php echo $i == $page ? 'bg-gray-300' : 'bg-white border'; ?>"><?php echo $i; ?></a>
    <?php endfor; ?>
    <?php if ($page < $total_pages): ?>
        <a href="?page=<?php echo $page + 1; ?>" class="bg-blue-600 text-white px-4 py-2 rounded">Successivo</a>
    <?php endif; ?>
</div>

<form method="post" id="branoForm" class="mt-4 bg-white p-4 rounded shadow">
    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
    <input type="hidden" name="id" id="branoId">
    <input type="text" name="titolo" id="titolo" placeholder="Titolo" class="w-full p-2 mb-2 border" required>
    <select name="tipologia" id="tipologia" class="w-full p-2 mb-2 border" required>
        <option value="Lode">Lode</option>
        <option value="Adorazione">Adorazione</option>
    </select>
    <button type="submit" name="add" id="addBtn" class="bg-blue-600 text-white p-2">Aggiungi</button>
    <button type="submit" name="edit" id="editBtn" class="bg-green-600 text-white p-2 hidden">Salva Modifiche</button>
</form>

<script>
function editBrano(id, titolo, tipologia) {
    document.getElementById('branoId').value = id;
    document.getElementById('titolo').value = titolo;
    document.getElementById('tipologia').value = tipologia;
    document.getElementById('addBtn').classList.add('hidden');
    document.getElementById('editBtn').classList.remove('hidden');
}
</script>
<?php include 'includes/footer.php'; ?>