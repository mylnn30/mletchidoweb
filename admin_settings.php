<?php
require_once "require_admin.php";
require_once "csrf.php";

$active_tab = "settings";
$errors = [];
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!csrf_verify($_POST["csrf_token"] ?? "")) {
        $errors["general"] = "Your session has expired. Please refresh and try again.";
    } else {
        $default_interest_rate = trim($_POST["default_interest_rate"] ?? "");
        $late_payment_penalty_percent = trim($_POST["late_payment_penalty_percent"] ?? "");
        $grace_period_days = trim($_POST["grace_period_days"] ?? "");

        if (!is_numeric($default_interest_rate) || (float) $default_interest_rate < 0) {
            $errors["default_interest_rate"] = "Enter a valid interest rate.";
        }
        if (!is_numeric($late_payment_penalty_percent) || (float) $late_payment_penalty_percent < 0) {
            $errors["late_payment_penalty_percent"] = "Enter a valid penalty percentage.";
        }
        if (!ctype_digit($grace_period_days)) {
            $errors["grace_period_days"] = "Enter a whole number of days.";
        }

        if (empty($errors)) {
            update_system_setting("default_interest_rate", $default_interest_rate);
            update_system_setting("late_payment_penalty_percent", $late_payment_penalty_percent);
            update_system_setting("grace_period_days", $grace_period_days);
            $success = true;
        }
    }
}

$settings = get_system_settings();
$default_interest_rate = $_POST["default_interest_rate"] ?? ($settings["default_interest_rate"] ?? "4.99");
$late_payment_penalty_percent = $_POST["late_payment_penalty_percent"] ?? ($settings["late_payment_penalty_percent"] ?? "2.00");
$grace_period_days = $_POST["grace_period_days"] ?? ($settings["grace_period_days"] ?? "5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings | Mletchido Financial Group</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>

<?php include "admin_nav.php"; ?>

<main class="admin-page">

    <header class="admin-header">
        <div>
            <h1>System Settings</h1>
            <p>Defaults used across the loan system. Individual loans can still be set differently on their own detail page.</p>
        </div>
    </header>

    <?php if (isset($errors["general"])): ?>
        <div class="form-error" role="alert" aria-live="polite">
            <p><?php echo htmlspecialchars($errors["general"], ENT_QUOTES, "UTF-8"); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="form-success">
            <p>Settings saved.</p>
        </div>
    <?php endif; ?>

    <form action="admin_settings.php" method="POST" class="admin-form">
        <?php csrf_field(); ?>

        <div class="form-group">
            <label for="default_interest_rate">Default Interest Rate (% per annum)</label>
            <input
                type="number"
                id="default_interest_rate"
                name="default_interest_rate"
                step="0.01"
                min="0"
                value="<?php echo htmlspecialchars($default_interest_rate, ENT_QUOTES, "UTF-8"); ?>"
                class="<?php echo isset($errors["default_interest_rate"]) ? "input-error" : ""; ?>"
            >
            <?php if (isset($errors["default_interest_rate"])): ?>
                <p class="field-error"><?php echo htmlspecialchars($errors["default_interest_rate"], ENT_QUOTES, "UTF-8"); ?></p>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="late_payment_penalty_percent">Late Payment Penalty (%)</label>
            <input
                type="number"
                id="late_payment_penalty_percent"
                name="late_payment_penalty_percent"
                step="0.01"
                min="0"
                value="<?php echo htmlspecialchars($late_payment_penalty_percent, ENT_QUOTES, "UTF-8"); ?>"
                class="<?php echo isset($errors["late_payment_penalty_percent"]) ? "input-error" : ""; ?>"
            >
            <?php if (isset($errors["late_payment_penalty_percent"])): ?>
                <p class="field-error"><?php echo htmlspecialchars($errors["late_payment_penalty_percent"], ENT_QUOTES, "UTF-8"); ?></p>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="grace_period_days">Grace Period (days after due date before marked overdue)</label>
            <input
                type="number"
                id="grace_period_days"
                name="grace_period_days"
                step="1"
                min="0"
                value="<?php echo htmlspecialchars($grace_period_days, ENT_QUOTES, "UTF-8"); ?>"
                class="<?php echo isset($errors["grace_period_days"]) ? "input-error" : ""; ?>"
            >
            <?php if (isset($errors["grace_period_days"])): ?>
                <p class="field-error"><?php echo htmlspecialchars($errors["grace_period_days"], ENT_QUOTES, "UTF-8"); ?></p>
            <?php endif; ?>
        </div>

        <button type="submit" class="admin-btn admin-btn-approve" style="align-self:flex-start;">Save Settings</button>
    </form>

</main>

</body>
</html>