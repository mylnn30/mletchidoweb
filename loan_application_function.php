<?php

require_once "database/config.php";

function get_loan_documents_upload_dir() {
    return __DIR__ . "/uploads/loan_documents/";
}

function get_loan_documents_upload_url() {
    return "uploads/loan_documents/";
}


function save_uploaded_document($file, $user_id, $label) {
    $upload_dir = get_loan_documents_upload_dir();

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $filename = $user_id . "_" . $label . "_" . bin2hex(random_bytes(8)) . "." . $extension;

    $destination = $upload_dir . $filename;

    if (!move_uploaded_file($file["tmp_name"], $destination)) {
        return null;
    }

    return get_loan_documents_upload_url() . $filename;
}


function submit_loan_application(
    $user_id,
    $loan_type,
    $id_type,
    $employment_status,
    $occupation,
    $employer_name,
    $length_of_employment,
    $employer_contact_number,
    $business_name,
    $business_type,
    $monthly_income,
    $amount,
    $term,
    $payment_frequency,
    $purpose,
    $valid_id_file,
    $proof_of_income_file,
    $proof_of_address_file,
    $employment_certificate_file
) {
    global $conn;

    $valid_id_path = save_uploaded_document($valid_id_file, $user_id, "valid_id");
    if ($valid_id_path === null) {
        return "Could not save your Valid ID. Please try again.";
    }

    $proof_of_income_path = save_uploaded_document($proof_of_income_file, $user_id, "proof_of_income");
    if ($proof_of_income_path === null) {
        return "Could not save your Proof of Income. Please try again.";
    }

    $proof_of_address_path = save_uploaded_document($proof_of_address_file, $user_id, "proof_of_address");
    if ($proof_of_address_path === null) {
        return "Could not save your Proof of Address. Please try again.";
    }

    // Optional -- only save it if one was actually attached.
    $employment_certificate_path = null;
    $certificate_was_attached = isset($employment_certificate_file)
        && $employment_certificate_file["error"] !== UPLOAD_ERR_NO_FILE;

    if ($certificate_was_attached) {
        $employment_certificate_path = save_uploaded_document($employment_certificate_file, $user_id, "employment_certificate");
        if ($employment_certificate_path === null) {
            return "Could not save your Employment Certificate. Please try again.";
        }
    }

    // Employer/business fields only apply to certain employment
    // statuses -- store NULL for the ones that don't apply, rather
    // than an empty string, since the columns are nullable for this.
    $employer_name = ($employment_status === "employed") ? $employer_name : null;
    $length_of_employment = ($employment_status === "employed") ? $length_of_employment : null;
    $employer_contact_number = ($employment_status === "employed") ? $employer_contact_number : null;
    $business_name = ($employment_status === "self_employed") ? $business_name : null;
    $business_type = ($employment_status === "self_employed") ? $business_type : null;

    $sql = "INSERT INTO `loan_applications`
        (user_id, loan_type, id_type, employment_status, occupation,
         employer_name, length_of_employment, employer_contact_number,
         business_name, business_type, monthly_income,
         amount, term_months, payment_frequency, purpose,
         valid_id_path, proof_of_income_path, proof_of_address_path, employment_certificate_path)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return "Something went wrong. Please try again.";
    }

    mysqli_stmt_bind_param(
        $stmt,
        "isssssssssddissssss",
        $user_id,
        $loan_type,
        $id_type,
        $employment_status,
        $occupation,
        $employer_name,
        $length_of_employment,
        $employer_contact_number,
        $business_name,
        $business_type,
        $monthly_income,
        $amount,
        $term,
        $payment_frequency,
        $purpose,
        $valid_id_path,
        $proof_of_income_path,
        $proof_of_address_path,
        $employment_certificate_path
    );

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return true;
    }

    mysqli_stmt_close($stmt);

    return "Could not submit your application. Please try again.";
}


function get_loan_application_by_id($application_id, $user_id) {
    global $conn;

    $sql = "SELECT id, loan_type, id_type, employment_status, occupation,
                   employer_name, length_of_employment, employer_contact_number,
                   business_name, business_type, monthly_income,
                   amount, term_months, payment_frequency, purpose,
                   valid_id_path, proof_of_income_path, proof_of_address_path, employment_certificate_path,
                   status, submitted_at
            FROM `loan_applications`
            WHERE id = ? AND user_id = ?
            LIMIT 1";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, "ii", $application_id, $user_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $application = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    return $application ?: null;
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