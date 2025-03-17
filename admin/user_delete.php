<?php
// admin/user_delete.php - Admin-Benutzer löschen
require_once '../includes/auth.php';

// Nur Administratoren haben Zugriff
require_admin();

// CSRF-Token überprüfen
if (!isset($_GET['csrf']) || !validate_csrf_token($_GET['csrf'])) {
    header("Location: users.php?error=invalid_request");
    exit;
}

// Benutzer-ID aus URL holen
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Wenn keine gültige ID, zur Übersicht zurückkehren
if ($id <= 0) {
    header("Location: users.php");
    exit;
}

// Überprüfen, ob der Benutzer sich selbst löschen will
if ($id === $_SESSION['admin_id']) {
    header("Location: users.php?error=self_delete");
    exit;
}

// Benutzer aus Datenbank laden
$user = get_admin_user($id);

// Wenn Benutzer nicht gefunden, zur Übersicht zurückkehren
if (!$user) {
    header("Location: users.php");
    exit;
}

// Sicherstellen, dass nicht der letzte Admin gelöscht wird
if ($user['role'] === 'admin' && count_admin_users_by_role('admin') <= 1) {
    header("Location: users.php?error=last_admin");
    exit;
}

// Benutzer löschen
if (delete_admin_user($id)) {
    header("Location: users.php?success=deleted");
} else {
    header("Location: users.php?error=delete_failed");
}
exit;
?>