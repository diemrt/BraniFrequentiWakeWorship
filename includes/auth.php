<?php
// session_start(); // Ora in header.php
require_once 'db.php';

function login($username, $password) {
    global $conn;
    $stmt = $conn->prepare("SELECT Password FROM Utenti WHERE Username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['Password'])) {
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $username;
            session_regenerate_id(true);
            return true;
        }
    }
    return false;
}

function logout() {
    $_SESSION = array();
    session_destroy();
}

function is_logged_in() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function get_user_role() {
    if (!is_logged_in()) {
        return null;
    }
    global $conn;
    $stmt = $conn->prepare("SELECT Ruolo FROM Utenti WHERE Username = ?");
    $stmt->bind_param('s', $_SESSION['username']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['Ruolo'];
    }
    return null;
}

function can_access_backup() {
    $role = get_user_role();
    return in_array($role, ['Admin', 'Developer']);
}
?>