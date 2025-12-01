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

// Ottieni tutte le date uniche di scalette passate dai dati in BraniSuonati
$result_dates = $conn->query("SELECT DISTINCT BranoSuonatoIl 
                               FROM BraniSuonati 
                               WHERE BranoSuonatoIl IS NOT NULL
                               ORDER BY BranoSuonatoIl DESC");
$dates = [];
while ($row = $result_dates->fetch_assoc()) {
    $dates[] = [
        'data' => $row['BranoSuonatoIl'],
        'formatted' => (new DateTime($row['BranoSuonatoIl']))->format('d/m/Y')
    ];
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

// Se è stata selezionata una data, carica i brani per quella scaletta
$selected_date = $_GET['date'] ?? $_GET['data'] ?? '';
$selected_songs = [];
if ($selected_date && DateTime::createFromFormat('Y-m-d', $selected_date)) {
    $stmt_sel = $conn->prepare("SELECT bs.IdBrano, bs.OrdineEsecuzione, b.Titolo, b.Tipologia
                                FROM BraniSuonati bs
                                JOIN Brani b ON bs.IdBrano = b.Id
                                WHERE bs.BranoSuonatoIl = ?
                                ORDER BY bs.OrdineEsecuzione ASC");
    $stmt_sel->bind_param('s', $selected_date);
    $stmt_sel->execute();
    $res_sel = $stmt_sel->get_result();
    while ($r = $res_sel->fetch_assoc()) {
        $selected_songs[] = [
            'id' => $r['IdBrano'],
            'titolo' => $r['Titolo'],
            'tipologia' => $r['Tipologia'],
        ];
    }
}

// Fetch all songs for the add control
$all_songs = [];
$res_all = $conn->query("SELECT Id, Titolo, Tipologia FROM Brani ORDER BY Titolo");
while ($r = $res_all->fetch_assoc()) {
    $all_songs[] = [
        'id' => $r['Id'],
        'titolo' => $r['Titolo'],
        'tipologia' => $r['Tipologia']
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                <h1 class="text-xl font-bold text-gray-900">Modifica Scaletta</h1>
            </div>

            <form method="get" class="space-y-4" id="select-date-form">
                <div>
                    <label for="data" class="block text-sm font-medium text-gray-700 mb-2">Seleziona Data Scaletta</label>
                    <select name="data" id="data" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-base min-h-[48px] cursor-pointer" onchange="document.getElementById('select-date-form').submit()">
                        <option value="">-- Seleziona una data --</option>
                        <?php foreach ($dates as $date_option): ?>
                            <option value="<?php echo $date_option['data']; ?>" <?php echo $selected_date === $date_option['data'] ? 'selected' : ''; ?>>
                                <?php echo $date_option['formatted']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>

            <?php if ($selected_date && !empty($selected_songs)): ?>
                <form method="post" class="space-y-4" id="playlist-form" action="loading.php">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="action" value="edit_playlist">
                    <input type="hidden" name="data" value="<?php echo sanitize($selected_date); ?>">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Aggiungi brano</label>
                        <div class="flex flex-col md:flex-row gap-2 md:gap-3">
                            <select id="add-select" class="flex-1 px-4 py-3 border-2 border-gray-300 rounded-lg text-base min-h-[48px] cursor-pointer focus:outline-none focus:ring-2 focus:ring-orange-500">
                                <option value="">-- Seleziona brano --</option>
                                <?php foreach ($all_songs as $song): ?>
                                    <option value="<?php echo $song['id']; ?>">
                                        <?php echo sanitize($song['titolo']); ?> — <?php echo sanitize($song['tipologia']); ?>
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
                        <button type="submit" class="w-full flex items-center justify-center px-4 py-3 text-base font-semibold rounded-lg text-white bg-orange-600 hover:bg-orange-700 active:bg-orange-800 min-h-[48px] select-none transition-colors">Salva Modifica</button>
                    </div>
                </form>
            <?php elseif ($selected_date && empty($selected_songs)): ?>
                <div class="mt-6 p-4 rounded-lg bg-red-50 border border-red-200">
                    <p class="text-red-800 text-sm md:text-base">Nessun brano trovato per questa scaletta.</p>
                </div>
            <?php elseif (count($dates) === 0): ?>
                <div class="mt-6 p-4 rounded-lg bg-yellow-50 border border-yellow-200">
                    <p class="text-yellow-800 text-sm md:text-base">Nessuna scaletta disponibile per la modifica.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Playlist management: add / remove / reorder and submit brani[] in order
(function() {
    var selected = <?php echo json_encode($selected_songs, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?> || [];
    var allSongs = <?php echo json_encode($all_songs, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?> || [];

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
            left.innerHTML = '<div class="font-medium">' + escapeHtml(item.titolo) + '</div><div class="text-xs text-gray-500">' + escapeHtml(item.tipologia) + '</div>';
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
                html += '<option value="'+escapeHtml(s.id)+'">'+escapeHtml(s.titolo)+' — '+escapeHtml(s.tipologia)+'</option>';
            }
        });
        addSelect.innerHTML = html;
    }

    function addSelected() {
        var val = addSelect.value;
        if (!val) return;
        var song = allSongs.find(function(s){ return String(s.id) === String(val); });
        if (!song) return;
        selected.push({id: song.id, titolo: song.titolo, tipologia: song.tipologia});
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
