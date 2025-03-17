// admin/logout.php - Abmelden aus dem Admin-Bereich
<?php
require_once '../includes/auth.php';

// Benutzer abmelden
logout();

// Zur Login-Seite umleiten
header("Location: index.php");
exit;
?>