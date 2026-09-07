<?php

require_once "database/config.php";

function get_user_by_id($user_id) {
    global $conn;

    $sql = "SELECT id, username, first_name, middle_name, last_name,
                   email, address, phone_number
            FROM `user`
            WHERE id = ?
            LIMIT 1";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    return $user ?: null;
}


function update_user_profile(
    $user_id,
    $first_name,
    $middle_name,
    $last_name,
    $email,
    $address,
    $phone_number
) {
    global $conn;

    /* MAKE SURE NO OTHER USER ALREADY HAS THIS EMAIL */

    $check_sql = "SELECT id FROM `user` WHERE email = ? AND id != ? LIMIT 1";
    $check_stmt = mysqli_prepare($conn, $check_sql);

    if (!$check_stmt) {
        return "Something went wrong. Please try again.";
    }

    mysqli_stmt_bind_param($check_stmt, "si", $email, $user_id);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt);

    if (mysqli_stmt_num_rows($check_stmt) > 0) {
        mysqli_stmt_close($check_stmt);
        return "That email address is already in use by another account.";
    }

    mysqli_stmt_close($check_stmt);

    /* UPDATE THE ROW */

    $sql = "UPDATE `user`
            SET first_name = ?,
                middle_name = ?,
                last_name = ?,
                email = ?,
                address = ?,
                phone_number = ?
            WHERE id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return "Something went wrong. Please try again.";
    }

    mysqli_stmt_bind_param(
        $stmt,
        "ssssssi",
        $first_name,
        $middle_name,
        $last_name,
        $email,
        $address,
        $phone_number,
        $user_id
    );

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return true;
    }

    mysqli_stmt_close($stmt);

    return "Could not update your profile. Please try again.";
}