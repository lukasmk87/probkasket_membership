<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Überprüfen, ob der Benutzer eingeloggt ist
require_login();

// CSRF-Token überprüfen
if (!isset($_GET['csrf']) || !validate_csrf_token($_GET['csrf'])) {
    header("Location: dashboard.php?error=invalid_request");
    exit;
}

// Anmeldung-ID aus URL holen
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Wenn keine gültige ID, zum Dashboard zurückkehren
if ($id <= 0) {
    header("Location: dashboard.php");
    exit;
}

// Anmeldung löschen
if (delete_registration($id)) {
    // Erfolgreich gelöscht, zum Dashboard zurückkehren mit Erfolgsmeldung
    header("Location: dashboard.php?success=deleted");
} else {
    // Fehler beim Löschen, zum Dashboard zurückkehren mit Fehlermeldung
    header("Location: dashboard.php?error=delete_failed");
}
exit;
?>