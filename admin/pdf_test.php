<?php
// Setze Header und Buffer-Kontrolle vor jeder Ausgabe
ob_start();

// Fehlerberichterstattung aktivieren, aber nicht anzeigen (in Log schreiben)
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../includes/auth.php';
require_login(); // Sicherheit: Nur für eingeloggte Benutzer

try {
    // TCPDF in verschiedenen möglichen Pfaden suchen
    $tcpdf_paths = [
        __DIR__ . '/../tcpdf/tcpdf.php',
        __DIR__ . '/../../tcpdf/tcpdf.php',
        __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php'
    ];
    
    $found_tcpdf = null;
    foreach ($tcpdf_paths as $path) {
        if (file_exists($path)) {
            $found_tcpdf = $path;
            break;
        }
    }
    
    if (!$found_tcpdf) {
        throw new Exception("TCPDF nicht gefunden. Bitte installieren Sie die Bibliothek zuerst.");
    }
    
    // TCPDF einbinden
    require_once($found_tcpdf);
    
    // Einfaches PDF erstellen
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    
    // Metadaten
    $pdf->SetCreator('Pro Basketball GT e.V.');
    $pdf->SetAuthor('PDF Test Script');
    $pdf->SetTitle('TCPDF Test');
    $pdf->SetSubject('PDF Test');
    
    // Header und Footer deaktivieren
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Standard-Schriftart
    $pdf->SetFont('helvetica', '', 12);
    
    // Neue Seite
    $pdf->AddPage();
    
    // Einfacher Text
    $pdf->Cell(0, 10, 'TCPDF Test - Basis-Funktionalität OK', 0, 1, 'C');
    $pdf->Ln(10);
    
    // Test für deutsche Umlaute
    $pdf->Cell(0, 10, 'Test für deutsche Umlaute: ä ö ü ß Ä Ö Ü', 0, 1, 'L');
    $pdf->Ln(5);
    
    // Versuch, verschiedene Schriftgrößen zu verwenden
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Fette Schrift - Größe 16', 0, 1, 'L');
    
    $pdf->SetFont('helvetica', 'I', 14);
    $pdf->Cell(0, 10, 'Kursive Schrift - Größe 14', 0, 1, 'L');
    
    $pdf->SetFont('courier', '', 12);
    $pdf->Cell(0, 10, 'Courier-Schrift - Größe 12', 0, 1, 'L');
    $pdf->Ln(10);
    
    // Tabelle
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(40, 10, 'Spalte 1', 1, 0, 'C');
    $pdf->Cell(40, 10, 'Spalte 2', 1, 0, 'C');
    $pdf->Cell(40, 10, 'Spalte 3', 1, 1, 'C');
    
    $pdf->Cell(40, 10, 'Wert 1', 1, 0, 'L');
    $pdf->Cell(40, 10, 'Wert 2', 1, 0, 'L');
    $pdf->Cell(40, 10, 'Wert 3', 1, 1, 'L');
    $pdf->Ln(10);
    
    // Test für Hintergrundfarben
    $pdf->SetFillColor(200, 220, 255);
    $pdf->Cell(0, 10, 'Zelle mit Hintergrundfarbe', 1, 1, 'C', true);
    $pdf->Ln(10);
    
    // Versuch, ein Bild einzufügen (falls vorhanden)
    $logo_path = __DIR__ . '/../assets/logo.png';
    if (file_exists($logo_path)) {
        try {
            $pdf->Image($logo_path, 15, $pdf->GetY(), 40, 0, 'PNG');
            $pdf->Ln(50); // Platz für das Bild
            $pdf->Cell(0, 10, 'Bild wurde erfolgreich eingefügt', 0, 1, 'L');
        } catch (Exception $e) {
            $pdf->Cell(0, 10, 'Fehler beim Einfügen des Bildes: ' . $e->getMessage(), 0, 1, 'L');
        }
    } else {
        $pdf->Cell(0, 10, 'Kein Logo gefunden unter: ' . $logo_path, 0, 1, 'L');
    }
    
    // Informationen zur PDF-Generierung
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 10, 'TCPDF-Version: ' . TCPDF_STATIC::getTCPDFVersion(), 0, 1, 'L');
    $pdf->Cell(0, 10, 'PHP-Version: ' . phpversion(), 0, 1, 'L');
    $pdf->Cell(0, 10, 'Uhrzeit: ' . date('Y-m-d H:i:s'), 0, 1, 'L');
    
    // PDF ausgeben
    $pdf->Output('tcpdf_test.pdf', 'D'); // 'D' bedeutet Download erzwingen
    
    // Buffer leeren (obwohl PDF-Output dies normalerweise tut)
    ob_end_clean();
    exit;
} catch (Exception $e) {
    // Buffer leeren und HTML-Fehlerseite anzeigen
    ob_end_clean();
    header('Content-Type: text/html; charset=utf-8');
    
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>PDF-Test Fehler</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h1 { color: #d9534f; }
            .error { background-color: #f9f2f2; border-left: 4px solid #d9534f; padding: 10px; margin: 20px 0; }
            pre { background: #f7f7f9; padding: 10px; overflow: auto; }
        </style>
    </head>
    <body>
        <h1>Fehler beim PDF-Test</h1>
        <div class='error'>
            <p><strong>Fehlermeldung:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
            <p><strong>Datei:</strong> " . htmlspecialchars($e->getFile()) . " (Zeile " . $e->getLine() . ")</p>
        </div>
        <h2>Stack-Trace:</h2>
        <pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>
        <p><a href='pdf_diagnostic.php'>Zurück zur Diagnoseseite</a></p>
    </body>
    </html>";
    exit;
}
?>