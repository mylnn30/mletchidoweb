<?php
session_start();

// Clear all session variables
$_SESSION = [];

// Delete the session cookie itself, if the client is using one
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        "",
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session data server-side
session_destroy();

header("Location: login.php");
exit;