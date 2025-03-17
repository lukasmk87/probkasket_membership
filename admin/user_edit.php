<?php
require_once '../includes/auth.php';

// Nur Administratoren haben Zugriff
require_admin();

// Benutzer-ID aus URL holen
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Wenn keine gültige ID, zur Übersicht zurückkehren
if ($id <= 0) {
    header("Location: users.php");
    exit;
}

// Benutzer aus Datenbank laden
$user = get_admin_user($id);

// Wenn Benutzer nicht gefunden, zur Übersicht zurückkehren
if (!$user) {
    header("Location: users.php");
    exit;
}

$error = '';
$success = false;

// Überprüfen, ob das Formular abgeschickt wurde
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF-Token überprüfen
    if (!validate_csrf_token($_POST['csrf_token'])) {
        $error = "Ungültige Anfrage. Bitte versuchen Sie es erneut.";
    } else {
        $username = trim($_POST['username']);
        $password = $_POST['password']; // Optional, kann leer sein
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $is_active = isset($_POST['is_active']) ? true : false;
        
        // Validierung
        if (empty($username) || empty($name) || empty($email)) {
            $error = "Name, Benutzername und E-Mail sind erforderlich.";
        } elseif (!empty($password) && strlen($password) < 8) {
            $error = "Das Passwort muss mindestens 8 Zeichen lang sein.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Die E-Mail-Adresse ist ungültig.";
        } elseif (username_exists($username, $id)) {
            $error = "Der Benutzername ist bereits vergeben.";
        } else {
            // Sicherstellen, dass der letzte Admin nicht deaktiviert wird
            if ($user['role'] === 'admin' && $role !== 'admin' && count_admin_users_by_role('admin') <= 1) {
                $error = "Der letzte Administrator kann nicht herabgestuft werden.";
            } elseif ($user['role'] === 'admin' && !$is_active && $user['id'] === $_SESSION['admin_id']) {
                $error = "Sie können Ihren eigenen Benutzer nicht deaktivieren.";
            } else {
                // Benutzer aktualisieren
                $data = [
                    'username' => $username,
                    'password' => $password, // Wenn leer, wird in update_admin_user() ignoriert
                    'name' => $name,
                    'email' => $email,
                    'role' => $role,
                    'is_active' => $is_active
                ];
                
                $result = update_admin_user($id, $data);
                
                if ($result) {
                    // Erfolg: Zurück zur Übersicht mit explizitem exit
                    header("Location: users.php?success=updated");
                    exit;
                } else {
                    $error = "Beim Aktualisieren des Benutzers ist ein Fehler aufgetreten.";
                }
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
    <title>Admin-Benutzer bearbeiten - Pro Basketball GT e.V.</title>
    <link rel="stylesheet" href="../style.css">
    <script src="../js/darkmode.js"></script>
</head>
<body>
    <div class="container admin-container">
        <div class="admin-header">
            <h1>Admin-Benutzer bearbeiten</h1>
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
                <li><a href="users.php" class="active">Benutzerverwaltung</a></li>
            </ul>
        </nav>
        
        <div class="admin-content">
            <h2>Benutzer bearbeiten: <?php echo htmlspecialchars($user['username']); ?></h2>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="user_edit.php?id=<?php echo $id; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Benutzername *</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Passwort</label>
                        <input type="password" id="password" name="password">
                        <div class="form-hint">Leer lassen, um das Passwort nicht zu ändern. Mindestens 8 Zeichen.</div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Name *</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">E-Mail *</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="role">Rolle *</label>
                        <select id="role" name="role" required<?php echo ($user['id'] === $_SESSION['admin_id'] && $user['role'] === 'admin') ? ' disabled' : ''; ?>>
                            <option value="editor" <?php echo $user['role'] === 'editor' ? 'selected' : ''; ?>>Editor</option>
                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                        </select>
                        <?php if ($user['id'] === $_SESSION['admin_id'] && $user['role'] === 'admin'): ?>
                            <input type="hidden" name="role" value="admin">
                            <div class="form-hint">Sie können Ihre eigene Rolle nicht ändern.</div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="is_active" name="is_active" <?php echo $user['is_active'] ? 'checked' : ''; ?><?php echo ($user['id'] === $_SESSION['admin_id']) ? ' disabled' : ''; ?>>
                        <label for="is_active">Aktiv</label>
                        <?php if ($user['id'] === $_SESSION['admin_id']): ?>
                            <input type="hidden" name="is_active" value="1">
                            <div class="form-hint">Sie können Ihren eigenen Benutzer nicht deaktivieren.</div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Erstellt am</label>
                        <div class="form-static"><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></div>
                    </div>
                    <div class="form-group">
                        <label>Letzter Login</label>
                        <div class="form-static">
                            <?php echo $user['last_login'] ? date('d.m.Y H:i', strtotime($user['last_login'])) : 'Nie'; ?>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-submit">Änderungen speichern</button>
                    <a href="users.php" class="btn-reset">Abbrechen</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>