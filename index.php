<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Query per ultimi brani suonati negli ultimi venerdì e domenica
$stmt = $conn->prepare("
    SELECT b.titolo, b.tipologia, bs.data
    FROM BraniSuonati bs
    JOIN Brani b ON bs.id_brano = b.id
    WHERE DAYOFWEEK(bs.data) IN (6, 1)  -- Venerdì=6, Domenica=1
    ORDER BY bs.data DESC
    LIMIT 10
");
$stmt->execute();
$result = $stmt->get_result();
$brani = $result->fetch_all(MYSQLI_ASSOC);
?>

<?php include 'includes/header.php'; ?>
<h1 class="text-3xl mb-4">Ultimi Brani Suonati</h1>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php foreach ($brani as $brano): ?>
        <div class="bg-white p-4 rounded shadow">
            <h2 class="text-xl"><?php echo sanitize($brano['titolo']); ?></h2>
            <p><?php echo sanitize($brano['tipologia']); ?></p>
            <p><?php echo sanitize($brano['data']); ?></p>
        </div>
    <?php endforeach; ?>
</div>
<?php include 'includes/footer.php'; ?>