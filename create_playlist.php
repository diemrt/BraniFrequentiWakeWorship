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
        $data = sanitize($_POST['data']);
        $dateObj = DateTime::createFromFormat('Y-m-d', $data);
        $oggi = new DateTime();
        if ($dateObj && $dateObj >= $oggi && in_array($dateObj->format('N'), ['5', '7'])) { // 5=ven, 7=dom
            if (isset($_POST['brani']) && is_array($_POST['brani'])) {
                $brani_selezionati = array_map('intval', $_POST['brani']);
                $stmt_check = $conn->prepare("SELECT Id FROM Brani WHERE Id = ?");
                $stmt_insert = $conn->prepare("INSERT INTO BraniSuonati (IdBrano, BranoSuonatoIl) VALUES (?, ?)");
                $inseriti = 0;
                foreach ($brani_selezionati as $id_brano) {
                    $stmt_check->bind_param('i', $id_brano);
                    $stmt_check->execute();
                    if ($stmt_check->get_result()->num_rows > 0) {
                        $stmt_insert->bind_param('is', $id_brano, $data);
                        $stmt_insert->execute();
                        $inseriti++;
                    }
                }
                $message = $inseriti . ' brani registrati per la scaletta';
            } else {
                $message = 'Nessun brano selezionato';
            }
        } else {
            $message = 'Data non valida (solo venerdì o domenica future)';
        }
    }
}

// Ottieni tutti i brani con ultima data suonata se entro un mese
$result = $conn->query("SELECT b.Id, b.Titolo, b.Tipologia, MAX(bs.BranoSuonatoIl) as UltimaData
                        FROM Brani b
                        LEFT JOIN BraniSuonati bs ON b.Id = bs.IdBrano
                        GROUP BY b.Id, b.Titolo, b.Tipologia
                        ORDER BY b.Titolo");
$brani = [];
while ($row = $result->fetch_assoc()) {
    $ultima_data = $row['UltimaData'];
    $warning = '';
    if ($ultima_data) {
        $ultima = new DateTime($ultima_data);
        $un_mese_fa = new DateTime();
        $un_mese_fa->modify('-1 month');
        if ($ultima >= $un_mese_fa) {
            $warning = 'Suonato l\'ultima volta il ' . $ultima->format('d/m/Y');
        }
    }
    $brani[] = [
        'id' => $row['Id'],
        'titolo' => $row['Titolo'],
        'tipologia' => $row['Tipologia'],
        'warning' => $warning
    ];
}
?>

<?php include 'includes/header.php'; ?>
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white shadow-lg rounded-lg p-6">
            <div class="flex items-center mb-6">
                <svg class="h-8 w-8 text-orange-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                </svg>
                <h1 class="text-2xl font-bold text-gray-900">Crea Scaletta</h1>
            </div>
            <p class="text-gray-600 mb-6">Seleziona una data futura (venerdì o domenica) e i brani per la scaletta. Verranno registrati come suonati in quella data.</p>

            <form method="post" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <div>
                    <label for="data" class="block text-sm font-medium text-gray-700 mb-2">Data della Scaletta</label>
                    <input type="date" name="data" id="data" min="<?php echo date('Y-m-d'); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500" required>
                    <p class="text-xs text-gray-500 mt-1">Solo venerdì e domeniche future</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Seleziona Brani</label>
                    <div class="max-h-96 overflow-y-auto border border-gray-300 rounded-md p-4 space-y-3">
                        <?php foreach ($brani as $brano): ?>
                            <div class="flex items-start space-x-3">
                                <input type="checkbox" name="brani[]" value="<?php echo $brano['id']; ?>" id="brano_<?php echo $brano['id']; ?>" class="mt-1">
                                <div class="flex-1">
                                    <label for="brano_<?php echo $brano['id']; ?>" class="text-sm font-medium text-gray-900 cursor-pointer">
                                        <?php echo sanitize($brano['titolo']); ?> (<?php echo sanitize($brano['tipologia']); ?>)
                                    </label>
                                    <?php if ($brano['warning']): ?>
                                        <p class="text-xs text-yellow-600 mt-1"><?php echo $brano['warning']; ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="rounded-md p-4 <?php echo strpos($message, 'registrati') !== false ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'; ?>">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <?php if (strpos($message, 'registrati') !== false): ?>
                                    <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                <?php else: ?>
                                    <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                            <?php endif; ?>
                            <div class="ml-3">
                                <p class="text-sm font-medium"><?php echo $message; ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div>
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition duration-150 ease-in-out">
                        <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Salva Scaletta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>