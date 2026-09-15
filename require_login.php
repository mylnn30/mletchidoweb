<?php
require_once "session_bootstrap.php";
start_secure_session();

require_once "remember_login.php";

restore_login_from_cookie();

if (empty($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

?>