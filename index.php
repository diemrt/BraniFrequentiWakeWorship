<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Gestione toggle filtri
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_filters'])) {
    $_SESSION['filters_open'] = !($_SESSION['filters_open'] ?? false);
    // Redirect per evitare re-submit
    $redirect_params = $_GET;
    unset($redirect_params['page']);
    header('Location: index.php?' . http_build_query($redirect_params));
    exit;
}
$filters_open = $_SESSION['filters_open'] ?? false;

// Ottieni parametri di ricerca
$title_search = $_GET['title'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$day_filter = $_GET['day'] ?? 'entrambi';

// Build query string for pagination
$query_string = http_build_query([
    'title' => $title_search,
    'date_from' => $date_from,
    'date_to' => $date_to,
    'day' => $day_filter
]);

$today = date('Y-m-d');

$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? 'info';
if ($message) {
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Handle delete confirmation request
if (isset($_GET['confirm_delete'])) {
    $delete_date = $_GET['confirm_delete'];
    $day = date('l', strtotime($delete_date));
    $day_it = ($day == 'Friday') ? 'Venerdì' : 'Domenica';
}

// Handle copy/share request
$copy_date = $_GET['copy_date'] ?? '';
if ($copy_date) {
    // We'll show a modal with the text to copy
    $stmt = $conn->prepare("
        SELECT b.titolo
        FROM BraniSuonati bs
        JOIN Brani b ON bs.IdBrano = b.Id
        WHERE bs.BranoSuonatoIl = ?
        ORDER BY bs.OrdineEsecuzione ASC
    ");
    $stmt->bind_param('s', $copy_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $copy_brani = $result->fetch_all(MYSQLI_ASSOC);
    $day = date('l', strtotime($copy_date));
    $day_it = ($day == 'Friday') ? 'Venerdì' : 'Domenica';
}

// Build base query
$base_query = "
    SELECT b.titolo, b.tipologia, bs.BranoSuonatoIl, bs.OrdineEsecuzione
    FROM BraniSuonati bs
    JOIN Brani b ON bs.IdBrano = b.Id
    WHERE DAYOFWEEK(bs.BranoSuonatoIl) IN (6, 1)
";
$count_query = "
    SELECT COUNT(DISTINCT bs.BranoSuonatoIl)
    FROM BraniSuonati bs
    JOIN Brani b ON bs.IdBrano = b.Id
    WHERE DAYOFWEEK(bs.BranoSuonatoIl) IN (6, 1)
";
$dates_query = "
    SELECT DISTINCT bs.BranoSuonatoIl as date_played
    FROM BraniSuonati bs
    JOIN Brani b ON bs.IdBrano = b.Id
    WHERE DAYOFWEEK(bs.BranoSuonatoIl) IN (6, 1)
";
$params = [];
$types = '';

if (!empty($title_search)) {
    $condition = " AND b.titolo LIKE ?";
    $count_query .= $condition;
    $dates_query .= $condition;
    $base_query .= $condition;
    $params[] = '%' . $title_search . '%';
    $types .= 's';
}
if (!empty($date_from)) {
    $condition = " AND bs.BranoSuonatoIl >= ?";
    $count_query .= $condition;
    $dates_query .= $condition;
    $base_query .= $condition;
    $params[] = $date_from;
    $types .= 's';
}
if (!empty($date_to)) {
    $condition = " AND bs.BranoSuonatoIl <= ?";
    $count_query .= $condition;
    $dates_query .= $condition;
    $base_query .= $condition;
    $params[] = $date_to;
    $types .= 's';
}

if ($day_filter == 'venerdi') {
    $base_query = str_replace("IN (6, 1)", "= 6", $base_query);
    $count_query = str_replace("IN (6, 1)", "= 6", $count_query);
    $dates_query = str_replace("IN (6, 1)", "= 6", $dates_query);
} elseif ($day_filter == 'dom') {
    $base_query = str_replace("IN (6, 1)", "= 1", $base_query);
    $count_query = str_replace("IN (6, 1)", "= 1", $count_query);
    $dates_query = str_replace("IN (6, 1)", "= 1", $dates_query);
}
$stmt_count = $conn->prepare($count_query);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$total_dates = $result_count->fetch_row()[0];
$limit_dates = 10;
$total_pages = ceil($total_dates / $limit_dates);
$page = isset($_GET['page']) ? max(1, min((int)$_GET['page'], $total_pages ?: 1)) : 1;
$offset_dates = ($page - 1) * $limit_dates;

// Get dates for this page
$dates_query .= " ORDER BY bs.BranoSuonatoIl DESC LIMIT ? OFFSET ?";
$dates_params = array_merge($params, [$limit_dates, $offset_dates]);
$dates_types = $types . 'ii';

$stmt_dates = $conn->prepare($dates_query);
$stmt_dates->bind_param($dates_types, ...$dates_params);
$stmt_dates->execute();
$result_dates = $stmt_dates->get_result();
$dates = array_column($result_dates->fetch_all(MYSQLI_ASSOC), 'date_played');

// Main query for all records in these dates
if (!empty($dates)) {
    $placeholders = str_repeat('?,', count($dates) - 1) . '?';
    $main_query = $base_query . " AND bs.BranoSuonatoIl IN ($placeholders) ORDER BY bs.BranoSuonatoIl DESC, bs.OrdineEsecuzione ASC";
    $main_params = array_merge($params, $dates);
    $main_types = $types . str_repeat('s', count($dates));

    $stmt = $conn->prepare($main_query);
    $stmt->bind_param($main_types, ...$main_params);
    $stmt->execute();
    $result = $stmt->get_result();
    $brani = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $brani = [];
}

// Raggruppa i brani per data
$grouped = [];
foreach ($brani as $brano) {
    $date = $brano['BranoSuonatoIl'];
    if (!isset($grouped[$date])) {
        $grouped[$date] = [];
    }
    $grouped[$date][] = $brano;
}
?>

<?php include 'includes/header.php'; ?>
<div class="max-w-6xl mx-auto">
    <h1 class="text-3xl md:text-4xl font-bold text-center mb-6 md:mb-8 text-gray-800">Ultimi Brani Suonati</h1>
    
    <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'; ?>">
            <p class="<?php echo $message_type === 'success' ? 'text-green-800' : 'text-red-800'; ?> text-sm md:text-base"><?php echo sanitize($message); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['confirm_delete'])): ?>
        <div class="fixed inset-0 bg-black/50 z-50 flex items-end md:items-center md:justify-center" id="delete-modal">
            <div class="bg-white w-full md:w-96 rounded-t-2xl md:rounded-2xl p-6 md:p-8 space-y-6 max-h-96 overflow-y-auto">
                <h2 class="text-xl md:text-2xl font-bold text-center text-gray-900">Eliminare la scaletta?</h2>
                <p class="text-gray-700 text-sm md:text-base">
                    Sei sicuro di voler eliminare la scaletta per il <?php echo sanitize($delete_date . ' (' . $day_it . ')'); ?>? Questa azione non può essere annullata.
                </p>
                <div class="flex gap-4">
                    <a href="index.php?<?php echo $query_string; ?>&page=<?php echo $_GET['page'] ?? 1; ?>" 
                       class="flex-1 px-4 py-3 md:py-4 bg-gray-100 hover:bg-gray-200 rounded-lg md:rounded-xl font-medium text-gray-900 text-center transition-colors">
                        Annulla
                    </a>
                    <a href="loading.php?action=delete_scaletta&delete_date=<?php echo urlencode($delete_date); ?>&query_string=<?php echo urlencode($query_string); ?>&page=<?php echo $_GET['page'] ?? 1; ?>" 
                       class="flex-1 px-4 py-3 md:py-4 bg-red-600 hover:bg-red-700 text-white rounded-lg md:rounded-xl font-medium text-center transition-colors">
                        Elimina
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($copy_date && !empty($copy_brani)): ?>
        <div class="fixed inset-0 bg-black/50 z-50 flex items-end md:items-center md:justify-center" id="copy-modal">
            <div class="bg-white w-full md:w-auto md:min-w-[500px] rounded-t-2xl md:rounded-2xl p-6 md:p-8 space-y-4 max-h-[80vh] overflow-y-auto">
                <div class="flex justify-between items-start">
                    <h2 class="text-xl md:text-2xl font-bold text-gray-900">Copia Scaletta</h2>
                    <a href="index.php?<?php echo $query_string; ?>&page=<?php echo $_GET['page'] ?? 1; ?>" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </a>
                </div>
                <p class="text-sm text-gray-600">Copia il testo sottostante e condividilo:</p>
                <textarea readonly class="w-full h-48 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-sm" id="copy-text"><?php
                    echo "Ciao a tutti! Ecco la scaletta in programma per {$copy_date} ({$day_it}):\n";
                    foreach ($copy_brani as $brano) {
                        echo "- " . $brano['titolo'] . "\n";
                    }
                ?></textarea>
                <div class="flex gap-3">
                    <button onclick="document.getElementById('copy-text').select();document.execCommand('copy');this.textContent='Copiato!';setTimeout(()=>this.textContent='Copia negli appunti',2000)" 
                            class="flex-1 px-4 py-3 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-medium transition-colors">
                        Copia negli appunti
                    </button>
                    <a href="index.php?<?php echo $query_string; ?>&page=<?php echo $_GET['page'] ?? 1; ?>" 
                       class="flex-1 px-4 py-3 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium text-center transition-colors">
                        Chiudi
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Search Form - Collapsible -->
    <div class="mb-6 md:mb-8 relative">
        <!-- Filter Toggle Button (Thumb Zone) -->
        <form method="POST">
            <input type="hidden" name="toggle_filters" value="1">
            <button type="submit" 
                    class="fixed bottom-20 right-4 z-40 w-14 h-14 <?php echo $filters_open ? 'bg-green-600 hover:bg-green-700 active:bg-green-800' : 'bg-orange-600 hover:bg-orange-700 active:bg-orange-800'; ?> text-white rounded-full shadow-lg transition-all duration-200 active:scale-95 flex items-center justify-center"
                    aria-label="<?php echo $filters_open ? 'Nascondi filtri' : 'Mostra filtri'; ?>">
                <svg class="w-6 h-6" 
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                </svg>
            </button>
        </form>

        <!-- Filtri Collapsibili -->
        <div class="overflow-hidden transition-all duration-300 ease-in-out <?php echo $filters_open ? 'max-h-[600px] opacity-100' : 'max-h-0 opacity-0'; ?>">
        <form method="POST" action="loading.php" class="bg-gray-100 md:bg-transparent p-4 md:p-0 rounded-lg md:rounded-none space-y-4">
            <input type="hidden" name="action" value="filter_index">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Cerca per titolo</label>
                    <input type="text" id="title" name="title" value="<?php echo sanitize($title_search); ?>" 
                           placeholder="Es: Amazing Grace"
                           class="w-full px-3 py-2 md:py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-base min-h-[44px]">
                </div>
                <div>
                    <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">Data da</label>
                    <input type="date" id="date_from" name="date_from" value="<?php echo sanitize($date_from); ?>" 
                           class="w-full px-3 py-2 md:py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-base min-h-[44px]">
                </div>
                <div>
                    <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">Data a</label>
                    <input type="date" id="date_to" name="date_to" value="<?php echo sanitize($date_to); ?>" 
                           class="w-full px-3 py-2 md:py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-base min-h-[44px]">
                </div>
            </div>
            
            <div class="pt-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Filtra per giorno</label>
                <div class="grid grid-cols-3 gap-2 md:flex md:gap-2">
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="day" value="venerdi" <?php echo $day_filter == 'venerdi' ? 'checked' : ''; ?> class="sr-only peer">
                        <span class="block w-full text-center px-3 py-2 border-2 border-gray-300 rounded-md bg-white text-gray-700 transition-all duration-200 peer-checked:bg-orange-100 peer-checked:border-orange-500 peer-checked:text-orange-700 peer-checked:font-medium peer-checked:shadow-sm active:scale-95 touch-manipulation select-none <?php echo $day_filter == 'venerdi' ? 'bg-orange-100 border-orange-500 text-orange-700 font-medium shadow-sm' : 'hover:bg-gray-50 hover:border-gray-400'; ?>">Venerdì</span>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="day" value="dom" <?php echo $day_filter == 'dom' ? 'checked' : ''; ?> class="sr-only peer">
                        <span class="block w-full text-center px-3 py-2 border-2 border-gray-300 rounded-md bg-white text-gray-700 transition-all duration-200 peer-checked:bg-orange-100 peer-checked:border-orange-500 peer-checked:text-orange-700 peer-checked:font-medium peer-checked:shadow-sm active:scale-95 touch-manipulation select-none <?php echo $day_filter == 'dom' ? 'bg-orange-100 border-orange-500 text-orange-700 font-medium shadow-sm' : 'hover:bg-gray-50 hover:border-gray-400'; ?>">Domenica</span>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="day" value="entrambi" <?php echo $day_filter == 'entrambi' ? 'checked' : ''; ?> class="sr-only peer">
                        <span class="block w-full text-center px-3 py-2 border-2 border-gray-300 rounded-md bg-white text-gray-700 transition-all duration-200 peer-checked:bg-orange-100 peer-checked:border-orange-500 peer-checked:text-orange-700 peer-checked:font-medium peer-checked:shadow-sm active:scale-95 touch-manipulation select-none <?php echo $day_filter == 'entrambi' ? 'bg-orange-100 border-orange-500 text-orange-700 font-medium shadow-sm' : 'hover:bg-gray-50 hover:border-gray-400'; ?>">Entrambi</span>
                    </label>
                </div>
            </div>

            <div class="flex gap-2 md:items-end">
                <button type="submit" class="flex-1 md:flex-initial px-4 py-2 md:py-3 bg-orange-600 hover:bg-orange-700 text-white rounded-md font-medium transition-colors min-h-[44px] min-w-[44px] inline-flex items-center justify-center select-none">Cerca</button>
            </form>
            <form method="POST" action="loading.php" style="display: inline;">
                <input type="hidden" name="action" value="filter_index">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <button type="submit" class="flex-1 md:flex-initial text-center px-4 py-2 md:py-3 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-md font-medium transition-colors min-h-[44px] min-w-[44px] inline-flex items-center justify-center select-none">Reset</button>
            </form>
            </div>
        </div>
    </div>

    <!-- Results Section -->
    <?php if (empty($brani)): ?>
        <div class="text-center py-12">
            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 15c-2.34 0-4.29-.98-5.5-2.5"></path>
            </svg>
            <p class="text-gray-500 text-lg">Nessun brano trovato.</p>
        </div>
    <?php else: ?>
        <?php foreach ($grouped as $date => $brani_per_data): ?>
            <div class="mb-6 md:mb-8">
                <!-- Date Header -->
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl md:text-2xl font-bold <?php echo ($date >= $today) ? 'text-green-700' : 'text-gray-800'; ?>">
                        <?php
                            $timestamp = strtotime($date);
                            $day = date('l', $timestamp);
                            $day_it = ($day == 'Friday') ? 'Venerdì' : 'Domenica';
                            echo sanitize($date . ' (' . $day_it . ')');
                        ?>
                    </h2>
                    <div class="flex items-center space-x-2">
                        <?php if ($date >= $today): ?>
                            <span class="bg-green-700 text-white text-xs px-2 py-1 rounded">Programmata</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-2 mb-4 flex-wrap">
                    <?php if ($date >= $today): ?>
                        <a href="index.php?copy_date=<?php echo urlencode($date); ?>&<?php echo $query_string; ?>&page=<?php echo $_GET['page'] ?? 1; ?>" 
                           class="flex items-center space-x-1 px-3 py-2 md:px-4 md:py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors text-sm md:text-base min-h-[44px] min-w-[44px] select-none" 
                           title="Condividi o copia scaletta">
                            <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                            <span class="hidden md:inline">Copia</span>
                        </a>
                        <?php if (is_logged_in()): ?>
                            <a href="index.php?confirm_delete=<?php echo urlencode($date); ?>&<?php echo $query_string; ?>&page=<?php echo $_GET['page'] ?? 1; ?>" 
                               class="flex items-center space-x-1 px-3 py-2 md:px-4 md:py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors text-sm md:text-base min-h-[44px] min-w-[44px] select-none" 
                               title="Elimina scaletta">
                                <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                <span class="hidden md:inline">Elimina</span>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Song Grid - Responsive -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                    <?php foreach ($brani_per_data as $brano): ?>
                        <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 p-4 md:p-6 border border-gray-200">
                            <div class="flex items-start gap-3">
                                <svg class="w-6 h-6 md:w-8 md:h-8 text-orange-600 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                                </svg>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-sm md:text-base font-semibold text-gray-800 break-words"><?php echo sanitize($brano['titolo']); ?></h3>
                                    <p class="text-xs md:text-sm text-gray-600 mt-1"><?php echo sanitize($brano['tipologia']); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="flex justify-center gap-2 mt-8 mb-4 flex-wrap">
        <?php if ($page > 1): ?>
            <a href="?<?php echo $query_string; ?>&page=<?php echo $page - 1; ?>" class="flex items-center px-3 py-2 md:px-4 md:py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
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
            <a href="?<?php echo $query_string; ?>&page=<?php echo $page + 1; ?>" class="flex items-center px-3 py-2 md:px-4 md:py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                <span class="hidden md:inline">Successivo</span>
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
