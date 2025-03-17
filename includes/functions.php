<?php
require_once 'config.php';

// Funktion zum Bereinigen von Eingabedaten
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Verbesserte Funktion zum Speichern einer Anmeldung in der Datenbank
function save_registration($data) {
    global $conn;
    
    // Logging der Daten (ohne sensible Inhalte)
    error_log("save_registration wurde aufgerufen mit Daten für: " . $data['vorname'] . " " . $data['name']);
    
    try {
        // Vorbereiten der Einfügeoperation
        $stmt = $conn->prepare("INSERT INTO registrations (name, vorname, strasse, plz_ort, telefon, email, geburtsdatum, dsgvo, beteiligung, beitrag, beitrag_custom, iban, bank, signature_data, date_submitted) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        
        if (!$stmt) {
            error_log("SQL-Vorbereitung fehlgeschlagen: " . $conn->error);
            throw new Exception("Datenbankfehler bei Vorbereitung: " . $conn->error);
        }
        
        // Bestimmen des tatsächlichen Beitrags
        $beitrag = $data['beitrag'];
        $beitrag_custom = null;
        
        if ($beitrag === 'custom' && !empty($data['beitrag_custom_value'])) {
            $beitrag_custom = (float)$data['beitrag_custom_value'];
        }
        
        // Binden der Parameter mit explizitem Datentyp-Handling
		$bindResult = $stmt->bind_param(
			"ssssssssssdsss",  // Added one more 's' for signature_data
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
        if (!$bindResult) {
            error_log("Parameter-Bindung fehlgeschlagen: " . $stmt->error);
            throw new Exception("Datenbankfehler bei Parameter-Bindung: " . $stmt->error);
        }
        
        // Statement ausführen
        $executeResult = $stmt->execute();
        
        if (!$executeResult) {
            error_log("SQL-Ausführung fehlgeschlagen: " . $stmt->error);
            throw new Exception("Datenbankfehler bei Ausführung: " . $stmt->error);
        }
        
        $id = $conn->insert_id;
        error_log("Datenbank-Eintrag erfolgreich erstellt mit ID: " . $id);
        $stmt->close();
        return $id;
    } catch (Exception $e) {
        error_log("Exception in save_registration: " . $e->getMessage());
        if (isset($stmt)) {
            $stmt->close();
        }
        throw $e; // Fehler weiterleiten
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

// Funktion zum Abrufen aller Anmeldungen mit Filteroptionen
function get_all_registrations($beteiligung = '', $search = '') {
    global $conn;
    
    $sql = "SELECT * FROM registrations WHERE 1=1";
    $params = [];
    $types = "";
    
    // Filter nach Beteiligung
    if (!empty($beteiligung)) {
        $sql .= " AND beteiligung = ?";
        $params[] = $beteiligung;
        $types .= "s";
    }
    
    // Suche nach Name, Vorname, E-Mail oder PLZ/Ort
    if (!empty($search)) {
        $sql .= " AND (name LIKE ? OR vorname LIKE ? OR email LIKE ? OR plz_ort LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "ssss";
    }
    
    $sql .= " ORDER BY date_submitted DESC";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $registrations = [];
    while ($row = $result->fetch_assoc()) {
        $registrations[] = $row;
    }
    
    $stmt->close();
    
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
    
    try {
        // Verify the connection is still good
        if ($conn->ping() === false) {
            error_log("Database connection lost. Reconnecting...");
            $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
            $conn->set_charset("utf8");
        }
        
        $stmt = $conn->prepare("DELETE FROM registrations WHERE id = ?");
        
        if (!$stmt) {
            error_log("SQL-Vorbereitung fehlgeschlagen: " . $conn->error);
            throw new Exception("Datenbankfehler bei Vorbereitung: " . $conn->error);
        }
        
        $bindResult = $stmt->bind_param("i", $id);
        
        if (!$bindResult) {
            error_log("Parameter-Bindung fehlgeschlagen: " . $stmt->error);
            $stmt->close();
            throw new Exception("Datenbankfehler bei Parameter-Bindung: " . $stmt->error);
        }
        
        $executeResult = $stmt->execute();
        
        if (!$executeResult) {
            error_log("SQL-Ausführung fehlgeschlagen: " . $stmt->error);
            $stmt->close();
            throw new Exception("Datenbankfehler bei Ausführung: " . $stmt->error);
        }
        
        $affected = $stmt->affected_rows;
        error_log("delete_registration: Affected rows: " . $affected);
        
        $stmt->close();
        
        // Return true if at least one row was affected
        return $affected > 0;
    } catch (Exception $e) {
        error_log("Exception in delete_registration: " . $e->getMessage());
        if (isset($stmt) && $stmt) {
            $stmt->close();
        }
        throw $e; // Fehler weiterleiten
    }
}

// Funktion zum Exportieren von Anmeldungen als CSV mit Filteroptionen
function export_registrations_csv($beteiligung = '', $search = '') {
    global $conn;
    
    // Dieselbe Logik wie in get_all_registrations für konsistente Ergebnisse
    $sql = "SELECT id, name, vorname, strasse, plz_ort, telefon, email, geburtsdatum, beteiligung, beitrag, beitrag_custom, iban, bank, date_submitted FROM registrations WHERE 1=1";
    $params = [];
    $types = "";
    
    if (!empty($beteiligung)) {
        $sql .= " AND beteiligung = ?";
        $params[] = $beteiligung;
        $types .= "s";
    }
    
    if (!empty($search)) {
        $sql .= " AND (name LIKE ? OR vorname LIKE ? OR email LIKE ? OR plz_ort LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "ssss";
    }
    
    $sql .= " ORDER BY date_submitted DESC";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $output = fopen('php://output', 'w');
    
    // UTF-8 BOM für Excel-Kompatibilität
    fputs($output, "\xEF\xBB\xBF");
    
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
    $stmt->close();
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

// Funktion zum Abrufen von Statistiken
function get_registration_statistics() {
    global $conn;
    
    $stats = [
        'total' => 0,
        'active' => 0,
        'passive' => 0,
        'total_revenue' => 0,
        'newest_date' => null,
        'newest_member' => null
    ];
    
    // Gesamtzahl, aktive und passive Mitglieder
    $result = $conn->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN beteiligung = 'aktiv' THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN beteiligung = 'passiv' THEN 1 ELSE 0 END) as passive
        FROM registrations
    ");
    
    if ($row = $result->fetch_assoc()) {
        $stats['total'] = (int)$row['total'];
        $stats['active'] = (int)$row['active'];
        $stats['passive'] = (int)$row['passive'];
    }
    
    // Gesamtbeiträge berechnen
    $result = $conn->query("
        SELECT 
            SUM(CASE 
                WHEN beitrag = '10' THEN 10 
                WHEN beitrag = '30' THEN 30 
                ELSE beitrag_custom 
            END) as total_revenue
        FROM registrations
    ");
    
    if ($row = $result->fetch_assoc()) {
        $stats['total_revenue'] = $row['total_revenue'] ? (float)$row['total_revenue'] : 0;
    }
    
    // Neuestes Mitglied
    $result = $conn->query("
        SELECT id, name, vorname, date_submitted
        FROM registrations 
        ORDER BY date_submitted DESC 
        LIMIT 1
    ");
    
    if ($row = $result->fetch_assoc()) {
        $stats['newest_date'] = $row['date_submitted'];
        $stats['newest_member'] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'vorname' => $row['vorname']
        ];
    }
    
    return $stats;
}
// Funktion zum Senden einer Benachrichtigungsmail an den Administrator
function send_admin_notification_email($data) {
    // Konstante für die Admin-E-Mail aus config.php verwenden, falls vorhanden
    $to = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'info@probasketballgt.de';
    $subject = "Neue Vereinsanmeldung: " . $data['vorname'] . " " . $data['name'];
    
    $message = "
    <html>
    <head>
        <title>Neue Vereinsanmeldung</title>
    </head>
    <body>
        <h2>Neue Vereinsanmeldung eingegangen</h2>
        <p>Ein neues Mitglied hat sich bei Pro Basketball GT e.V. angemeldet:</p>
        
        <h3>Mitgliedsdaten:</h3>
        <table style='border-collapse: collapse; width: 100%;'>
            <tr>
                <th style='text-align: left; padding: 8px; border: 1px solid #ddd; background-color: #f2f2f2;'>Feld</th>
                <th style='text-align: left; padding: 8px; border: 1px solid #ddd; background-color: #f2f2f2;'>Wert</th>
            </tr>
            <tr>
                <td style='padding: 8px; border: 1px solid #ddd;'><strong>Name</strong></td>
                <td style='padding: 8px; border: 1px solid #ddd;'>{$data['vorname']} {$data['name']}</td>
            </tr>
            <tr>
                <td style='padding: 8px; border: 1px solid #ddd;'><strong>Adresse</strong></td>
                <td style='padding: 8px; border: 1px solid #ddd;'>{$data['strasse']}, {$data['plz_ort']}</td>
            </tr>
            <tr>
                <td style='padding: 8px; border: 1px solid #ddd;'><strong>Telefon</strong></td>
                <td style='padding: 8px; border: 1px solid #ddd;'>{$data['telefon']}</td>
            </tr>
            <tr>
                <td style='padding: 8px; border: 1px solid #ddd;'><strong>E-Mail</strong></td>
                <td style='padding: 8px; border: 1px solid #ddd;'>{$data['email']}</td>
            </tr>
            <tr>
                <td style='padding: 8px; border: 1px solid #ddd;'><strong>Geburtsdatum</strong></td>
                <td style='padding: 8px; border: 1px solid #ddd;'>{$data['geburtsdatum']}</td>
            </tr>
            <tr>
                <td style='padding: 8px; border: 1px solid #ddd;'><strong>Beteiligung</strong></td>
                <td style='padding: 8px; border: 1px solid #ddd;'>" . ($data['beteiligung'] == 'aktiv' ? 'Aktive Beteiligung' : 'Passive Unterstützung') . "</td>
            </tr>
            <tr>
                <td style='padding: 8px; border: 1px solid #ddd;'><strong>Mitgliedsbeitrag</strong></td>
                <td style='padding: 8px; border: 1px solid #ddd;'>";
    
    // Beitrag anzeigen
    if ($data['beitrag'] == '10') {
        $message .= "10 € (Mindestbeitrag)";
    } elseif ($data['beitrag'] == '30') {
        $message .= "30 €";
    } else {
        $message .= "{$data['beitrag_custom_value']} €";
    }
    
    $message .= "</td>
            </tr>
            <tr>
                <td style='padding: 8px; border: 1px solid #ddd;'><strong>IBAN</strong></td>
                <td style='padding: 8px; border: 1px solid #ddd;'>{$data['iban']}</td>
            </tr>
            <tr>
                <td style='padding: 8px; border: 1px solid #ddd;'><strong>Bank</strong></td>
                <td style='padding: 8px; border: 1px solid #ddd;'>{$data['bank']}</td>
            </tr>
            <tr>
                <td style='padding: 8px; border: 1px solid #ddd;'><strong>Datum</strong></td>
                <td style='padding: 8px; border: 1px solid #ddd;'>{$data['date']}</td>
            </tr>
        </table>
        
        <p style='margin-top: 20px;'>Sie können diese Anmeldung im Admin-Bereich der Website einsehen und verwalten.</p>
        
        <hr>
        <p style='font-size: 12px;'>
            Diese E-Mail wurde automatisch vom Anmeldesystem von Pro Basketball GT e.V. gesendet.<br>
            Bei Fragen oder Problemen kontaktieren Sie bitte den Webmaster.
        </p>
    </body>
    </html>
    ";
    
    // Header für HTML-E-Mail
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Pro Basketball GT e.V. Anmeldesystem <noreply@probasketballgt.de>" . "\r\n";
    $headers .= "Reply-To: {$data['email']}" . "\r\n"; // Reply-To auf die E-Mail des neuen Mitglieds setzen
    
    // E-Mail senden
    return mail($to, $subject, $message, $headers);
}
// Funktion zum Exportieren einer Anmeldung als PDF
function export_registration_as_pdf($id) {
    global $conn;
    
    // Anmeldung aus der Datenbank holen
    $registration = get_registration($id);
    
    // Prüfen, ob Anmeldung existiert
    if (!$registration) {
        return false;
    }
    
    // TCPDF-Bibliothek einbinden
    require_once('tcpdf/tcpdf.php');
    
    // Neue PDF-Instanz erstellen
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    
    // PDF-Metadaten setzen
    $pdf->SetCreator('Pro Basketball GT e.V.');
    $pdf->SetAuthor('Pro Basketball GT e.V.');
    $pdf->SetTitle('Mitgliedsantrag ' . $registration['vorname'] . ' ' . $registration['name']);
    $pdf->SetSubject('Beitrittserklärung Pro Basketball GT e.V.');
    
    // Header und Footer deaktivieren
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Standard-Schriftart setzen
    $pdf->SetFont('helvetica', '', 12);
    
    // Seitenränder anpassen
    $pdf->SetMargins(15, 15, 15);
    
    // Neue Seite hinzufügen
    $pdf->AddPage();
    
    // Logo einfügen, falls vorhanden
    if (file_exists('../assets/logo.png')) {
        $pdf->Image('../assets/logo.png', 15, 15, 40, 0, 'PNG');
    }
    
    // Überschrift
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 20, 'Beitrittserklärung Pro Basketball GT e.V.', 0, 1, 'C');
    $pdf->Ln(5);
    
    // Vereinsinformationen
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Pro Basketball GT e.V.', 0, 1, 'R');
    $pdf->Cell(0, 5, 'Pavenstädter Weg 35', 0, 1, 'R');
    $pdf->Cell(0, 5, '33334 Gütersloh', 0, 1, 'R');
    $pdf->Ln(10);
    
    // Einleitung
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Hiermit erkläre ich meinen Beitrittswillen zum Verein „pro Basketball GT e.V."', 0, 1, 'L');
    $pdf->Ln(5);
    
    // Persönliche Daten
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Persönliche Daten:', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 11);
    
    // Tabelle erstellen für persönliche Daten
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(50, 8, 'Name:', 1, 0, 'L', true);
    $pdf->Cell(125, 8, $registration['vorname'] . ' ' . $registration['name'], 1, 1, 'L');
    
    $pdf->Cell(50, 8, 'Adresse:', 1, 0, 'L', true);
    $pdf->Cell(125, 8, $registration['strasse'] . ', ' . $registration['plz_ort'], 1, 1, 'L');
    
    $pdf->Cell(50, 8, 'Telefon:', 1, 0, 'L', true);
    $pdf->Cell(125, 8, $registration['telefon'], 1, 1, 'L');
    
    $pdf->Cell(50, 8, 'E-Mail:', 1, 0, 'L', true);
    $pdf->Cell(125, 8, $registration['email'], 1, 1, 'L');
    
    $pdf->Cell(50, 8, 'Geburtsdatum:', 1, 0, 'L', true);
    $pdf->Cell(125, 8, $registration['geburtsdatum'], 1, 1, 'L');
    $pdf->Ln(5);
    
    // Beteiligung
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Beteiligung:', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 11);
    
    $beteiligung = $registration['beteiligung'] == 'aktiv' ? 
        'Aktive Beteiligung: Ich möchte mich aktiv im Verein beteiligen.' : 
        'Passive Unterstützung: Ich unterstütze den Verein, eine aktive Beteiligung wird mir nicht möglich sein.';
    
    $pdf->MultiCell(0, 8, $beteiligung, 0, 'L');
    $pdf->Ln(5);
    
    // Beitrag
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Mitgliedsbeitrag:', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 11);
    
    $beitrag = '';
    if ($registration['beitrag'] == '10') {
        $beitrag = '10 € (Mindestbeitrag)';
    } elseif ($registration['beitrag'] == '30') {
        $beitrag = '30 €';
    } else {
        $beitrag = $registration['beitrag_custom'] . ' €';
    }
    
    $pdf->Cell(50, 8, 'Jährlicher Beitrag:', 1, 0, 'L', true);
    $pdf->Cell(125, 8, $beitrag, 1, 1, 'L');
    $pdf->Ln(5);
    
    // Bankdaten
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Einzugsermächtigung:', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 11);
    
    $pdf->MultiCell(0, 8, 'Hiermit ermächtige ich den Verein „pro Basketball GT e.V." bis auf Widerruf, den oben genannten Jahres-Mitgliedsbeitrag von folgendem Konto abzubuchen:', 0, 'L');
    $pdf->Ln(3);
    
    $pdf->Cell(50, 8, 'IBAN:', 1, 0, 'L', true);
    $pdf->Cell(125, 8, $registration['iban'], 1, 1, 'L');
    
    $pdf->Cell(50, 8, 'Bank:', 1, 0, 'L', true);
    $pdf->Cell(125, 8, $registration['bank'], 1, 1, 'L');
    $pdf->Ln(10);
    
    // Unterschrift
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Unterschrift:', 0, 1, 'L');
    
    // Unterschriftsbild einfügen, falls vorhanden
    if (!empty($registration['signature_data'])) {
        // Unterschrift aus Base64-Daten extrahieren
        $signature_img = $registration['signature_data'];
        
        // Wenn es ein Daten-URI ist, die tatsächlichen Daten extrahieren
        if (strpos($signature_img, 'data:image') !== false) {
            $signature_parts = explode(',', $signature_img, 2);
            $signature_data = base64_decode($signature_parts[1]);
            
            // Temporäre Datei für die Unterschrift erstellen
            $temp_sig_file = tempnam(sys_get_temp_dir(), 'sig');
            file_put_contents($temp_sig_file, $signature_data);
            
            // Unterschrift in PDF einfügen
            $pdf->Image($temp_sig_file, 15, $pdf->GetY(), 80, 0, 'PNG');
            
            // Temporäre Datei löschen
            unlink($temp_sig_file);
        }
    }
    
    // Unterschriftsdatum
    $pdf->Ln(30); // Platz für die Signatur
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(50, 8, 'Datum:', 1, 0, 'L', true);
    $pdf->Cell(125, 8, $registration['date'], 1, 1, 'L');
    
    // Datenschutzhinweis
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->MultiCell(0, 5, 'Hinweis: Der Unterzeichner hat den Datenschutzbestimmungen des Vereins „pro Basketball GT e.V." zugestimmt.', 0, 'L');
    
    // Fußzeile
    $pdf->Ln(15);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 5, 'Pro Basketball GT e.V. - Pavenstädter Weg 35 - 33334 Gütersloh', 0, 1, 'C');
    $pdf->Cell(0, 5, 'E-Mail: info@probasketballgt.de - Web: www.probasketballgt.de', 0, 1, 'C');
    $pdf->Cell(0, 5, 'Bankverbindung: Volksbank Gütersloh, IBAN: DE31478601250585471800, BIC: GENODEM1GTL', 0, 1, 'C');
    
    // PDF ausgeben
    $filename = 'Beitrittserklärung_' . $registration['name'] . '_' . $registration['vorname'] . '.pdf';
    $pdf->Output($filename, 'D');
    
    return true;
}
?>