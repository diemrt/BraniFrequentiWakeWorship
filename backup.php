<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

// Verifica che l'utente sia Admin o Developer
$stmt = $conn->prepare("SELECT Ruolo FROM Utenti WHERE Username = ?");
$stmt->bind_param('s', $_SESSION['username']);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();

if (!$user_data || !in_array($user_data['Ruolo'], ['Admin', 'Developer'])) {
    $_SESSION['message'] = 'Accesso negato. Solo Admin e Developer possono accedere al backup.';
    $_SESSION['message_type'] = 'error';
    header('Location: index.php');
    exit;
}

$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? 'info';
if ($message) {
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Gestione download backup
if (isset($_POST['download_backup'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['message'] = 'Token CSRF invalido';
        $_SESSION['message_type'] = 'error';
        header('Location: backup.php');
        exit;
    }

    // Crea directory temporanea per i file CSV
    $temp_dir = sys_get_temp_dir() . '/backup_' . time();
    mkdir($temp_dir);

    try {
        // Ottieni tutte le tabelle del database
        $tables_query = "SHOW TABLES";
        $tables_result = $conn->query($tables_query);
        
        while ($table_row = $tables_result->fetch_array()) {
            $table_name = $table_row[0];
            
            // Ottieni i dati della tabella
            $data_query = "SELECT * FROM `$table_name`";
            $data_result = $conn->query($data_query);
            
            // Crea file CSV
            $csv_file = $temp_dir . '/' . $table_name . '.csv';
            $fp = fopen($csv_file, 'w');
            
            // Aggiungi BOM UTF-8 per compatibilità con Excel
            fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Scrivi intestazioni
            if ($data_result->num_rows > 0) {
                $first_row = $data_result->fetch_assoc();
                fputcsv($fp, array_keys($first_row), ';');
                fputcsv($fp, $first_row, ';');
                
                // Scrivi resto dei dati
                while ($row = $data_result->fetch_assoc()) {
                    fputcsv($fp, $row, ';');
                }
            } else {
                // Tabella vuota, scrivi solo intestazioni
                $fields_query = "SHOW COLUMNS FROM `$table_name`";
                $fields_result = $conn->query($fields_query);
                $headers = [];
                while ($field = $fields_result->fetch_assoc()) {
                    $headers[] = $field['Field'];
                }
                fputcsv($fp, $headers, ';');
            }
            
            fclose($fp);
        }
        
        // Crea file ZIP
        $zip_file = sys_get_temp_dir() . '/backup_' . date('Y-m-d_H-i-s') . '.zip';
        $zip = new ZipArchive();
        
        if ($zip->open($zip_file, ZipArchive::CREATE) === TRUE) {
            $files = scandir($temp_dir);
            foreach ($files as $file) {
                if ($file != '.' && $file != '..') {
                    $zip->addFile($temp_dir . '/' . $file, $file);
                }
            }
            $zip->close();
            
            // Download del file
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="backup_' . date('Y-m-d_H-i-s') . '.zip"');
            header('Content-Length: ' . filesize($zip_file));
            readfile($zip_file);
            
            // Pulizia file temporanei
            $files = scandir($temp_dir);
            foreach ($files as $file) {
                if ($file != '.' && $file != '..') {
                    unlink($temp_dir . '/' . $file);
                }
            }
            rmdir($temp_dir);
            unlink($zip_file);
            
            exit;
        } else {
            throw new Exception('Impossibile creare il file ZIP');
        }
    } catch (Exception $e) {
        $_SESSION['message'] = 'Errore durante la creazione del backup: ' . $e->getMessage();
        $_SESSION['message_type'] = 'error';
        
        // Pulizia in caso di errore
        if (is_dir($temp_dir)) {
            $files = scandir($temp_dir);
            foreach ($files as $file) {
                if ($file != '.' && $file != '..') {
                    unlink($temp_dir . '/' . $file);
                }
            }
            rmdir($temp_dir);
        }
        
        header('Location: backup.php');
        exit;
    }
}

// Ottieni statistiche database
$stats = [];
$tables_query = "SHOW TABLES";
$tables_result = $conn->query($tables_query);
while ($table_row = $tables_result->fetch_array()) {
    $table_name = $table_row[0];
    $count_query = "SELECT COUNT(*) as count FROM `$table_name`";
    $count_result = $conn->query($count_query);
    $count = $count_result->fetch_assoc()['count'];
    $stats[$table_name] = $count;
}
?>

<?php include 'includes/header.php'; ?>
<div class="max-w-6xl mx-auto mt-4 lg:mt-0">
    <div class="flex items-center mb-4">
        <svg class="h-6 w-6 text-orange-600 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
        </svg>
        <h1 class="text-xl font-bold text-gray-900">Backup Database</h1>
    </div>

    <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'; ?>">
            <p class="<?php echo $message_type === 'success' ? 'text-green-800' : 'text-red-800'; ?> text-sm md:text-base"><?php echo sanitize($message); ?></p>
        </div>
    <?php endif; ?>

    <!-- Info Card -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 md:p-6 mb-6">
        <div class="flex items-start space-x-3">
            <svg class="w-6 h-6 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div class="flex-1">
                <h3 class="text-blue-900 font-semibold mb-2">Informazioni Backup</h3>
                <p class="text-blue-800 text-sm">
                    Il backup includerà tutte le tabelle del database in formato CSV (separatore: punto e virgola).
                    I file verranno compressi in un archivio ZIP per il download.
                    <strong class="block mt-2">Ricorda di effettuare backup regolari dei dati!</strong>
                </p>
            </div>
        </div>
    </div>

    <!-- Statistiche Database -->
    <div class="bg-white rounded-lg shadow-lg border border-gray-200 p-4 md:p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Statistiche Database</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <?php foreach ($stats as $table => $count): ?>
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600 font-medium"><?php echo sanitize($table); ?></p>
                            <p class="text-2xl font-bold text-gray-900 mt-1"><?php echo $count; ?></p>
                            <p class="text-xs text-gray-500 mt-1">record<?php echo $count != 1 ? 's' : ''; ?></p>
                        </div>
                        <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
                        </svg>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Form Download Backup -->
    <div class="bg-white rounded-lg shadow-lg border border-gray-200 p-4 md:p-6">
        <div class="flex items-center mb-4">
            <svg class="h-6 w-6 text-orange-600 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
            <h2 class="text-xl font-bold text-gray-900">Scarica Backup</h2>
        </div>
        
        <form method="post" action="backup.php">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            
            <div class="space-y-4">
                <p class="text-gray-700 text-sm md:text-base">
                    Clicca sul pulsante per generare e scaricare il backup completo del database.
                    Il file ZIP conterrà un file CSV per ogni tabella.
                </p>
                
                <button type="submit" name="download_backup"
                    class="w-full md:w-auto flex items-center justify-center px-6 py-3 md:py-4 bg-orange-600 hover:bg-orange-700 text-white rounded-lg md:rounded-xl font-medium transition-colors min-h-[44px] select-none">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    <span>Scarica Backup</span>
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
