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