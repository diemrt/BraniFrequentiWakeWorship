<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

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

// Handle delete scaletta
if (isset($_GET['delete_date'])) {
    $delete_date = $_GET['delete_date'];
    if ($delete_date > $today) {
        $stmt = $conn->prepare("DELETE FROM BraniSuonati WHERE BranoSuonatoIl = ?");
        $stmt->bind_param('s', $delete_date);
        $stmt->execute();
        header('Location: index.php?' . $query_string . '&page=' . ($_GET['page'] ?? 1));
        exit;
    }
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
    
    <!-- Search Form - Always Visible -->
    <div class="mb-6 md:mb-8">
        <div class="md:p-0">
            <form method="GET" class="bg-gray-100 md:bg-transparent p-4 md:p-0 rounded-lg md:rounded-none space-y-4 md:space-y-0">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Cerca per titolo</label>
                        <input type="text" id="title" name="title" value="<?php echo sanitize($title_search); ?>" 
                               placeholder="Es: Amazing Grace"
                               class="w-full px-3 py-2 md:py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500 text-base md:text-sm">
                    </div>
                    <div>
                        <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">Data da</label>
                        <input type="date" id="date_from" name="date_from" value="<?php echo sanitize($date_from); ?>" 
                               class="w-full px-3 py-2 md:py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500">
                    </div>
                    <div>
                        <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">Data a</label>
                        <input type="date" id="date_to" name="date_to" value="<?php echo sanitize($date_to); ?>" 
                               class="w-full px-3 py-2 md:py-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Filtra per giorno</label>
                    <div class="grid grid-cols-3 gap-2 md:flex md:gap-2">
                        <label class="flex items-center cursor-pointer">
                            <input type="radio" name="day" value="venerdi" <?php echo $day_filter == 'venerdi' ? 'checked' : ''; ?> class="sr-only">
                            <span class="block w-full text-center px-3 py-2 border border-gray-300 rounded-md bg-white text-gray-700 hover:bg-gray-50 transition-colors <?php echo $day_filter == 'venerdi' ? 'bg-orange-100 border-orange-500 text-orange-700 font-medium' : ''; ?>">Venerdì</span>
                        </label>
                        <label class="flex items-center cursor-pointer">
                            <input type="radio" name="day" value="dom" <?php echo $day_filter == 'dom' ? 'checked' : ''; ?> class="sr-only">
                            <span class="block w-full text-center px-3 py-2 border border-gray-300 rounded-md bg-white text-gray-700 hover:bg-gray-50 transition-colors <?php echo $day_filter == 'dom' ? 'bg-orange-100 border-orange-500 text-orange-700 font-medium' : ''; ?>">Domenica</span>
                        </label>
                        <label class="flex items-center cursor-pointer">
                            <input type="radio" name="day" value="entrambi" <?php echo $day_filter == 'entrambi' ? 'checked' : ''; ?> class="sr-only">
                            <span class="block w-full text-center px-3 py-2 border border-gray-300 rounded-md bg-white text-gray-700 hover:bg-gray-50 transition-colors <?php echo $day_filter == 'entrambi' ? 'bg-orange-100 border-orange-500 text-orange-700 font-medium' : ''; ?>">Entrambi</span>
                        </label>
                    </div>
                </div>

                <div class="flex flex-col md:flex-row gap-2">
                    <button type="submit" class="flex-1 md:flex-initial bg-orange-600 hover:bg-orange-700 text-white px-4 py-3 md:py-2 rounded-md font-medium transition-colors">Cerca</button>
                    <a href="index.php" class="flex-1 md:flex-initial text-center bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-3 md:py-2 rounded-md font-medium transition-colors">Reset</a>
                </div>
            </form>
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
                    <h2 class="text-xl md:text-2xl font-bold <?php echo ($date > $today) ? 'text-green-700' : 'text-gray-800'; ?>">
                        <?php
                            $timestamp = strtotime($date);
                            $day = date('l', $timestamp);
                            $day_it = ($day == 'Friday') ? 'Venerdì' : 'Domenica';
                            echo sanitize($date . ' (' . $day_it . ')');
                        ?>
                    </h2>
                    <div class="flex items-center space-x-2">
                        <?php if ($date > $today): ?>
                            <span class="bg-green-700 text-white text-xs px-2 py-1 rounded">Programmata</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-2 mb-4 flex-wrap">
                    <?php if ($date > $today): ?>
                        <button onclick="copyScaletta(<?php echo htmlspecialchars(json_encode($date)); ?>, <?php echo htmlspecialchars(json_encode($day_it)); ?>, <?php echo htmlspecialchars(json_encode(array_column($brani_per_data, 'titolo'))); ?>)" 
                                class="flex items-center space-x-1 px-3 py-2 md:px-4 md:py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors text-sm md:text-base" 
                                title="Condividi o copia scaletta">
                            <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                            <span class="hidden md:inline">Copia</span>
                        </button>
                        <?php if (is_logged_in()): ?>
                            <button onclick="deleteScaletta('<?php echo $date; ?>')" 
                                    class="flex items-center space-x-1 px-3 py-2 md:px-4 md:py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors text-sm md:text-base" 
                                    title="Elimina scaletta">
                                <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                <span class="hidden md:inline">Elimina</span>
                            </button>
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
