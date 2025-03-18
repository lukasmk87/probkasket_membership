<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Überprüfen, ob der Benutzer eingeloggt ist
require_login();

// Anmeldung-ID aus URL holen
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Wenn keine gültige ID, zum Dashboard zurückkehren
if ($id <= 0) {
    header("Location: dashboard.php");
    exit;
}

// Anmeldung aus Datenbank laden
$registration = get_registration($id);

// Wenn Anmeldung nicht gefunden, zum Dashboard zurückkehren
if (!$registration) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anmeldung ansehen - Pro Basketball GT e.V.</title>
    <link rel="stylesheet" href="../style.css">
	<script src="js/darkmode.js"></script>
</head>
<body>
    <div class="container admin-container">
        <div class="admin-header">
            <h1>Anmeldung ansehen</h1>
            <div>
                <a href="dashboard.php" class="btn-reset">Zurück zum Dashboard</a>
            </div>
        </div>
        
        <div class="admin-content">
            <h2>Mitgliedsanmeldung von <?php echo htmlspecialchars($registration['vorname'] . ' ' . $registration['name']); ?></h2>
            
            <div class="registration-details">
                <div class="detail-row">
                    <div class="detail-label">ID:</div>
                    <div class="detail-value"><?php echo $registration['id']; ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Name:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($registration['vorname'] . ' ' . $registration['name']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Adresse:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($registration['strasse'] . ', ' . $registration['plz_ort']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Telefon:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($registration['telefon']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">E-Mail:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($registration['email']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Geburtsdatum:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($registration['geburtsdatum']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Datenschutz zugestimmt:</div>
                    <div class="detail-value"><?php echo $registration['dsgvo'] ? 'Ja' : 'Nein'; ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Beteiligung:</div>
                    <div class="detail-value"><?php echo $registration['beteiligung'] == 'aktiv' ? 'Aktive Beteiligung' : 'Passive Unterstützung'; ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Mitgliedsbeitrag:</div>
                    <div class="detail-value">
                        <?php 
                        if ($registration['beitrag'] == '10') {
                            echo '10 € (Mindestbeitrag)';
                        } elseif ($registration['beitrag'] == '30') {
                            echo '30 €';
                        } else {
                            echo htmlspecialchars($registration['beitrag_custom']) . ' €';
                        }
                        ?>
                    </div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">IBAN:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($registration['iban']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Bank:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($registration['bank']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Anmeldedatum:</div>
                    <div class="detail-value"><?php echo date('d.m.Y H:i', strtotime($registration['date_submitted'])); ?></div>
                </div>
            </div>
            
            <div class="signature-section">
                <h3>Unterschrift</h3>
                <img src="<?php echo $registration['signature_data']; ?>" alt="Unterschrift" style="max-width: 100%; border: 1px solid #ddd;">
            </div>
            
            <div class="admin-actions" style="margin-top: 30px;">
                <a href="edit.php?id=<?php echo $registration['id']; ?>" class="btn-edit">Bearbeiten</a>
                <a href="pdf_standalone.php?id=<?php echo $registration['id']; ?>" class="btn-export">Als PDF exportieren</a>
                <a href="delete.php?id=<?php echo $registration['id']; ?>&csrf=<?php echo generate_csrf_token(); ?>" class="btn-delete" onclick="return confirm('Sind Sie sicher, dass Sie diese Anmeldung löschen möchten?');">Löschen</a>
                <a href="dashboard.php" class="btn-reset">Zurück</a>
            </div>
        </div>
    </div>
</body>
</html>