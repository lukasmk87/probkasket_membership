// admin/index.php - Login-Seite für Admin-Bereich
<?php
require_once '../includes/auth.php';

// Überprüfen, ob der Benutzer bereits eingeloggt ist
if (is_logged_in()) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

// Überprüfen, ob das Formular abgeschickt wurde
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (login($username, $password)) {
        header("Location: dashboard.php");
        exit;
    } else {
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
</head>
<body>
    <div class="container">
        <div class="login-form">
            <h2>Admin Login</h2>
            
            <?php if ($error): ?>
                <div class="error-message" style="margin-bottom: 20px;"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
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