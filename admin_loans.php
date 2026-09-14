<?php
require_once "require_admin.php";
require_once "csrf.php";

$active_tab = "loans";
$errors = [];
$success_message = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!csrf_verify($_POST["csrf_token"] ?? "")) {
        $errors["general"] = "Your session has expired. Please refresh and try again.";
    } else {
        $loan_id = (int) ($_POST["loan_id"] ?? 0);
        $action = $_POST["action"] ?? "";
        $loan = get_loan_application_by_id_admin($loan_id);
        if ($loan) {
            $loan["interest_rate"] = $loan["interest_rate"] !== null ? (float) $loan["interest_rate"] : 0.0;
            $loan["total_payable"] = calculate_loan_total_payable($loan["amount"], $loan["interest_rate"]);
            $loan["installment_amount"] = get_installment_amount($loan["amount"], $loan["interest_rate"], $loan["term_months"], $loan["payment_frequency"]);
            $loan["remaining_balance"] = get_loan_remaining_balance($loan_id);
        }

        if (!$loan) {
            $errors["general"] = "Loan not found.";
        } elseif ($action === "remind") {
            create_notification(
                $loan["user_id"],
                "This is a reminder that a payment on your " . $loan["loan_type"] . " loan is coming due.",
                "payment_reminder"
            );
            $success_message = "Reminder sent to the borrower.";
        } elseif ($action === "notify_overdue") {
            create_notification(
                $loan["user_id"],
                "Your payment on your " . $loan["loan_type"] . " loan is overdue. Please settle it as soon as possible.",
                "overdue"
            );
            $success_message = "Overdue notice sent to the borrower.";
        } elseif ($action === "apply_penalty") {
            $settings = get_system_settings();
            $late_fee_percent = isset($settings["late_fee_percent"])
                ? (float) $settings["late_fee_percent"]
                : 5.00;

            $penalty_amount = round(
                (float) $loan["installment_amount"] * $late_fee_percent / 100,
                2
            );

            if ($penalty_amount <= 0) {
                $errors["general"] = "The late fee could not be calculated.";
            } else {
                $result = apply_late_penalty($loan_id, $penalty_amount);
                $success_message = ($result === true)
                    ? "Late penalty of ₱" . number_format($penalty_amount, 2) . " applied."
                    : null;

                if ($result !== true) {
                    $errors["general"] = $result;
                }
            }
        }
    }
}

$loan_type_labels = [
    "home" => "Home Loan",
    "business" => "Business Loan",
    "personal" => "Personal Loan",
    "asset_backed" => "Asset-Backed Loan",
];

$filter = $_GET["filter"] ?? "all";

if ($filter === "overdue") {
    $loans = get_overdue_loans();
} elseif ($filter === "upcoming") {
    $loans = get_upcoming_payments(7);
} else {
    $loans = get_active_loans();
}

$today = date("Y-m-d");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loans | Mletchido Financial Group</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>

<?php include "admin_nav.php"; ?>

<main class="admin-page">

    <header class="admin-header">
        <div>
            <h1>Loans</h1>
            <p>Every approved loan currently active, with interest, term, and remaining balance.</p>
        </div>
    </header>

    <nav class="report-tabs">
        <a href="admin_loans.php" class="report-tab <?php echo $filter === "all" ? "is-active" : ""; ?>">All Active</a>
        <a href="admin_loans.php?filter=upcoming" class="report-tab <?php echo $filter === "upcoming" ? "is-active" : ""; ?>">Due in 7 Days</a>
        <a href="admin_loans.php?filter=overdue" class="report-tab <?php echo $filter === "overdue" ? "is-active" : ""; ?>">Overdue</a>
    </nav>

    <?php if (isset($errors["general"])): ?>
        <div class="form-error" role="alert" aria-live="polite">
            <p><?php echo htmlspecialchars($errors["general"], ENT_QUOTES, "UTF-8"); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="form-success">
            <p><?php echo htmlspecialchars($success_message, ENT_QUOTES, "UTF-8"); ?></p>
        </div>
    <?php endif; ?>


    <div class="admin-table-wrap">
        <?php if (empty($loans)): ?>
            <div class="empty-state">No loans match this filter.</div>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Borrower</th>
                        <th>Loan Type</th>
                        <th>Amount</th>
                        <th>Interest</th>
                        <th>Term</th>
                        <th>Remaining Balance</th>
                        <th>Installment</th>
                        <th>Next Due</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($loans as $loan): ?>
                        <?php
                            $is_overdue = $loan["next_due_date"] !== null
                                && $loan["next_due_date"] < $today
                                && $loan["remaining_balance"] > 0;
                        ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars(trim($loan["first_name"] . " " . $loan["last_name"]), ENT_QUOTES, "UTF-8"); ?>
                                <div class="cell-muted">@<?php echo htmlspecialchars($loan["username"], ENT_QUOTES, "UTF-8"); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($loan_type_labels[$loan["loan_type"]] ?? $loan["loan_type"], ENT_QUOTES, "UTF-8"); ?></td>
                            <td>₱<?php echo number_format((float) $loan["amount"], 2); ?></td>
                            <td class="cell-muted"><?php echo $loan["interest_rate"] !== null ? number_format((float) $loan["interest_rate"], 2) . "%" : "Not set"; ?></td>
                            <td><?php echo (int) $loan["term_months"]; ?> mo</td>
                            <td>₱<?php echo number_format($loan["remaining_balance"], 2); ?></td>
                            <td>₱<?php echo number_format($loan["installment_amount"], 2); ?> / <?php echo htmlspecialchars($loan["payment_frequency"], ENT_QUOTES, "UTF-8"); ?></td>
                            <td>
                                <?php if ($loan["next_due_date"]): ?>
                                    <span class="status-badge <?php echo $is_overdue ? "status-rejected" : "status-pending"; ?>">
                                        <?php echo date("M j, Y", strtotime($loan["next_due_date"])); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="cell-muted">Not set</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="row-actions">
                                    <a href="admin_view_application.php?id=<?php echo (int) $loan["id"]; ?>" class="admin-btn admin-btn-view">View</a>

                                    <?php if ($loan["remaining_balance"] > 0): ?>
                                        <form action="admin_loans.php?filter=<?php echo htmlspecialchars($filter, ENT_QUOTES, "UTF-8"); ?>" method="POST" class="inline-form">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="loan_id" value="<?php echo (int) $loan["id"]; ?>">
                                            <input type="hidden" name="action" value="<?php echo $is_overdue ? "notify_overdue" : "remind"; ?>">
                                            <button type="submit" class="admin-btn admin-btn-pending">
                                                <?php echo $is_overdue ? "Notify Overdue" : "Send Reminder"; ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($is_overdue): ?>
                                        <form action="admin_loans.php?filter=<?php echo htmlspecialchars($filter, ENT_QUOTES, "UTF-8"); ?>" method="POST" class="inline-form" onsubmit="return confirm('Apply the configured late fee to this overdue loan?');">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="loan_id" value="<?php echo (int) $loan["id"]; ?>">
                                            <input type="hidden" name="action" value="apply_penalty">
                                            <button type="submit" class="admin-btn admin-btn-reject">Apply Late Fee</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</main>



</body>
</html>