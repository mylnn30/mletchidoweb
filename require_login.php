<?php
require_once "session_bootstrap.php";
start_secure_session();

if (empty($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}