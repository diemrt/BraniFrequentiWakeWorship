<?php
// session_start(); // Ora in header.php

function login($username, $password) {
    // Hardcoded per semplicità
    if ($username === 'admin' && $password === 'admin') {
        $_SESSION['logged_in'] = true;
        session_regenerate_id(true);
        return true;
    }
    return false;
}

function logout() {
    session_destroy();
}

function is_logged_in() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}
?>