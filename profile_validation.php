<?php

function validate_profile_update(
    $first_name,
    $middle_name,
    $last_name,
    $email,
    $address,
    $phone_number
) {
    $errors = [];

    /* REQUIRED FIELDS */

    if ($first_name === "") {
        $errors["first_name"] = "First name is required.";
    }

    if ($last_name === "") {
        $errors["last_name"] = "Last name is required.";
    }

    if ($email === "") {
        $errors["email"] = "Email address is required.";
    }

    if ($phone_number === "") {
        $errors["phone_number"] = "Phone number is required.";
    }

    if ($address === "") {
        $errors["address"] = "Address is required.";
    }

    /* NAME VALIDATION */

    if (
        $first_name !== "" &&
        !preg_match("/^[a-zA-Z\s'-]+$/", $first_name)
    ) {
        $errors["first_name"] = "First name can only contain letters, spaces, hyphens, and apostrophes.";
    }

    if (
        $middle_name !== "" &&
        !preg_match("/^[a-zA-Z\s'-]+$/", $middle_name)
    ) {
        $errors["middle_name"] = "Middle name can only contain letters, spaces, hyphens, and apostrophes.";
    }

    if (
        $last_name !== "" &&
        !preg_match("/^[a-zA-Z\s'-]+$/", $last_name)
    ) {
        $errors["last_name"] = "Last name can only contain letters, spaces, hyphens, and apostrophes.";
    }

    /* EMAIL VALIDATION */

    if (
        $email !== "" &&
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {
        $errors["email"] = "Please enter a valid email address.";
    }

    /* PHONE NUMBER VALIDATION */

    if (
        $phone_number !== "" &&
        !preg_match("/^\+?[0-9\s\-()]+$/", $phone_number)
    ) {
        $errors["phone_number"] = "Please enter a valid phone number.";
    }

    /* LENGTH VALIDATION */

    if (strlen($first_name) > 50) {
        $errors["first_name"] = "First name cannot be more than 50 characters.";
    }

    if (strlen($middle_name) > 50) {
        $errors["middle_name"] = "Middle name cannot be more than 50 characters.";
    }

    if (strlen($last_name) > 50) {
        $errors["last_name"] = "Last name cannot be more than 50 characters.";
    }

    if (strlen($email) > 150) {
        $errors["email"] = "Email cannot be more than 150 characters.";
    }

    if (strlen($phone_number) > 20) {
        $errors["phone_number"] = "Phone number cannot be more than 20 characters.";
    }

    if (strlen($address) > 255) {
        $errors["address"] = "Address cannot be more than 255 characters.";
    }

    return $errors;
}