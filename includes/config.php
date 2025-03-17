<?php
// Datenbankverbindung
$db_host = 'localhost';
$db_user = 'd0431d6a';  // Ändern Sie dies zu Ihrem all-inkl.com Datenbankbenutzer
$db_pass = 'rp8qEdqz5kFAzixM8YrM';  // Ihr Datenbankpasswort
$db_name = 'd0431d6a';  // Ihr Datenbankname

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Überprüfen der Verbindung
if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}

// UTF-8 für die Verbindung setzen
$conn->set_charset("utf8");

// Konstanten für die Anwendung
define('SITE_URL', 'https://anmeldung.probasketballgt.de');
define('ADMIN_EMAIL', 'info@probasketballgt.de');
?>

