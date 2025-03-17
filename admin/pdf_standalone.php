<?php
// Standalone PDF-Generator mit Unterschrift und korrigiertem Datum
// Keine Leerzeilen vor oder nach PHP-Tags!

// Session starten für Auth-Check
session_start();

// Überprüfen, ob Benutzer eingeloggt ist
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Zugriff verweigert';
    exit;
}

// Fehlerberichterstattung unterdrücken, um unerwünschte Ausgaben zu vermeiden
error_reporting(0);

// ID des Antrags holen
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    die('Ungültige ID');
}

// Database connection
$db_host = 'localhost';
$db_user = 'd0431d6a';  // Ändern zu Ihrem Datenbankbenutzer
$db_pass = 'rp8qEdqz5kFAzixM8YrM';  // Ihr Datenbankpasswort
$db_name = 'd0431d6a';  // Ihr Datenbankname

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die('Database connection failed');
}
$conn->set_charset("utf8");

// Anmeldung aus der Datenbank holen
$stmt = $conn->prepare("SELECT * FROM registrations WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$registration = $result->fetch_assoc();
$stmt->close();

if (!$registration) {
    die('Anmeldung nicht gefunden');
}

// TCPDF-Bibliothek einbinden
$tcpdf_file = __DIR__ . '/../tcpdf/tcpdf.php';
if (!file_exists($tcpdf_file)) {
    die('TCPDF nicht gefunden');
}
require_once($tcpdf_file);

// PDF Headers setzen
header('Content-Type: application/pdf');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Verarbeite Unterschriftsdaten (falls vorhanden)
$signature_image = null;
if (!empty($registration['signature_data']) && strpos($registration['signature_data'], 'data:image') !== false) {
    try {
        // Daten-URI aufspalten
        $signature_parts = explode(',', $registration['signature_data'], 2);
        if (count($signature_parts) == 2) {
            // Base64-Daten dekodieren
            $signature_image = base64_decode($signature_parts[1]);
        }
    } catch (Exception $e) {
        // Fehler bei der Signaturverarbeitung ignorieren
        $signature_image = null;
    }
}

// Datumsformatierung vorbereiten
$formattedDate = "";
// Prüfen ob date-Feld existiert
if (isset($registration['date']) && !empty($registration['date'])) {
    // Versuche das Datum zu formatieren
    try {
        $date = new DateTime($registration['date']);
        $formattedDate = $date->format('d.m.Y');
    } catch (Exception $e) {
        // Bei ungültigem Datum: Originalwert beibehalten
        $formattedDate = $registration['date'];
    }
} elseif (isset($registration['date_submitted']) && !empty($registration['date_submitted'])) {
    // Alternative: Verwende das Einreichungsdatum, falls date-Feld nicht vorhanden
    try {
        $date = new DateTime($registration['date_submitted']);
        $formattedDate = $date->format('d.m.Y');
    } catch (Exception $e) {
        $formattedDate = $registration['date_submitted'];
    }
} else {
    // Fallback: Aktuelles Datum
    $formattedDate = date('d.m.Y');
}

// PDF erstellen
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Pro Basketball GT e.V.');
$pdf->SetAuthor('Pro Basketball GT e.V.');
$pdf->SetTitle('Mitgliedsantrag ' . $registration['vorname'] . ' ' . $registration['name']);
$pdf->SetSubject('Beitrittserklärung Pro Basketball GT e.V.');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetFont('helvetica', '', 12);
$pdf->SetMargins(15, 15, 15);

// Neue Seite
$pdf->AddPage();

// Logo einfügen (falls vorhanden)
$logo_path = __DIR__ . '/../assets/logo.png';
if (file_exists($logo_path)) {
    try {
        $pdf->Image($logo_path, 15, 15, 40, 0, 'PNG');
    } catch (Exception $e) {
        // Fehler beim Logo-Laden ignorieren
    }
}

// --- Inhalt ---
// Überschrift
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 20, 'Beitrittserklärung Pro Basketball GT e.V.', 0, 1, 'C');

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

// Mitgliedsdaten
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Mitgliedsdaten:', 0, 1, 'L');

// Tabelle erstellen für persönliche Daten
$pdf->SetFillColor(240, 240, 240);
$pdf->SetFont('helvetica', '', 11);

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

// Unterschrift und Datum
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'Unterschrift:', 0, 1, 'L');

// Unterschriftsbild einfügen, wenn verfügbar
if ($signature_image !== null) {
    try {
        // Temporäre Datei für die Unterschrift erstellen
        $temp_dir = sys_get_temp_dir();
        $temp_sig_file = tempnam($temp_dir, 'sig');
        file_put_contents($temp_sig_file, $signature_image);
        
        // Position und Größe für die Unterschrift
        $pdf->Image($temp_sig_file, 15, $pdf->GetY(), 80, 0, 'PNG');
        
        // Temporäre Datei löschen
        @unlink($temp_sig_file);
        
        // Platz für die Unterschrift
        $pdf->Ln(30);
    } catch (Exception $e) {
        // Bei Fehler: Texthinweis anzeigen
        $pdf->Cell(0, 8, "[Elektronische Unterschrift liegt vor]", 0, 1, 'L');
        $pdf->Ln(5);
    }
} else {
    // Wenn keine Unterschrift: Hinweis anzeigen
    $pdf->Cell(0, 8, "[Elektronische Unterschrift liegt vor]", 0, 1, 'L');
    $pdf->Ln(5);
}

// Unterschriftsdatum - Hervorgehoben
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(50, 8, 'Datum:', 1, 0, 'L', true);
$pdf->Cell(125, 8, $formattedDate, 1, 1, 'L');

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

// Eindeutigen Dateinamen generieren
$filename = 'Beitrittserklärung_' . 
           preg_replace('/[^a-zA-Z0-9]/', '_', $registration['name']) . '_' . 
           preg_replace('/[^a-zA-Z0-9]/', '_', $registration['vorname']) . '.pdf';

// PDF ausgeben
$pdf->Output($filename, 'D');

// Verbindung schließen
$conn->close();
?>