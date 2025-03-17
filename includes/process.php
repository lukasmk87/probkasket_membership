// includes/process.php - Verarbeitung des Anmeldeformulars
<?php
require_once 'functions.php';

// Überprüfen, ob das Formular abgeschickt wurde
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Alle Formularfelder bereinigen
    $name = clean_input($_POST['name']);
    $vorname = clean_input($_POST['vorname']);
    $strasse = clean_input($_POST['strasse']);
    $plz_ort = clean_input($_POST['plz_ort']);
    $telefon = clean_input($_POST['telefon']);
    $email = clean_input($_POST['email']);
    $geburtsdatum = clean_input($_POST['geburtsdatum']);
    $dsgvo = isset($_POST['dsgvo']) ? 1 : 0;
    $beteiligung = clean_input($_POST['beteiligung']);
    $beitrag = clean_input($_POST['beitrag']);
    $beitrag_custom_value = isset($_POST['beitrag_custom_value']) ? clean_input($_POST['beitrag_custom_value']) : null;
    $iban = clean_input($_POST['iban']);
    $bank = clean_input($_POST['bank']);
    $signature_data = $_POST['signature_data'];
    $date = clean_input($_POST['date']);
    
    // Validierung (zusätzlich zur JavaScript-Validierung)
    $errors = [];
    
    if (empty($name)) $errors[] = "Name ist erforderlich";
    if (empty($vorname)) $errors[] = "Vorname ist erforderlich";
    if (empty($strasse)) $errors[] = "Straße ist erforderlich";
    if (empty($plz_ort)) $errors[] = "PLZ / Ort ist erforderlich";
    if (empty($telefon)) $errors[] = "Telefon ist erforderlich";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Gültige E-Mail-Adresse ist erforderlich";
    if (empty($geburtsdatum)) $errors[] = "Geburtsdatum ist erforderlich";
    if (!$dsgvo) $errors[] = "Sie müssen den Datenschutzbestimmungen zustimmen";
    if (empty($beteiligung)) $errors[] = "Bitte wählen Sie eine Beteiligungsart";
    if (empty($beitrag)) $errors[] = "Bitte wählen Sie einen Beitrag";
    if ($beitrag === 'custom' && (empty($beitrag_custom_value) || $beitrag_custom_value < 10)) $errors[] = "Bei individuellem Beitrag muss ein Betrag von mindestens 10€ angegeben werden";
    if (empty($iban) || !preg_match('/^DE[0-9]{2}[0-9]{16}$/', $iban)) $errors[] = "Gültige IBAN ist erforderlich";
    if (empty($bank)) $errors[] = "Name des Kreditinstituts ist erforderlich";
    if (empty($signature_data)) $errors[] = "Unterschrift ist erforderlich";
    if (empty($date)) $errors[] = "Datum ist erforderlich";
    
    // Wenn keine Fehler vorhanden sind, Anmeldung speichern
    if (empty($errors)) {
        $data = [
            'name' => $name,
            'vorname' => $vorname,
            'strasse' => $strasse,
            'plz_ort' => $plz_ort,
            'telefon' => $telefon,
            'email' => $email,
            'geburtsdatum' => $geburtsdatum,
            'dsgvo' => $dsgvo,
            'beteiligung' => $beteiligung,
            'beitrag' => $beitrag,
            'beitrag_custom_value' => $beitrag_custom_value,
            'iban' => $iban,
            'bank' => $bank,
            'signature_data' => $signature_data,
            'date' => $date
        ];
        
        $registration_id = save_registration($data);
        
        if ($registration_id) {
            // Bestätigungsmail senden
            send_confirmation_email($data);
            
            // Zur Erfolgsseite weiterleiten
            header("Location: ../success.php?id=" . $registration_id);
            exit;
        } else {
            // Fehler beim Speichern
            $error_message = "Beim Speichern Ihrer Anmeldung ist ein Fehler aufgetreten. Bitte versuchen Sie es später noch einmal.";
        }
    } else {
        // Fehler bei der Validierung
        $error_message = "Bitte korrigieren Sie die folgenden Fehler:<br>" . implode("<br>", $errors);
    }
    
    // Bei Fehlern zur Fehlerseite weiterleiten
    if (isset($error_message)) {
        session_start();
        $_SESSION['error_message'] = $error_message;
        $_SESSION['form_data'] = $_POST;
        header("Location: ../index.php");
        exit;
    }
} else {
    // Wenn keine POST-Anfrage, zur Startseite umleiten
    header("Location: ../index.php");
    exit;
}
?>
