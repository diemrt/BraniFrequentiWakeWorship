<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$redirect = 'index.php'; // default

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'login') {
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $_SESSION['message'] = 'Token CSRF invalido';
            $_SESSION['message_type'] = 'error';
            $redirect = 'login.php';
        } else {
            $username = sanitize($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            if (login($username, $password)) {
                $redirect = 'index.php';
            } else {
                $_SESSION['message'] = 'Credenziali errate';
                $_SESSION['message_type'] = 'error';
                $redirect = 'login.php';
            }
        }
    } elseif ($action === 'manage_brani') {
        $title_search = $_POST['title_search'] ?? '';
        $page = $_POST['page'] ?? 1;
        $redirect = 'manage_brani.php?title=' . urlencode($title_search) . '&page=' . $page;
        
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $_SESSION['message'] = 'Token CSRF invalido';
            $_SESSION['message_type'] = 'error';
        } elseif (isset($_POST['add'])) {
            $titolo = sanitize($_POST['titolo']);
            $tipologia = sanitize($_POST['tipologia']);
            if (!empty($titolo) && in_array($tipologia, ['Lode', 'Adorazione'])) {
                $stmt = $conn->prepare("INSERT INTO Brani (titolo, tipologia) VALUES (?, ?)");
                $stmt->bind_param('ss', $titolo, $tipologia);
                $stmt->execute();
                $_SESSION['message'] = 'Brano aggiunto con successo';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Dati invalidi';
                $_SESSION['message_type'] = 'error';
            }
        } elseif (isset($_POST['edit'])) {
            $id = (int)$_POST['id'];
            $titolo = sanitize($_POST['titolo']);
            $tipologia = sanitize($_POST['tipologia']);
            if (!empty($titolo) && in_array($tipologia, ['Lode', 'Adorazione'])) {
                $stmt = $conn->prepare("UPDATE Brani SET titolo = ?, tipologia = ? WHERE id = ?");
                $stmt->bind_param('ssi', $titolo, $tipologia, $id);
                $stmt->execute();
                $_SESSION['message'] = 'Brano aggiornato con successo';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Dati invalidi';
                $_SESSION['message_type'] = 'error';
            }
        }
    } elseif ($action === 'create_playlist') {
        $redirect = 'index.php';
        
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $_SESSION['message'] = 'Token CSRF invalido';
            $_SESSION['message_type'] = 'error';
        } else {
            $data = sanitize($_POST['data']);
            $dateObj = DateTime::createFromFormat('Y-m-d', $data);
            $oggi = new DateTime();
            if ($dateObj && $dateObj >= $oggi && in_array($dateObj->format('N'), ['5', '7'])) { // 5=ven, 7=dom
                if (isset($_POST['brani']) && is_array($_POST['brani'])) {
                    $checked = array_map('intval', $_POST['brani']);
                    if (!empty($_POST['order'])) {
                        $order_ids = array_map('intval', explode(',', $_POST['order']));
                        $checked = array_intersect($order_ids, $checked);
                    }
                    $stmt_check = $conn->prepare("SELECT Id FROM Brani WHERE Id = ?");
                    $stmt_insert = $conn->prepare("INSERT INTO BraniSuonati (IdBrano, BranoSuonatoIl, OrdineEsecuzione) VALUES (?, ?, ?)");
                    $inseriti = 0;
                    $ordine = 1;
                    foreach ($checked as $id_brano) {
                        $stmt_check->bind_param('i', $id_brano);
                        $stmt_check->execute();
                        if ($stmt_check->get_result()->num_rows > 0) {
                            $stmt_insert->bind_param('isi', $id_brano, $data, $ordine);
                            $stmt_insert->execute();
                            $inseriti++;
                            $ordine++;
                        }
                    }
                    $_SESSION['message'] = $inseriti . ' brani registrati per la scaletta';
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = 'Nessun brano selezionato';
                    $_SESSION['message_type'] = 'error';
                }
            } else {
                $_SESSION['message'] = 'Data non valida (solo venerdì o domenica future)';
                $_SESSION['message_type'] = 'error';
            }
        }
    } elseif ($action === 'filter_index') {
        // Gestione filtri index.php
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $_SESSION['message'] = 'Token CSRF invalido';
            $_SESSION['message_type'] = 'error';
            $redirect = 'index.php';
        } else {
            $title = $_POST['title'] ?? '';
            $date_from = $_POST['date_from'] ?? '';
            $date_to = $_POST['date_to'] ?? '';
            $day = $_POST['day'] ?? 'entrambi';
            $page = $_POST['page'] ?? 1;
            
            $redirect = 'index.php?' . http_build_query([
                'title' => $title,
                'date_from' => $date_from,
                'date_to' => $date_to,
                'day' => $day,
                'page' => $page
            ]);
        }
    } elseif ($action === 'filter_manage_brani') {
        // Gestione filtri manage_brani.php
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $_SESSION['message'] = 'Token CSRF invalido';
            $_SESSION['message_type'] = 'error';
            $redirect = 'manage_brani.php';
        } else {
            $title = $_POST['title'] ?? '';
            $page = $_POST['page'] ?? 1;
            
            $redirect = 'manage_brani.php?' . http_build_query([
                'title' => $title,
                'page' => $page
            ]);
        }
    } elseif ($action === 'manage_users') {
        $page = $_POST['page'] ?? 1;
        $redirect = 'manage_users.php?page=' . $page;
        
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $_SESSION['message'] = 'Token CSRF invalido';
            $_SESSION['message_type'] = 'error';
        } elseif (isset($_POST['add'])) {
            $username = sanitize($_POST['username']);
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            if (!empty($username) && !empty($password) && $password === $confirm_password) {
                // Check if username exists
                $stmt = $conn->prepare("SELECT COUNT(*) FROM Utenti WHERE Username = ?");
                $stmt->bind_param('s', $username);
                $stmt->execute();
                $result = $stmt->get_result();
                $count = $result->fetch_row()[0];
                if ($count == 0) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO Utenti (Username, Password) VALUES (?, ?)");
                    $stmt->bind_param('ss', $username, $hashed_password);
                    $stmt->execute();
                    $_SESSION['message'] = 'Utente aggiunto con successo';
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = 'Username già esistente';
                    $_SESSION['message_type'] = 'error';
                }
            } else {
                $_SESSION['message'] = 'Dati invalidi o password non corrispondenti';
                $_SESSION['message_type'] = 'error';
            }
        } elseif (isset($_POST['edit'])) {
            $id = (int)$_POST['id'];
            $username = sanitize($_POST['username']);
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            if (!empty($username)) {
                // Check if username exists for other users
                $stmt = $conn->prepare("SELECT COUNT(*) FROM Utenti WHERE Username = ? AND Id != ?");
                $stmt->bind_param('si', $username, $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $count = $result->fetch_row()[0];
                if ($count == 0) {
                    if (!empty($password)) {
                        if ($password === $confirm_password) {
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            $stmt = $conn->prepare("UPDATE Utenti SET Username = ?, Password = ? WHERE Id = ?");
                            $stmt->bind_param('ssi', $username, $hashed_password, $id);
                            $stmt->execute();
                            $_SESSION['message'] = 'Utente aggiornato con successo';
                            $_SESSION['message_type'] = 'success';
                        } else {
                            $_SESSION['message'] = 'Password non corrispondenti';
                            $_SESSION['message_type'] = 'error';
                        }
                    } else {
                        $stmt = $conn->prepare("UPDATE Utenti SET Username = ? WHERE Id = ?");
                        $stmt->bind_param('si', $username, $id);
                        $stmt->execute();
                        $_SESSION['message'] = 'Utente aggiornato con successo';
                        $_SESSION['message_type'] = 'success';
                    }
                } else {
                    $_SESSION['message'] = 'Username già esistente';
                    $_SESSION['message_type'] = 'error';
                }
            } else {
                $_SESSION['message'] = 'Dati invalidi';
                $_SESSION['message_type'] = 'error';
            }
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Handle GET actions if needed, e.g., delete
    $action = $_GET['action'] ?? '';
    if ($action === 'delete_scaletta') {
        $delete_date = $_GET['delete_date'] ?? '';
        $query_string = $_GET['query_string'] ?? '';
        $page = $_GET['page'] ?? 1;
        $redirect = 'index.php?' . $query_string . '&page=' . $page;
        
        if ($delete_date > date('Y-m-d')) {
            $stmt = $conn->prepare("DELETE FROM BraniSuonati WHERE BranoSuonatoIl = ?");
            $stmt->bind_param('s', $delete_date);
            $stmt->execute();
            $_SESSION['message'] = 'Scaletta eliminata con successo';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Impossibile eliminare scalette passate';
            $_SESSION['message_type'] = 'error';
        }
    } elseif ($action === 'delete_brano') {
        $id = (int)$_GET['delete'];
        $confirmed = isset($_GET['confirmed']);
        $title_search = $_GET['title'] ?? '';
        $page = $_GET['page'] ?? 1;
        $redirect = 'manage_brani.php?title=' . urlencode($title_search) . '&page=' . $page;
        
        if ($confirmed) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM BraniSuonati WHERE IdBrano = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $count = $result->fetch_row()[0];
            if ($count == 0) {
                $stmt = $conn->prepare("DELETE FROM Brani WHERE Id = ?");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $_SESSION['message'] = 'Brano eliminato con successo';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Impossibile eliminare, associato a registrazioni';
                $_SESSION['message_type'] = 'error';
            }
        }
    } elseif ($action === 'delete_utente') {
        $id = (int)$_GET['delete'];
        $confirmed = isset($_GET['confirmed']);
        $page = $_GET['page'] ?? 1;
        $redirect = 'manage_users.php?page=' . $page;
        
        if ($confirmed) {
            $stmt = $conn->prepare("DELETE FROM Utenti WHERE Id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $_SESSION['message'] = 'Utente eliminato con successo';
            $_SESSION['message_type'] = 'success';
        }
    }
}

// Output loading page with meta refresh
echo '<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Elaborazione...</title>
    <meta http-equiv="refresh" content="1;url=' . $redirect . '">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        @keyframes pulse-slow {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
        @keyframes progress {
            0% { width: 0%; }
            100% { width: 100%; }
        }
        .animate-spin { animation: spin 0.8s linear infinite; }
        .animate-pulse-slow { animation: pulse-slow 1.5s ease-in-out infinite; }
        .progress-bar { animation: progress 1s ease-out forwards; }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <div class="bg-white rounded-2xl shadow-xl p-8 space-y-6">
            <!-- Spinner -->
            <div class="flex justify-center">
                <div class="w-20 h-20 border-4 border-gray-200 border-t-orange-600 rounded-full animate-spin"></div>
            </div>
            
            <!-- Loading Text -->
            <div class="text-center space-y-3">
                <h2 class="text-2xl font-bold text-gray-800">Elaborazione</h2>
                <p class="text-sm text-gray-500">Attendere prego...</p>
            </div>
            
            <!-- Progress Bar -->
            <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                <div class="progress-bar h-full bg-gradient-to-r from-orange-500 to-orange-600 rounded-full"></div>
            </div>
        </div>
    </div>
</body>
</html>';
exit;
?>