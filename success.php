<?php
require_once 'includes/functions.php';

// ID aus der URL holen
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Wenn keine gültige ID, zur Startseite umleiten
if ($id <= 0) {
    header("Location: index.php");
    exit;
}

// Anmeldung aus der Datenbank holen
$registration = get_registration($id);

// Wenn Anmeldung nicht gefunden, zur Startseite umleiten
if (!$registration) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anmeldung erfolgreich - Pro Basketball GT e.V.</title>
    <link rel="stylesheet" href="style.css">
    <script src="js/darkmode.js"></script>
</head>
<body>
    <div class="container">
        <header>
            <img src="assets/logo.png" alt="Pro Basketball GT e.V. Logo" class="logo">
            <h1>Anmeldung erfolgreich</h1>
            <div class="contact-info">
                <p>Pro Basketball GT e.V.</p>
                <p>Pavenstädter Weg 35</p>
                <p>33334 Gütersloh</p>
            </div>
        </header>
        
        <div class="success-message">
            <h2>Vielen Dank für Ihre Anmeldung, <?php echo htmlspecialchars($registration['vorname'] . ' ' . $registration['name']); ?>!</h2>
            <p>Ihre Anmeldung wurde erfolgreich gespeichert. Eine Bestätigungsmail wurde an Ihre angegebene E-Mail-Adresse <?php echo htmlspecialchars($registration['email']); ?> gesendet.</p>
            
            <h3>Ihre angegebenen Daten:</h3>
            <div class="registration-details">
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
            </div>
            
            <p>Der Jahresbeitrag wird gemäß Ihrer Einzugsermächtigung von Ihrem angegebenen Konto in der zweiten Jahreshälfte abgebucht.</p>
            
            <p>Falls Sie Fragen oder Anliegen haben, können Sie uns jederzeit unter <a href="mailto:info@probasketballgt.de">info@probasketballgt.de</a> kontaktieren.</p>
            
            <div class="button-container">
                <a href="index.html" class="btn-back">Zurück zur Startseite</a>
            </div>
        </div>
        
        <footer>
            <p>1. Vorsitzender Ole Ahnepohl, 2. Vorsitzende Saskia Kramer, Kassenwart Maria Teresa Moreno Arco</p>
            <p>Bankverbindung: Volksbank Gütersloh, IBAN: DE31478601250585471800 , BIC: GENODEM1GTL</p>
            <p>mail: <a href="mailto:info@probasketballgt.de">info@probasketballgt.de</a> │ <a href="https://www.probasketballgt.de">www.probasketballgt.de</a></p>
        </footer>
    </div>
</body>
</html>