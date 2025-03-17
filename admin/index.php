<?php
// Starte Output-Buffering um Probleme mit header() zu vermeiden
ob_start();

// Session starten falls noch nicht geschehen
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/auth.php';

// Debug-Informationen in Error-Log schreiben
error_log("Admin Login Page: Session ID: " . session_id());
error_log("Admin Login Page: Session Status: " . (is_logged_in() ? "Logged in" : "Not logged in"));

// Überprüfen, ob der Benutzer bereits eingeloggt ist
if (is_logged_in()) {
    error_log("User bereits eingeloggt, leite weiter zum Dashboard");
    // Direkte Weiterleitung zum Dashboard
    header("Location: dashboard.php");
    // JavaScript-Fallback für die Weiterleitung
    echo "<script>window.location.href = 'dashboard.php';</script>";
    // Ausgabepuffer leeren und beenden
    ob_end_flush();
    exit;
}

$error = '';

// Überprüfen, ob das Formular abgeschickt wurde
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    error_log("Login-Versuch für Benutzername: " . $username);
    
    if (login($username, $password)) {
        error_log("Login erfolgreich, leite weiter zum Dashboard");
        // Nach erfolgreichem Login direkt zum Dashboard weiterleiten
        header("Location: dashboard.php");
        // JavaScript-Fallback für die Weiterleitung
        echo "<script>window.location.href = 'dashboard.php';</script>";
        // Ausgabepuffer leeren und beenden
        ob_end_flush();
        exit;
    } else {
        error_log("Login fehlgeschlagen für Benutzername: " . $username);
        $error = "Ungültiger Benutzername oder Passwort";
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Pro Basketball GT e.V.</title>
    <link rel="stylesheet" href="../style.css">
    <script src="../js/darkmode.js"></script>
</head>
<body>
    <div class="container">
        <div class="login-form">
            <h2>Admin Login</h2>
            
            <?php if ($error): ?>
                <div class="error-message" style="margin-bottom: 20px;"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="index.php">
                <div class="form-group">
                    <label for="username">Benutzername</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Passwort</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn-submit">Anmelden</button>
            </form>
            
            <div style="margin-top: 20px; text-align: center;">
                <a href="../index.html" class="back-link">« Zurück zur Webseite</a>
            </div>
        </div>
    </div>
</body>
</html>
<?php
// Sicherstellen, dass der Ausgabepuffer geleert wird
ob_end_flush();
?>