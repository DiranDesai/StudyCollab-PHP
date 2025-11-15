<?php
session_start();

// --- Clear all session data ---
$_SESSION = [];

// --- Delete the session cookie ---
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// --- Also delete the remember_me cookie ---
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
}

// --- Destroy the session completely ---
session_destroy();

// --- Redirect to login page ---
header("Location: ../auth/login.php");
exit();
?>
