<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Überprüfen, ob der Benutzer eingeloggt ist
require_login();

// Anmeldungs-ID aus URL holen
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Wenn keine gültige ID, zum Dashboard zurückkehren
if ($id <= 0) {
    header("Location: dashboard.php");
    exit;
}

// Anmeldung als PDF exportieren
try {
    // PDF erzeugen (Diese Funktion gibt die PDF direkt aus und beendet die Ausführung)
    export_registration_as_pdf($id);
} catch (Exception $e) {
    // Fehler beim PDF-Export
    error_log("Fehler beim PDF-Export: " . $e->getMessage());
    header("Location: view.php?id=$id&error=" . urlencode("Fehler beim PDF-Export: " . $e->getMessage()));
    exit;
}

// Falls die PDF-Funktion die Ausführung nicht beendet, zurück zur Detailansicht
header("Location: view.php?id=$id");
exit;
?>