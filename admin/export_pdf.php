<?php
// Fehler anzeigen (für Debugging - später auf 0 setzen)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Output-Buffering starten für bessere Fehlerbehandlung
ob_start();

require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Überprüfen, ob der Benutzer eingeloggt ist
require_login();

// Anmeldungs-ID aus URL holen
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Wenn keine gültige ID, zum Dashboard zurückkehren
if ($id <= 0) {
    error_log("PDF-Export: Ungültige ID: $id");
    header("Location: dashboard.php");
    echo "<script>window.location.href = 'dashboard.php';</script>";
    ob_end_flush();
    exit;
}

// Anmeldung aus der Datenbank holen
$registration = get_registration($id);

// Wenn Anmeldung nicht gefunden, zum Dashboard zurückkehren
if (!$registration) {
    error_log("PDF-Export: Anmeldung mit ID $id nicht gefunden");
    header("Location: dashboard.php?error=" . urlencode("Anmeldung nicht gefunden."));
    echo "<script>window.location.href = 'dashboard.php?error=" . urlencode("Anmeldung nicht gefunden.") . "';</script>";
    ob_end_flush();
    exit;
}

// Prüfen, ob die TCPDF-Funktionen existieren
if (!function_exists('export_registration_as_pdf') && !function_exists('export_registration_as_pdf_alt')) {
    error_log("PDF-Export: PDF-Exportfunktion nicht gefunden in functions.php");
    header("Location: view.php?id=$id&error=" . urlencode("PDF-Exportfunktion nicht gefunden. Bitte wenden Sie sich an den Administrator."));
    echo "<script>window.location.href = 'view.php?id=$id&error=" . urlencode("PDF-Exportfunktion nicht gefunden.") . "';</script>";
    ob_end_flush();
    exit;
}

// Versuchen, die Anmeldung als PDF zu exportieren
try {
    // Zuerst die alternative Funktion verwenden, wenn verfügbar
    if (function_exists('export_registration_as_pdf_alt')) {
        error_log("PDF-Export: Versuche alternative PDF-Exportfunktion für ID $id");
        $result = export_registration_as_pdf_alt($id);
    } else {
        error_log("PDF-Export: Versuche Standard-PDF-Exportfunktion für ID $id");
        $result = export_registration_as_pdf($id);
    }
    
    // Falls die PDF-Funktionen die Ausführung nicht beenden,
    // zurück zur Detailansicht
    error_log("PDF-Export: PDF-Funktion hat nicht wie erwartet beendet für ID $id");
    header("Location: view.php?id=$id");
    echo "<script>window.location.href = 'view.php?id=$id';</script>";
    ob_end_flush();
    exit;
    
} catch (Exception $e) {
    // Bei Fehler: Buffer leeren und HTML-Fehlerseite anzeigen
    ob_end_clean();
    
    error_log("PDF-Export Fehler für ID $id: " . $e->getMessage());
    error_log("Fehlercode: " . $e->getCode());
    error_log("Fehler in Datei: " . $e->getFile() . " Zeile: " . $e->getLine());
    error_log("Stack Trace: " . $e->getTraceAsString());
    
    // HTML-Fehlerseite anzeigen
    header('Content-Type: text/html; charset=utf-8');
    
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>PDF-Export Fehler</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
            h1 { color: #d9534f; }
            .error { background-color: #f9f2f2; border-left: 4px solid #d9534f; padding: 15px; margin: 20px 0; }
            pre { background: #f7f7f9; padding: 10px; overflow: auto; font-size: 13px; }
            .btn { display: inline-block; padding: 8px 16px; background-color: #eb971b; color: white; 
                   text-decoration: none; border-radius: 4px; margin-right: 10px; }
            .btn-secondary { background-color: #6c757d; }
            .actions { margin-top: 30px; }
        </style>
    </head>
    <body>
        <h1>Fehler beim PDF-Export</h1>
        
        <div class='error'>
            <p><strong>Fehlermeldung:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
            <p><strong>Mitglied:</strong> " . htmlspecialchars($registration['vorname'] . ' ' . $registration['name']) . "</p>
            <p><strong>In Datei:</strong> " . htmlspecialchars($e->getFile()) . " (Zeile " . $e->getLine() . ")</p>
        </div>
        
        <h2>Mögliche Lösungen:</h2>
        <ol>
            <li>Überprüfen Sie die Installation der TCPDF-Bibliothek</li>
            <li>Stellen Sie sicher, dass PHP-GD aktiviert ist</li>
            <li>Überprüfen Sie die Schreibrechte für temporäre Verzeichnisse</li>
            <li>Prüfen Sie, ob die PDF-Exportfunktion korrekt implementiert ist</li>
        </ol>
        
        <div class='actions'>
            <a href='pdf_diagnostic.php?id=$id' class='btn'>PDF-Diagnose ausführen</a>
            <a href='view.php?id=$id' class='btn btn-secondary'>Zurück zur Mitgliedsansicht</a>
        </div>
        
        <h3>Technische Details (für Administrator):</h3>
        <pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>
    </body>
    </html>";
    exit;
}
?>