<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Token CSRF invalido';
    } else {
        $id_brano = (int)$_POST['id_brano'];
        $data = sanitize($_POST['data']);
        $dateObj = DateTime::createFromFormat('Y-m-d', $data);
        if ($dateObj && in_array($dateObj->format('N'), ['5', '7'])) { // 5=ven, 7=dom
            $stmt = $conn->prepare("SELECT COUNT(*) FROM Brani WHERE id = ?");
            $stmt->bind_param('i', $id_brano);
            $stmt->execute();
            $result = $stmt->get_result();
            $count = $result->fetch_row()[0];
            if ($count > 0) {
                $stmt = $conn->prepare("INSERT INTO BraniSuonati (id_brano, data) VALUES (?, ?)");
                $stmt->bind_param('is', $id_brano, $data);
                $stmt->execute();
                $message = 'Frequenza registrata';
            } else {
                $message = 'Brano non esistente';
            }
        } else {
            $message = 'Data non valida (solo venerdì o domenica)';
        }
    }
}

$result = $conn->query("SELECT id, titolo FROM Brani ORDER BY titolo");
$brani = $result->fetch_all(MYSQLI_ASSOC);
?>

<?php include 'includes/header.php'; ?>
<h1 class="text-3xl mb-4">Registra Frequenza</h1>
<?php if ($message): ?>
    <p class="text-green-500"><?php echo $message; ?></p>
<?php endif; ?>

<form method="post" class="bg-white p-4 rounded shadow">
    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
    <select name="id_brano" class="w-full p-2 mb-2 border" required>
        <option value="">Seleziona Brano</option>
        <?php foreach ($brani as $brano): ?>
            <option value="<?php echo $brano['id']; ?>"><?php echo sanitize($brano['titolo']); ?></option>
        <?php endforeach; ?>
    </select>
    <input type="date" name="data" class="w-full p-2 mb-2 border" required>
    <button type="submit" class="bg-blue-600 text-white p-2">Registra</button>
</form>
<?php include 'includes/footer.php'; ?>