<?php
// includes/auth.php - Überarbeitete Version mit Datenbankanbindung
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
    
    $stmt = $conn->prepare("SELECT id, username, password, name, email, role FROM admin_users WHERE username = ? AND is_active = 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            // Login erfolgreich - Session setzen
            $_SESSION['admin_loggedin'] = true;
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_name'] = $user['name'];
            $_SESSION['admin_email'] = $user['email'];
            $_SESSION['admin_role'] = $user['role'];
            
            // Letzten Login-Zeitpunkt aktualisieren
            update_last_login($user['id']);
            
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
    session_unset();
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

// Funktion zum Abrufen aller Admin-Benutzer
function get_all_admin_users() {
    global $conn;
    
    $result = $conn->query("SELECT id, username, name, email, role, last_login, created_at, is_active FROM admin_users ORDER BY username");
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    return $users;
}

// Funktion zum Abrufen eines einzelnen Admin-Benutzers
function get_admin_user($id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT id, username, name, email, role, last_login, created_at, updated_at, is_active FROM admin_users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    $stmt->close();
    
    return $user;
}

// Funktion zum Hinzufügen eines neuen Admin-Benutzers
function add_admin_user($data) {
    global $conn;
    
    // Passwort hashen
    $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO admin_users (username, password, name, email, role, created_at, updated_at, is_active) VALUES (?, ?, ?, ?, ?, NOW(), NOW(), ?)");
    
    $is_active = isset($data['is_active']) ? 1 : 0;
    
    $stmt->bind_param("sssssi", 
        $data['username'],
        $hashed_password,
        $data['name'],
        $data['email'],
        $data['role'],
        $is_active
    );
    
    $result = $stmt->execute();
    $user_id = $result ? $conn->insert_id : false;
    
    $stmt->close();
    
    return $user_id;
}

// Funktion zum Aktualisieren eines Admin-Benutzers
function update_admin_user($id, $data) {
    global $conn;
    
    // Wenn ein neues Passwort angegeben wurde, dieses aktualisieren
    if (!empty($data['password'])) {
        $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("UPDATE admin_users SET username = ?, password = ?, name = ?, email = ?, role = ?, updated_at = NOW(), is_active = ? WHERE id = ?");
        
        $is_active = isset($data['is_active']) ? 1 : 0;
        
        $stmt->bind_param("sssssii", 
            $data['username'],
            $hashed_password,
            $data['name'],
            $data['email'],
            $data['role'],
            $is_active,
            $id
        );
    } else {
        // Kein neues Passwort, alles außer dem Passwort aktualisieren
        $stmt = $conn->prepare("UPDATE admin_users SET username = ?, name = ?, email = ?, role = ?, updated_at = NOW(), is_active = ? WHERE id = ?");
        
        $is_active = isset($data['is_active']) ? 1 : 0;
        
        $stmt->bind_param("ssssii", 
            $data['username'],
            $data['name'],
            $data['email'],
            $data['role'],
            $is_active,
            $id
        );
    }
    
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

// Funktion zum Löschen eines Admin-Benutzers
function delete_admin_user($id) {
    global $conn;
    
    // Nicht den letzten Admin-Benutzer löschen
    $admin_count = count_admin_users_by_role('admin');
    if ($admin_count <= 1) {
        $user = get_admin_user($id);
        if ($user && $user['role'] === 'admin') {
            return false; // Nicht den letzten Admin löschen
        }
    }
    
    $stmt = $conn->prepare("DELETE FROM admin_users WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

// Funktion zum Zählen der Benutzer nach Rolle
function count_admin_users_by_role($role) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM admin_users WHERE role = ?");
    $stmt->bind_param("s", $role);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    
    $stmt->close();
    
    return $count;
}

// Funktion zur Überprüfung, ob ein Benutzername bereits existiert
function username_exists($username, $exclude_id = null) {
    global $conn;
    
    if ($exclude_id) {
        $stmt = $conn->prepare("SELECT id FROM admin_users WHERE username = ? AND id != ?");
        $stmt->bind_param("si", $username, $exclude_id);
    } else {
        $stmt = $conn->prepare("SELECT id FROM admin_users WHERE username = ?");
        $stmt->bind_param("s", $username);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    
    $stmt->close();
    
    return $exists;
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
?>
