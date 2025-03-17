<?php
// Session muss vor jeder Ausgabe gestartet werden
session_start();

require_once 'functions.php';

// Aktiviere Fehler-Logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Überprüfen, ob das Formular abgeschickt wurde
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Alle Formularfelder bereinigen
    $name = clean_input($_POST['name'] ?? '');
    $vorname = clean_input($_POST['vorname'] ?? '');
    $strasse = clean_input($_POST['strasse'] ?? '');
    $plz_ort = clean_input($_POST['plz_ort'] ?? '');
    $telefon = clean_input($_POST['telefon'] ?? '');
    $email = clean_input($_POST['email'] ?? '');
    $geburtsdatum = clean_input($_POST['geburtsdatum'] ?? '');
    $dsgvo = isset($_POST['dsgvo']) ? 1 : 0;
    $beteiligung = clean_input($_POST['beteiligung'] ?? '');
    $beitrag = clean_input($_POST['beitrag'] ?? '');
    $beitrag_custom_value = isset($_POST['beitrag_custom_value']) ? clean_input($_POST['beitrag_custom_value']) : null;
    
    // IBAN bereinigen - alle Leerzeichen entfernen
    $iban = isset($_POST['iban']) ? preg_replace('/\s+/', '', clean_input($_POST['iban'])) : '';
    
    $bank = clean_input($_POST['bank'] ?? '');
    $signature_data = $_POST['signature_data'] ?? '';
    $date = clean_input($_POST['date'] ?? '');
    
    // Debug-Meldungen speichern
    $debug_info = "Formulardaten empfangen:\n";
    $debug_info .= "Name: $name\n";
    $debug_info .= "Vorname: $vorname\n";
    $debug_info .= "IBAN: " . substr($iban, 0, 4) . "..." . "\n"; // Aus Sicherheitsgründen nur Anfang zeigen
    $debug_info .= "Bank: $bank\n";
    $debug_info .= "Beitrag: $beitrag\n";
    error_log($debug_info);
    
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
    
    // IBAN-Validierung - Deutsche IBAN hat 22 Zeichen (DE + 20 Ziffern)
    // Korrigiertes Muster für deutsche IBAN
    if (empty($iban)) {
        $errors[] = "IBAN ist erforderlich";
    } else if (!preg_match('/^DE[0-9]{20}$/', $iban)) {
        $errors[] = "Gültige IBAN ist erforderlich (Format: DE + 20 Ziffern)";
        error_log("IBAN Validation fehlgeschlagen: " . $iban);
    }
    
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
            'iban' => $iban, // Bereinigte IBAN
            'bank' => $bank,
            'signature_data' => $signature_data,
            'date' => $date
        ];
        
        try {
            error_log("Versuche Anmeldung zu speichern...");
            $registration_id = save_registration($data);
            
            if ($registration_id) {
                error_log("Anmeldung erfolgreich gespeichert, ID: " . $registration_id);
                // Bestätigungsmail senden versuchen
                try {
                    send_confirmation_email($data);
                } catch (Exception $e) {
                    // E-Mail-Fehler protokollieren, aber Prozess fortsetzen
                    error_log("E-Mail-Fehler: " . $e->getMessage());
                }
                
                // Erfolg: Zur Erfolgsseite weiterleiten
                header("Location: ../success.php?id=" . $registration_id);
                exit;
            } else {
                // Fehler beim Speichern
                error_log("Fehler beim Speichern der Anmeldung: save_registration returned false");
                $_SESSION['error_message'] = "Beim Speichern Ihrer Anmeldung ist ein Fehler aufgetreten. Bitte versuchen Sie es später noch einmal.";
                $_SESSION['form_data'] = $_POST;
                header("Location: ../index.html");
                exit;
            }
        } catch (Exception $e) {
            // Datenbankfehler
            error_log("Datenbank-Exception: " . $e->getMessage());
            $_SESSION['error_message'] = "Datenbankfehler: " . $e->getMessage();
            $_SESSION['form_data'] = $_POST;
            header("Location: ../index.html");
            exit;
        }
    } else {
        // Fehler bei der Validierung
        error_log("Validierungsfehler: " . implode(", ", $errors));
        $_SESSION['error_message'] = "Bitte korrigieren Sie die folgenden Fehler:<br>" . implode("<br>", $errors);
        $_SESSION['form_data'] = $_POST;
        header("Location: ../index.html");
        exit;
    }
} else {
    // Wenn keine POST-Anfrage, zur Startseite umleiten
    header("Location: ../index.html");
    exit;
}
?>