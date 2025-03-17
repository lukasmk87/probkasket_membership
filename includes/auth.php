<?php
// includes/auth.php - Funktion zum Überprüfen des Logins und der Berechtigungen
session_start();
require_once 'config.php';

// Überprüfen, ob der Benutzer eingeloggt ist
function is_logged_in() {
    return isset($_SESSION['admin_loggedin']) && $_SESSION['admin_loggedin'] === true;
}

// Überprüfen, ob der eingeloggte Benutzer Admin-Rechte hat
function is_admin() {
    return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'admin';
}

// Login-Funktion mit Datenbankanbindung
function login($username, $password) {
    global $conn;
    
    // SQL-Anfrage vorbereiten
    $stmt = $conn->prepare("SELECT id, username, password, name, email, role FROM admin_users WHERE username = ? AND is_active = 1");
    
    // Parameter binden
    $stmt->bind_param("s", $username);
    
    // SQL-Anfrage ausführen
    $stmt->execute();
    
    // Ergebnis holen
    $result = $stmt->get_result();
    
    // Prüfen, ob genau ein Benutzer gefunden wurde
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Überprüfung des Passworts
        if (password_verify($password, $user['password'])) {
            // Login erfolgreich - Session-Variablen setzen
            $_SESSION['admin_loggedin'] = true;
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_name'] = $user['name'];
            $_SESSION['admin_email'] = $user['email'];
            $_SESSION['admin_role'] = $user['role'];
            
            // Letzten Login-Zeitpunkt aktualisieren
            update_last_login($user['id']);
            
            $stmt->close();
            return true;
        }
    }
    
    $stmt->close();
    return false;
}

// Aktualisieren des letzten Login-Zeitpunkts
function update_last_login($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
}

// Logout-Funktion
function logout() {
    // Alle Session-Variablen löschen
    session_unset();
    
    // Session zerstören
    session_destroy();
}

// Überprüfen des Login-Status und Umleiten falls nicht eingeloggt
function require_login() {
    if (!is_logged_in()) {
        header("Location: index.php");
        exit;
    }
}

// Überprüfen, ob der Benutzer Admin-Rechte hat, sonst umleiten
function require_admin() {
    require_login();
    
    if (!is_admin()) {
        header("Location: dashboard.php?error=insufficient_privileges");
        exit;
    }
}

// Rest des Codes für Benutzerverwaltung bleibt unverändert
// ... (Funktionen für Benutzerverwaltung)

// CSRF-Token generieren
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// CSRF-Token validieren
function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}