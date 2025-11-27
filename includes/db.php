<?php
$host = 'sql100.infinityfree.com';
$db = 'if0_40534462_BraniFrequenti_WakeWorship';
$user = 'if0_40534462';
$pass = 'V7chZ6ES4zcx';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Errore di connessione al database: " . $conn->connect_error);
}
?>