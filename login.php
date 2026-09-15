<?php

require_once "session_bootstrap.php";
start_secure_session();

require_once "csrf.php";
require_once "login_validation.php";
require_once "login_function.php";
require_once "admin_function.php";
require_once "remember_login.php";

restore_login_from_cookie();

if (!empty($_SESSION["user_id"])) {
    header("Location: " . (user_is_admin($_SESSION["user_id"]) ? "admin_dashboard.php" : "dashboard.php"));
    exit;
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!csrf_verify($_POST["csrf_token"] ?? "")) {

        $_SESSION["login_flash_errors"] = [
            "Your session has expired. Please try again."
        ];

    } else {

        $username = trim($_POST["username"] ?? "");
        $password = $_POST["password"] ?? "";

        $errors = validate_login($username, $password);

        if (empty($errors)) {

            $result = login_user($username, $password);

            if ($result === true) {

                if (user_is_admin($_SESSION["user_id"])) {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: dashboard.php");
                }

                exit;
            }

            $errors[] = ($result === LOGIN_LOCKOUT_MESSAGE)
                ? $result
                : "Invalid username or password.";
        }

        $_SESSION["login_flash_errors"] = $errors;
        $_SESSION["login_flash_username"] = $username;
    }

    header("Location: login.php");
    exit;
}

$errors = $_SESSION["login_flash_errors"] ?? [];
$flash_username = $_SESSION["login_flash_username"] ?? "";

unset(
    $_SESSION["login_flash_errors"],
    $_SESSION["login_flash_username"]
);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | Mletchido Financial Group</title>
    <link rel="stylesheet" href="css/login.css">
</head>

<body>

<main class="login-page">

    <section class="login-container">

        <header class="login-header">

            <h1>Welcome Back</h1>

            <p>
                Sign in to your Mletchido Financial Group account
            </p>

        </header>

        <?php if (!empty($errors)): ?>

            <div
                class="form-error"
                role="alert"
                aria-live="polite"
            >

                <?php foreach ($errors as $error): ?>

                    <p>
                        <?php echo htmlspecialchars(
                            $error,
                            ENT_QUOTES,
                            "UTF-8"
                        ); ?>
                    </p>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

        <form
            action="login.php"
            method="POST"
            class="login-form"
            novalidate
        >

            <?php csrf_field(); ?>

            <div class="form-group">

                <label for="username">
                    Username
                </label>

                <input
                    type="text"
                    id="username"
                    name="username"
                    value="<?php echo htmlspecialchars(
                        $flash_username,
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?>"
                    autocomplete="username"
                    autofocus
                    required
                >

            </div>

            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    required
                >

            </div>

            <button
                type="submit"
                class="login-button"
            >
                Sign In
            </button>

            <p class="register-link">

                Don't have an account?

                <a href="register.php">
                    Create Account
                </a>

            </p>

        </form>

    </section>

</main>

</body>
</html>