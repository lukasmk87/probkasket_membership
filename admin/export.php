// admin/export.php - Exportieren der Anmeldungen
<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Überprüfen, ob der Benutzer eingeloggt ist
require_login();

// Überprüfen des Export-Formats
$format = isset($_GET['format']) ? $_GET['format'] : '';

if ($format === 'csv') {
    // CSV-Header setzen
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=anmeldungen_' . date('Y-m-d') . '.csv');
    
    // Ausgabe zum Browser umleiten
    export_registrations_csv();
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
</head>
<body>
    <div class="container admin-container">
        <div class="admin-header">
            <h1>Anmeldungen exportieren</h1>
            <div>
                <a href="dashboard.php" class="btn-reset">Zurück zum Dashboard</a>
            </div>
        </div>
        
        <nav class="admin-nav">
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="export.php" class="active">Exportieren</a></li>
            </ul>
        </nav>
        
        <div class="admin-content">
            <h2>Export-Optionen</h2>
            
            <div class="export-options">
                <div class="export-option">
                    <h3>CSV-Export</h3>
                    <p>Exportieren Sie alle Anmeldungen als CSV-Datei, die in Excel oder anderen Tabellenkalkulationsprogrammen geöffnet werden kann.</p>
                    <a href="export.php?format=csv" class="btn-export">Als CSV exportieren</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php
}
?>