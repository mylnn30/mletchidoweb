<?php
require_once "require_login.php";
require_once "csrf.php";
require_once "loan_application_function.php";
require_once "admin_function.php";

block_admin_from_customer_area();

$user_id = (int) $_SESSION["user_id"];
$errors = [];
$success_message = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!csrf_verify($_POST["csrf_token"] ?? "")) {

        $errors["general"] = "Your session has expired. Please refresh and try again.";

    } else {

        $loan_id = (int) ($_POST["loan_id"] ?? 0);
        $payment_date = $_POST["payment_date"] ?? "";
        $amount_paid = (float) ($_POST["amount_paid"] ?? 0);
        $payment_method = $_POST["payment_method"] ?? "";
        $reference_number = trim($_POST["reference_number"] ?? "");

        $loan = get_user_loan_details($loan_id, $user_id);

        if (!$loan) {
            $errors["loan_id"] = "Invalid loan.";
        }

        if ($amount_paid <= 0) {
            $errors["amount_paid"] = "Enter an amount greater than zero.";
        }

        if ($loan && $amount_paid > get_loan_remaining_balance($loan_id)) {
            $errors["amount_paid"] = "Payment cannot be greater than your remaining balance.";
        }

        if (!array_key_exists($payment_method, get_payment_methods())) {
            $errors["payment_method"] = "Select a valid payment method.";
        }

        if ($payment_date === "") {
            $errors["payment_date"] = "Payment date is required.";
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
    $success_message = "Payment submitted successfully. Your payment is waiting for admin confirmation.";
}

$loans = get_user_active_loans($user_id);
$payment_methods = get_payment_methods();

$selected_loan_id = (int) ($_GET["loan_id"] ?? 0);
$selected_loan = null;

foreach ($loans as $loan) {

    if ((int) $loan["id"] === $selected_loan_id) {
        $selected_loan = $loan;
        break;
    }
}

if (!$selected_loan && !empty($loans)) {

    $selected_loan = $loans[0];
    $selected_loan_id = (int) $selected_loan["id"];
}

$total_remaining = 0;
$total_payable = 0;
$total_paid = 0;

foreach ($loans as $loan) {

    $total_remaining += (float) $loan["remaining_balance"];
    $total_payable += (float) $loan["total_payable"];
    $total_paid += (float) $loan["total_paid"];
}

$progress = $total_payable > 0
    ? ($total_paid / $total_payable) * 100
    : 0;

$progress = min(100, max(0, $progress));

$payments = [];

foreach ($loans as $loan) {

    $loan_payments = get_user_payments(
        (int) $loan["id"],
        $user_id
    );

    foreach ($loan_payments as $payment) {

        $payment["loan_id"] = $loan["id"];
        $payment["loan_type"] = $loan["loan_type"];

        $payments[] = $payment;
    }
}

usort($payments, function ($a, $b) {

    return strcmp(
        $b["payment_date"],
        $a["payment_date"]
    );
});
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Payments | Mletchido Financial Group</title>

    <link
        rel="stylesheet"
        href="css/dashboard.css"
    >

    <link
        rel="stylesheet"
        href="css/payments.css"
    >

</head>

<body>

<header class="dashboard-topbar">

    <div class="topbar-brand">

        <img
            src="./assets/home_01.png"
            alt="Mletchido Financial Group logo"
            class="topbar-logo"
        >

        Mletchido Financial Group

    </div>

    <nav class="topbar-nav">

        <a href="dashboard.php">
            Dashboard
        </a>

        <a href="applications.php">
            My Applications
        </a>

        <a
            href="payments.php"
            class="is-active"
        >
            Payments
        </a>

        <a href="notifications.php">
            Notifications
        </a>

        <a href="profile.php">
            Profile
        </a>

        <a href="logout.php">
            Sign Out
        </a>

    </nav>

</header>


<main class="payment-page">

    <section class="payment-page-header">

        <span class="payment-eyebrow">
            ACCOUNT PAYMENTS
        </span>

        <h1>
            Payments
        </h1>

        <p>
            Manage your loan payments and track your repayment progress.
        </p>

    </section>


    <?php if ($success_message): ?>

        <div class="payment-alert success">

            <?php
            echo htmlspecialchars(
                $success_message,
                ENT_QUOTES,
                "UTF-8"
            );
            ?>

        </div>

    <?php endif; ?>


    <?php if (isset($errors["general"])): ?>

        <div class="payment-alert error">

            <?php
            echo htmlspecialchars(
                $errors["general"],
                ENT_QUOTES,
                "UTF-8"
            );
            ?>

        </div>

    <?php endif; ?>


    <?php if (empty($loans)): ?>

        <section class="payment-panel">

            <div class="payment-panel-header">

                <h2>
                    No Active Loans
                </h2>

                <p>
                    You currently do not have an approved active loan.
                </p>

            </div>

            <a
                href="loan_application.php"
                class="payment-button"
            >
                Apply for a Loan
            </a>

        </section>

    <?php else: ?>


        <!-- SUMMARY -->

        <section class="payment-summary">

            <div class="payment-summary-card main">

                <span class="summary-label">
                    Remaining Balance
                </span>

                <strong class="summary-value">

                    ₱<?php
                    echo number_format(
                        $total_remaining,
                        2
                    );
                    ?>

                </strong>

                <span class="summary-sub">

                    Across
                    <?php echo count($loans); ?>
                    active loan<?php echo count($loans) !== 1 ? "s" : ""; ?>

                </span>

            </div>


            <div class="payment-summary-card">

                <span class="summary-label">
                    Total Paid
                </span>

                <strong class="summary-value">

                    ₱<?php
                    echo number_format(
                        $total_paid,
                        2
                    );
                    ?>

                </strong>

                <span class="summary-sub">
                    Confirmed payments
                </span>

            </div>


            <div class="payment-summary-card">

                <span class="summary-label">
                    Total Payable
                </span>

                <strong class="summary-value">

                    ₱<?php
                    echo number_format(
                        $total_payable,
                        2
                    );
                    ?>

                </strong>

                <span class="summary-sub">
                    Principal + interest
                </span>

            </div>

        </section>


        <!-- OVERALL PROGRESS -->

        <section class="payment-overview">

            <div class="overview-top">

                <span>
                    Overall Repayment Progress
                </span>

                <strong>
                    <?php
                    echo number_format(
                        $progress,
                        0
                    );
                    ?>%
                </strong>

            </div>

            <div class="overview-track">

                <div
                    class="overview-fill"
                    style="width: <?php echo $progress; ?>%;"
                ></div>

            </div>

            <div class="overview-bottom">

                <span>
                    ₱<?php echo number_format($total_paid, 2); ?>
                    paid
                </span>

                <span>
                    ₱<?php echo number_format($total_payable, 2); ?>
                    total
                </span>

            </div>

        </section>


        <!-- PAYMENT CONTENT -->

        <section class="payment-layout">


            <!-- MAKE PAYMENT -->

            <div class="payment-panel">

                <div class="payment-panel-header">

                    <h2>
                        Make a Payment
                    </h2>

                    <p>
                        Submit your payment for admin confirmation.
                    </p>

                </div>


                <?php if ($selected_loan): ?>


                    <!-- REPAYMENT PLAN -->

                    <div class="repayment-plan">

                        <div class="repayment-plan-header">

                            <span>
                                YOUR REPAYMENT PLAN
                            </span>

                            <strong>

                                <?php
                                echo htmlspecialchars(
                                    ucfirst(
                                        $selected_loan[
                                            "payment_frequency"
                                        ]
                                    ),
                                    ENT_QUOTES,
                                    "UTF-8"
                                );
                                ?>

                            </strong>

                        </div>


                        <div class="repayment-amount">

                            <span>

                                <?php
                                echo htmlspecialchars(
                                    ucfirst(
                                        $selected_loan[
                                            "payment_frequency"
                                        ]
                                    ),
                                    ENT_QUOTES,
                                    "UTF-8"
                                );
                                ?>

                                Payment

                            </span>

                            <strong>

                                ₱<?php
                                echo number_format(
                                    (float)
                                    $selected_loan[
                                        "installment_amount"
                                    ],
                                    2
                                );
                                ?>

                            </strong>

                        </div>


                        <div class="repayment-details">


                            <div>

                                <span>
                                    Next Payment Due
                                </span>

                                <strong>

                                    <?php

                                    if (
                                        !empty(
                                            $selected_loan[
                                                "next_due_date"
                                            ]
                                        )
                                    ) {

                                        echo date(
                                            "F j, Y",
                                            strtotime(
                                                $selected_loan[
                                                    "next_due_date"
                                                ]
                                            )
                                        );

                                    } else {

                                        echo "Not set";

                                    }

                                    ?>

                                </strong>

                            </div>


                            <div>

                                <span>
                                    Remaining Balance
                                </span>

                                <strong>

                                    ₱<?php
                                    echo number_format(
                                        (float)
                                        $selected_loan[
                                            "remaining_balance"
                                        ],
                                        2
                                    );
                                    ?>

                                </strong>

                            </div>


                            <div>

                                <span>
                                    Interest Rate
                                </span>

                                <strong>

                                    <?php
                                    echo number_format(
                                        (float)
                                        (
                                            $selected_loan[
                                                "interest_rate"
                                            ] ?? 0
                                        ),
                                        2
                                    );
                                    ?>%

                                </strong>

                            </div>


                            <div>

                                <span>
                                    Total Payable
                                </span>

                                <strong>

                                    ₱<?php
                                    echo number_format(
                                        (float)
                                        $selected_loan[
                                            "total_payable"
                                        ],
                                        2
                                    );
                                    ?>

                                </strong>

                            </div>


                        </div>

                    </div>


                    <!-- PAYMENT FORM -->

                    <form
                        action="payments.php"
                        method="POST"
                        class="payment-form"
                    >

                        <?php csrf_field(); ?>


                        <div class="form-group">

                            <label for="loan_id">
                                Loan
                            </label>

                            <select
                                name="loan_id"
                                id="loan_id"
                                required
                            >

                                <?php foreach ($loans as $loan): ?>

                                    <option
                                        value="<?php echo (int) $loan["id"]; ?>"
                                        <?php echo $selected_loan_id === (int) $loan["id"] ? "selected" : ""; ?>
                                    >

                                        Loan #<?php echo (int) $loan["id"]; ?>

                                        -

                                        ₱<?php
                                        echo number_format(
                                            (float)
                                            $loan[
                                                "remaining_balance"
                                            ],
                                            2
                                        );
                                        ?>

                                        remaining

                                    </option>

                                <?php endforeach; ?>

                            </select>

                            <?php if (isset($errors["loan_id"])): ?>

                                <small class="field-error">

                                    <?php
                                    echo htmlspecialchars(
                                        $errors["loan_id"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                    ?>

                                </small>

                            <?php endif; ?>

                        </div>


                        <div class="form-group">

                            <label for="payment_date">
                                Payment Date
                            </label>

                            <input
                                type="date"
                                name="payment_date"
                                id="payment_date"
                                value="<?php
                                echo htmlspecialchars(
                                    $_POST[
                                        "payment_date"
                                    ] ?? date("Y-m-d"),
                                    ENT_QUOTES,
                                    "UTF-8"
                                );
                                ?>"
                                max="<?php echo date("Y-m-d"); ?>"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label for="amount_paid">
                                Amount Paid
                            </label>

                            <input
                                type="number"
                                name="amount_paid"
                                id="amount_paid"
                                min="0.01"
                                step="0.01"
                                placeholder="Enter payment amount"
                                required
                            >

                            <small class="payment-hint">

                                Recommended payment:

                                ₱<?php
                                echo number_format(
                                    (float)
                                    $selected_loan[
                                        "installment_amount"
                                    ],
                                    2
                                );
                                ?>

                                per

                                <?php
                                echo htmlspecialchars(
                                    strtolower(
                                        $selected_loan[
                                            "payment_frequency"
                                        ]
                                    )
                                );
                                ?>

                            </small>

                            <?php if (isset($errors["amount_paid"])): ?>

                                <small class="field-error">

                                    <?php
                                    echo htmlspecialchars(
                                        $errors["amount_paid"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                    ?>

                                </small>

                            <?php endif; ?>

                        </div>


                        <div class="form-group">

                            <label for="payment_method">
                                Payment Method
                            </label>

                            <select
                                name="payment_method"
                                id="payment_method"
                                required
                            >

                                <option value="">
                                    Select payment method
                                </option>

                                <?php foreach ($payment_methods as $key => $label): ?>

                                    <option
                                        value="<?php echo htmlspecialchars($key); ?>"
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $label,
                                            ENT_QUOTES,
                                            "UTF-8"
                                        );
                                        ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                            <?php if (isset($errors["payment_method"])): ?>

                                <small class="field-error">

                                    <?php
                                    echo htmlspecialchars(
                                        $errors["payment_method"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );
                                    ?>

                                </small>

                            <?php endif; ?>

                        </div>


                        <div class="form-group">

                            <label for="reference_number">
                                Reference Number
                            </label>

                            <input
                                type="text"
                                name="reference_number"
                                id="reference_number"
                                maxlength="100"
                                placeholder="Optional transaction/reference number"
                            >

                        </div>


                        <button
                            type="submit"
                            class="payment-button"
                        >
                            Submit Payment
                        </button>

                    </form>


                <?php endif; ?>

            </div>


            <!-- PAYMENT HISTORY -->

            <div class="payment-panel">

                <div class="payment-panel-header">

                    <h2>
                        Payment History
                    </h2>

                    <p>
                        Track all your submitted payments.
                    </p>

                </div>


                <?php if (empty($payments)): ?>

                    <div class="payment-empty">

                        No payments submitted yet.

                    </div>

                <?php else: ?>

                    <div class="payment-history">

                        <?php foreach ($payments as $payment): ?>

                            <div class="payment-history-item">

                                <div class="history-top">

                                    <strong class="history-amount">

                                        ₱<?php
                                        echo number_format(
                                            (float)
                                            $payment[
                                                "amount_paid"
                                            ],
                                            2
                                        );
                                        ?>

                                    </strong>


                                    <span
                                        class="payment-status <?php echo htmlspecialchars($payment["status"]); ?>"
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            ucfirst(
                                                $payment["status"]
                                            ),
                                            ENT_QUOTES,
                                            "UTF-8"
                                        );
                                        ?>

                                    </span>

                                </div>


                                <div class="history-meta">

                                    <span>

                                        Loan #<?php
                                        echo (int)
                                        $payment["loan_id"];
                                        ?>

                                    </span>

                                    <span>

                                        <?php
                                        echo date(
                                            "M j, Y",
                                            strtotime(
                                                $payment[
                                                    "payment_date"
                                                ]
                                            )
                                        );
                                        ?>

                                    </span>

                                    <span>

                                        <?php
                                        echo htmlspecialchars(
                                            $payment[
                                                "payment_method"
                                            ],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        );
                                        ?>

                                    </span>

                                </div>


                                <?php if (!empty($payment["reference_number"])): ?>

                                    <div class="history-reference">

                                        Reference:

                                        <?php
                                        echo htmlspecialchars(
                                            $payment[
                                                "reference_number"
                                            ],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        );
                                        ?>

                                    </div>

                                <?php endif; ?>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </div>


        </section>

    <?php endif; ?>

</main>

</body>
</html>