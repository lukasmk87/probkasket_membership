// includes/auth.php - Authentifizierung für Admin-Bereich
<?php
session_start();

// Admin-Benutzer festlegen (in Produktion in eine Datenbank auslagern)
$admin_users = [
    'admin' => [
        'password' => password_hash('SecurePassword123', PASSWORD_DEFAULT),
        'name' => 'Administrator'
    ]
];

// Überprüfen, ob der Benutzer eingeloggt ist
function is_logged_in() {
    return isset($_SESSION['admin_loggedin']) && $_SESSION['admin_loggedin'] === true;
}

// Login-Funktion
function login($username, $password) {
    global $admin_users;
    
    if (isset($admin_users[$username]) && password_verify($password, $admin_users[$username]['password'])) {
        $_SESSION['admin_loggedin'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_name'] = $admin_users[$username]['name'];
        return true;
    }
    
    return false;
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
?>
