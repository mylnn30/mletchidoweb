<?php

require_once "database/config.php";

function login_user($username, $password) {
    global $conn;

    $sql = "SELECT id, username, first_name, last_name, email, password
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
        return "Invalid username or password.";
    }

    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!password_verify($password, $user["password"])) {
        return "Invalid username or password.";
    }

    session_regenerate_id(true);

    $_SESSION["user_id"] = $user["id"];
    $_SESSION["username"] = $user["username"];
    $_SESSION["first_name"] = $user["first_name"];
    $_SESSION["last_name"] = $user["last_name"];
    $_SESSION["email"] = $user["email"];

    return true;
}