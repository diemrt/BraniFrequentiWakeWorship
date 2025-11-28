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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caricamento...</title>
    <meta http-equiv="refresh" content="1;url=' . $redirect . '">
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 50px;
            background: #f3f4f6;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .loading {
            font-size: 24px;
            color: #ea580c;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #ea580c;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div>
        <div class="spinner"></div>
        <div class="loading">Caricamento in corso...</div>
    </div>
</body>
</html>';
exit;
?>