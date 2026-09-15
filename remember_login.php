<?php

require_once "database/config.php";

const REMEMBER_COOKIE_NAME = "mletchido_remember";
const REMEMBER_DAYS = 30;

function remember_cookie_options($expires) {
    return [
        "expires" => $expires,
        "path" => "/",
        "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
        "httponly" => true,
        "samesite" => "Lax"
    ];
}

function create_remember_token($user_id) {
    global $conn;

    $token = bin2hex(random_bytes(32));
    $token_hash = hash("sha256", $token);

    $expires = time() + (REMEMBER_DAYS * 24 * 60 * 60);
    $expires_at = date("Y-m-d H:i:s", $expires);

    $sql = "INSERT INTO `remember_tokens`
            (user_id, token_hash, expires_at)
            VALUES (?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "iss",
        $user_id,
        $token_hash,
        $expires_at
    );

    $success = mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);

    if (!$success) {
        return false;
    }

    setcookie(
        REMEMBER_COOKIE_NAME,
        $token,
        remember_cookie_options($expires)
    );

    return true;
}

function delete_remember_token() {
    global $conn;

    if (empty($_COOKIE[REMEMBER_COOKIE_NAME])) {
        return;
    }

    $token = $_COOKIE[REMEMBER_COOKIE_NAME];
    $token_hash = hash("sha256", $token);

    $sql = "DELETE FROM `remember_tokens`
            WHERE token_hash = ?";

    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $token_hash);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    setcookie(
        REMEMBER_COOKIE_NAME,
        "",
        remember_cookie_options(time() - 3600)
    );
}

function restore_login_from_cookie() {
    global $conn;

    if (!empty($_SESSION["user_id"])) {
        return true;
    }

    if (empty($_COOKIE[REMEMBER_COOKIE_NAME])) {
        return false;
    }

    $token = $_COOKIE[REMEMBER_COOKIE_NAME];
    $token_hash = hash("sha256", $token);

    $sql = "SELECT
                rt.id AS token_id,
                rt.user_id,
                u.username,
                u.first_name,
                u.last_name,
                u.email,
                u.is_active
            FROM `remember_tokens` rt
            INNER JOIN `user` u
                ON u.id = rt.user_id
            WHERE rt.token_hash = ?
              AND rt.expires_at > NOW()
            LIMIT 1";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "s", $token_hash);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) !== 1) {
        mysqli_stmt_close($stmt);

        delete_remember_token();

        return false;
    }

    $user = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if ((int) $user["is_active"] === 0) {
        delete_remember_token();

        return false;
    }

    session_regenerate_id(true);

    $_SESSION["user_id"] = $user["user_id"];
    $_SESSION["username"] = $user["username"];
    $_SESSION["first_name"] = $user["first_name"];
    $_SESSION["last_name"] = $user["last_name"];
    $_SESSION["email"] = $user["email"];

    /*
     * Rotate the remember token after successful restoration.
     * This prevents the same token from being reused indefinitely.
     */

    $delete_sql = "DELETE FROM `remember_tokens`
                   WHERE id = ?";

    $delete_stmt = mysqli_prepare($conn, $delete_sql);

    if ($delete_stmt) {
        mysqli_stmt_bind_param(
            $delete_stmt,
            "i",
            $user["token_id"]
        );

        mysqli_stmt_execute($delete_stmt);
        mysqli_stmt_close($delete_stmt);
    }

    create_remember_token($user["user_id"]);

    return true;
}