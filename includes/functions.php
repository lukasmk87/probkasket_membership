<?php
require_once 'config.php';

// Funktion zum Bereinigen von Eingabedaten
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Funktion zum Speichern einer Anmeldung in der Datenbank
function save_registration($data) {
    global $conn;
    
    // Vorbereiten der Einfügeoperation
    $stmt = $conn->prepare("INSERT INTO registrations (name, vorname, strasse, plz_ort, telefon, email, geburtsdatum, dsgvo, beteiligung, beitrag, beitrag_custom, iban, bank, signature_data, date_submitted) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    
    // Bestimmen des tatsächlichen Beitrags
    $beitrag = $data['beitrag'];
    $beitrag_custom = null;
    
    if ($beitrag === 'custom' && !empty($data['beitrag_custom_value'])) {
        $beitrag_custom = (float)$data['beitrag_custom_value'];
    }
    
    // Binden der Parameter
    $stmt->bind_param(
        "ssssssssssdss",
        $data['name'],
        $data['vorname'],
        $data['strasse'],
        $data['plz_ort'],
        $data['telefon'],
        $data['email'],
        $data['geburtsdatum'],
        $data['dsgvo'],
        $data['beteiligung'],
        $data['beitrag'],
        $beitrag_custom,
        $data['iban'],
        $data['bank'],
        $data['signature_data']
    );
    
    // Statement ausführen
    if ($stmt->execute()) {
        $id = $conn->insert_id;
        $stmt->close();
        return $id;
    } else {
        $stmt->close();
        return false;
    }
}

// Funktion zum Abrufen einer einzelnen Anmeldung
function get_registration($id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM registrations WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $registration = $result->fetch_assoc();
    
    $stmt->close();
    
    return $registration;
}

// Funktion zum Abrufen aller Anmeldungen
function get_all_registrations() {
    global $conn;
    
    $result = $conn->query("SELECT * FROM registrations ORDER BY date_submitted DESC");
    
    $registrations = [];
    while ($row = $result->fetch_assoc()) {
        $registrations[] = $row;
    }
    
    return $registrations;
}

// Funktion zum Aktualisieren einer Anmeldung
function update_registration($id, $data) {
    global $conn;
    
    // Beitrag verarbeiten
    $beitrag = $data['beitrag'];
    $beitrag_custom = null;
    
    if ($beitrag === 'custom' && !empty($data['beitrag_custom_value'])) {
        $beitrag_custom = (float)$data['beitrag_custom_value'];
    }
    
    $stmt = $conn->prepare("UPDATE registrations SET name = ?, vorname = ?, strasse = ?, plz_ort = ?, telefon = ?, email = ?, geburtsdatum = ?, beteiligung = ?, beitrag = ?, beitrag_custom = ?, iban = ?, bank = ? WHERE id = ?");
    
    $stmt->bind_param(
        "sssssssssdssi",
        $data['name'],
        $data['vorname'],
        $data['strasse'],
        $data['plz_ort'],
        $data['telefon'],
        $data['email'],
        $data['geburtsdatum'],
        $data['beteiligung'],
        $data['beitrag'],
        $beitrag_custom,
        $data['iban'],
        $data['bank'],
        $id
    );
    
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

// Funktion zum Löschen einer Anmeldung
function delete_registration($id) {
    global $conn;
    
    $stmt = $conn->prepare("DELETE FROM registrations WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

// Funktion zum Exportieren von Anmeldungen als CSV
function export_registrations_csv() {
    global $conn;
    
    $result = $conn->query("SELECT id, name, vorname, strasse, plz_ort, telefon, email, geburtsdatum, beteiligung, beitrag, beitrag_custom, iban, bank, date_submitted FROM registrations ORDER BY date_submitted DESC");
    
    $output = fopen('php://output', 'w');
    
    // Setzen der CSV-Header
    fputcsv($output, [
        'ID', 'Name', 'Vorname', 'Straße', 'PLZ / Ort', 'Telefon', 'E-Mail',
        'Geburtsdatum', 'Beteiligung', 'Beitrag', 'Individueller Beitrag', 'IBAN', 'Bank', 'Datum'
    ]);
    
    // Ausgabe aller Datensätze
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
    
    fclose($output);
}

// Funktion zum Senden einer Bestätigungsmail
function send_confirmation_email($data) {
    $to = $data['email'];
    $subject = "Ihre Anmeldung bei Pro Basketball GT e.V.";
    
    $message = "
    <html>
    <head>
        <title>Bestätigung Ihrer Anmeldung</title>
    </head>
    <body>
        <h2>Vielen Dank für Ihre Anmeldung bei Pro Basketball GT e.V.!</h2>
        <p>Sehr geehrte(r) {$data['vorname']} {$data['name']},</p>
        <p>wir freuen uns, Ihre Anmeldung bei Pro Basketball GT e.V. bestätigen zu können. Ihre Mitgliedschaft ist nun aktiv.</p>
        
        <h3>Ihre angegebenen Daten:</h3>
        <ul>
            <li><strong>Name:</strong> {$data['vorname']} {$data['name']}</li>
            <li><strong>Adresse:</strong> {$data['strasse']}, {$data['plz_ort']}</li>
            <li><strong>Telefon:</strong> {$data['telefon']}</li>
            <li><strong>E-Mail:</strong> {$data['email']}</li>
            <li><strong>Geburtsdatum:</strong> {$data['geburtsdatum']}</li>
            <li><strong>Beteiligung:</strong> " . ($data['beteiligung'] == 'aktiv' ? 'Aktive Beteiligung' : 'Passive Unterstützung') . "</li>
            <li><strong>Mitgliedsbeitrag:</strong> ";
    
    // Beitrag anzeigen
    if ($data['beitrag'] == '10') {
        $message .= "10 € (Mindestbeitrag)";
    } elseif ($data['beitrag'] == '30') {
        $message .= "30 €";
    } else {
        $message .= "{$data['beitrag_custom_value']} €";
    }
    
    $message .= "</li>
        </ul>
        
        <p>Der Jahresbeitrag wird gemäß Ihrer Einzugsermächtigung von Ihrem angegebenen Konto in der zweiten Jahreshälfte abgebucht.</p>
        
        <p>Falls Sie Fragen oder Anliegen haben, können Sie uns jederzeit unter info@probasketballgt.de kontaktieren.</p>
        
        <p>Mit sportlichen Grüßen,<br>Ihr Team von Pro Basketball GT e.V.</p>
        
        <hr>
        <p style='font-size: 12px;'>
            Pro Basketball GT e.V.<br>
            Pavenstädter Weg 35<br>
            33334 Gütersloh<br>
            E-Mail: info@probasketballgt.de<br>
            Web: www.probasketballgt.de
        </p>
    </body>
    </html>
    ";
    
    // Header für HTML-E-Mail
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Pro Basketball GT e.V. <info@probasketballgt.de>" . "\r\n";
    
    // E-Mail senden
    return mail($to, $subject, $message, $headers);
}
?>