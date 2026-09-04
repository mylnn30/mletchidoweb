<?php

function validate_login($username, $password) {
    $errors = [];

    if ($username === "") {
        $errors[] = "Username is required.";
    }

    if ($password === "") {
        $errors[] = "Password is required.";
    }

    return $errors;
}