<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Query per ultimi brani suonati negli ultimi venerdì e domenica
$stmt = $conn->prepare("
    SELECT b.titolo, b.tipologia, bs.BranoSuonatoIl
    FROM BraniSuonati bs
    JOIN Brani b ON bs.IdBrano = b.Id
    WHERE DAYOFWEEK(bs.BranoSuonatoIl) IN (6, 1)  -- Venerdì=6, Domenica=1
    ORDER BY bs.BranoSuonatoIl DESC
    LIMIT 10
");
$stmt->execute();
$result = $stmt->get_result();
$brani = $result->fetch_all(MYSQLI_ASSOC);
?>

<?php include 'includes/header.php'; ?>
<div class="max-w-6xl mx-auto">
    <h1 class="text-4xl font-bold text-center mb-8 text-gray-800">Ultimi Brani Suonati</h1>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($brani as $brano): ?>
            <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 p-6 border border-gray-200">
                <div class="flex items-center mb-4">
                    <svg class="w-8 h-8 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                    </svg>
                    <h2 class="text-xl font-semibold text-gray-800"><?php echo sanitize($brano['titolo']); ?></h2>
                </div>
                <p class="text-gray-600 mb-2"><span class="font-medium">Tipologia:</span> <?php echo sanitize($brano['tipologia']); ?></p>
                <p class="text-gray-500 text-sm"><span class="font-medium">Suonato il:</span> <?php echo sanitize($brano['BranoSuonatoIl']); ?></p>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if (empty($brani)): ?>
        <div class="text-center py-12">
            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 15c-2.34 0-4.29-.98-5.5-2.5"></path>
            </svg>
            <p class="text-gray-500 text-lg">Nessun brano trovato.</p>
        </div>
    <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>