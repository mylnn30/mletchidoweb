<?php

require_once "database/config.php";

function submit_loan_application($user_id, $loan_type, $amount, $term, $purpose) {
    global $conn;

    $sql = "INSERT INTO `loan_applications`
        (user_id, loan_type, amount, term_months, purpose)
        VALUES (?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return "Something went wrong. Please try again.";
    }

    mysqli_stmt_bind_param(
        $stmt,
        "isdis",
        $user_id,
        $loan_type,
        $amount,
        $term,
        $purpose
    );

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return true;
    }

    mysqli_stmt_close($stmt);

    return "Could not submit your application. Please try again.";
}


function get_user_loan_applications($user_id) {
    global $conn;

    $sql = "SELECT id, loan_type, amount, term_months, status, submitted_at
            FROM `loan_applications`
            WHERE user_id = ?
            ORDER BY submitted_at DESC";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return [];
    }

    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $applications = mysqli_fetch_all($result, MYSQLI_ASSOC);

    mysqli_stmt_close($stmt);

    return $applications;
}