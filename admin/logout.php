<?php
// admin/logout.php - Abmelden aus dem Admin-Bereich

// Starte Output-Buffering um Probleme mit header() zu vermeiden
ob_start();

require_once '../includes/auth.php';

// Debug-Log für Logout-Tracking
error_log("Logout gestartet für Benutzer: " . ($_SESSION['admin_username'] ?? 'Unbekannt'));

// Speichere temporäre Variable für Log nach Session-Zerstörung
$username = isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'Unbekannt';

// Benutzer abmelden
logout();

// Protokolliere den erfolgreichen Logout
error_log("Logout erfolgreich für Benutzer: " . $username);

// Absolute URL zur Login-Seite erstellen
$admin_path = dirname($_SERVER['PHP_SELF']);
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$login_url = $base_url . $admin_path . '/index.php';

// Standardmäßige Weiterleitung mit header()
header("Location: index.php");

// HTML-Ausgabe mit JavaScript-Fallback für die Weiterleitung
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="0;url=index.php">
    <title>Abmeldung - Weiterleitung</title>
    <script>
        window.location.href = "index.php";
    </script>
</head>
<body>
    <p>Sie wurden abgemeldet. Falls die automatische Weiterleitung nicht funktioniert, 
    klicken Sie bitte <a href="index.php">hier</a>.</p>
</body>
</html>
<?php
// Sicherstellen, dass der Ausgabepuffer geleert wird
ob_end_flush();
exit;
?>