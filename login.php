<?php
require_once "session_bootstrap.php";
start_secure_session();

require_once "csrf.php";
require_once "login_validation.php";
require_once "login_function.php";
require_once "admin_function.php";

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!csrf_verify($_POST["csrf_token"] ?? "")) {
        $errors[] = "Your session has expired. Please try again.";
    } else {
        $username = trim($_POST["username"] ?? "");
        $password = $_POST["password"] ?? "";

        $errors = validate_login($username, $password);

        if (empty($errors)) {
            $result = login_user($username, $password);

            if ($result === true) {
                // Admin accounts only ever see the admin side of the
                // site -- send them straight there instead of the
                // customer dashboard.
                if (user_is_admin($_SESSION["user_id"])) {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit;
            }

            // Deliberately generic in every case except the lockout
            // notice itself: don't reveal whether the username exists
            // or the password was wrong, which would let an attacker
            // enumerate valid usernames against this form. The lockout
            // message is safe to show as-is since it's shown the same
            // way whether or not the username is real (see login_user()).
            $errors[] = ($result === LOGIN_LOCKOUT_MESSAGE)
                ? $result
                : "Invalid username or password.";
        }
    }
}
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
            <p>Sign in to your Mletchido Financial Group account</p>
        </header>

        <?php if (!empty($errors)): ?>
            <div class="form-error" role="alert" aria-live="polite">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="login-form" novalidate>

            <?php csrf_field(); ?>

            <div class="form-group">
                <label for="username">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    value="<?php echo htmlspecialchars($_POST["username"] ?? "", ENT_QUOTES, "UTF-8"); ?>"
                    autocomplete="username"
                    autofocus
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button type="submit" class="login-button">
                Sign In
            </button>

            <p class="register-link">
                Don't have an account?
                <a href="register.php">Create Account</a>
            </p>

        </form>

    </section>
</main>

</body>
</html>