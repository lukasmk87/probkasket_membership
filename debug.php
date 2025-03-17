<?php
// Fehlerberichterstattung einschalten
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Session starten - MUSS vor jeglicher Ausgabe erfolgen
session_start();

// Jetzt erst HTML-Ausgabe beginnen
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debugging-Informationen</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 20px; }
        h1, h2, h3 { color: #eb971b; }
        pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; overflow: auto; }
        .success { color: green; }
        .error { color: red; }
        ul { padding-left: 20px; }
    </style>
</head>
<body>
    <h1>Debugging-Informationen</h1>

    <?php
    // PHP-Version anzeigen
    echo "<h2>PHP-Version</h2>";
    echo "<p>" . phpversion() . "</p>";

    // Serverpfade überprüfen
    echo "<h2>Serverpfade</h2>";
    echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
    echo "<p>Script Filename: " . $_SERVER['SCRIPT_FILENAME'] . "</p>";
    echo "<p>PHP Self: " . $_SERVER['PHP_SELF'] . "</p>";

    // Verzeichnisstruktur anzeigen
    echo "<h2>Verzeichnisstruktur</h2>";
    echo "<pre>";
    $rootDir = dirname(__FILE__);
    function listFolderFiles($dir) {
        $ffs = scandir($dir);
        echo '<ul>';
        foreach($ffs as $ff){
            if($ff != '.' && $ff != '..'){
                echo '<li>'.$ff;
                if(is_dir($dir.'/'.$ff)) {
                    listFolderFiles($dir.'/'.$ff);
                }
                echo '</li>';
            }
        }
        echo '</ul>';
    }
    listFolderFiles($rootDir);
    echo "</pre>";

    // Datenbankverbindung testen
    echo "<h2>Datenbankverbindung testen</h2>";
    try {
        require_once 'includes/config.php';
        if (isset($conn) && $conn instanceof mysqli) {
            if ($conn->connect_error) {
                echo "<p class='error'>Verbindung fehlgeschlagen: " . $conn->connect_error . "</p>";
            } else {
                echo "<p class='success'>Datenbankverbindung erfolgreich!</p>";
                
                // Prüfen, ob die 'registrations' Tabelle existiert
                $result = $conn->query("SHOW TABLES LIKE 'registrations'");
                if ($result->num_rows > 0) {
                    echo "<p class='success'>Tabelle 'registrations' existiert.</p>";
                    
                    // Überprüfen der Tabellenstruktur
                    $result = $conn->query("DESCRIBE registrations");
                    if ($result) {
                        echo "<p>Tabellenstruktur 'registrations':</p>";
                        echo "<ul>";
                        while ($row = $result->fetch_assoc()) {
                            echo "<li>" . $row['Field'] . " - " . $row['Type'] . "</li>";
                        }
                        echo "</ul>";
                    }
                } else {
                    echo "<p class='error'>Tabelle 'registrations' existiert nicht!</p>";
                }
                
                // Prüfen, ob die 'admin_users' Tabelle existiert
                $result = $conn->query("SHOW TABLES LIKE 'admin_users'");
                if ($result->num_rows > 0) {
                    echo "<p class='success'>Tabelle 'admin_users' existiert.</p>";
                    
                    // Anzahl der Admin-Benutzer prüfen
                    $result = $conn->query("SELECT COUNT(*) as count FROM admin_users");
                    if ($result) {
                        $row = $result->fetch_assoc();
                        echo "<p>Anzahl der Admin-Benutzer: " . $row['count'] . "</p>";
                    }
                } else {
                    echo "<p class='error'>Tabelle 'admin_users' existiert nicht!</p>";
                }
            }
        } else {
            echo "<p class='error'>Datenbankverbindung konnte nicht hergestellt werden. Variable \$conn ist nicht verfügbar.</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>Fehler beim Testen der Datenbankverbindung: " . $e->getMessage() . "</p>";
    }

    // Dateiberechtigungen prüfen
    echo "<h2>Dateiberechtigungen prüfen</h2>";
    $filesToCheck = [
        'index.html',
        'includes/process.php',
        'includes/functions.php',
        'includes/config.php',
        'success.php',
        'admin/dashboard.php'
    ];

    foreach ($filesToCheck as $file) {
        if (file_exists($file)) {
            echo "<p>$file: ";
            $perms = fileperms($file);
            
            switch ($perms & 0xF000) {
                case 0xC000: // Socket
                    $info = 's';
                    break;
                case 0xA000: // Symbolic Link
                    $info = 'l';
                    break;
                case 0x8000: // Regular
                    $info = '-';
                    break;
                case 0x6000: // Block special
                    $info = 'b';
                    break;
                case 0x4000: // Directory
                    $info = 'd';
                    break;
                case 0x2000: // Character special
                    $info = 'c';
                    break;
                case 0x1000: // FIFO pipe
                    $info = 'p';
                    break;
                default: // Unknown
                    $info = 'u';
                    break;
            }

            // Owner
            $info .= (($perms & 0x0100) ? 'r' : '-');
            $info .= (($perms & 0x0080) ? 'w' : '-');
            $info .= (($perms & 0x0040) ?
                        (($perms & 0x0800) ? 's' : 'x' ) :
                        (($perms & 0x0800) ? 'S' : '-'));

            // Group
            $info .= (($perms & 0x0020) ? 'r' : '-');
            $info .= (($perms & 0x0010) ? 'w' : '-');
            $info .= (($perms & 0x0008) ?
                        (($perms & 0x0400) ? 's' : 'x' ) :
                        (($perms & 0x0400) ? 'S' : '-'));

            // World
            $info .= (($perms & 0x0004) ? 'r' : '-');
            $info .= (($perms & 0x0002) ? 'w' : '-');
            $info .= (($perms & 0x0001) ?
                        (($perms & 0x0200) ? 't' : 'x' ) :
                        (($perms & 0x0200) ? 'T' : '-'));

            echo $info;
            
            // Auf einigen Shared Hosting-Umgebungen sind diese Funktionen deaktiviert
            if (function_exists('posix_getpwuid') && function_exists('posix_getgrgid')) {
                echo " (Owner: " . posix_getpwuid(fileowner($file))['name'] . 
                     ", Group: " . posix_getgrgid(filegroup($file))['name'] . ")";
            } else {
                echo " (Owner ID: " . fileowner($file) . ", Group ID: " . filegroup($file) . ")";
            }
            
            echo "</p>";
        } else {
            echo "<p class='error'>$file: Datei existiert nicht!</p>";
        }
    }

    // Formular-Daten in der Session anzeigen
    echo "<h2>Formular-Daten in der Session</h2>";
    if (isset($_SESSION['form_data'])) {
        echo "<pre>";
        print_r($_SESSION['form_data']);
        echo "</pre>";
    } else {
        echo "<p>Keine Formular-Daten in der Session vorhanden.</p>";
    }

    // Fehlermeldungen in der Session anzeigen
    echo "<h2>Fehlermeldungen in der Session</h2>";
    if (isset($_SESSION['error_message'])) {
        echo "<p class='error'>" . $_SESSION['error_message'] . "</p>";
    } else {
        echo "<p>Keine Fehlermeldungen in der Session vorhanden.</p>";
    }

    // Formular zur Fehlersuche anzeigen
    echo "<h2>Formular zur Fehlersuche</h2>";
    echo '<form action="includes/process.php" method="POST">';
    echo '<input type="hidden" name="name" value="Test">';
    echo '<input type="hidden" name="vorname" value="Benutzer">';
    echo '<input type="hidden" name="strasse" value="Teststraße 1">';
    echo '<input type="hidden" name="plz_ort" value="12345 Teststadt">';
    echo '<input type="hidden" name="telefon" value="01234567890">';
    echo '<input type="hidden" name="email" value="test@example.com">';
    echo '<input type="hidden" name="geburtsdatum" value="2000-01-01">';
    echo '<input type="hidden" name="dsgvo" value="1">';
    echo '<input type="hidden" name="beteiligung" value="aktiv">';
    echo '<input type="hidden" name="beitrag" value="10">';
    echo '<input type="hidden" name="iban" value="DE12345678901234567890">';
    echo '<input type="hidden" name="bank" value="Testbank">';
    echo '<input type="hidden" name="signature_data" value="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAASwAAACWCAYAAABkW7XSAAAAAXNSR0IArs4c6QAABGJJREFUeF7t1AEJAAAMAsHZv/RyPNwSyDncOQIECEQEFskpJgECBM5geQICBDICBitTlaAECBgsP0CAQEbAYGWqEpQAAYPlBwgQyAgYrExVghIgYLD8AAECGQGDlalKUAIEDJYfIEAgI2CwMlUJSoCAwfIDBAhkBAxWpipBCRAwWH6AAIGMgMHKVCUoAQIGyw8QIJARMFiZqgQlQMBg+QECBDICBitTlaAECBgsP0CAQEbAYGWqEpQAAYPlBwgQyAgYrExVghIgYLD8AAECGQGDlalKUAIEDJYfIEAgI2CwMlUJSoCAwfIDBAhkBAxWpipBCRAwWH6AAIGMgMHKVCUoAQIGyw8QIJARMFiZqgQlQMBg+QECBDICBitTlaAECBgsP0CAQEbAYGWqEpQAAYPlBwgQyAgYrExVghIgYLD8AAECGQGDlalKUAIEDJYfIEAgI2CwMlUJSoCAwfIDBAhkBAxWpipBCRAwWH6AAIGMgMHKVCUoAQIGyw8QIJARMFiZqgQlQMBg+QECBDICBitTlaAECBgsP0CAQEbAYGWqEpQAAYPlBwgQyAgYrExVghIgYLD8AAECGQGDlalKUAIEDJYfIEAgI2CwMlUJSoCAwfIDBAhkBAxWpipBCRAwWH6AAIGMgMHKVCUoAQIGyw8QIJARMFiZqgQlQMBg+QECBDICBitTlaAECBgsP0CAQEbAYGWqEpQAAYPlBwgQyAgYrExVghIgYLD8AAECGQGDlalKUAIEDJYfIEAgI2CwMlUJSoCAwfIDBAhkBAxWpipBCRAwWH6AAIGMgMHKVCUoAQIGyw8QIJARMFiZqgQlQMBg+QECBDICBitTlaAECBgsP0CAQEbAYGWqEpQAAYPlBwgQyAgYrExVghIgYLD8AAECGQGDlalKUAIEDJYfIEAgI2CwMlUJSoCAwfIDBAhkBAxWpipBCRAwWH6AAIGMgMHKVCUoAQIGyw8QIJARMFiZqgQlQMBg+QECBDICBitTlaAECBgsP0CAQEbAYGWqEpQAAYPlBwgQyAgYrExVghIgYLD8AAECGQGDlalKUAIEDJYfIEAgI2CwMlUJSoCAwfIDBAhkBAxWpipBCRAwWH6AAIGMgMHKVCUoAQIGyw8QIJARMFiZqgQlQMBg+QECBDICBitTlaAECBgsP0CAQEbAYGWqEpQAAYPlBwgQyAgYrExVghIgYLD8AAECGQGDlalKUAIEDJYfIEAgI2CwMlUJSoCAwfIDBAhkBAxWpipBCRAwWH6AAIGMgMHKVCUoAQIGyw8QIJARMFiZqgQlQMBg+QECBDICBitTlaAECBgsP0CAQEbAYGWqEpQAAYPlBwgQyAgYrExVghIgYLD8AAECGQGDlalKUAIEDJYfIEAgI2CwMlUJSoCAwfIDBAhkBAxWpipBCRAwWH6AAIGMgMHKVCUoAQIGyw8QIJARMFiZqgQlQMBg+QECBDICBitTlaAECBgsP0CAQEbAYGWqEpQAgQdWMQCX4yW9owAAAABJRU5ErkJggg==">';
    echo '<input type="hidden" name="date" value="' . date('Y-m-d') . '">';
    echo '<button type="submit">Test-Anmeldung senden</button>';
    echo '</form>';

    // PHP-Informationen anzeigen (reduziert)
    echo "<h2>PHP-Module und Erweiterungen</h2>";
    echo "<pre>";
    $extensions = get_loaded_extensions();
    sort($extensions);
    echo "Geladene Erweiterungen:\n";
    foreach ($extensions as $ext) {
        echo "- $ext\n";
    }
    echo "</pre>";
    
    // Session-Konfiguration anzeigen
    echo "<h2>Session-Konfiguration</h2>";
    echo "<pre>";
    $sessionSettings = [
        'session.save_path',
        'session.name',
        'session.save_handler',
        'session.use_cookies',
        'session.use_only_cookies',
        'session.cookie_lifetime',
        'session.cookie_path',
        'session.cookie_domain',
        'session.cookie_secure',
        'session.cookie_httponly',
        'session.use_strict_mode',
        'session.gc_maxlifetime',
        'session.gc_probability',
        'session.gc_divisor'
    ];
    
    foreach ($sessionSettings as $setting) {
        echo "$setting: " . ini_get($setting) . "\n";
    }
    echo "</pre>";
    ?>
</body>
</html>