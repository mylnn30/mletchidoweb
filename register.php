<?php
require_once "validation.php";
require_once "function.php";

$errors = [];
$success = isset($_GET['success']) && $_GET['success'] === '1';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $first_name = trim($_POST["first_name"] ?? "");
    $middle_name = trim($_POST["middle_name"] ?? "");
    $last_name = trim($_POST["last_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone_number = trim($_POST["phone_number"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    $errors = validate_registration(
        $username,
        $first_name,
        $middle_name,
        $last_name,
        $email,
        $address,
        $password,
        $confirm_password,
        $phone_number
    );

    if (empty($errors)) {

        $result = register_user(
            $username,
            $first_name,
            $middle_name,
            $last_name,
            $email,
            $address,
            $password,
            $phone_number
        );

        if ($result === true) {

            // Redirect after successful POST
            header("Location: register.php?success=1");
            exit;

        } else {
            $errors[] = $result;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | Mletchido Financial Group</title>
    <link rel="stylesheet" href="css/register.css">
</head>

<body>

<main class="register-page">
    <section class="register-container">

        <?php if ($success): ?>

    <div class="register-success">

        <div class="success-icon">✓</div>

        <h1>Registration Successful</h1>

        <p>
            Your Mletchido Financial Group account
            has been created successfully.
        </p>

        <p>
            Your account is now ready. You may sign in
            to continue.
        </p>

        <a href="login.php" class="register-button">
            Sign In
        </a>

    </div>

        <?php else: ?>

            <header class="register-header">
                <h1>Create Your Account</h1>
                <p>Join Mletchido Financial Group</p>
            </header>


            <?php
                // Registration-level failures (e.g. "username already taken")
                // are pushed onto $errors with a numeric key by register_user(),
                // rather than an associative field key. Surface those here.
                $general_errors = array_filter($errors, 'is_int', ARRAY_FILTER_USE_KEY);
            ?>

            <?php if (!empty($general_errors)): ?>
                <div class="form-error">
                    <?php foreach ($general_errors as $general_error): ?>
                        <p style="margin:0;"><?php echo htmlspecialchars($general_error, ENT_QUOTES, "UTF-8"); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" class="register-form" novalidate>

                <!-- PERSONAL INFORMATION -->
                <fieldset>
                    <legend>Personal Information</legend>

                    <div class="form-row">

                        <!-- FIRST NAME -->
                        <div class="form-group">
                            <label for="first_name">
                                First Name<span class="required-mark" aria-hidden="true">*</span>
                            </label>

                            <input
                                type="text"
                                id="first_name"
                                name="first_name"
                                value="<?php echo htmlspecialchars($_POST["first_name"] ?? "", ENT_QUOTES, "UTF-8"); ?>"
                                class="<?php echo isset($errors['first_name']) ? 'input-error' : ''; ?>"
                                autocomplete="given-name"
                                aria-invalid="<?php echo isset($errors['first_name']) ? 'true' : 'false'; ?>"
                                <?php echo isset($errors['first_name']) ? 'aria-describedby="first_name-error"' : ''; ?>
                                required
                            >

                            <?php if (isset($errors['first_name'])): ?>
                                <p class="field-error" id="first_name-error">
                                    <?php echo htmlspecialchars($errors['first_name'], ENT_QUOTES, "UTF-8"); ?>
                                </p>
                            <?php endif; ?>
                        </div>


                        <!-- MIDDLE NAME -->
                        <div class="form-group">
                            <label for="middle_name">Middle Name</label>

                            <input
                                type="text"
                                id="middle_name"
                                name="middle_name"
                                value="<?php echo htmlspecialchars($_POST["middle_name"] ?? "", ENT_QUOTES, "UTF-8"); ?>"
                                class="<?php echo isset($errors['middle_name']) ? 'input-error' : ''; ?>"
                                autocomplete="additional-name"
                                aria-invalid="<?php echo isset($errors['middle_name']) ? 'true' : 'false'; ?>"
                                <?php echo isset($errors['middle_name']) ? 'aria-describedby="middle_name-error"' : ''; ?>
                            >

                            <?php if (isset($errors['middle_name'])): ?>
                                <p class="field-error" id="middle_name-error">
                                    <?php echo htmlspecialchars($errors['middle_name'], ENT_QUOTES, "UTF-8"); ?>
                                </p>
                            <?php endif; ?>
                        </div>


                        <!-- LAST NAME -->
                        <div class="form-group">
                            <label for="last_name">
                                Last Name<span class="required-mark" aria-hidden="true">*</span>
                            </label>

                            <input
                                type="text"
                                id="last_name"
                                name="last_name"
                                value="<?php echo htmlspecialchars($_POST["last_name"] ?? "", ENT_QUOTES, "UTF-8"); ?>"
                                class="<?php echo isset($errors['last_name']) ? 'input-error' : ''; ?>"
                                autocomplete="family-name"
                                aria-invalid="<?php echo isset($errors['last_name']) ? 'true' : 'false'; ?>"
                                <?php echo isset($errors['last_name']) ? 'aria-describedby="last_name-error"' : ''; ?>
                                required
                            >

                            <?php if (isset($errors['last_name'])): ?>
                                <p class="field-error" id="last_name-error">
                                    <?php echo htmlspecialchars($errors['last_name'], ENT_QUOTES, "UTF-8"); ?>
                                </p>
                            <?php endif; ?>
                        </div>

                    </div>


                    <div class="form-row">

                        <!-- EMAIL -->
                        <div class="form-group">
                            <label for="email">
                                Email Address<span class="required-mark" aria-hidden="true">*</span>
                            </label>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="<?php echo htmlspecialchars($_POST["email"] ?? "", ENT_QUOTES, "UTF-8"); ?>"
                                class="<?php echo isset($errors['email']) ? 'input-error' : ''; ?>"
                                autocomplete="email"
                                aria-invalid="<?php echo isset($errors['email']) ? 'true' : 'false'; ?>"
                                <?php echo isset($errors['email']) ? 'aria-describedby="email-error"' : ''; ?>
                                required
                            >

                            <?php if (isset($errors['email'])): ?>
                                <p class="field-error" id="email-error">
                                    <?php echo htmlspecialchars($errors['email'], ENT_QUOTES, "UTF-8"); ?>
                                </p>
                            <?php endif; ?>
                        </div>


                        <!-- PHONE -->
                        <div class="form-group">
                            <label for="phone_number">
                                Phone Number<span class="required-mark" aria-hidden="true">*</span>
                            </label>

                            <input
                                type="tel"
                                id="phone_number"
                                name="phone_number"
                                value="<?php echo htmlspecialchars($_POST["phone_number"] ?? "", ENT_QUOTES, "UTF-8"); ?>"
                                class="<?php echo isset($errors['phone_number']) ? 'input-error' : ''; ?>"
                                autocomplete="tel"
                                inputmode="tel"
                                aria-invalid="<?php echo isset($errors['phone_number']) ? 'true' : 'false'; ?>"
                                <?php echo isset($errors['phone_number']) ? 'aria-describedby="phone_number-error"' : ''; ?>
                                required
                            >

                            <?php if (isset($errors['phone_number'])): ?>
                                <p class="field-error" id="phone_number-error">
                                    <?php echo htmlspecialchars($errors['phone_number'], ENT_QUOTES, "UTF-8"); ?>
                                </p>
                            <?php endif; ?>
                        </div>

                    </div>


                    <!-- ADDRESS -->
                    <div class="form-group form-full">
                        <label for="address">
                            Address<span class="required-mark" aria-hidden="true">*</span>
                        </label>

                        <textarea
                            id="address"
                            name="address"
                            rows="3"
                            class="<?php echo isset($errors['address']) ? 'input-error' : ''; ?>"
                            autocomplete="street-address"
                            aria-invalid="<?php echo isset($errors['address']) ? 'true' : 'false'; ?>"
                            <?php echo isset($errors['address']) ? 'aria-describedby="address-error"' : ''; ?>
                            required
                        ><?php echo htmlspecialchars($_POST["address"] ?? "", ENT_QUOTES, "UTF-8"); ?></textarea>

                        <?php if (isset($errors['address'])): ?>
                            <p class="field-error" id="address-error">
                                <?php echo htmlspecialchars($errors['address'], ENT_QUOTES, "UTF-8"); ?>
                            </p>
                        <?php endif; ?>
                    </div>

                </fieldset>


                <!-- ACCOUNT INFORMATION -->
                <fieldset>
                    <legend>Account Information</legend>


                    <!-- USERNAME -->
                    <div class="form-group">
                        <label for="username">
                            Username<span class="required-mark" aria-hidden="true">*</span>
                        </label>

                        <input
                            type="text"
                            id="username"
                            name="username"
                            value="<?php echo htmlspecialchars($_POST["username"] ?? "", ENT_QUOTES, "UTF-8"); ?>"
                            class="<?php echo isset($errors['username']) ? 'input-error' : ''; ?>"
                            autocomplete="username"
                            aria-invalid="<?php echo isset($errors['username']) ? 'true' : 'false'; ?>"
                            <?php echo isset($errors['username']) ? 'aria-describedby="username-error"' : ''; ?>
                            required
                        >

                        <?php if (isset($errors['username'])): ?>
                            <p class="field-error" id="username-error">
                                <?php echo htmlspecialchars($errors['username'], ENT_QUOTES, "UTF-8"); ?>
                            </p>
                        <?php endif; ?>
                    </div>


                    <div class="form-row">

                        <!-- PASSWORD -->
                        <div class="form-group">
                            <label for="password">
                                Password<span class="required-mark" aria-hidden="true">*</span>
                            </label>

                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="<?php echo isset($errors['password']) ? 'input-error' : ''; ?>"
                                autocomplete="new-password"
                                aria-invalid="<?php echo isset($errors['password']) ? 'true' : 'false'; ?>"
                                aria-describedby="password-note<?php echo isset($errors['password']) ? ' password-error' : ''; ?>"
                                required
                            >

                            <small class="password-note" id="password-note">
                                At least 8 characters with uppercase,
                                lowercase, number, and special character.
                            </small>

                            <?php if (isset($errors['password'])): ?>
                                <p class="field-error" id="password-error">
                                    <?php echo htmlspecialchars($errors['password'], ENT_QUOTES, "UTF-8"); ?>
                                </p>
                            <?php endif; ?>
                        </div>


                        <!-- CONFIRM PASSWORD -->
                        <div class="form-group">
                            <label for="confirm_password">
                                Confirm Password<span class="required-mark" aria-hidden="true">*</span>
                            </label>

                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                class="<?php echo isset($errors['confirm_password']) ? 'input-error' : ''; ?>"
                                autocomplete="new-password"
                                aria-invalid="<?php echo isset($errors['confirm_password']) ? 'true' : 'false'; ?>"
                                <?php echo isset($errors['confirm_password']) ? 'aria-describedby="confirm_password-error"' : ''; ?>
                                required
                            >

                            <?php if (isset($errors['confirm_password'])): ?>
                                <p class="field-error" id="confirm_password-error">
                                    <?php echo htmlspecialchars($errors['confirm_password'], ENT_QUOTES, "UTF-8"); ?>
                                </p>
                            <?php endif; ?>
                        </div>

                    </div>

                </fieldset>


                <button type="submit" class="register-button">
                    Create Account
                </button>


                <p class="login-link">
                    Already have an account?
                    <a href="login.php">Sign In</a>
                </p>

            </form>

        <?php endif; ?>

    </section>
</main>

</body>
</html>