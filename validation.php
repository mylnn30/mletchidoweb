```php
<?php

function validate_registration(
    $username,
    $first_name,
    $middle_name,
    $last_name,
    $email,
    $address,
    $password,
    $confirm_password,
    $phone_number
) {

    $errors = [];

    /* REQUIRED FIELDS */

    if ($username === "") {
        $errors["username"] = "Username is required.";
    }

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

    if ($password === "") {
        $errors["password"] = "Password is required.";
    }

    if ($confirm_password === "") {
        $errors["confirm_password"] = "Please confirm your password.";
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


    /* USERNAME VALIDATION */

    if (
        $username !== "" &&
        !preg_match("/^[a-zA-Z0-9_]+$/", $username)
    ) {
        $errors["username"] = "Username can only contain letters, numbers, and underscores.";
    }

    if (
        $username !== "" &&
        strlen($username) < 4
    ) {
        $errors["username"] = "Username must be at least 4 characters.";
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


    /* PASSWORD VALIDATION */

    if (
        $password !== "" &&
        strlen($password) < 8
    ) {
        $errors["password"] = "Password must be at least 8 characters.";
    }

    if (
        $password !== "" &&
        !preg_match("/[A-Z]/", $password)
    ) {
        $errors["password"] = "Password must contain at least one uppercase letter.";
    }

    if (
        $password !== "" &&
        !preg_match("/[a-z]/", $password)
    ) {
        $errors["password"] = "Password must contain at least one lowercase letter.";
    }

    if (
        $password !== "" &&
        !preg_match("/[0-9]/", $password)
    ) {
        $errors["password"] = "Password must contain at least one number.";
    }

    if (
        $password !== "" &&
        !preg_match("/[\W_]/", $password)
    ) {
        $errors["password"] = "Password must contain at least one special character.";
    }


    /* PASSWORD CONFIRMATION */

    if (
        $password !== "" &&
        $confirm_password !== "" &&
        $password !== $confirm_password
    ) {
        $errors["confirm_password"] = "Passwords do not match.";
    }


    /* LENGTH VALIDATION */

    if (strlen($username) > 50) {
        $errors["username"] = "Username cannot be more than 50 characters.";
    }

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

