<?php
// admin/users.php - Übersicht aller Admin-Benutzer
require_once '../includes/auth.php';

// Nur Administratoren haben Zugriff
require_admin();

// Alle Admin-Benutzer abrufen
$users = get_all_admin_users();

// Erfolgsmeldung aus URL-Parameter
$success_message = '';
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'added':
            $success_message = 'Benutzer erfolgreich hinzugefügt.';
            break;
        case 'updated':
            $success_message = 'Benutzer erfolgreich aktualisiert.';
            break;
        case 'deleted':
            $success_message = 'Benutzer erfolgreich gelöscht.';
            break;
    }
}

// Fehlermeldung aus URL-Parameter
$error_message = '';
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'last_admin':
            $error_message = 'Der letzte Administrator kann nicht gelöscht werden.';
            break;
        case 'delete_failed':
            $error_message = 'Beim Löschen des Benutzers ist ein Fehler aufgetreten.';
            break;
        case 'self_delete':
            $error_message = 'Sie können Ihren eigenen Benutzer nicht löschen.';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin-Benutzerverwaltung - Pro Basketball GT e.V.</title>
    <link rel="stylesheet" href="../style.css">
	<script src="js/darkmode.js"></script>
</head>
<body>
    <div class="container admin-container">
        <div class="admin-header">
            <h1>Admin-Benutzerverwaltung</h1>
            <div>
                <p>Angemeldet als: <?php echo htmlspecialchars($_SESSION['admin_name']); ?></p>
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
            <div class="admin-actions">
                <a href="user_add.php" class="btn-submit">Neuen Benutzer hinzufügen</a>
            </div>
            
            <?php if ($success_message): ?>
                <div class="success-message" style="margin: 15px 0; padding: 10px; background-color: #d4edda; color: #155724; border-radius: 4px;">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="error-message" style="margin: 15px 0; padding: 10px; background-color: #f8d7da; color: #721c24; border-radius: 4px;">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <h2>Admin-Benutzer</h2>
            
            <?php if (count($users) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Benutzername</th>
                            <th>Name</th>
                            <th>E-Mail</th>
                            <th>Rolle</th>
                            <th>Letzter Login</th>
                            <th>Status</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <?php if ($user['role'] === 'admin'): ?>
                                        <span style="color: #dc3545;">Administrator</span>
                                    <?php else: ?>
                                        Editor
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo $user['last_login'] ? date('d.m.Y H:i', strtotime($user['last_login'])) : 'Nie'; ?>
                                </td>
                                <td>
                                    <?php if ($user['is_active']): ?>
                                        <span style="color: #28a745;">Aktiv</span>
                                    <?php else: ?>
                                        <span style="color: #dc3545;">Inaktiv</span>
                                    <?php endif; ?>
                                </td>
                                <td class="action-buttons">
                                    <a href="user_edit.php?id=<?php echo $user['id']; ?>" class="btn-edit">Bearbeiten</a>
                                    <?php if ($user['id'] != $_SESSION['admin_id']): // Verhindern, dass man sich selbst löscht ?>
                                        <a href="user_delete.php?id=<?php echo $user['id']; ?>&csrf=<?php echo generate_csrf_token(); ?>" class="btn-delete" onclick="return confirm('Sind Sie sicher, dass Sie diesen Benutzer löschen möchten?');">Löschen</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Keine Benutzer vorhanden.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
