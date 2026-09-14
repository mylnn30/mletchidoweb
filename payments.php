<?php
require_once "require_login.php";
require_once "csrf.php";
require_once "loan_application_function.php";
require_once "admin_function.php";

block_admin_from_customer_area();

$errors = [];
$success_message = null;
$user_id = (int) $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!csrf_verify($_POST["csrf_token"] ?? "")) {
        $errors["general"] = "Your session has expired. Please refresh and try again.";
    } else {
        $loan_id = (int) ($_POST["loan_id"] ?? 0);
        $payment_date = $_POST["payment_date"] ?? date("Y-m-d");
        $amount_paid = (float) ($_POST["amount_paid"] ?? 0);
        $payment_method = $_POST["payment_method"] ?? "";
        $reference_number = trim($_POST["reference_number"] ?? "");

        $loan = get_user_loan_details($loan_id, $user_id);

        if (!$loan) {
            $errors["loan_id"] = "Invalid loan.";
        } elseif ($amount_paid <= 0) {
            $errors["amount_paid"] = "Enter an amount greater than zero.";
        } elseif ($amount_paid > get_loan_remaining_balance($loan_id)) {
            $errors["amount_paid"] = "Payment cannot be greater than your remaining balance.";
        } elseif (!isset(get_payment_methods()[$payment_method])) {
            $errors["payment_method"] = "Select a valid payment method.";
        } elseif ($payment_date === "") {
            $errors["payment_date"] = "Enter a payment date.";
        }

        if (empty($errors)) {
            $result = record_payment(
                $loan_id,
                $payment_date,
                $amount_paid,
                $payment_method,
                $reference_number !== "" ? $reference_number : null,
                "pending"
            );

            if ($result === true) {
                create_notification(
                    $user_id,
                    "Your payment of ₱" . number_format($amount_paid, 2) . " has been submitted and is awaiting confirmation.",
                    "payment_pending"
                );

                header("Location: payments.php?submitted=1");
                exit;
            }

            $errors["general"] = $result;
        }
    }
}

if (isset($_GET["submitted"])) {
    $success_message = "Payment submitted successfully. It will update your balance after admin confirmation.";
}

$loans = get_user_active_loans($user_id);
$payment_methods = get_payment_methods();
$selected_loan_id = (int) ($_GET["loan_id"] ?? ($_POST["loan_id"] ?? 0));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments | Mletchido Financial Group</title>
    <link rel="stylesheet" href="css/dashboard.css">
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
        <a href="payments.php" class="is-active">Payments</a>
        <a href="notifications.php">Notifications</a>
        <a href="profile.php">Profile</a>
        <a href="logout.php">Sign Out</a>
    </nav>
</header>

<main class="dashboard-page">

    <section class="dashboard-welcome">
        <div>
            <h1>Payments</h1>
            <p>Submit a payment for an approved loan and track its status.</p>
        </div>
    </section>

    <?php if (isset($errors["general"])): ?>
        <div class="dashboard-card" role="alert">
            <p>
                <?php echo htmlspecialchars($errors["general"], ENT_QUOTES, "UTF-8"); ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="dashboard-card">
            <p>
                <?php echo htmlspecialchars($success_message, ENT_QUOTES, "UTF-8"); ?>
            </p>
        </div>
    <?php endif; ?>

    <!-- PAYMENT DESTINATIONS -->

        <section class="payment-destinations">

            <div class="payment-header">

                <span class="payment-eyebrow">
                    PAYMENT OPTIONS
                </span>

                <h2>Where to Send Your Payment</h2>

                <p>
                    Choose your preferred payment method.
                </p>

            </div>

            <div class="payment-grid">

                <article class="payment-card">

                    <div class="payment-content">

                        <h3>GCash</h3>

                        <p>GCash Number</p>

                        <span>
                            0991 818 8995
                        </span>

                    </div>

                    <a
                        href="assets/Gcash QR.jpg"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="payment-qr-link"
                    >
                        <img
                            src="assets/Gcash QR.jpg"
                            alt="GCash QR Code"
                            class="payment-qr"
                        >
                    </a>

                </article>

                <article class="payment-card">

                    <div class="payment-content">

                        <h3>MariBank</h3>

                        <p>Account Name</p>

                        <span>
                            MYLEN LETCHIDO
                        </span>

                        <p>Account</p>

                        <span>
                            MariBank (****1397)
                        </span>

                    </div>

                    <a
                        href="assets/MariBank QR.jpg"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="payment-qr-link"
                    >
                        <img
                            src="assets/MariBank QR.jpg"
                            alt="MariBank QR Code"
                            class="payment-qr"
                        >
                    </a>

                </article>

                <article class="payment-card">

                    <div class="payment-content">

                        <h3>Cash</h3>

                        <p>Payment Location</p>

                        <span>
                            026 Pulantubig,<br>
                            Dumaguete City,<br>
                            Negros Oriental,<br>
                            Philippines
                        </span>

                    </div>

                    <div class="cash-symbol">
                        ₱
                    </div>

                </article>

            </div>

            <div class="payment-note">

                After sending your payment, submit your payment details
                and proof of payment through

                <a href="payments.php">
                    Payments
                </a>.

            </div>

        </section>

    <!-- PAYMENT FORM + HISTORY -->
    <section class="dashboard-cards">

        <!-- SUBMIT PAYMENT -->
        <div class="dashboard-card">
            <h2>Submit Payment</h2>

            <?php if (empty($loans)): ?>

                <p class="card-empty">
                    You do not have an active approved loan.
                </p>

            <?php else: ?>

                <form action="payments.php" method="POST">

                    <?php csrf_field(); ?>

                    <p>
                        <strong>Remaining Balance:</strong>
                        ₱<?php

                        $selected_loan = null;

                        foreach ($loans as $loan) {
                            if ((int) $loan["id"] === $selected_loan_id) {
                                $selected_loan = $loan;
                                break;
                            }
                        }

                        if (!$selected_loan) {
                            $selected_loan = $loans[0];
                            $selected_loan_id = (int) $selected_loan["id"];
                        }

                        echo number_format(
                            (float) $selected_loan["remaining_balance"],
                            2
                        );

                        ?>
                    </p>

                    <!-- LOAN -->
                    <div class="form-group">
                        <label for="loan_id">Loan</label>

                        <select id="loan_id" name="loan_id">

                            <?php foreach ($loans as $loan): ?>

                                <option
                                    value="<?php echo (int) $loan["id"]; ?>"
                                    <?php echo $selected_loan_id === (int) $loan["id"] ? "selected" : ""; ?>
                                >

                                    #<?php echo (int) $loan["id"]; ?> —
                                    <?php echo htmlspecialchars(
                                        ucwords(
                                            str_replace(
                                                "_",
                                                " ",
                                                $loan["loan_type"]
                                            )
                                        ),
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ); ?>

                                    — ₱<?php echo number_format(
                                        (float) $loan["remaining_balance"],
                                        2
                                    ); ?> remaining

                                </option>

                            <?php endforeach; ?>

                        </select>

                        <?php if (isset($errors["loan_id"])): ?>
                            <p class="field-error">
                                <?php echo htmlspecialchars(
                                    $errors["loan_id"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>
                            </p>
                        <?php endif; ?>

                    </div>

                    <!-- PAYMENT DATE -->
                    <div class="form-group">

                        <label for="payment_date">
                            Payment Date
                        </label>

                        <input
                            type="date"
                            id="payment_date"
                            name="payment_date"
                            value="<?php echo htmlspecialchars(
                                $_POST["payment_date"] ?? date("Y-m-d"),
                                ENT_QUOTES,
                                "UTF-8"
                            ); ?>"
                            max="<?php echo date("Y-m-d"); ?>"
                        >

                        <?php if (isset($errors["payment_date"])): ?>
                            <p class="field-error">
                                <?php echo htmlspecialchars(
                                    $errors["payment_date"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>
                            </p>
                        <?php endif; ?>

                    </div>

                    <!-- AMOUNT -->
                    <div class="form-group">

                        <label for="amount_paid">
                            Amount Paid (₱)
                        </label>

                        <input
                            type="number"
                            id="amount_paid"
                            name="amount_paid"
                            min="0.01"
                            step="0.01"
                            value="<?php echo htmlspecialchars(
                                $_POST["amount_paid"] ?? "",
                                ENT_QUOTES,
                                "UTF-8"
                            ); ?>"
                        >

                        <?php if (isset($errors["amount_paid"])): ?>
                            <p class="field-error">
                                <?php echo htmlspecialchars(
                                    $errors["amount_paid"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>
                            </p>
                        <?php endif; ?>

                    </div>

                    <!-- PAYMENT METHOD -->
                    <div class="form-group">

                        <label for="payment_method">
                            Payment Method
                        </label>

                        <select
                            id="payment_method"
                            name="payment_method"
                        >

                            <option value="">
                                Select a method
                            </option>

                            <?php foreach ($payment_methods as $key => $label): ?>

                                <option
                                    value="<?php echo htmlspecialchars(
                                        $key,
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ); ?>"
                                    <?php echo ($_POST["payment_method"] ?? "") === $key ? "selected" : ""; ?>
                                >

                                    <?php echo htmlspecialchars(
                                        $label,
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ); ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                        <?php if (isset($errors["payment_method"])): ?>
                            <p class="field-error">
                                <?php echo htmlspecialchars(
                                    $errors["payment_method"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>
                            </p>
                        <?php endif; ?>

                    </div>

                    <!-- REFERENCE NUMBER -->
                    <div class="form-group">

                        <label for="reference_number">
                            Reference Number
                        </label>

                        <input
                            type="text"
                            id="reference_number"
                            name="reference_number"
                            maxlength="100"
                            value="<?php echo htmlspecialchars(
                                $_POST["reference_number"] ?? "",
                                ENT_QUOTES,
                                "UTF-8"
                            ); ?>"
                            placeholder="e.g. GCash transaction ID"
                        >

                    </div>

                    <button
                        type="submit"
                        class="welcome-cta"
                    >
                        Submit Payment
                    </button>

                </form>

            <?php endif; ?>

        </div>

        <!-- PAYMENT HISTORY -->
        <div class="dashboard-card">

            <h2>Payment History</h2>

            <?php

            $all_user_payments = [];

            foreach ($loans as $loan) {

                foreach (
                    get_user_payments(
                        (int) $loan["id"],
                        $user_id
                    ) as $payment
                ) {

                    $payment["loan_id"] = $loan["id"];
                    $all_user_payments[] = $payment;

                }
            }

            usort(
                $all_user_payments,
                function ($a, $b) {
                    return strcmp(
                        $b["payment_date"],
                        $a["payment_date"]
                    );
                }
            );

            ?>

            <?php if (empty($all_user_payments)): ?>

                <p class="card-empty">
                    No payments yet.
                </p>

            <?php else: ?>

                <ul class="activity-list">

                    <?php foreach ($all_user_payments as $payment): ?>

                        <li>

                            <span>

                                ₱<?php echo number_format(
                                    (float) $payment["amount_paid"],
                                    2
                                ); ?>

                                <small>
                                    Loan #<?php echo (int) $payment["loan_id"]; ?>
                                    ·
                                    <?php echo date(
                                        "M j, Y",
                                        strtotime($payment["payment_date"])
                                    ); ?>
                                </small>

                            </span>

                            <span
                                class="status-badge status-<?php
                                    echo $payment["status"] === "confirmed"
                                        ? "approved"
                                        : (
                                            $payment["status"] === "failed"
                                                ? "rejected"
                                                : "pending"
                                        );
                                ?>"
                            >

                                <?php echo htmlspecialchars(
                                    ucfirst($payment["status"]),
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>

                            </span>

                        </li>

                    <?php endforeach; ?>

                </ul>

            <?php endif; ?>

        </div>

    </section>

</main>

</body>
</html>