<?php
// Fehlerberichterstattung maximieren
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../includes/auth.php';
require_login(); // Nur eingeloggte Benutzer dürfen zugreifen

echo "<h1>PDF-Export Diagnose</h1>";

// 1. PHP-Version überprüfen
echo "<h2>PHP-Umgebung</h2>";
echo "PHP-Version: " . phpversion() . "<br>";
echo "Betriebssystem: " . PHP_OS . "<br>";
echo "Webserver: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";

// 2. TCPDF-Bibliothek überprüfen
echo "<h2>TCPDF-Bibliothek</h2>";
$tcpdf_paths = [
    __DIR__ . '/../tcpdf/tcpdf.php',
    __DIR__ . '/../../tcpdf/tcpdf.php',
    __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php'
];

foreach ($tcpdf_paths as $path) {
    echo "Pfad: " . $path . " - ";
    if (file_exists($path)) {
        echo "<span style='color:green'>EXISTIERT</span><br>";
        $found_tcpdf = $path;
    } else {
        echo "<span style='color:red'>NICHT GEFUNDEN</span><br>";
    }
}

// 3. GD-Bibliothek überprüfen
echo "<h2>Bildverarbeitung</h2>";
if (extension_loaded('gd')) {
    echo "GD-Bibliothek: <span style='color:green'>INSTALLIERT</span><br>";
    echo "GD-Info: <pre>";
    print_r(gd_info());
    echo "</pre>";
} else {
    echo "GD-Bibliothek: <span style='color:red'>NICHT INSTALLIERT</span><br>";
}

// 4. Temporäre Verzeichnisse überprüfen
echo "<h2>Temporäre Verzeichnisse</h2>";
$temp_dirs = [
    sys_get_temp_dir(),
    __DIR__ . '/../tmp',
    '/tmp'
];

foreach ($temp_dirs as $dir) {
    echo "Verzeichnis: " . $dir . " - ";
    if (file_exists($dir)) {
        echo "Existiert: <span style='color:green'>JA</span>, ";
        if (is_writable($dir)) {
            echo "Schreibbar: <span style='color:green'>JA</span>";
        } else {
            echo "Schreibbar: <span style='color:red'>NEIN</span>";
        }
    } else {
        echo "Existiert: <span style='color:red'>NEIN</span>";
    }
    echo "<br>";
}

// 5. Testen Sie einfaches TCPDF-Beispiel
echo "<h2>TCPDF-Test</h2>";
if (isset($found_tcpdf)) {
    try {
        echo "Versuche TCPDF zu laden...<br>";
        require_once($found_tcpdf);
        
        echo "TCPDF wurde geladen. Versuche, eine leere PDF zu erstellen...<br>";
        $pdf = new TCPDF();
        echo "TCPDF-Instanz wurde erstellt.<br>";
        
        echo "TCPDF-Version: " . TCPDF_STATIC::getTCPDFVersion() . "<br>";
        
        echo "<p>Um einen vollständigen PDF-Test durchzuführen, klicken Sie auf diesen Button:</p>";
        echo "<form action='pdf_test.php' method='post'>";
        echo "<input type='submit' value='PDF-Test durchführen'>";
        echo "</form>";
    } catch (Exception $e) {
        echo "<span style='color:red'>FEHLER beim Laden von TCPDF: " . $e->getMessage() . "</span><br>";
        echo "Stack-Trace: <pre>" . $e->getTraceAsString() . "</pre>";
    }
} else {
    echo "<span style='color:red'>TCPDF wurde nicht gefunden. Bitte installieren Sie die Bibliothek.</span><br>";
}

// 6. Testen einer Unterschrift (falls Anmeldungs-ID verfügbar)
if (isset($_GET['id']) && intval($_GET['id']) > 0) {
    $id = intval($_GET['id']);
    echo "<h2>Unterschrift-Test für Anmeldung #$id</h2>";
    
    require_once '../includes/functions.php';
    $registration = get_registration($id);
    
    if ($registration) {
        echo "Anmeldung gefunden: " . htmlspecialchars($registration['vorname'] . ' ' . $registration['name']) . "<br>";
        
        if (!empty($registration['signature_data'])) {
            echo "Unterschriftsdaten vorhanden. Länge: " . strlen($registration['signature_data']) . " Zeichen<br>";
            echo "Unterschriftsdaten beginnen mit: " . htmlspecialchars(substr($registration['signature_data'], 0, 50)) . "...<br>";
            
            echo "<h3>Unterschriftsbild-Vorschau:</h3>";
            echo "<img src='" . htmlspecialchars($registration['signature_data']) . "' alt='Unterschrift' style='border:1px solid black; max-width:300px;'><br>";
        } else {
            echo "<span style='color:red'>Keine Unterschriftsdaten vorhanden!</span><br>";
        }
    } else {
        echo "<span style='color:red'>Anmeldung mit ID $id nicht gefunden!</span><br>";
    }
}

// 7. PDF-Export-Funktion prüfen
echo "<h2>PDF-Export-Funktion</h2>";
$function_file = __DIR__ . '/../includes/functions.php';
if (file_exists($function_file)) {
    echo "functions.php gefunden.<br>";
    
    $content = file_get_contents($function_file);
    if (strpos($content, 'function export_registration_as_pdf') !== false) {
        echo "Funktion 'export_registration_as_pdf' gefunden.<br>";
    } else {
        echo "<span style='color:red'>Funktion 'export_registration_as_pdf' NICHT gefunden!</span><br>";
    }
} else {
    echo "<span style='color:red'>functions.php nicht gefunden!</span><br>";
}

// 8. Aktuelle PHP-Einstellungen
echo "<h2>PHP-Einstellungen</h2>";
echo "<table border='1'>";
echo "<tr><th>Einstellung</th><th>Wert</th></tr>";
$settings = [
    'memory_limit', 
    'max_execution_time', 
    'upload_max_filesize', 
    'post_max_size',
    'display_errors',
    'error_reporting',
    'file_uploads',
    'allow_url_fopen'
];
foreach ($settings as $setting) {
    echo "<tr><td>$setting</td><td>" . ini_get($setting) . "</td></tr>";
}
echo "</table>";
?>