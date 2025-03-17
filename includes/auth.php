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

// Funktionen für Benutzerverwaltung

// Abrufen aller Admin-Benutzer
function get_all_admin_users() {
    global $conn;
    
    $sql = "SELECT * FROM admin_users ORDER BY role DESC, name ASC";
    $result = $conn->query($sql);
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    return $users;
}

// Abrufen eines bestimmten Admin-Benutzers über seine ID
function get_admin_user($id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM admin_users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    $stmt->close();
    
    return $user;
}

// Hinzufügen eines neuen Admin-Benutzers
function add_admin_user($data) {
    global $conn;
    
    // Passwort hashen
    $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
    
    // Aktiv-Status umwandeln
    $is_active = $data['is_active'] ? 1 : 0;
    
    $stmt = $conn->prepare("INSERT INTO admin_users (username, password, name, email, role, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssssi", $data['username'], $hashed_password, $data['name'], $data['email'], $data['role'], $is_active);
    
    $result = $stmt->execute();
    $user_id = $result ? $conn->insert_id : 0;
    
    $stmt->close();
    
    return $user_id;
}

// Aktualisieren eines bestehenden Admin-Benutzers
function update_admin_user($id, $data) {
    global $conn;
    
    // Wenn Passwort leer ist, behalten wir das alte bei
    if (empty($data['password'])) {
        $sql = "UPDATE admin_users SET username = ?, name = ?, email = ?, role = ?, is_active = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        
        // Aktiv-Status umwandeln
        $is_active = $data['is_active'] ? 1 : 0;
        
        $stmt->bind_param("ssssii", $data['username'], $data['name'], $data['email'], $data['role'], $is_active, $id);
    } else {
        // Neues Passwort hashen
        $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $sql = "UPDATE admin_users SET username = ?, password = ?, name = ?, email = ?, role = ?, is_active = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        
        // Aktiv-Status umwandeln
        $is_active = $data['is_active'] ? 1 : 0;
        
        $stmt->bind_param("sssssii", $data['username'], $hashed_password, $data['name'], $data['email'], $data['role'], $is_active, $id);
    }
    
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

// Löschen eines Admin-Benutzers
function delete_admin_user($id) {
    global $conn;
    
    $stmt = $conn->prepare("DELETE FROM admin_users WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

// Zählen der Admin-Benutzer nach Rolle
function count_admin_users_by_role($role) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM admin_users WHERE role = ?");
    $stmt->bind_param("s", $role);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $stmt->close();
    
    return $row['count'];
}

// Prüfen, ob ein Benutzername bereits existiert (optional: außer bei dem Benutzer mit der angegebenen ID)
function username_exists($username, $exclude_id = 0) {
    global $conn;
    
    if ($exclude_id > 0) {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM admin_users WHERE username = ? AND id != ?");
        $stmt->bind_param("si", $username, $exclude_id);
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM admin_users WHERE username = ?");
        $stmt->bind_param("s", $username);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $stmt->close();
    
    return $row['count'] > 0;
}

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

// Passwort eines Admin-Benutzers ändern
function change_admin_password($user_id, $new_password) {
    global $conn;
    
    // Passwort hashen
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed_password, $user_id);
    
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}