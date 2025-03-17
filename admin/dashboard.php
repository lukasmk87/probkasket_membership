// admin/dashboard.php - Admin-Dashboard
<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Überprüfen, ob der Benutzer eingeloggt ist
require_login();

// Anmeldungen abrufen
$registrations = get_all_registrations();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Pro Basketball GT e.V.</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="container admin-container">
        <div class="admin-header">
            <h1>Admin Dashboard</h1>
            <div>
                <p>Angemeldet als: <?php echo htmlspecialchars($_SESSION['admin_name']); ?></p>
                <a href="logout.php" class="btn-reset">Abmelden</a>
            </div>
        </div>
        
        <nav class="admin-nav">
            <ul>
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="export.php">Exportieren</a></li>
            </ul>
        </nav>
        
        <div class="admin-content">
            <h2>Vereinsanmeldungen</h2>
            
            <div class="export-buttons">
                <a href="export.php?format=csv" class="btn-export">Als CSV exportieren</a>
            </div>
            
            <?php if (count($registrations) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>E-Mail</th>
                            <th>Datum</th>
                            <th>Beteiligung</th>
                            <th>Beitrag</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registrations as $reg): ?>
                            <tr>
                                <td><?php echo $reg['id']; ?></td>
                                <td><?php echo htmlspecialchars($reg['vorname'] . ' ' . $reg['name']); ?></td>
                                <td><?php echo htmlspecialchars($reg['email']); ?></td>
                                <td><?php echo date('d.m.Y', strtotime($reg['date_submitted'])); ?></td>
                                <td>
                                    <?php echo $reg['beteiligung'] == 'aktiv' ? 'Aktiv' : 'Passiv'; ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($reg['beitrag'] == '10') {
                                        echo '10 €';
                                    } elseif ($reg['beitrag'] == '30') {
                                        echo '30 €';
                                    } else {
                                        echo $reg['beitrag_custom'] . ' €';
                                    }
                                    ?>
                                </td>
                                <td class="action-buttons">
                                    <a href="view.php?id=<?php echo $reg['id']; ?>" class="btn-view">Ansehen</a>
                                    <a href="edit.php?id=<?php echo $reg['id']; ?>" class="btn-edit">Bearbeiten</a>
                                    <a href="delete.php?id=<?php echo $reg['id']; ?>" class="btn-delete" onclick="return confirm('Sind Sie sicher, dass Sie diese Anmeldung löschen möchten?');">Löschen</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Keine Anmeldungen vorhanden.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>