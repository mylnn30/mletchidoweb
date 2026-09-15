<?php

require_once "database/config.php";
require_once "remember_login.php";

const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_LOCKOUT_WINDOW_SECONDS = 15 * 60;
const LOGIN_LOCKOUT_MESSAGE = "Too many failed login attempts. Please try again in a few minutes.";

function record_failed_login($username) {
    global $conn;

    $sql = "INSERT INTO `login_attempts`
            (username, attempted_at)
            VALUES (?, NOW())";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return;
    }

    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function count_recent_failed_logins($username) {
    global $conn;

    $sql = "SELECT COUNT(*) AS attempts
            FROM `login_attempts`
            WHERE username = ?
              AND attempted_at >= (
                  NOW() - INTERVAL " .
                  (int) LOGIN_LOCKOUT_WINDOW_SECONDS .
                  " SECOND
              )";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return 0;
    }

    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    return (int) ($row["attempts"] ?? 0);
}

function clear_failed_logins($username) {
    global $conn;

    $sql = "DELETE FROM `login_attempts`
            WHERE username = ?";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return;
    }

    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function login_user($username, $password) {
    global $conn;

    if (count_recent_failed_logins($username) >= LOGIN_MAX_ATTEMPTS) {
        return LOGIN_LOCKOUT_MESSAGE;
    }

    $sql = "SELECT
                id,
                username,
                first_name,
                last_name,
                email,
                password,
                is_active
            FROM `user`
            WHERE username = ?
            LIMIT 1";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return "Something went wrong. Please try again.";
    }

    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) !== 1) {
        mysqli_stmt_close($stmt);

        record_failed_login($username);

        return "Invalid username or password.";
    }

    $user = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if (!password_verify($password, $user["password"])) {
        record_failed_login($username);

        return "Invalid username or password.";
    }

    if ((int) $user["is_active"] === 0) {
        record_failed_login($username);

        return "Invalid username or password.";
    }

    clear_failed_logins($username);
session_regenerate_id(true);

$_SESSION["user_id"] = $user["id"];
$_SESSION["username"] = $user["username"];
$_SESSION["first_name"] = $user["first_name"];
$_SESSION["last_name"] = $user["last_name"];
$_SESSION["email"] = $user["email"];

create_remember_token($user["id"]);

return true;
}

?>