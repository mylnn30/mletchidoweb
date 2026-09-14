<?php

/**
 * Loan types and allowed repayment terms.
 */
function get_loan_types() {
    return [
        "home" => [
            "label" => "Home Loan",
            "terms" => [60, 120, 180, 240, 300, 360]
        ],
        "business" => [
            "label" => "Business Loan",
            "terms" => [12, 24, 36, 48, 60, 72, 84]
        ],
        "personal" => [
            "label" => "Personal Loan",
            "terms" => [12, 18, 24, 36, 48, 60]
        ],
        "asset_backed" => [
            "label" => "Asset-Backed Loan",
            "terms" => [24, 36, 48, 60, 72, 84, 96, 108, 120]
        ]
    ];
}

/**
 * Accepted ID types.
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
        "tin_id" => "TIN ID"
    ];
}

/**
 * Employment status options.
 */
function get_employment_statuses() {
    return [
        "employed" => "Employed",
        "self_employed" => "Self-Employed"
    ];
}

/**
 * Employment length options.
 */
function get_employment_lengths() {
    return [
        "less_than_1_year" => "Less than 1 year",
        "1_to_2_years" => "1–2 years",
        "3_to_5_years" => "3–5 years",
        "6_to_10_years" => "6–10 years",
        "more_than_10_years" => "More than 10 years"
    ];
}

/**
 * Business type options.
 */
function get_business_types() {
    return [
        "sole_proprietorship" => "Sole Proprietorship",
        "retail_sari_sari_store" => "Retail / Sari-Sari Store",
        "food_and_beverage" => "Food & Beverage",
        "services" => "Services (e.g. repair, salon, tutoring)",
        "online_business" => "Online Business / E-commerce",
        "other" => "Other"
    ];
}

/**
 * Payment frequency options.
 */
function get_payment_frequencies() {
    return [
        "monthly" => "Monthly",
        "biweekly" => "Bi-weekly",
        "weekly" => "Weekly"
    ];
}

/**
 * Main loan application validation.
 */
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

    /*
     * TRIM ALL TEXT VALUES.
     * This prevents values such as "     " from passing validation.
     */

    $loan_type = trim((string) $loan_type);
    $id_type = trim((string) $id_type);
    $employment_status = trim((string) $employment_status);
    $occupation = trim((string) $occupation);
    $monthly_income = trim((string) $monthly_income);
    $amount = trim((string) $amount);
    $term = trim((string) $term);
    $purpose = trim((string) $purpose);
    $payment_frequency = trim((string) $payment_frequency);
    $employer_name = trim((string) $employer_name);
    $length_of_employment = trim((string) $length_of_employment);
    $employer_contact_number = trim((string) $employer_contact_number);
    $business_name = trim((string) $business_name);
    $business_type = trim((string) $business_type);

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

    /* OCCUPATION */

    if ($occupation === "") {
        $errors["occupation"] = "Occupation is required.";
    } elseif (strlen($occupation) > 100) {
        $errors["occupation"] = "Occupation cannot be more than 100 characters.";
    }

    /* MONTHLY INCOME */

    if ($monthly_income === "") {
        $errors["monthly_income"] = "Monthly income is required.";
    } elseif (!is_numeric($monthly_income)) {
        $errors["monthly_income"] = "Please enter a valid amount.";
    } elseif (!is_finite((float) $monthly_income)) {
        $errors["monthly_income"] = "Please enter a valid amount.";
    } elseif ((float) $monthly_income <= 0) {
        $errors["monthly_income"] = "Monthly income must be greater than 0.";
    } elseif ((float) $monthly_income > 10000000) {
        $errors["monthly_income"] = "Please enter a realistic monthly income.";
    }

    /* PAYMENT FREQUENCY */

    if ($payment_frequency === "") {
        $errors["payment_frequency"] = "Please select your preferred payment frequency.";
    } elseif (!isset($payment_frequencies[$payment_frequency])) {
        $errors["payment_frequency"] = "Please select a valid payment frequency.";
    }

    /* LOAN AMOUNT */

    if ($amount === "") {
        $errors["amount"] = "Loan amount is required.";
    } elseif (!is_numeric($amount)) {
        $errors["amount"] = "Please enter a valid loan amount.";
    } elseif (!is_finite((float) $amount)) {
        $errors["amount"] = "Please enter a valid loan amount.";
    } elseif ((float) $amount <= 0) {
        $errors["amount"] = "Loan amount must be greater than 0.";
    } elseif ((float) $amount < 5000) {
        $errors["amount"] = "Loan amount must be at least ₱5,000.";
    } elseif ((float) $amount > 50000000) {
        $errors["amount"] = "Loan amount cannot exceed ₱50,000,000.";
    }

    /* REPAYMENT TERM */

    if ($term === "") {
        $errors["term"] = "Please select a repayment term.";
    } elseif (!ctype_digit($term)) {
        $errors["term"] = "Please select a valid repayment term.";
    } elseif (!isset($loan_types[$loan_type])) {
        $errors["term"] = "Please select a valid loan type first.";
    } else {
        $allowed_terms = $loan_types[$loan_type]["terms"];

        if (!in_array((int) $term, $allowed_terms, true)) {
            $errors["term"] = "Please select one of the available terms for your loan.";
        }
    }

    /* PURPOSE */

    if ($purpose === "") {
        $errors["purpose"] = "Loan purpose is required.";
    } elseif (strlen($purpose) > 500) {
        $errors["purpose"] = "Purpose cannot be more than 500 characters.";
    }

    /* EMPLOYED FIELDS */

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

    /* SELF-EMPLOYED FIELDS */

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

    return $errors;
}


/**
 * Secure document validation.
 *
 * Only JPG/JPEG/PNG images are accepted.
 * Maximum size is 5 MB.
 * Actual image content is checked using getimagesize().
 */
function validate_uploaded_document($file, $field_label) {

    /* NO FILE */

    if (!isset($file) || !is_array($file)) {
        return "{$field_label} is required.";
    }

    if (!isset($file["error"])) {
        return "{$field_label} is required.";
    }

    if ($file["error"] === UPLOAD_ERR_NO_FILE) {
        return "{$field_label} is required.";
    }

    /* UPLOAD ERROR */

    if ($file["error"] !== UPLOAD_ERR_OK) {
        return "There was a problem uploading your {$field_label}. Please try again.";
    }

    /* TEMPORARY FILE MUST EXIST */

    if (
        !isset($file["tmp_name"]) ||
        !is_string($file["tmp_name"]) ||
        !is_uploaded_file($file["tmp_name"])
    ) {
        return "Invalid {$field_label} upload.";
    }

    /* FILE SIZE */

    if (!isset($file["size"]) || !is_numeric($file["size"])) {
        return "Invalid {$field_label} file.";
    }

    $max_bytes = 5 * 1024 * 1024;

    if ((int) $file["size"] <= 0) {
        return "{$field_label} cannot be empty.";
    }

    if ((int) $file["size"] > $max_bytes) {
        return "{$field_label} must be smaller than 5MB.";
    }

    /* FILE EXTENSION */

    if (!isset($file["name"]) || !is_string($file["name"])) {
        return "Invalid {$field_label} file.";
    }

    $extension = strtolower(
        pathinfo($file["name"], PATHINFO_EXTENSION)
    );

    $allowed_extensions = [
        "jpg",
        "jpeg",
        "png"
    ];

    if (!in_array($extension, $allowed_extensions, true)) {
        return "{$field_label} must be a JPG or PNG image.";
    }

    /* CHECK REAL IMAGE CONTENT */

    $image_info = @getimagesize($file["tmp_name"]);

    if ($image_info === false) {
        return "{$field_label} does not appear to be a valid image.";
    }

    $allowed_mime_types = [
        "image/jpeg",
        "image/png"
    ];

    if (
        !isset($image_info["mime"]) ||
        !in_array($image_info["mime"], $allowed_mime_types, true)
    ) {
        return "{$field_label} contains an unsupported image type.";
    }

    /* CHECK IMAGE DIMENSIONS */

    if (
        !isset($image_info[0]) ||
        !isset($image_info[1]) ||
        $image_info[0] <= 0 ||
        $image_info[1] <= 0
    ) {
        return "{$field_label} has invalid image dimensions.";
    }

    return null;
}


/**
 * Validate all required loan documents.
 */
function validate_loan_documents(
    $valid_id_file,
    $proof_of_income_file,
    $proof_of_address_file,
    $employment_certificate_file,
    $employment_status
) {
    $errors = [];

    /* VALID ID */

    $valid_id_error = validate_uploaded_document(
        $valid_id_file,
        "Valid ID"
    );

    if ($valid_id_error !== null) {
        $errors["valid_id"] = $valid_id_error;
    }

    /* PROOF OF INCOME */

    $proof_of_income_error = validate_uploaded_document(
        $proof_of_income_file,
        "Proof of Income"
    );

    if ($proof_of_income_error !== null) {
        $errors["proof_of_income"] = $proof_of_income_error;
    }

    /* PROOF OF ADDRESS */

    $proof_of_address_error = validate_uploaded_document(
        $proof_of_address_file,
        "Proof of Address"
    );

    if ($proof_of_address_error !== null) {
        $errors["proof_of_address"] = $proof_of_address_error;
    }

    /*
     * EMPLOYMENT CERTIFICATE
     *
     * Required only when the applicant selected Employed.
     */

    $certificate_provided =
        isset($employment_certificate_file) &&
        is_array($employment_certificate_file) &&
        isset($employment_certificate_file["error"]) &&
        $employment_certificate_file["error"] !== UPLOAD_ERR_NO_FILE;

    if ($employment_status === "employed") {

        $certificate_error = validate_uploaded_document(
            $employment_certificate_file,
            "Employment Certificate"
        );

        if ($certificate_error !== null) {
            $errors["employment_certificate"] = $certificate_error;
        }

    } elseif ($certificate_provided) {

        /*
         * If a certificate was uploaded even though it is not required,
         * still validate it.
         */

        $certificate_error = validate_uploaded_document(
            $employment_certificate_file,
            "Employment Certificate"
        );

        if ($certificate_error !== null) {
            $errors["employment_certificate"] = $certificate_error;
        }
    }

    return $errors;
}