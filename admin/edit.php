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

$message = '';

// Überprüfen, ob das Formular abgeschickt wurde
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Formularfelder bereinigen
    $name = clean_input($_POST['name']);
    $vorname = clean_input($_POST['vorname']);
    $strasse = clean_input($_POST['strasse']);
    $plz_ort = clean_input($_POST['plz_ort']);
    $telefon = clean_input($_POST['telefon']);
    $email = clean_input($_POST['email']);
    $geburtsdatum = clean_input($_POST['geburtsdatum']);
    $beteiligung = clean_input($_POST['beteiligung']);
    $beitrag = clean_input($_POST['beitrag']);
    $beitrag_custom_value = isset($_POST['beitrag_custom_value']) ? clean_input($_POST['beitrag_custom_value']) : null;
    $iban = clean_input($_POST['iban']);
    $bank = clean_input($_POST['bank']);
    
    // Daten für Update vorbereiten
    $data = [
        'name' => $name,
        'vorname' => $vorname,
        'strasse' => $strasse,
        'plz_ort' => $plz_ort,
        'telefon' => $telefon,
        'email' => $email,
        'geburtsdatum' => $geburtsdatum,
        'beteiligung' => $beteiligung,
        'beitrag' => $beitrag,
        'beitrag_custom_value' => $beitrag_custom_value,
        'iban' => $iban,
        'bank' => $bank
    ];
    
    // Anmeldung aktualisieren
    if (update_registration($id, $data)) {
        $message = "Anmeldung erfolgreich aktualisiert";
        // Aktualisierte Daten laden
        $registration = get_registration($id);
    } else {
        $message = "Fehler beim Aktualisieren der Anmeldung";
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anmeldung bearbeiten - Pro Basketball GT e.V.</title>
    <link rel="stylesheet" href="../style.css">
	<script src="js/darkmode.js"></script>
</head>
<body>
    <div class="container admin-container">
        <div class="admin-header">
            <h1>Anmeldung bearbeiten</h1>
            <div>
                <a href="dashboard.php" class="btn-reset">Zurück zum Dashboard</a>
            </div>
        </div>
        
        <div class="admin-content">
            <h2>Mitgliedsanmeldung von <?php echo htmlspecialchars($registration['vorname'] . ' ' . $registration['name']); ?></h2>
            
            <?php if ($message): ?>
                <div class="message" style="margin-bottom: 20px; padding: 10px; background-color: #d4edda; color: #155724; border-radius: 4px;">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($registration['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="vorname">Vorname</label>
                        <input type="text" id="vorname" name="vorname" value="<?php echo htmlspecialchars($registration['vorname']); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="strasse">Straße</label>
                        <input type="text" id="strasse" name="strasse" value="<?php echo htmlspecialchars($registration['strasse']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="plz_ort">PLZ / Ort</label>
                        <input type="text" id="plz_ort" name="plz_ort" value="<?php echo htmlspecialchars($registration['plz_ort']); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="telefon">Telefon</label>
                        <input type="tel" id="telefon" name="telefon" value="<?php echo htmlspecialchars($registration['telefon']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">E-Mail</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($registration['email']); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="geburtsdatum">Geburtsdatum</label>
                        <input type="date" id="geburtsdatum" name="geburtsdatum" value="<?php echo htmlspecialchars($registration['geburtsdatum']); ?>" required>
                    </div>
                </div>
                
                <h3>Beteiligung</h3>
                <div class="form-group radio-group">
                    <div class="radio-option">
                        <input type="radio" id="aktiv" name="beteiligung" value="aktiv" <?php echo $registration['beteiligung'] == 'aktiv' ? 'checked' : ''; ?> required>
                        <label for="aktiv">Aktive Beteiligung</label>
                    </div>
                    <div class="radio-option">
                        <input type="radio" id="passiv" name="beteiligung" value="passiv" <?php echo $registration['beteiligung'] == 'passiv' ? 'checked' : ''; ?>>
                        <label for="passiv">Passive Unterstützung</label>
                    </div>
                </div>
                
                <h3>Mitgliedsbeitrag</h3>
                <div class="form-group radio-group">
                    <div class="radio-option">
                        <input type="radio" id="beitrag_10" name="beitrag" value="10" <?php echo $registration['beitrag'] == '10' ? 'checked' : ''; ?> required>
                        <label for="beitrag_10">10 € (Mindestbeitrag)</label>
                    </div>
                    <div class="radio-option">
                        <input type="radio" id="beitrag_30" name="beitrag" value="30" <?php echo $registration['beitrag'] == '30' ? 'checked' : ''; ?>>
                        <label for="beitrag_30">30 €</label>
                    </div>
                    <div class="radio-option custom-amount">
                        <input type="radio" id="beitrag_custom" name="beitrag" value="custom" <?php echo $registration['beitrag'] == 'custom' ? 'checked' : ''; ?>>
                        <input type="number" id="beitrag_custom_value" name="beitrag_custom_value" min="10" value="<?php echo $registration['beitrag_custom']; ?>" <?php echo $registration['beitrag'] != 'custom' ? 'disabled' : ''; ?>>
                        <span>€</span>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="iban">IBAN</label>
                        <input type="text" id="iban" name="iban" value="<?php echo htmlspecialchars($registration['iban']); ?>" required pattern="DE[0-9]{2}[0-9]{4}[0-9]{4}[0-9]{4}[0-9]{4}[0-9]{2}" title="Bitte geben Sie eine gültige IBAN ein">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="bank">Name des Kreditinstituts</label>
                        <input type="text" id="bank" name="bank" value="<?php echo htmlspecialchars($registration['bank']); ?>" required>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-submit">Änderungen speichern</button>
                    <a href="view.php?id=<?php echo $id; ?>" class="btn-reset">Abbrechen</a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // JavaScript für benutzerdefiniertes Beitragsfeld
        document.addEventListener("DOMContentLoaded", function() {
            const customBeitragRadio = document.getElementById('beitrag_custom');
            const customBeitragValue = document.getElementById('beitrag_custom_value');
            
            customBeitragRadio.addEventListener('change', function() {
                customBeitragValue.disabled = !this.checked;
                if (this.checked) {
                    customBeitragValue.focus();
                }
            });
            
            customBeitragValue.addEventListener('click', function() {
                customBeitragRadio.checked = true;
                this.disabled = false;
            });
        });
    </script>
</body>
</html>
