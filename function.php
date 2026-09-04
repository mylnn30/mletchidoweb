<?php

require_once "database/config.php";

function register_user(
    $username,
    $first_name,
    $middle_name,
    $last_name,
    $email,
    $address,
    $password,
    $phone_number
) {

    global $conn;


    /* CHECK USERNAME AND EMAIL */

    $check_sql = "SELECT id FROM `user`
                  WHERE username = ? OR email = ?
                  LIMIT 1";

    $check_stmt = mysqli_prepare($conn, $check_sql);

    if (!$check_stmt) {
        return "Something went wrong. Please try again.";
    }

    mysqli_stmt_bind_param(
        $check_stmt,
        "ss",
        $username,
        $email
    );

    mysqli_stmt_execute($check_stmt);

    mysqli_stmt_store_result($check_stmt);

    if (mysqli_stmt_num_rows($check_stmt) > 0) {

        mysqli_stmt_close($check_stmt);

        return "Username or email is already registered.";
    }

    mysqli_stmt_close($check_stmt);


    /* HASH PASSWORD */

    $password_hash = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    if ($password_hash === false) {
        return "Could not secure your password.";
    }


    /* INSERT USER */

    $sql = "INSERT INTO `user`
        (
            username,
            first_name,
            middle_name,
            last_name,
            email,
            address,
            password,
            phone_number
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return "Something went wrong. Please try again.";
    }

    mysqli_stmt_bind_param(
        $stmt,
        "ssssssss",
        $username,
        $first_name,
        $middle_name,
        $last_name,
        $email,
        $address,
        $password_hash,
        $phone_number
    );


    /* EXECUTE INSERT */

    if (mysqli_stmt_execute($stmt)) {

        mysqli_stmt_close($stmt);

        return true;
    }


    /* HANDLE DATABASE ERROR */

    $error = mysqli_stmt_errno($stmt);

    mysqli_stmt_close($stmt);

    if ($error === 1062) {
        return "Username or email is already registered.";
    }

    return "Could not create your account. Please try again.";
}