<?php
// admin/user_add.php - Neuen Admin-Benutzer hinzufügen
require_once '../includes/auth.php';

// Nur Administratoren haben Zugriff
require_admin();

$error = '';
$success = false;

// Überprüfen, ob das Formular abgeschickt wurde
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF-Token überprüfen
    if (!validate_csrf_token($_POST['csrf_token'])) {
        $error = "Ungültige Anfrage. Bitte versuchen Sie es erneut.";
    } else {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $is_active = isset($_POST['is_active']) ? true : false;
        
        // Validierung
        if (empty($username) || empty($password) || empty($name) || empty($email)) {
            $error = "Alle Felder sind erforderlich.";
        } elseif (strlen($password) < 8) {
            $error = "Das Passwort muss mindestens 8 Zeichen lang sein.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Die E-Mail-Adresse ist ungültig.";
        } elseif (username_exists($username)) {
            $error = "Der Benutzername ist bereits vergeben.";
        } else {
            // Benutzer hinzufügen
            $data = [
                'username' => $username,
                'password' => $password,
                'name' => $name,
                'email' => $email,
                'role' => $role,
                'is_active' => $is_active
            ];
            
            $user_id = add_admin_user($data);
            
            if ($user_id) {
                // Erfolg: Zurück zur Übersicht
                header("Location: users.php?success=added");
                exit;
            } else {
                $error = "Beim Hinzufügen des Benutzers ist ein Fehler aufgetreten.";
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
    <title>Neuen Admin-Benutzer hinzufügen - Pro Basketball GT e.V.</title>
    <link rel="stylesheet" href="../style.css">
	<script src="js/darkmode.js"></script>
</head>
<body>
    <div class="container admin-container">
        <div class="admin-header">
            <h1>Neuen Admin-Benutzer hinzufügen</h1>
            <div>
                <a href="users.php" class="btn-reset">Zurück zur Übersicht</a>
            </div>
        </div>
        
        <nav class="admin-nav">
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="export.php">Exportieren</a></li>
                <li><a href="users.php" class="active">Benutzerverwaltung</a></li>
            </ul>
        </nav>
        
        <div class="admin-content">
            <h2>Neuen Benutzer anlegen</h2>
            
            <?php if ($error): ?>
                <div class="error-message" style="margin-bottom: 20px; padding: 10px; background-color: #f8d7da; color: #721c24; border-radius: 4px;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Benutzername *</label>
                        <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Passwort *</label>
                        <input type="password" id="password" name="password" required>
                        <div class="form-hint">Mindestens 8 Zeichen.</div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Name *</label>
                        <input type="text" id="name" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">E-Mail *</label>
                        <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="role">Rolle *</label>
                        <select id="role" name="role" required>
                            <option value="editor" <?php echo (isset($_POST['role']) && $_POST['role'] === 'editor') ? 'selected' : ''; ?>>Editor</option>
                            <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'selected' : ''; ?>>Administrator</option>
                        </select>
                    </div>
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="is_active" name="is_active" <?php echo (!isset($_POST['is_active']) || $_POST['is_active']) ? 'checked' : ''; ?>>
                        <label for="is_active">Aktiv</label>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-submit">Benutzer anlegen</button>
                    <a href="users.php" class="btn-reset">Abbrechen</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
