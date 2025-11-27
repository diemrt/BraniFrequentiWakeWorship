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

// Build base query
$base_query = "
    SELECT b.titolo, b.tipologia, bs.BranoSuonatoIl
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
    $main_query = $base_query . " AND bs.BranoSuonatoIl IN ($placeholders) ORDER BY bs.BranoSuonatoIl DESC";
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
    <h1 class="text-4xl font-bold text-center mb-8 text-gray-800">Ultimi Brani Suonati</h1>
    <form method="GET" class="mb-8 bg-gray-100 p-4 rounded-lg">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700">Cerca per titolo</label>
                <input type="text" id="title" name="title" value="<?php echo sanitize($title_search); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500">
            </div>
            <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700">Data da</label>
                <input type="date" id="date_from" name="date_from" value="<?php echo sanitize($date_from); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500">
            </div>
            <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700">Data a</label>
                <input type="date" id="date_to" name="date_to" value="<?php echo sanitize($date_to); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500">
            </div>
        </div>
        <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Filtra per giorno</label>
            <div class="flex space-x-2">
                <label class="flex-1">
                    <input type="radio" name="day" value="venerdi" <?php echo $day_filter == 'venerdi' ? 'checked' : ''; ?> onchange="this.form.submit()" class="sr-only">
                    <span class="block w-full text-center px-4 py-2 border border-gray-300 rounded-md bg-white text-gray-700 hover:bg-gray-50 cursor-pointer <?php echo $day_filter == 'venerdi' ? 'bg-orange-100 border-orange-500 text-orange-700' : ''; ?>">Venerdì</span>
                </label>
                <label class="flex-1">
                    <input type="radio" name="day" value="dom" <?php echo $day_filter == 'dom' ? 'checked' : ''; ?> onchange="this.form.submit()" class="sr-only">
                    <span class="block w-full text-center px-4 py-2 border border-gray-300 rounded-md bg-white text-gray-700 hover:bg-gray-50 cursor-pointer <?php echo $day_filter == 'dom' ? 'bg-orange-100 border-orange-500 text-orange-700' : ''; ?>">Domenica</span>
                </label>
                <label class="flex-1">
                    <input type="radio" name="day" value="entrambi" <?php echo $day_filter == 'entrambi' ? 'checked' : ''; ?> onchange="this.form.submit()" class="sr-only">
                    <span class="block w-full text-center px-4 py-2 border border-gray-300 rounded-md bg-white text-gray-700 hover:bg-gray-50 cursor-pointer <?php echo $day_filter == 'entrambi' ? 'bg-orange-100 border-orange-500 text-orange-700' : ''; ?>">Entrambi</span>
                </label>
            </div>
        </div>
        <div class="mt-4">
            <button type="submit" class="bg-orange-600 text-white px-4 py-2 rounded-md hover:bg-orange-700">Cerca</button>
            <a href="index.php" class="ml-4 text-gray-600 hover:text-gray-800">Reset</a>
        </div>
    </form>
    <?php foreach ($grouped as $date => $brani_per_data): ?>
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4"><?php
                $timestamp = strtotime($date);
                $day = date('l', $timestamp);
                $day_it = ($day == 'Friday') ? 'Venerdì' : 'Domenica';
                echo sanitize($date . ' (' . $day_it . ')');
            ?></h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($brani_per_data as $brano): ?>
                    <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 p-6 border border-gray-200">
                        <div class="flex items-center mb-4">
                            <svg class="w-8 h-8 text-orange-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                            </svg>
                            <h2 class="text-xl font-semibold text-gray-800"><?php echo sanitize($brano['titolo']); ?></h2>
                        </div>
                        <p class="text-gray-600 mb-2"><span class="font-medium">Tipologia:</span> <?php echo sanitize($brano['tipologia']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (empty($brani)): ?>
        <div class="text-center py-12">
            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 15c-2.34 0-4.29-.98-5.5-2.5"></path>
            </svg>
            <p class="text-gray-500 text-lg">Nessun brano trovato.</p>
        </div>
    <?php endif; ?>

    <?php if ($total_pages > 1): ?>
    <div class="flex justify-center space-x-2 mt-4 mb-8">
        <?php if ($page > 1): ?>
            <a href="?<?php echo $query_string; ?>&page=<?php echo $page - 1; ?>" class="flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Precedente
            </a>
        <?php endif; ?>
        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
            <a href="?<?php echo $query_string; ?>&page=<?php echo $i; ?>" class="px-4 py-2 text-sm font-medium rounded-md <?php echo $i == $page ? 'text-orange-600 bg-orange-50 border border-orange-500' : 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-50'; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
        <?php if ($page < $total_pages): ?>
            <a href="?<?php echo $query_string; ?>&page=<?php echo $page + 1; ?>" class="flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                Successivo
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>