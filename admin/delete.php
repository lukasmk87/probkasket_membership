<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Überprüfen, ob der Benutzer eingeloggt ist
require_login();

// Buffer output to prevent any headers already sent issues
ob_start();

// CSRF-Token überprüfen
if (!isset($_GET['csrf']) || !validate_csrf_token($_GET['csrf'])) {
    header("Location: dashboard.php?error=invalid_request");
    echo "<script>window.location.href = 'dashboard.php?error=invalid_request';</script>";
    exit;
}

// Anmeldung-ID aus URL holen
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Wenn keine gültige ID, zum Dashboard zurückkehren
if ($id <= 0) {
    header("Location: dashboard.php");
    echo "<script>window.location.href = 'dashboard.php';</script>";
    exit;
}

// Anmeldung löschen
try {
    $result = delete_registration($id);
    
    if ($result) {
        // Erfolgreich gelöscht, zum Dashboard zurückkehren mit Erfolgsmeldung
        header("Location: dashboard.php?success=deleted");
        echo "<script>window.location.href = 'dashboard.php?success=deleted';</script>";
    } else {
        // Fehler beim Löschen, zum Dashboard zurückkehren mit Fehlermeldung
        header("Location: dashboard.php?error=delete_failed");
        echo "<script>window.location.href = 'dashboard.php?error=delete_failed';</script>";
    }
} catch (Exception $e) {
    // Log the error
    error_log("Error in delete.php: " . $e->getMessage());
    header("Location: dashboard.php?error=exception&message=" . urlencode($e->getMessage()));
    echo "<script>window.location.href = 'dashboard.php?error=exception&message=" . urlencode($e->getMessage()) . "';</script>";
}

// Ensure the script terminates
ob_end_flush();
exit;
?>