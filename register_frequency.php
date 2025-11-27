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
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-green-100">
                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">Registra Frequenza Brano</h2>
            <p class="mt-2 text-center text-sm text-gray-600">Seleziona un brano e la data (solo venerdì o domenica)</p>
        </div>
        <form class="mt-8 space-y-6 bg-white py-8 px-6 shadow-lg rounded-lg" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <div class="space-y-4">
                <div>
                    <label for="id_brano" class="block text-sm font-medium text-gray-700 mb-1">Seleziona Brano</label>
                    <select name="id_brano" id="id_brano" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500" required>
                        <option value="">Seleziona un brano</option>
                        <?php foreach ($brani as $brano): ?>
                            <option value="<?php echo $brano['id']; ?>"><?php echo sanitize($brano['titolo']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="data" class="block text-sm font-medium text-gray-700 mb-1">Data</label>
                    <input type="date" name="data" id="data" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500" required>
                </div>
            </div>
            <?php if ($message): ?>
                <div class="rounded-md p-4 <?php echo strpos($message, 'registrata') !== false ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'; ?>">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <?php if (strpos($message, 'registrata') !== false): ?>
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
            <div>
                <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150 ease-in-out">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <svg class="h-5 w-5 text-green-500 group-hover:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </span>
                    Registra Frequenza
                </button>
            </div>
        </form>
    </div>
</div>
<?php include 'includes/footer.php'; ?>