<?php
require_once "require_login.php";
require_once "loan_application_function.php";
require_once "loan_application_validation.php";
require_once "admin_function.php";
require_once "csrf.php";

block_admin_from_customer_area();

$notifications = get_notifications_for_user($_SESSION["user_id"], true);

$first_name = $_SESSION["first_name"] ?? "";
$last_name = $_SESSION["last_name"] ?? "";
$username = $_SESSION["username"] ?? "";
$email = $_SESSION["email"] ?? "";

$loan_type_labels = get_loan_types();
$applications = get_user_loan_applications($_SESSION["user_id"]);
$active_loans = get_user_active_loans((int) $_SESSION["user_id"]);

$active_count = 0;
$approved_count = 0;
$total_borrowed = 0.0;

foreach ($applications as $app) {
    if ($app["status"] === "pending") {
        $active_count++;
    } elseif ($app["status"] === "approved") {
        $approved_count++;
        $total_borrowed += (float) $app["amount"];
    }
}

$total_remaining = 0.0;

foreach ($active_loans as $loan) {
    $total_remaining += (float) $loan["remaining_balance"];
}

$stats = [
    [
        "label" => "Active Applications",
        "value" => (string) $active_count
    ],
    [
        "label" => "Approved Loans",
        "value" => (string) $approved_count
    ],
    [
        "label" => "Remaining Balance",
        "value" => "₱" . number_format($total_remaining, 2)
    ]
];

$recent_activity = array_map(
    function ($app) use ($loan_type_labels) {
        return [
            "title" => $loan_type_labels[$app["loan_type"]]["label"]
                ?? $app["loan_type"],
            "status" => $app["status"]
        ];
    },
    array_slice($applications, 0, 3)
);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Dashboard | Mletchido Financial Group</title>

    <link
        rel="stylesheet"
        href="css/dashboard.css"
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

        <a
            href="dashboard.php"
            class="is-active"
        >
            Dashboard
        </a>

        <a href="applications.php">
            My Applications
        </a>

        <a href="payments.php">
            Payments
        </a>

        <a href="notifications.php">
            Notifications<?php
            echo !empty($notifications)
                ? " (" . count($notifications) . ")"
                : "";
            ?>
        </a>

        <a href="profile.php">
            Profile
        </a>

        <a href="logout.php">
            Sign Out
        </a>

    </nav>

</header>


<main class="dashboard-page">


    <!-- ==================== WELCOME ==================== -->

    <section class="dashboard-welcome">

        <div>

            <h1>
                Welcome back,
                <?php
                echo htmlspecialchars(
                    $first_name !== ""
                        ? $first_name
                        : $username,
                    ENT_QUOTES,
                    "UTF-8"
                );
                ?>
            </h1>

            <p>
                Here's what's happening with your account today.
            </p>

        </div>

        <a
            href="loan_application.php"
            class="welcome-cta"
        >
            Apply for a Loan
        </a>

    </section>


    <!-- ==================== STATS ==================== -->

    <section class="stat-row">

        <?php foreach ($stats as $stat): ?>

            <div class="stat-tile">

                <span class="stat-value">

                    <?php
                    echo htmlspecialchars(
                        $stat["value"],
                        ENT_QUOTES,
                        "UTF-8"
                    );
                    ?>

                </span>

                <span class="stat-label">

                    <?php
                    echo htmlspecialchars(
                        $stat["label"],
                        ENT_QUOTES,
                        "UTF-8"
                    );
                    ?>

                </span>

            </div>

        <?php endforeach; ?>

    </section>


    <!-- ==================== NOTIFICATIONS ==================== -->

    <?php if (!empty($notifications)): ?>

        <section class="dashboard-card notification-card">

            <h2>
                Unread Notifications
            </h2>

            <ul class="activity-list">

                <?php foreach (
                    array_slice($notifications, 0, 3)
                    as $notification
                ): ?>

                    <li>

                        <span>

                            <?php
                            echo htmlspecialchars(
                                $notification["message"],
                                ENT_QUOTES,
                                "UTF-8"
                            );
                            ?>

                        </span>

                    </li>

                <?php endforeach; ?>

            </ul>

            <a
                href="notifications.php"
                class="card-link"
            >
                View All Notifications →
            </a>

        </section>

    <?php endif; ?>


    <!-- ==================== ACTIVE LOANS ==================== -->

    <?php if (!empty($active_loans)): ?>

        <section class="payments-section">

            <div class="payments-header">

                <div>

                    <span class="payments-eyebrow">
                        LOAN REPAYMENT
                    </span>

                    <h2>
                        My Active Loans
                    </h2>

                    <p>
                        Track your balance, repayment progress,
                        and upcoming payments.
                    </p>

                </div>

            </div>


            <?php foreach ($active_loans as $loan): ?>

                <?php

                $is_overdue =
                    $loan["next_due_date"] !== null
                    && $loan["next_due_date"] < date("Y-m-d")
                    && $loan["remaining_balance"] > 0;

                $total_payable =
                    (float) $loan["total_payable"];

                $total_paid =
                    (float) $loan["total_paid"];

                $remaining =
                    (float) $loan["remaining_balance"];

                $progress =
                    $total_payable > 0
                        ? ($total_paid / $total_payable) * 100
                        : 0;

                $progress =
                    min(100, max(0, $progress));

                ?>

                <article class="loan-payment-card">


                    <!-- LOAN HEADER -->

                    <div class="loan-payment-top">

                        <div>

                            <span class="loan-type">

                                <?php
                                echo htmlspecialchars(
                                    ucwords(
                                        str_replace(
                                            "_",
                                            " ",
                                            $loan["loan_type"]
                                        )
                                    ),
                                    ENT_QUOTES,
                                    "UTF-8"
                                );
                                ?>

                            </span>

                        </div>


                        <span
                            class="loan-status <?php
                            echo $is_overdue
                                ? "overdue"
                                : "active";
                            ?>"
                        >

                            <span class="status-dot"></span>

                            <?php
                            echo $is_overdue
                                ? "Overdue"
                                : "Active";
                            ?>

                        </span>

                    </div>


                    <!-- REMAINING BALANCE -->

                    <div class="loan-balance">

                        <span>
                            Remaining Balance
                        </span>

                        <strong>

                            ₱<?php
                            echo number_format(
                                $remaining,
                                2
                            );
                            ?>

                        </strong>

                    </div>


                    <!-- PROGRESS -->

                    <div class="payment-progress">

                        <div class="progress-info">

                            <span>
                                Repayment Progress
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


                        <div class="progress-track">

                            <div
                                class="progress-fill"
                                style="width: <?php
                                echo $progress;
                                ?>%;"
                            ></div>

                        </div>


                        <div class="progress-amounts">

                            <span>

                                ₱<?php
                                echo number_format(
                                    $total_paid,
                                    2
                                );
                                ?>
                                paid

                            </span>

                            <span>

                                ₱<?php
                                echo number_format(
                                    $total_payable,
                                    2
                                ); ?>
                                total

                            </span>

                        </div>

                    </div>


                    <!-- LOAN DETAILS -->

                    <div class="loan-details">


                        <div class="loan-detail">

                            <span class="detail-label">
                                Loan Amount
                            </span>

                            <strong>

                                ₱<?php
                                echo number_format(
                                    (float) $loan["amount"],
                                    2
                                );
                                ?>

                            </strong>

                        </div>


                        <div class="loan-detail">

                            <span class="detail-label">
                                Interest
                            </span>

                            <strong>

                                <?php
                                echo number_format(
                                    (float) (
                                        $loan["interest_rate"]
                                        ?? 0
                                    ),
                                    2
                                );
                                ?>%

                            </strong>

                        </div>


                        <div class="loan-detail">

                            <span class="detail-label">
                                Installment
                            </span>

                            <strong>

                                ₱<?php
                                echo number_format(
                                    (float)
                                    $loan["installment_amount"],
                                    2
                                );
                                ?>

                            </strong>

                            <small>

                                <?php
                                echo htmlspecialchars(
                                    ucfirst(
                                        $loan["payment_frequency"]
                                    ),
                                    ENT_QUOTES,
                                    "UTF-8"
                                );
                                ?>

                            </small>

                        </div>


                        <div class="loan-detail">

                            <span class="detail-label">
                                Next Due
                            </span>


                            <?php if ($loan["next_due_date"]): ?>

                                <strong
                                    class="<?php
                                    echo $is_overdue
                                        ? "text-overdue"
                                        : "text-due";
                                    ?>"
                                >

                                    <?php
                                    echo date(
                                        "M j, Y",
                                        strtotime(
                                            $loan["next_due_date"]
                                        )
                                    );
                                    ?>

                                </strong>


                                <?php if ($is_overdue): ?>

                                    <small class="overdue-text">
                                        Payment overdue
                                    </small>

                                <?php endif; ?>


                            <?php else: ?>

                                <strong>
                                    Not set
                                </strong>

                            <?php endif; ?>

                        </div>

                    </div>


                    <!-- PAYMENT FOOTER -->

                    <div class="loan-payment-footer">

                        <span>

                            Total payable:

                            <strong>

                                ₱<?php
                                echo number_format(
                                    $total_payable,
                                    2
                                );
                                ?>

                            </strong>

                        </span>


                        <a
                            href="payments.php?loan_id=<?php
                            echo (int) $loan["id"];
                            ?>"
                            class="payment-action"
                        >

                            Make a Payment

                            <span>
                                →
                            </span>

                        </a>

                    </div>

                </article>

            <?php endforeach; ?>

        </section>

    <?php endif; ?>


    <!-- ==================== DASHBOARD CARDS ==================== -->

    <section class="dashboard-cards">


        <!-- ACCOUNT -->

        <div class="dashboard-card">

            <div class="card-icon">
                ◒
            </div>

            <h2>
                Account
            </h2>

            <p>

                <strong>Username:</strong>

                <?php
                echo htmlspecialchars(
                    $username,
                    ENT_QUOTES,
                    "UTF-8"
                );
                ?>

            </p>

            <p>

                <strong>Name:</strong>

                <?php
                echo htmlspecialchars(
                    trim(
                        "$first_name $last_name"
                    ),
                    ENT_QUOTES,
                    "UTF-8"
                );
                ?>

            </p>

            <p>

                <strong>Email:</strong>

                <?php
                echo htmlspecialchars(
                    $email,
                    ENT_QUOTES,
                    "UTF-8"
                );
                ?>

            </p>

            <a
                href="profile.php"
                class="card-link"
            >
                Edit Profile →
            </a>

        </div>


        <!-- LOAN APPLICATIONS -->

        <div class="dashboard-card">

            <div class="card-icon">
                ▤
            </div>

            <h2>
                Loan Applications
            </h2>


            <?php if (empty($recent_activity)): ?>

                <p class="card-empty">
                    You haven't submitted any
                    applications yet.
                </p>

            <?php else: ?>

                <ul class="activity-list">

                    <?php foreach (
                        $recent_activity
                        as $item
                    ): ?>

                        <li>

                            <span>

                                <?php
                                echo htmlspecialchars(
                                    $item["title"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                );
                                ?>

                            </span>


                            <span
                                class="status-badge status-<?php
                                echo htmlspecialchars(
                                    $item["status"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                );
                                ?>"
                            >

                                <?php
                                echo htmlspecialchars(
                                    ucfirst(
                                        $item["status"]
                                    ),
                                    ENT_QUOTES,
                                    "UTF-8"
                                );
                                ?>

                            </span>

                        </li>

                    <?php endforeach; ?>

                </ul>

            <?php endif; ?>


            <a
                href="applications.php"
                class="card-link"
            >
                View Applications →
            </a>

        </div>


        <!-- APPLY FOR LOAN -->

        <div class="dashboard-card dashboard-card-highlight">

            <div class="card-icon">
                ✦
            </div>

            <h2>
                Apply for a Loan
            </h2>

            <p>
                Ready to take the next step?
                Start a new loan application in minutes.
            </p>

            <a
                href="loan_application.php"
                class="card-link"
            >
                Apply Now →
            </a>

        </div>


    </section>

</main>

</body>
</html>