<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Überprüfen, ob der Benutzer eingeloggt ist
require_login();

// Überprüfen des Export-Formats
$format = isset($_GET['format']) ? $_GET['format'] : '';

// Filtereinstellungen übernehmen
$filter_beteiligung = isset($_GET['beteiligung']) ? $_GET['beteiligung'] : '';
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($format === 'csv') {
    // CSV-Header setzen
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=anmeldungen_' . date('Y-m-d') . '.csv');
    
    // Ausgabe zum Browser umleiten
    export_registrations_csv($filter_beteiligung, $search_term);
    exit;
} else {
    // Export-Optionen anzeigen
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anmeldungen exportieren - Pro Basketball GT e.V.</title>
    <link rel="stylesheet" href="../style.css">
	<script src="js/darkmode.js"></script>
</head>
<body>
    <div class="container admin-container">
        <div class="admin-header">
            <h1>Anmeldungen exportieren</h1>
            <div>
                <p>Angemeldet als: <?php echo htmlspecialchars($_SESSION['admin_name']); ?> 
                   (<?php echo $_SESSION['admin_role'] === 'admin' ? 'Administrator' : 'Editor'; ?>)</p>
                <a href="logout.php" class="btn-reset">Abmelden</a>
            </div>
        </div>
        
        <nav class="admin-nav">
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="export.php" class="active">Exportieren</a></li>
                <?php if (is_admin()): // Nur Administratoren sehen den Menüpunkt ?>
                <li><a href="users.php">Benutzerverwaltung</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        
        <div class="admin-content">
            <h2>Export-Optionen</h2>
            
            <!-- Filter für den Export -->
            <div class="filter-search-container">
                <form action="export.php" method="GET" class="filter-form">
                    <input type="hidden" name="format" value="csv">
                    
                    <div class="filter-options">
                        <h3>Daten filtern (optional)</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="beteiligung">Beteiligung:</label>
                                <select name="beteiligung" id="beteiligung">
                                    <option value="">Alle</option>
                                    <option value="aktiv">Aktiv</option>
                                    <option value="passiv">Passiv</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="search">Suche:</label>
                                <input type="text" name="search" id="search" placeholder="Suche nach Name, E-Mail...">
                            </div>
                        </div>
                    </div>
                    
                    <div class="export-actions">
                        <button type="submit" class="btn-export">CSV-Export starten</button>
                    </div>
                </form>
            </div>
            
            <div class="export-info">
                <h3>Hinweise zum Export</h3>
                <ul>
                    <li>Die exportierte CSV-Datei kann mit Microsoft Excel, Google Sheets oder anderen Tabellenkalkulationsprogrammen geöffnet werden.</li>
                    <li>Die Daten werden mit UTF-8-Kodierung exportiert, was Umlaute und Sonderzeichen korrekt darstellt.</li>
                    <li>Verwenden Sie die Filteroptionen, um nur bestimmte Daten zu exportieren.</li>
                    <li>Wenn Sie nach einem Text suchen, werden alle Einträge exportiert, die diesen Text im Namen, Vornamen, der E-Mail-Adresse oder Postleitzahl/Ort enthalten.</li>
                </ul>
            </div>
        </div>
    </div>
    
    <style>
        .filter-options {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .export-actions {
            margin: 20px 0;
        }
        
        .export-info {
            background-color: #e9f5ff;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
        }
        
        .export-info ul {
            margin-left: 20px;
        }
        
        .export-info li {
            margin-bottom: 10px;
        }
    </style>
</body>
</html>
<?php
}
?>