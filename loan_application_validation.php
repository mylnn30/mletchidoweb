<?php

/**
 * The four loan types, each with a realistic, fixed list of
 * repayment terms (in months) instead of a free-typed number.
 * These match the general term ranges shown on the homepage,
 * but broken into sensible steps a lender would actually offer.
 * Reused by the form dropdown AND validation, so both always agree.
 */
function get_loan_types() {
    return [
        "home" => [
            "label" => "Home Loan",
            "terms" => [60, 120, 180, 240, 300, 360], // 5, 10, 15, 20, 25, 30 yrs
        ],
        "business" => [
            "label" => "Business Loan",
            "terms" => [12, 24, 36, 48, 60, 72, 84], // 1–7 yrs
        ],
        "personal" => [
            "label" => "Personal Loan",
            "terms" => [12, 18, 24, 36, 48, 60], // 1–5 yrs (with a 1.5 yr step)
        ],
        "asset_backed" => [
            "label" => "Asset-Backed Loan",
            "terms" => [24, 36, 48, 60, 72, 84, 96, 108, 120], // 2–10 yrs
        ],
    ];
}

/**
 * Government-issued ID types accepted for the "Valid ID" upload.
 * Shown as a dropdown so applicants pick from a fixed list instead
 * of typing a free-text value that would be hard to standardize.
 */
function get_id_types() {
    return [
        "philippine_passport" => "Philippine Passport",
        "drivers_license" => "Driver's License",
        "sss_id" => "SSS ID",
        "umid" => "UMID",
        "philhealth_id" => "PhilHealth ID",
        "postal_id" => "Postal ID",
        "voters_id" => "Voter's ID",
        "philsys_id" => "National ID (PhilSys)",
        "tin_id" => "TIN ID",
    ];
}

/**
 * Employment status options for the applicant.
 */
function get_employment_statuses() {
    return [
        "employed" => "Employed",
        "self_employed" => "Self-Employed",
    ];
}

/**
 * How long the applicant has held their current job. Only relevant
 * (and required) when Employment Status = Employed.
 */
function get_employment_lengths() {
    return [
        "less_than_1_year" => "Less than 1 year",
        "1_to_2_years" => "1–2 years",
        "3_to_5_years" => "3–5 years",
        "6_to_10_years" => "6–10 years",
        "more_than_10_years" => "More than 10 years",
    ];
}

/**
 * Business categories for self-employed applicants.
 */
function get_business_types() {
    return [
        "sole_proprietorship" => "Sole Proprietorship",
        "retail_sari_sari_store" => "Retail / Sari-Sari Store",
        "food_and_beverage" => "Food & Beverage",
        "services" => "Services (e.g. repair, salon, tutoring)",
        "online_business" => "Online Business / E-commerce",
        "other" => "Other",
    ];
}

/**
 * How the applicant would prefer to pay their loan back, used later
 * to schedule due dates once a loan is approved.
 */
function get_payment_frequencies() {
    return [
        "monthly" => "Monthly",
        "biweekly" => "Bi-weekly",
        "weekly" => "Weekly",
    ];
}


function validate_loan_application(
    $loan_type,
    $id_type,
    $employment_status,
    $occupation,
    $monthly_income,
    $amount,
    $term,
    $purpose,
    $payment_frequency,
    $employer_name,
    $length_of_employment,
    $employer_contact_number,
    $business_name,
    $business_type
) {
    $errors = [];
    $loan_types = get_loan_types();
    $id_types = get_id_types();
    $employment_statuses = get_employment_statuses();
    $employment_lengths = get_employment_lengths();
    $business_types = get_business_types();
    $payment_frequencies = get_payment_frequencies();

    /* LOAN TYPE */

    if ($loan_type === "") {
        $errors["loan_type"] = "Please select a loan type.";
    } elseif (!isset($loan_types[$loan_type])) {
        $errors["loan_type"] = "Please select a valid loan type.";
    }

    /* ID TYPE */

    if ($id_type === "") {
        $errors["id_type"] = "Please select the type of ID you're uploading.";
    } elseif (!isset($id_types[$id_type])) {
        $errors["id_type"] = "Please select a valid ID type.";
    }

    /* EMPLOYMENT STATUS */

    if ($employment_status === "") {
        $errors["employment_status"] = "Please select your employment status.";
    } elseif (!isset($employment_statuses[$employment_status])) {
        $errors["employment_status"] = "Please select a valid employment status.";
    }

    /* OCCUPATION / JOB TITLE */

    if ($occupation === "") {
        $errors["occupation"] = "Please tell us your occupation (or course, if a student).";
    } elseif (strlen($occupation) > 100) {
        $errors["occupation"] = "Occupation cannot be more than 100 characters.";
    }

    /* EMPLOYED-ONLY FIELDS */

    if ($employment_status === "employed") {
        if ($employer_name === "") {
            $errors["employer_name"] = "Employer / company name is required.";
        } elseif (strlen($employer_name) > 150) {
            $errors["employer_name"] = "Employer name cannot be more than 150 characters.";
        }

        if ($length_of_employment === "") {
            $errors["length_of_employment"] = "Please select your length of employment.";
        } elseif (!isset($employment_lengths[$length_of_employment])) {
            $errors["length_of_employment"] = "Please select a valid length of employment.";
        }

        if ($employer_contact_number === "") {
            $errors["employer_contact_number"] = "Employer contact number is required.";
        } elseif (!preg_match("/^\+?[0-9\s\-()]+$/", $employer_contact_number)) {
            $errors["employer_contact_number"] = "Please enter a valid contact number.";
        } elseif (strlen($employer_contact_number) > 20) {
            $errors["employer_contact_number"] = "Contact number cannot be more than 20 characters.";
        }
    }

    /* SELF-EMPLOYED-ONLY FIELDS */

    if ($employment_status === "self_employed") {
        if ($business_name === "") {
            $errors["business_name"] = "Business name is required.";
        } elseif (strlen($business_name) > 150) {
            $errors["business_name"] = "Business name cannot be more than 150 characters.";
        }

        if ($business_type === "") {
            $errors["business_type"] = "Please select a business type.";
        } elseif (!isset($business_types[$business_type])) {
            $errors["business_type"] = "Please select a valid business type.";
        }
    }

    /* MONTHLY INCOME (labeled "Estimated Monthly Income" for self-employed) */

    if ($monthly_income === "" || $monthly_income === null) {
        $errors["monthly_income"] = "Monthly income is required.";
    } elseif (!is_numeric($monthly_income)) {
        $errors["monthly_income"] = "Please enter a valid amount.";
    } elseif ((float) $monthly_income < 0) {
        $errors["monthly_income"] = "Monthly income cannot be negative.";
    } elseif ((float) $monthly_income > 10000000) {
        $errors["monthly_income"] = "Please enter a realistic monthly income.";
    }

    /* PREFERRED PAYMENT FREQUENCY */

    if ($payment_frequency === "") {
        $errors["payment_frequency"] = "Please select your preferred payment frequency.";
    } elseif (!isset($payment_frequencies[$payment_frequency])) {
        $errors["payment_frequency"] = "Please select a valid payment frequency.";
    }

    /* AMOUNT */

    if ($amount === "" || $amount === null) {
        $errors["amount"] = "Loan amount is required.";
    } elseif (!is_numeric($amount)) {
        $errors["amount"] = "Please enter a valid amount.";
    } elseif ((float) $amount < 5000) {
        $errors["amount"] = "Loan amount must be at least ₱5,000.";
    } elseif ((float) $amount > 50000000) {
        $errors["amount"] = "Loan amount cannot exceed ₱50,000,000.";
    }

    /* TERM — must be one of the fixed options for the chosen loan type */

    if ($term === "" || $term === null) {
        $errors["term"] = "Please select a repayment term.";
    } elseif (!ctype_digit((string) $term)) {
        $errors["term"] = "Please select a valid repayment term.";
    } elseif (isset($loan_types[$loan_type])) {
        $allowed_terms = $loan_types[$loan_type]["terms"];

        if (!in_array((int) $term, $allowed_terms, true)) {
            $errors["term"] = "Please select one of the available terms for a "
                . $loan_types[$loan_type]["label"] . ".";
        }
    }

    /* PURPOSE */

    if ($purpose === "") {
        $errors["purpose"] = "Please tell us what the loan is for.";
    } elseif (strlen($purpose) > 500) {
        $errors["purpose"] = "Purpose cannot be more than 500 characters.";
    }

    return $errors;
}


/**
 * Validates a single uploaded file for the "Valid ID" / "Proof of
 * Income" fields. Only JPG and PNG are allowed. We don't trust the
 * file extension or the browser-supplied MIME type alone -- both
 * can be faked by renaming a file -- so we also call getimagesize()
 * to confirm the file's actual content is a real image.
 */
function validate_uploaded_document($file, $field_label) {
    // No file selected at all.
    if (!isset($file) || $file["error"] === UPLOAD_ERR_NO_FILE) {
        return "{$field_label} is required.";
    }

    if ($file["error"] !== UPLOAD_ERR_OK) {
        return "There was a problem uploading your {$field_label}. Please try again.";
    }

    $max_bytes = 5 * 1024 * 1024; // 5 MB
    if ($file["size"] > $max_bytes) {
        return "{$field_label} must be smaller than 5MB.";
    }

    $allowed_extensions = ["jpg", "jpeg", "png"];
    $extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));

    if (!in_array($extension, $allowed_extensions, true)) {
        return "{$field_label} must be a JPG or PNG image.";
    }

    // Confirm the file's actual content is a real image, not just
    // something renamed to look like one.
    $image_info = @getimagesize($file["tmp_name"]);
    $allowed_mime_types = ["image/jpeg", "image/png"];

    if ($image_info === false || !in_array($image_info["mime"], $allowed_mime_types, true)) {
        return "{$field_label} does not appear to be a valid image file.";
    }

    return null;
}


function validate_loan_documents(
    $valid_id_file,
    $proof_of_income_file,
    $proof_of_address_file,
    $employment_certificate_file,
    $employment_status
) {
    $errors = [];

    $valid_id_error = validate_uploaded_document($valid_id_file, "Valid ID");
    if ($valid_id_error !== null) {
        $errors["valid_id"] = $valid_id_error;
    }

    $proof_of_income_error = validate_uploaded_document($proof_of_income_file, "Proof of Income");
    if ($proof_of_income_error !== null) {
        $errors["proof_of_income"] = $proof_of_income_error;
    }

    $proof_of_address_error = validate_uploaded_document($proof_of_address_file, "Proof of Address");
    if ($proof_of_address_error !== null) {
        $errors["proof_of_address"] = $proof_of_address_error;
    }

    // Employment Certificate is only required for applicants who say
    // they're Employed. Everyone else may skip it, but if a file was
    // still attached, it's validated like any other upload.
    $certificate_provided = isset($employment_certificate_file)
        && $employment_certificate_file["error"] !== UPLOAD_ERR_NO_FILE;

    if ($employment_status === "employed") {
        $certificate_error = validate_uploaded_document($employment_certificate_file, "Employment Certificate");
        if ($certificate_error !== null) {
            $errors["employment_certificate"] = $certificate_error;
        }
    } elseif ($certificate_provided) {
        $certificate_error = validate_uploaded_document($employment_certificate_file, "Employment Certificate");
        if ($certificate_error !== null) {
            $errors["employment_certificate"] = $certificate_error;
        }
    }

    return $errors;
}