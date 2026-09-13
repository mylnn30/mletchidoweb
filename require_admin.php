<?php
require_once "require_login.php";
require_once "admin_function.php";

// Re-checked against the database on every admin page load (not just
// read from the session) so that revoking someone's admin flag takes
// effect immediately, even if they already have an active session.
if (!user_is_admin($_SESSION["user_id"])) {
    http_response_code(403);
    die("403 Forbidden — you don't have access to this page.");
}