<?php
/*
 * Session Logout Handler
 * Destroys the session and redirects to the login page using BASE_URL.
 */
require_once __DIR__ . '/config.php';

// Unset all session variables
$_SESSION = array();

// Delete session cookie if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to the login page using BASE_URL for correct path resolution
header("Location: " . BASE_URL . "index.php");
exit();
?>