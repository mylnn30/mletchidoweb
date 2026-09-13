<?php

require_once "database/config.php";

const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_LOCKOUT_WINDOW_SECONDS = 15 * 60; // 15 minutes
const LOGIN_LOCKOUT_MESSAGE = "Too many failed login attempts. Please try again in a few minutes.";

/**
 * Records one failed login attempt for a given username (real or not --
 * see the note in login_user() about why nonexistent usernames are
 * tracked too).
 */
function record_failed_login($username) {
    global $conn;

    $sql = "INSERT INTO `login_attempts` (username, attempted_at) VALUES (?, NOW())";
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return;
    }

    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

/**
 * How many failed attempts this username has racked up inside the
 * lockout window.
 */
function count_recent_failed_logins($username) {
    global $conn;

    $sql = "SELECT COUNT(*) AS attempts
            FROM `login_attempts`
            WHERE username = ?
              AND attempted_at >= (NOW() - INTERVAL " . (int) LOGIN_LOCKOUT_WINDOW_SECONDS . " SECOND)";

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

/**
 * Wipes this username's failed-attempt history, called after a
 * successful login so a real user doesn't stay "at risk" of lockout
 * because of a few earlier typos.
 */
function clear_failed_logins($username) {
    global $conn;

    $sql = "DELETE FROM `login_attempts` WHERE username = ?";
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

    // Checked BEFORE we even look the username up, and using the exact
    // same "too many attempts" message whether or not the account
    // exists -- so this can't be used to test which usernames are
    // registered (unlike, say, only locking out real accounts).
    if (count_recent_failed_logins($username) >= LOGIN_MAX_ATTEMPTS) {
        return LOGIN_LOCKOUT_MESSAGE;
    }

    $sql = "SELECT id, username, first_name, last_name, email, password, is_active
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

    // Deactivated accounts fail the same way a wrong password would --
    // no separate message, so a deactivated user can't tell from the
    // login form whether their account exists but is disabled, versus
    // simply mistyping their password.
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

    return true;
}