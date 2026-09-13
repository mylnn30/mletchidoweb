<?php

require_once "session_bootstrap.php";

/**
 * Returns the current CSRF token for this session, generating one the
 * first time it's needed. Call this from a form page and echo the
 * result into a hidden <input>.
 */
function csrf_token() {
    start_secure_session();

    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }

    return $_SESSION["csrf_token"];
}

/**
 * Renders a ready-to-use hidden CSRF input for a <form>.
 */
function csrf_field() {
    $token = htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8");
    echo '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Checks a submitted token against the one stored in the session.
 * hash_equals() is used instead of === so the comparison takes the
 * same amount of time regardless of where the strings first differ --
 * a plain string comparison can leak the correct token one byte at a
 * time through response-time differences (a timing attack).
 */
function csrf_verify($submitted_token) {
    start_secure_session();

    if (!is_string($submitted_token) || empty($_SESSION["csrf_token"])) {
        return false;
    }

    return hash_equals($_SESSION["csrf_token"], $submitted_token);
}