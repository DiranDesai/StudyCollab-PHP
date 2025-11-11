<?php
// auth/logout.php
session_start();

// Clear all session data
$_SESSION = [];
session_unset();
session_destroy();

// Delete PHP session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// Remove any custom cookies you may use
foreach (['user_id', 'email', 'name', 'PHPSESSID'] as $cookie) {
    setcookie($cookie, '', time() - 3600, '/');
}

// Prevent caching of protected pages
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect safely
header("Location:../auth/login.php?logout=1");
exit;
?>