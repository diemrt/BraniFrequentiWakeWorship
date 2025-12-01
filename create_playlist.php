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
<div class="container mx-auto px-3 mt-4 lg:mt-0 pb-32 md:pb-8">
    <div class="max-w-2xl mx-auto">
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'; ?>">
                <p class="<?php echo $message_type === 'success' ? 'text-green-800' : 'text-red-800'; ?> text-sm md:text-base"><?php echo sanitize($message); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="bg-white shadow-lg rounded-lg p-4">
            <div class="flex items-center mb-4">
                <svg class="h-6 w-6 text-orange-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                </svg>
                <h1 class="text-xl font-bold text-gray-900">Crea Scaletta</h1>
            </div>

            <form method="post" class="space-y-4" id="playlist-form" action="loading.php">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="action" value="create_playlist">

                <div>
                    <label for="data" class="block text-sm font-medium text-gray-700 mb-2">Data della Scaletta</label>
                    <input type="date" name="data" id="data" min="<?php echo date('Y-m-d'); ?>" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-base min-h-[48px]" required>
                    <p class="text-xs text-gray-500 mt-1">Solo ven/dom future</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Aggiungi brano</label>
                    <div class="flex flex-col md:flex-row gap-2 md:gap-3">
                        <select id="add-select" class="flex-1 px-4 py-3 border-2 border-gray-300 rounded-lg text-base min-h-[48px] cursor-pointer focus:outline-none focus:ring-2 focus:ring-orange-500">
                            <option value="">-- Seleziona brano --</option>
                            <?php foreach ($brani as $brano): ?>
                                <option value="<?php echo $brano['id']; ?>" data-warning="<?php echo sanitize($brano['warning']); ?>">
                                    <?php echo sanitize($brano['titolo']); ?> — <?php echo sanitize($brano['tipologia']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" id="add-btn" class="px-4 py-3 bg-orange-600 text-white font-medium rounded-lg hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 min-h-[48px] transition-colors select-none">Aggiungi</button>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Scaletta (ordine)</label>
                    <ul id="playlist-list" class="space-y-2">
                        <!-- Items saranno renderizzati da JS -->
                    </ul>
                    <p class="text-xs text-gray-500 mt-2">Usa le frecce per spostare i brani o il pulsante rimuovi per eliminarli.</p>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full flex items-center justify-center px-4 py-3 text-base font-semibold rounded-lg text-white bg-orange-600 hover:bg-orange-700 active:bg-orange-800 min-h-[48px] select-none transition-colors">
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

<script>
// Playlist management: add / remove / reorder and submit brani[] in order
(function() {
    var selected = [];
    var allSongs = <?php echo json_encode($brani, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?> || [];

    var listEl = document.getElementById('playlist-list');
    var addSelect = document.getElementById('add-select');
    var addBtn = document.getElementById('add-btn');
    var form = document.getElementById('playlist-form');

    function render() {
        // render list
        listEl.innerHTML = '';
        selected.forEach(function(item, idx) {
            var li = document.createElement('li');
            li.className = 'p-3 border-2 border-gray-200 rounded-lg flex items-center justify-between';
            var left = document.createElement('div');
            left.className = 'flex-1';
            var html = '<div class="font-medium">' + escapeHtml(item.titolo) + '</div><div class="text-xs text-gray-500">' + escapeHtml(item.tipologia) + '</div>';
            if (item.warning) {
                html += '<div class="text-xs text-yellow-600 mt-1 flex items-center"><svg class="h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>' + escapeHtml(item.warning) + '</div>';
            }
            left.innerHTML = html;
            var controls = document.createElement('div');
            controls.className = 'flex items-center gap-2';

            var up = document.createElement('button');
            up.type = 'button';
            up.className = 'px-2 py-1 bg-gray-100 rounded';
            up.title = 'Su';
            up.innerHTML = '↑';
            up.disabled = idx === 0;
            up.addEventListener('click', function(){ moveUp(idx); });

            var down = document.createElement('button');
            down.type = 'button';
            down.className = 'px-2 py-1 bg-gray-100 rounded';
            down.title = 'Giù';
            down.innerHTML = '↓';
            down.disabled = idx === selected.length - 1;
            down.addEventListener('click', function(){ moveDown(idx); });

            var remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'px-2 py-1 bg-red-600 text-white rounded';
            remove.title = 'Rimuovi';
            remove.innerHTML = 'Rimuovi';
            remove.addEventListener('click', function(){ removeAt(idx); });

            controls.appendChild(up);
            controls.appendChild(down);
            controls.appendChild(remove);

            li.appendChild(left);
            li.appendChild(controls);
            listEl.appendChild(li);
        });
        refreshAddOptions();
    }

    function refreshAddOptions() {
        // remove already selected from add select
        var selIds = selected.map(function(s){ return String(s.id); });
        // rebuild options keeping placeholder
        var html = '<option value="">-- Seleziona brano --</option>';
        allSongs.forEach(function(s){
            if (selIds.indexOf(String(s.id)) === -1) {
                html += '<option value="'+escapeHtml(s.id)+'" data-warning="'+escapeHtml(s.warning)+'">'+escapeHtml(s.titolo)+' — '+escapeHtml(s.tipologia)+'</option>';
            }
        });
        addSelect.innerHTML = html;
    }

    function addSelected() {
        var val = addSelect.value;
        if (!val) return;
        var song = allSongs.find(function(s){ return String(s.id) === String(val); });
        if (!song) return;
        selected.push({id: song.id, titolo: song.titolo, tipologia: song.tipologia, warning: song.warning});
        render();
    }

    function moveUp(idx) {
        if (idx <= 0) return;
        var tmp = selected[idx-1]; selected[idx-1] = selected[idx]; selected[idx] = tmp;
        render();
    }
    function moveDown(idx) {
        if (idx >= selected.length-1) return;
        var tmp = selected[idx+1]; selected[idx+1] = selected[idx]; selected[idx] = tmp;
        render();
    }
    function removeAt(idx) {
        selected.splice(idx,1);
        render();
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // submit handler: create hidden inputs brani[] in order
    if (form) {
        form.addEventListener('submit', function(e){
            // remove any existing brani[] inputs
            var existing = form.querySelectorAll('input[name="brani[]"]');
            existing.forEach(function(n){ n.parentNode.removeChild(n); });
            // append in order
            selected.forEach(function(item){
                var inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'brani[]';
                inp.value = item.id;
                form.appendChild(inp);
            });
        });
    }

    if (addBtn) addBtn.addEventListener('click', addSelected);

    // initial render
    render();
})();
</script>

<?php include 'includes/footer.php'; ?>