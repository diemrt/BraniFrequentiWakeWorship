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
                $checked = array_map('intval', $_POST['brani']);
                $ordered = isset($_POST['brani_order']) && $_POST['brani_order'] ? array_map('intval', explode(',', $_POST['brani_order'])) : [];
                $brani_selezionati = array_intersect($ordered, $checked);
                $stmt_check = $conn->prepare("SELECT Id FROM Brani WHERE Id = ?");
                $stmt_insert = $conn->prepare("INSERT INTO BraniSuonati (IdBrano, BranoSuonatoIl, OrdineEsecuzione) VALUES (?, ?, ?)");
                $inseriti = 0;
                $ordine = 1;
                foreach ($brani_selezionati as $id_brano) {
                    $stmt_check->bind_param('i', $id_brano);
                    $stmt_check->execute();
                    if ($stmt_check->get_result()->num_rows > 0) {
                        $stmt_insert->bind_param('isi', $id_brano, $data, $ordine);
                        $stmt_insert->execute();
                        $inseriti++;
                        $ordine++;
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
<div class="container mx-auto px-3 py-4 pb-32 md:pb-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white shadow-lg rounded-lg p-4">
            <div class="flex items-center mb-4">
                <svg class="h-6 w-6 text-orange-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                </svg>
                <h1 class="text-xl font-bold text-gray-900">Crea Scaletta</h1>
            </div>

            <form method="post" class="space-y-4" id="playlist-form">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="brani_order" id="brani_order" value="">

                <div>
                    <label for="data" class="block text-sm font-medium text-gray-700 mb-2">Data della Scaletta</label>
                    <input type="date" name="data" id="data" min="<?php echo date('Y-m-d'); ?>" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-base min-h-[48px]" required>
                    <p class="text-xs text-gray-500 mt-1">Solo ven/dom future</p>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-3">
                        <label class="block text-sm font-medium text-gray-700">Seleziona Brani</label>
                        <span id="counter" class="text-xs font-semibold text-orange-600 bg-orange-50 px-2 py-1 rounded-full">0 selezionati</span>
                    </div>
                    <div class="space-y-2">
                        <?php foreach ($brani as $brano): ?>
                            <label for="brano_<?php echo $brano['id']; ?>" class="block cursor-pointer">
                                <input type="checkbox" name="brani[]" value="<?php echo $brano['id']; ?>" id="brano_<?php echo $brano['id']; ?>" class="hidden peer">
                                <div class="min-h-[48px] p-3 border-2 border-gray-200 rounded-lg peer-checked:border-orange-500 peer-checked:bg-orange-50 active:scale-[0.98] transition-all">
                                    <div class="font-medium text-sm text-gray-900">
                                        <?php echo sanitize($brano['titolo']); ?>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-0.5">
                                        <?php echo sanitize($brano['tipologia']); ?>
                                    </div>
                                    <?php if ($brano['warning']): ?>
                                        <div class="text-xs text-yellow-600 mt-1 flex items-center">
                                            <svg class="h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            <?php echo $brano['warning']; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>


            </form>
        </div>
    </div>
</div>

<!-- Fixed Bottom Button (Above Mobile Nav) -->
<div class="fixed bottom-20 md:bottom-4 left-0 right-0 px-3 z-50">
    <button type="submit" form="playlist-form" class="w-full flex items-center justify-center px-4 py-3 text-base font-semibold rounded-lg text-white bg-orange-600 active:bg-orange-700 min-h-[48px] select-none disabled:opacity-50 disabled:cursor-not-allowed transition-colors shadow-2xl">
        <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        Salva Scaletta
    </button>
</div>
<script>
let selectedOrder = [];
const checkboxes = document.querySelectorAll('input[name="brani[]"]');
const counter = document.getElementById('counter');
const submitBtn = document.querySelector('button[type="submit"]');

function updateUI() {
    const count = selectedOrder.length;
    counter.textContent = `${count} selezionati`;
    document.getElementById('brani_order').value = selectedOrder.join(',');
    submitBtn.disabled = count === 0;
}

checkboxes.forEach(cb => cb.addEventListener('change', function() {
    const id = cb.value;
    if (cb.checked) {
        if (!selectedOrder.includes(id)) selectedOrder.push(id);
    } else {
        selectedOrder = selectedOrder.filter(item => item !== id);
    }
    updateUI();
}));

updateUI();
</script>
<?php if ($message): ?>
<script>
window.addEventListener('DOMContentLoaded', () => {
    new Toast('<?php echo htmlspecialchars($message, ENT_QUOTES); ?>', '<?php echo strpos($message, 'registrati') !== false ? 'success' : 'error'; ?>');
});
</script>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>