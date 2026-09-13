<?php
require_once "require_admin.php";
require_once "csrf.php";
require_once "function.php";
require_once "profile_function.php";
require_once "profile_validation.php";
require_once "validation.php";

$active_tab = "borrowers";

$edit_user_id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;
$is_edit = $edit_user_id > 0;

$existing = null;
if ($is_edit) {
    $existing = get_user_full_by_id($edit_user_id);
    if (!$existing) {
        header("Location: admin_users.php");
        exit;
    }
}

$errors = [];

$username      = $existing["username"] ?? "";
$first_name    = $existing["first_name"] ?? "";
$middle_name   = $existing["middle_name"] ?? "";
$last_name     = $existing["last_name"] ?? "";
$email         = $existing["email"] ?? "";
$address       = $existing["address"] ?? "";
$phone_number  = $existing["phone_number"] ?? "";
$grant_admin   = $is_edit ? ((int) ($existing["is_admin"] ?? 0) === 1) : false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!csrf_verify($_POST["csrf_token"] ?? "")) {
        $errors["general"] = "Your session has expired. Please refresh and try again.";
    } else {
        $username     = trim($_POST["username"] ?? "");
        $first_name   = trim($_POST["first_name"] ?? "");
        $middle_name  = trim($_POST["middle_name"] ?? "");
        $last_name    = trim($_POST["last_name"] ?? "");
        $email        = trim($_POST["email"] ?? "");
        $address      = trim($_POST["address"] ?? "");
        $phone_number = trim($_POST["phone_number"] ?? "");
        $grant_admin  = isset($_POST["grant_admin"]);

        if ($is_edit) {
            $errors = validate_profile_update($first_name, $middle_name, $last_name, $email, $address, $phone_number);

            if (empty($errors)) {
                $result = update_user_profile($edit_user_id, $first_name, $middle_name, $last_name, $email, $address, $phone_number);

                if ($result === true) {
                    set_user_admin_status($edit_user_id, (int) $_SESSION["user_id"], $grant_admin);
                    header("Location: admin_users.php?updated=1");
                    exit;
                }

                $errors["general"] = $result;
            }
        } else {
            $password = $_POST["password"] ?? "";
            $confirm_password = $_POST["confirm_password"] ?? "";

            $errors = validate_registration(
                $username, $first_name, $middle_name, $last_name,
                $email, $address, $password, $confirm_password, $phone_number
            );

            if (empty($errors)) {
                $result = register_user($username, $first_name, $middle_name, $last_name, $email, $address, $password, $phone_number);

                if ($result === true) {
                    // register_user() doesn't return the new id, so look
                    // the account back up by username to grant admin if
                    // the checkbox was ticked.
                    if ($grant_admin) {
                        $new_user = get_user_by_username($username);
                        if ($new_user) {
                            set_user_admin_status((int) $new_user["id"], (int) $_SESSION["user_id"], true);
                        }
                    }
                    header("Location: admin_users.php?created=1");
                    exit;
                }

                $errors["general"] = $result;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? "Edit User" : "Add User"; ?> | Mletchido Financial Group</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>

<?php include "admin_nav.php"; ?>

<main class="admin-page">

    <a href="admin_users.php" class="back-link">← Back to Users</a>

    <header class="admin-header">
        <div>
            <h1><?php echo $is_edit ? "Edit User" : "Add New User"; ?></h1>
            <p><?php echo $is_edit ? "Update this account's profile details and role." : "Create a new borrower or admin account."; ?></p>
        </div>
    </header>

    <?php if (isset($errors["general"])): ?>
        <div class="form-error" role="alert" aria-live="polite">
            <p><?php echo htmlspecialchars($errors["general"], ENT_QUOTES, "UTF-8"); ?></p>
        </div>
    <?php endif; ?>

    <form action="admin_user_form.php<?php echo $is_edit ? "?id=" . $edit_user_id : ""; ?>" method="POST" class="admin-form" novalidate>
        <?php csrf_field(); ?>

        <div class="form-row">
            <div class="form-group">
                <label for="username">Username<span class="required-mark">*</span></label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    value="<?php echo htmlspecialchars($username, ENT_QUOTES, "UTF-8"); ?>"
                    class="<?php echo isset($errors["username"]) ? "input-error" : ""; ?>"
                    <?php echo $is_edit ? "readonly" : ""; ?>
                    required
                >
                <?php if (isset($errors["username"])): ?>
                    <p class="field-error"><?php echo htmlspecialchars($errors["username"], ENT_QUOTES, "UTF-8"); ?></p>
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

        <div class="form-group">
            <label for="address">Address<span class="required-mark">*</span></label>
            <textarea id="address" name="address" rows="2" class="<?php echo isset($errors["address"]) ? "input-error" : ""; ?>" required><?php echo htmlspecialchars($address, ENT_QUOTES, "UTF-8"); ?></textarea>
            <?php if (isset($errors["address"])): ?>
                <p class="field-error"><?php echo htmlspecialchars($errors["address"], ENT_QUOTES, "UTF-8"); ?></p>
            <?php endif; ?>
        </div>

        <?php if (!$is_edit): ?>
            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password<span class="required-mark">*</span></label>
                    <input type="password" id="password" name="password" class="<?php echo isset($errors["password"]) ? "input-error" : ""; ?>" required>
                    <?php if (isset($errors["password"])): ?>
                        <p class="field-error"><?php echo htmlspecialchars($errors["password"], ENT_QUOTES, "UTF-8"); ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password<span class="required-mark">*</span></label>
                    <input type="password" id="confirm_password" name="confirm_password" class="<?php echo isset($errors["confirm_password"]) ? "input-error" : ""; ?>" required>
                    <?php if (isset($errors["confirm_password"])): ?>
                        <p class="field-error"><?php echo htmlspecialchars($errors["confirm_password"], ENT_QUOTES, "UTF-8"); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label>
                <input type="checkbox" name="grant_admin" value="1" <?php echo $grant_admin ? "checked" : ""; ?>>
                Grant admin access
            </label>
        </div>

        <button type="submit" class="admin-btn admin-btn-approve" style="align-self:flex-start;">
            <?php echo $is_edit ? "Save Changes" : "Create Account"; ?>
        </button>
    </form>

</main>

</body>
</html>