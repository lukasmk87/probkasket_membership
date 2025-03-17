<?php
// admin/dashboard.php - Aktualisiert, um Benutzerverwaltung zu integrieren
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Überprüfen, ob der Benutzer eingeloggt ist
require_login();

// Erfolgsmeldung aus URL-Parameter
$success_message = '';
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'deleted':
            $success_message = 'Anmeldung erfolgreich gelöscht.';
            break;
        case 'edited':
            $success_message = 'Anmeldung erfolgreich aktualisiert.';
            break;
    }
}

// Fehlermeldung aus URL-Parameter
$error_message = '';
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'delete_failed':
            $error_message = 'Beim Löschen der Anmeldung ist ein Fehler aufgetreten.';
            break;
        case 'insufficient_privileges':
            $error_message = 'Sie haben nicht die erforderlichen Rechte für diese Aktion.';
            break;
    }
}

// Filtereinstellungen
$filter_beteiligung = isset($_GET['beteiligung']) ? $_GET['beteiligung'] : '';
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// Anmeldungen abrufen (mit Filtern)
$registrations = get_all_registrations($filter_beteiligung, $search_term);

// Statistiken berechnen
$total_registrations = count($registrations);
$aktive_registrations = 0;
$passive_registrations = 0;
$total_beitrag = 0;

foreach ($registrations as $reg) {
    if ($reg['beteiligung'] == 'aktiv') {
        $aktive_registrations++;
    } else {
        $passive_registrations++;
    }
    
    // Beitrag berechnen
    if ($reg['beitrag'] == '10') {
        $total_beitrag += 10;
    } elseif ($reg['beitrag'] == '30') {
        $total_beitrag += 30;
    } else {
        $total_beitrag += $reg['beitrag_custom'];
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Pro Basketball GT e.V.</title>
    <link rel="stylesheet" href="../style.css">
	<script src="js/darkmode.js"></script>
</head>
<body>
    <div class="container admin-container">
        <div class="admin-header">
            <h1>Admin Dashboard</h1>
            <div>
                <p>Angemeldet als: <?php echo htmlspecialchars($_SESSION['admin_name']); ?> 
                   (<?php echo $_SESSION['admin_role'] === 'admin' ? 'Administrator' : 'Editor'; ?>)</p>
                <a href="logout.php" class="btn-reset">Abmelden</a>
            </div>
        </div>
        
        <nav class="admin-nav">
            <ul>
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="export.php">Exportieren</a></li>
                <?php if (is_admin()): // Nur Administratoren sehen den Menüpunkt ?>
                <li><a href="users.php">Benutzerverwaltung</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        
        <div class="admin-content">
            <?php if ($success_message): ?>
                <div class="success-message">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="error-message">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Statistik-Dashboard -->
            <div class="statistics-dashboard">
                <h2>Statistik-Übersicht</h2>
                <div class="statistics-grid">
                    <div class="statistic-box">
                        <div class="statistic-title">Mitglieder gesamt</div>
                        <div class="statistic-value"><?php echo $total_registrations; ?></div>
                    </div>
                    <div class="statistic-box">
                        <div class="statistic-title">Aktive Mitglieder</div>
                        <div class="statistic-value"><?php echo $aktive_registrations; ?></div>
                    </div>
                    <div class="statistic-box">
                        <div class="statistic-title">Passive Mitglieder</div>
                        <div class="statistic-value"><?php echo $passive_registrations; ?></div>
                    </div>
                    <div class="statistic-box">
                        <div class="statistic-title">Gesamtbeiträge pro Jahr</div>
                        <div class="statistic-value"><?php echo number_format($total_beitrag, 2, ',', '.'); ?> €</div>
                    </div>
                </div>
            </div>
            
            <h2>Vereinsanmeldungen</h2>
            
            <!-- Filter und Suchfunktion -->
            <div class="filter-search-container">
                <form action="dashboard.php" method="GET" class="filter-form">
                    <div class="filter-group">
                        <label for="beteiligung">Beteiligung:</label>
                        <select name="beteiligung" id="beteiligung" onchange="this.form.submit()">
                            <option value="" <?php echo $filter_beteiligung === '' ? 'selected' : ''; ?>>Alle</option>
                            <option value="aktiv" <?php echo $filter_beteiligung === 'aktiv' ? 'selected' : ''; ?>>Aktiv</option>
                            <option value="passiv" <?php echo $filter_beteiligung === 'passiv' ? 'selected' : ''; ?>>Passiv</option>
                        </select>
                    </div>
                    <div class="search-group">
                        <input type="text" name="search" placeholder="Suche nach Name, E-Mail..." value="<?php echo htmlspecialchars($search_term); ?>">
                        <button type="submit" class="btn-search">Suchen</button>
                        <?php if ($filter_beteiligung !== '' || $search_term !== ''): ?>
                            <a href="dashboard.php" class="btn-reset-filter">Filter zurücksetzen</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <div class="export-buttons">
                <a href="export.php?format=csv<?php echo ($filter_beteiligung ? '&beteiligung=' . $filter_beteiligung : '') . ($search_term ? '&search=' . urlencode($search_term) : ''); ?>" class="btn-export">
                    Als CSV exportieren
                </a>
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
                                    <?php if ($reg['beteiligung'] == 'aktiv'): ?>
                                        <span class="status-active">Aktiv</span>
                                    <?php else: ?>
                                        <span class="status-passive">Passiv</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($reg['beitrag'] == '10') {
                                        echo '10 €';
                                    } elseif ($reg['beitrag'] == '30') {
                                        echo '30 €';
                                    } else {
                                        echo number_format($reg['beitrag_custom'], 2, ',', '.') . ' €';
                                    }
                                    ?>
                                </td>
                                <td class="action-buttons">
                                    <a href="view.php?id=<?php echo $reg['id']; ?>" class="btn-view">Ansehen</a>
                                    <a href="edit.php?id=<?php echo $reg['id']; ?>" class="btn-edit">Bearbeiten</a>
                                    <a href="delete.php?id=<?php echo $reg['id']; ?>&csrf=<?php echo generate_csrf_token(); ?>" class="btn-delete" onclick="return confirm('Sind Sie sicher, dass Sie diese Anmeldung löschen möchten?');">Löschen</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Keine Anmeldungen gefunden. <?php echo ($filter_beteiligung || $search_term) ? 'Bitte versuchen Sie es mit anderen Filtereinstellungen.' : ''; ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <style>
        /* Zusätzliche Inline-Styles für neue Funktionen */
        .statistics-dashboard {
            margin-bottom: 30px;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .statistics-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 15px;
        }
        
        .statistic-box {
            flex: 1;
            min-width: 200px;
            background-color: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .statistic-title {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 8px;
        }
        
        .statistic-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #333;
        }
        
        .filter-search-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        
        .filter-form {
            display: flex;
            width: 100%;
            justify-content: space-between;
        }
        
        .filter-group, .search-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .search-group input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 250px;
        }
        
        .btn-search {
            padding: 8px 16px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn-reset-filter {
            padding: 8px 16px;
            background-color: #eee;
            color: #333;
            text-decoration: none;
            border-radius: 4px;
        }
        
        .status-active {
            color: #28a745;
            font-weight: bold;
        }
        
        .status-passive {
            color: #6c757d;
        }
        
        @media (max-width: 768px) {
            .statistics-grid {
                flex-direction: column;
            }
            
            .filter-form {
                flex-direction: column;
                gap: 15px;
            }
            
            .search-group {
                flex-wrap: wrap;
            }
        }
    </style>
</body>
</html>