<?php
// admin/change_password.php - Passwort ändern für eigenen Benutzer
require_once '../includes/auth.php';

// Überprüfen, ob der Benutzer eingeloggt ist
require_login();

$success_message = '';
$error_message = '';

// Überprüfen, ob das Formular abgeschickt wurde
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF-Token überprüfen
    if (!validate_csrf_token($_POST['csrf_token'])) {
        $error_message = "Ungültige Anfrage. Bitte versuchen Sie es erneut.";
    } else {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validierung
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error_message = "Alle Felder müssen ausgefüllt werden.";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "Die neuen Passwörter stimmen nicht überein.";
        } elseif (strlen($new_password) < 8) {
            $error_message = "Das neue Passwort muss mindestens 8 Zeichen lang sein.";
        } else {
            // Aktuelles Passwort überprüfen
            $user_id = $_SESSION['admin_id'];
            $user = get_admin_user($user_id);
            
            if (password_verify($current_password, $user['password'])) {
                // Passwort ändern
                $result = change_admin_password($user_id, $new_password);
                
                if ($result) {
                    // Weiterleiten zum Dashboard mit Erfolgsmeldung
                    header("Location: dashboard.php?success=password_changed");
                    exit;
                } else {
                    $error_message = "Beim Ändern des Passworts ist ein Fehler aufgetreten.";
                }
            } else {
                $error_message = "Das aktuelle Passwort ist nicht korrekt.";
            }
        }
    }
}

// CSRF-Token generieren
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passwort ändern - Pro Basketball GT e.V.</title>
    <link rel="stylesheet" href="../style.css">
    <script src="../js/darkmode.js"></script>
</head>
<body>
    <div class="container admin-container">
        <div class="admin-header">
            <h1>Passwort ändern</h1>
            <div>
                <p>Angemeldet als: <?php echo htmlspecialchars($_SESSION['admin_name']); ?> 
                   (<?php echo $_SESSION['admin_role'] === 'admin' ? 'Administrator' : 'Editor'; ?>)</p>
                <a href="logout.php" class="btn-reset">Abmelden</a>
            </div>
        </div>
        
        <nav class="admin-nav">
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="export.php">Exportieren</a></li>
                <?php if (is_admin()): // Nur Administratoren sehen den Menüpunkt ?>
                <li><a href="users.php">Benutzerverwaltung</a></li>
                <?php endif; ?>
                <li><a href="change_password.php" class="active">Passwort ändern</a></li>
            </ul>
        </nav>
        
        <div class="admin-content">
            <h2>Eigenes Passwort ändern</h2>
            
            <?php if ($success_message): ?>
                <div class="success-message"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="change_password.php">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <label for="current_password">Aktuelles Passwort *</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password">Neues Passwort *</label>
                    <input type="password" id="new_password" name="new_password" required>
                    <div class="form-hint">Mindestens 8 Zeichen.</div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Neues Passwort bestätigen *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-submit">Passwort ändern</button>
                    <a href="dashboard.php" class="btn-reset">Abbrechen</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>