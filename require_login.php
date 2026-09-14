<?php
require_once "session_bootstrap.php";
start_secure_session();

if (empty($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// Tell the browser never to cache this page. Without this, hitting the
// back button after logging out could show a stale cached copy of a
// protected page (dashboard, applications, etc.) without the browser
// re-checking with the server first.
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");