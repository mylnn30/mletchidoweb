<?php

/**
 * Starts the session with hardened cookie settings. Every file that
 * needs a session (login.php, logout.php, require_login.php, ...)
 * should call this instead of a bare session_start(), so the cookie
 * flags are applied consistently everywhere instead of depending on
 * PHP's (often looser) defaults.
 */
function start_secure_session() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    // Detect HTTPS so the "secure" flag only gets set when it's
    // actually usable -- forcing it on plain HTTP would silently break
    // login on a local http://localhost dev setup.
    $is_https = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off")
        || ($_SERVER["SERVER_PORT"] ?? null) == 443;

    session_set_cookie_params([
        "lifetime" => 0,
        "path" => "/",
        "domain" => "",
        "secure" => $is_https,
        "httponly" => true,   // JavaScript can never read the session cookie
        "samesite" => "Lax",  // blocks the cookie being sent on most cross-site requests
    ]);

    session_start();

    send_security_headers();
}

/**
 * A few defensive HTTP headers that cost nothing and block whole
 * classes of attack: clickjacking (a hidden iframe of this site
 * layered under attacker-controlled buttons), MIME-sniffing (the
 * browser guessing a file is executable content based on its bytes
 * instead of trusting the declared content type), and referrer leakage.
 */
function send_security_headers() {
    if (headers_sent()) {
        return;
    }

    header("X-Frame-Options: DENY");
    header("X-Content-Type-Options: nosniff");
    header("Referrer-Policy: strict-origin-when-cross-origin");
}