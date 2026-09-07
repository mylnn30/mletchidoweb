<?php

/**
 * The four loan types and their allowed term ranges (in months),
 * matching the "Financing Solutions" section on index.php exactly.
 * Reuse this array anywhere the loan types need to be listed
 * (the form dropdown, the term dropdown, validation) so they can
 * never drift out of sync with each other or with the homepage.
 */
function get_loan_types() {
    return [
        "home" => [
            "label" => "Home Loan",
            "min_term" => 120,
            "max_term" => 360,
        ],
        "business" => [
            "label" => "Business Loan",
            "min_term" => 12,
            "max_term" => 84,
        ],
        "personal" => [
            "label" => "Personal Loan",
            "min_term" => 12,
            "max_term" => 60,
        ],
        "asset_backed" => [
            "label" => "Asset-Backed Loan",
            "min_term" => 24,
            "max_term" => 120,
        ],
    ];
}

function validate_loan_application($loan_type, $amount, $term, $purpose) {
    $errors = [];
    $loan_types = get_loan_types();

    /* LOAN TYPE */

    if ($loan_type === "") {
        $errors["loan_type"] = "Please select a loan type.";
    } elseif (!isset($loan_types[$loan_type])) {
        $errors["loan_type"] = "Please select a valid loan type.";
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

    /* TERM (depends on which loan type was picked) */

    if ($term === "" || $term === null) {
        $errors["term"] = "Please select a repayment term.";
    } elseif (!ctype_digit((string) $term)) {
        $errors["term"] = "Please select a valid repayment term.";
    } elseif (isset($loan_types[$loan_type])) {
        $min = $loan_types[$loan_type]["min_term"];
        $max = $loan_types[$loan_type]["max_term"];

        if ((int) $term < $min || (int) $term > $max) {
            $errors["term"] = "For a " . $loan_types[$loan_type]["label"]
                . ", the term must be between {$min} and {$max} months.";
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
