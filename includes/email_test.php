<?php
// Email-Test-Seite - Verwenden Sie diese, um E-Mail-Probleme zu diagnostizieren
// Aktiviere Fehlerausgabe für Debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'functions.php';

echo "<h1>E-Mail-Test</h1>";

// Einfaches E-Mail senden ohne Anhang
function test_simple_email() {
    $to = "empfaenger@example.com"; // Ändern Sie dies zu Ihrer E-Mail-Adresse
    $subject = "Test-E-Mail von Pro Basketball GT";
    $message = "<html><body><h2>Test-E-Mail</h2><p>Dies ist eine Test-E-Mail.</p></body></html>";
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Pro Basketball GT e.V. <noreply@probasketballgt.de>" . "\r\n";
    
    $result = mail($to, $subject, $message, $headers);
    
    if ($result) {
        echo "<p style='color:green'>Einfache E-Mail erfolgreich gesendet an: $to</p>";
    } else {
        echo "<p style='color:red'>Fehler beim Senden der einfachen E-Mail an: $to</p>";
    }
}

// Funktion zum Testen des Verzeichniszugriffs
function test_directory_access() {
    echo "<h2>Verzeichniszugriff-Test</h2>";
    
    // Prüfen, ob ein uploads-Ordner existiert oder erstellt werden kann
    $uploads_dir = __DIR__ . '/../uploads';
    
    echo "<p>Prüfe Verzeichnis: $uploads_dir</p>";
    
    if (!file_exists($uploads_dir)) {
        echo "<p>Verzeichnis existiert nicht. Versuche zu erstellen...</p>";
        if (mkdir($uploads_dir, 0755, true)) {
            echo "<p style='color:green'>Verzeichnis erfolgreich erstellt!</p>";
        } else {
            echo "<p style='color:red'>Konnte Verzeichnis nicht erstellen! Überprüfen Sie die Berechtigungen.</p>";
        }
    } else {
        echo "<p>Verzeichnis existiert.</p>";
        if (is_writable($uploads_dir)) {
            echo "<p style='color:green'>Verzeichnis ist beschreibbar.</p>";
            
            // Test-Datei erstellen
            $test_file = $uploads_dir . '/test_' . time() . '.txt';
            if (file_put_contents($test_file, 'Test')) {
                echo "<p style='color:green'>Test-Datei erfolgreich erstellt: $test_file</p>";
                // Datei wieder löschen
                if (unlink($test_file)) {
                    echo "<p style='color:green'>Test-Datei erfolgreich gelöscht.</p>";
                } else {
                    echo "<p style='color:red'>Konnte Test-Datei nicht löschen!</p>";
                }
            } else {
                echo "<p style='color:red'>Konnte keine Test-Datei erstellen!</p>";
            }
        } else {
            echo "<p style='color:red'>Verzeichnis ist NICHT beschreibbar!</p>";
        }
    }
}

// Verzeichnistest durchführen
test_directory_access();

// PHP-Konfiguration für E-Mail prüfen
echo "<h2>PHP-Mail-Konfiguration</h2>";
echo "<p>PHP-Version: " . phpversion() . "</p>";

$mail_settings = [
    'SMTP',
    'smtp_port',
    'sendmail_path',
    'sendmail_from',
    'mail.add_x_header',
    'mail.force_extra_parameters'
];

echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>Einstellung</th><th>Wert</th></tr>";
foreach ($mail_settings as $setting) {
    echo "<tr><td>$setting</td><td>" . ini_get($setting) . "</td></tr>";
}
echo "</table>";

// E-Mail-Test-Formular
echo "<h2>E-Mail-Test-Formular</h2>";
echo "<form method='post'>";
echo "<p><label>Test-Typ:</label><br>";
echo "<input type='radio' name='test_type' value='simple' checked> Einfache E-Mail<br>";
echo "<input type='radio' name='test_type' value='attachment'> E-Mail mit Anhang</p>";
echo "<p><label>Empfänger-E-Mail:</label><br>";
echo "<input type='email' name='recipient' size='40' required></p>";
echo "<p><input type='submit' name='send_test' value='Test durchführen'></p>";
echo "</form>";

// Test ausführen, wenn Formular abgeschickt wurde
if (isset($_POST['send_test'])) {
    $recipient = filter_var($_POST['recipient'], FILTER_VALIDATE_EMAIL);
    $test_type = $_POST['test_type'];
    
    if (!$recipient) {
        echo "<p style='color:red'>Bitte geben Sie eine gültige E-Mail-Adresse ein.</p>";
    } else {
        if ($test_type == 'simple') {
            // Einfache E-Mail
            $subject = "Test-E-Mail von Pro Basketball GT";
            $message = "<html><body><h2>Test-E-Mail</h2><p>Dies ist eine Test-E-Mail ohne Anhang.</p></body></html>";
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: Pro Basketball GT e.V. <noreply@probasketballgt.de>" . "\r\n";
            
            $result = mail($recipient, $subject, $message, $headers);
            
            if ($result) {
                echo "<p style='color:green'>Einfache E-Mail erfolgreich gesendet an: $recipient</p>";
            } else {
                echo "<p style='color:red'>Fehler beim Senden der einfachen E-Mail an: $recipient</p>";
            }
        } else {
            // E-Mail mit Anhang - nur testen, wenn die Funktion existiert
            if (function_exists('send_email_with_attachment')) {
                // Test-Datei erstellen
                $uploads_dir = __DIR__ . '/../uploads';
                if (!file_exists($uploads_dir)) {
                    mkdir($uploads_dir, 0755, true);
                }
                
                $test_file = $uploads_dir . '/test_attachment.txt';
                file_put_contents($test_file, 'Dies ist ein Test-Anhang.');
                
                $subject = "Test-E-Mail mit Anhang von Pro Basketball GT";
                $message = "<html><body><h2>Test-E-Mail</h2><p>Dies ist eine Test-E-Mail mit Anhang.</p></body></html>";
                
                $result = send_email_with_attachment($recipient, $subject, $message, $test_file);
                
                // Test-Datei wieder löschen
                @unlink($test_file);
                
                if ($result) {
                    echo "<p style='color:green'>E-Mail mit Anhang erfolgreich gesendet an: $recipient</p>";
                } else {
                    echo "<p style='color:red'>Fehler beim Senden der E-Mail mit Anhang an: $recipient</p>";
                }
            } else {
                echo "<p style='color:red'>Funktion 'send_email_with_attachment' existiert nicht!</p>";
                echo "<p>Stellen Sie sicher, dass Sie die Funktionsdefinitionen zur functions.php hinzugefügt haben.</p>";
            }
        }
    }
}
?>