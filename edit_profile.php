<?php
require_once "require_login.php";
require_once "profile_function.php";
require_once "profile_validation.php";
require_once "csrf.php";
require_once "admin_function.php";

block_admin_from_customer_area();

$user = get_user_by_id($_SESSION["user_id"]);

if (!$user) {
    // Session points to a user that no longer exists in the DB.
    header("Location: logout.php");
    exit;
}

$errors = [];

// Pre-fill the form with the user's current values; a failed
// validation further down overwrites these with what they typed.
$first_name   = $user["first_name"];
$middle_name  = $user["middle_name"];
$last_name    = $user["last_name"];
$email        = $user["email"];
$address      = $user["address"];
$phone_number = $user["phone_number"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!csrf_verify($_POST["csrf_token"] ?? "")) {
        // The token is missing/stale/mismatched -- most likely the
        // session expired or the form was submitted from somewhere
        // other than this page. Treat it as a generic form error
        // rather than a specific one, same reasoning as the login
        // form: don't give an attacker feedback on why it failed.
        $errors["general"] = "Your session has expired. Please try again.";
    } else {
        $first_name   = trim($_POST["first_name"] ?? "");
        $middle_name  = trim($_POST["middle_name"] ?? "");
        $last_name    = trim($_POST["last_name"] ?? "");
        $email        = trim($_POST["email"] ?? "");
        $address      = trim($_POST["address"] ?? "");
        $phone_number = trim($_POST["phone_number"] ?? "");

        $errors = validate_profile_update(
            $first_name,
            $middle_name,
            $last_name,
            $email,
            $address,
            $phone_number
        );

        if (empty($errors)) {
            $result = update_user_profile(
                $_SESSION["user_id"],
                $first_name,
                $middle_name,
                $last_name,
                $email,
                $address,
                $phone_number
            );

            if ($result === true) {
                // Keep the session's display values (used on the
                // dashboard welcome banner) in sync with the update.
                $_SESSION["first_name"] = $first_name;
                $_SESSION["last_name"]  = $last_name;
                $_SESSION["email"]      = $email;

                header("Location: profile.php?updated=1");
                exit;
            }

            $errors["general"] = $result;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | Mletchido Financial Group</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/profile.css">
</head>
<body>

<header class="dashboard-topbar">
    <div class="topbar-brand">
        <img src="./assets/home_01.png" alt="Mletchido Financial Group logo" class="topbar-logo">
        Mletchido Financial Group
    </div>
    <nav class="topbar-nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="applications.php">My Applications</a>
        <a href="profile.php" class="is-active">Profile</a>
        <a href="logout.php">Sign Out</a>
    </nav>
</header>

<main class="profile-page">
    <section class="profile-container">

        <header class="profile-header">
            <h1>Edit Profile</h1>
            <a href="profile.php" class="profile-cancel-link">Cancel</a>
        </header>

        <?php if (isset($errors["general"])): ?>
            <div class="form-error" role="alert" aria-live="polite">
                <p><?php echo htmlspecialchars($errors["general"], ENT_QUOTES, "UTF-8"); ?></p>
            </div>
        <?php endif; ?>

        <form action="edit_profile.php" method="POST" class="profile-form" novalidate>
            <?php csrf_field(); ?>

            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name<span class="required-mark">*</span></label>
                    <input
                        type="text"
                        id="first_name"
                        name="first_name"
                        value="<?php echo htmlspecialchars($first_name, ENT_QUOTES, "UTF-8"); ?>"
                        class="<?php echo isset($errors["first_name"]) ? "input-error" : ""; ?>"
                        required
                    >
                    <?php if (isset($errors["first_name"])): ?>
                        <p class="field-error"><?php echo htmlspecialchars($errors["first_name"], ENT_QUOTES, "UTF-8"); ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="middle_name">Middle Name</label>
                    <input
                        type="text"
                        id="middle_name"
                        name="middle_name"
                        value="<?php echo htmlspecialchars($middle_name, ENT_QUOTES, "UTF-8"); ?>"
                        class="<?php echo isset($errors["middle_name"]) ? "input-error" : ""; ?>"
                    >
                    <?php if (isset($errors["middle_name"])): ?>
                        <p class="field-error"><?php echo htmlspecialchars($errors["middle_name"], ENT_QUOTES, "UTF-8"); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="last_name">Last Name<span class="required-mark">*</span></label>
                    <input
                        type="text"
                        id="last_name"
                        name="last_name"
                        value="<?php echo htmlspecialchars($last_name, ENT_QUOTES, "UTF-8"); ?>"
                        class="<?php echo isset($errors["last_name"]) ? "input-error" : ""; ?>"
                        required
                    >
                    <?php if (isset($errors["last_name"])): ?>
                        <p class="field-error"><?php echo htmlspecialchars($errors["last_name"], ENT_QUOTES, "UTF-8"); ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="email">Email<span class="required-mark">*</span></label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?php echo htmlspecialchars($email, ENT_QUOTES, "UTF-8"); ?>"
                        class="<?php echo isset($errors["email"]) ? "input-error" : ""; ?>"
                        required
                    >
                    <?php if (isset($errors["email"])): ?>
                        <p class="field-error"><?php echo htmlspecialchars($errors["email"], ENT_QUOTES, "UTF-8"); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="phone_number">Phone Number<span class="required-mark">*</span></label>
                    <input
                        type="text"
                        id="phone_number"
                        name="phone_number"
                        value="<?php echo htmlspecialchars($phone_number, ENT_QUOTES, "UTF-8"); ?>"
                        class="<?php echo isset($errors["phone_number"]) ? "input-error" : ""; ?>"
                        required
                    >
                    <?php if (isset($errors["phone_number"])): ?>
                        <p class="field-error"><?php echo htmlspecialchars($errors["phone_number"], ENT_QUOTES, "UTF-8"); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group form-full">
                <label for="address">Address<span class="required-mark">*</span></label>
                <textarea
                    id="address"
                    name="address"
                    rows="3"
                    class="<?php echo isset($errors["address"]) ? "input-error" : ""; ?>"
                    required
                ><?php echo htmlspecialchars($address, ENT_QUOTES, "UTF-8"); ?></textarea>
                <?php if (isset($errors["address"])): ?>
                    <p class="field-error"><?php echo htmlspecialchars($errors["address"], ENT_QUOTES, "UTF-8"); ?></p>
                <?php endif; ?>
            </div>

            <button type="submit" class="profile-save-btn">Save Changes</button>
        </form>

    </section>
</main>

</body>
</html>