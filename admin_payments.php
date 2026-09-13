<?php
require_once "require_admin.php";
require_once "csrf.php";

$active_tab = "payments";
$errors = [];
$success_message = null;

$preselected_loan_id = isset($_GET["loan_id"]) ? (int) $_GET["loan_id"] : 0;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!csrf_verify($_POST["csrf_token"] ?? "")) {
        $errors["general"] = "Your session has expired. Please refresh and try again.";
    } elseif (isset($_POST["update_payment_status"])) {
        $payment_id = (int) ($_POST["payment_id"] ?? 0);
        $new_status = $_POST["new_status"] ?? "";
        $result = update_payment_status($payment_id, $new_status);

        if ($result === true) {
            $success_message = "Payment status updated.";
        } else {
            $errors["general"] = $result;
        }
    } else {
        $loan_id = (int) ($_POST["loan_id"] ?? 0);
        $payment_date = $_POST["payment_date"] ?? "";
        $amount_paid = (float) ($_POST["amount_paid"] ?? 0);
        $payment_method = $_POST["payment_method"] ?? "";
        $reference_number = trim($_POST["reference_number"] ?? "");
        $status = $_POST["status"] ?? "confirmed";

        $payment_methods = get_payment_methods();
        $payment_statuses = get_payment_statuses();

        if ($loan_id <= 0) {
            $errors["loan_id"] = "Please select a loan.";
        }
        if ($payment_date === "") {
            $errors["payment_date"] = "Please enter a payment date.";
        }
        if ($amount_paid <= 0) {
            $errors["amount_paid"] = "Please enter a valid amount.";
        }
        if (!isset($payment_methods[$payment_method])) {
            $errors["payment_method"] = "Please select a valid payment method.";
        }
        if (!isset($payment_statuses[$status])) {
            $errors["status"] = "Please select a valid status.";
        }

        if (empty($errors)) {
            $result = record_payment($loan_id, $payment_date, $amount_paid, $payment_method, $reference_number ?: null, $status);

            if ($result === true) {
                header("Location: admin_payments.php?loan_id=" . $loan_id . "&recorded=1");
                exit;
            }

            $errors["general"] = $result;
        }

        $preselected_loan_id = $loan_id;
    }
}

if (isset($_GET["recorded"])) {
    $success_message = "Payment recorded.";
}

$active_loans = get_active_loans();
$payment_methods = get_payment_methods();
$payment_statuses = get_payment_statuses();

$payments = $preselected_loan_id > 0
    ? get_payments_for_loan($preselected_loan_id)
    : get_all_payments();

$loan_type_labels = [
    "home" => "Home Loan",
    "business" => "Business Loan",
    "personal" => "Personal Loan",
    "asset_backed" => "Asset-Backed Loan",
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments | Mletchido Financial Group</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>

<?php include "admin_nav.php"; ?>

<main class="admin-page">

    <header class="admin-header">
        <div>
            <h1>Payments</h1>
            <p>Record payments against a loan and review payment history.</p>
        </div>
    </header>

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

    <h2 class="section-heading">Record a Payment</h2>
    <form action="admin_payments.php" method="POST" class="admin-form" style="margin-bottom: 28px;">
        <?php csrf_field(); ?>

        <div class="form-row">
            <div class="form-group">
                <label for="loan_id">Loan<span class="required-mark">*</span></label>
                <select id="loan_id" name="loan_id" class="<?php echo isset($errors["loan_id"]) ? "input-error" : ""; ?>">
                    <option value="">Select a loan</option>
                    <?php foreach ($active_loans as $loan): ?>
                        <option value="<?php echo (int) $loan["id"]; ?>" <?php echo $preselected_loan_id === (int) $loan["id"] ? "selected" : ""; ?>>
                            #<?php echo (int) $loan["id"]; ?> —
                            <?php echo htmlspecialchars(trim($loan["first_name"] . " " . $loan["last_name"]), ENT_QUOTES, "UTF-8"); ?>
                            (<?php echo htmlspecialchars($loan_type_labels[$loan["loan_type"]] ?? $loan["loan_type"], ENT_QUOTES, "UTF-8"); ?>,
                            balance ₱<?php echo number_format($loan["remaining_balance"], 2); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors["loan_id"])): ?>
                    <p class="field-error"><?php echo htmlspecialchars($errors["loan_id"], ENT_QUOTES, "UTF-8"); ?></p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="payment_date">Payment Date<span class="required-mark">*</span></label>
                <input type="date" id="payment_date" name="payment_date" value="<?php echo date("Y-m-d"); ?>" class="<?php echo isset($errors["payment_date"]) ? "input-error" : ""; ?>">
                <?php if (isset($errors["payment_date"])): ?>
                    <p class="field-error"><?php echo htmlspecialchars($errors["payment_date"], ENT_QUOTES, "UTF-8"); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="amount_paid">Amount Paid (₱)<span class="required-mark">*</span></label>
                <input type="number" id="amount_paid" name="amount_paid" step="0.01" min="0.01" class="<?php echo isset($errors["amount_paid"]) ? "input-error" : ""; ?>">
                <?php if (isset($errors["amount_paid"])): ?>
                    <p class="field-error"><?php echo htmlspecialchars($errors["amount_paid"], ENT_QUOTES, "UTF-8"); ?></p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="payment_method">Payment Method<span class="required-mark">*</span></label>
                <select id="payment_method" name="payment_method" class="<?php echo isset($errors["payment_method"]) ? "input-error" : ""; ?>">
                    <option value="">Select a method</option>
                    <?php foreach ($payment_methods as $key => $label): ?>
                        <option value="<?php echo htmlspecialchars($key, ENT_QUOTES, "UTF-8"); ?>"><?php echo htmlspecialchars($label, ENT_QUOTES, "UTF-8"); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors["payment_method"])): ?>
                    <p class="field-error"><?php echo htmlspecialchars($errors["payment_method"], ENT_QUOTES, "UTF-8"); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="reference_number">Reference Number</label>
                <input type="text" id="reference_number" name="reference_number" placeholder="e.g. GCash transaction ID">
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <?php foreach ($payment_statuses as $key => $label): ?>
                        <option value="<?php echo htmlspecialchars($key, ENT_QUOTES, "UTF-8"); ?>" <?php echo $key === "confirmed" ? "selected" : ""; ?>>
                            <?php echo htmlspecialchars($label, ENT_QUOTES, "UTF-8"); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <button type="submit" class="admin-btn admin-btn-approve" style="align-self:flex-start;">Record Payment</button>
    </form>

    <h2 class="section-heading">
        <?php echo $preselected_loan_id > 0 ? "Payment History for Loan #" . $preselected_loan_id : "All Payments"; ?>
    </h2>
    <?php if ($preselected_loan_id > 0): ?>
        <p><a href="admin_payments.php" class="cell-muted">← View all payments</a></p>
    <?php endif; ?>

    <div class="admin-table-wrap">
        <?php if (empty($payments)): ?>
            <div class="empty-state">No payments recorded yet.</div>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <?php if ($preselected_loan_id === 0): ?>
                            <th>Borrower</th>
                            <th>Loan</th>
                        <?php endif; ?>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                        <tr>
                            <?php if ($preselected_loan_id === 0): ?>
                                <td>
                                    <?php echo htmlspecialchars(trim($p["first_name"] . " " . $p["last_name"]), ENT_QUOTES, "UTF-8"); ?>
                                    <div class="cell-muted">@<?php echo htmlspecialchars($p["username"], ENT_QUOTES, "UTF-8"); ?></div>
                                </td>
                                <td>
                                    <a href="admin_payments.php?loan_id=<?php echo (int) $p["loan_id"]; ?>">
                                        #<?php echo (int) $p["loan_id"]; ?> — <?php echo htmlspecialchars($loan_type_labels[$p["loan_type"]] ?? $p["loan_type"], ENT_QUOTES, "UTF-8"); ?>
                                    </a>
                                </td>
                            <?php endif; ?>
                            <td><?php echo date("M j, Y", strtotime($p["payment_date"])); ?></td>
                            <td>₱<?php echo number_format((float) $p["amount_paid"], 2); ?></td>
                            <td><?php echo htmlspecialchars($payment_methods[$p["payment_method"]] ?? $p["payment_method"], ENT_QUOTES, "UTF-8"); ?></td>
                            <td class="cell-muted"><?php echo htmlspecialchars($p["reference_number"] ?? "—", ENT_QUOTES, "UTF-8"); ?></td>
                            <td>
                                <span class="status-badge <?php
                                    echo $p["status"] === "confirmed" ? "status-approved"
                                        : ($p["status"] === "failed" ? "status-rejected" : "status-pending");
                                ?>">
                                    <?php echo htmlspecialchars(ucfirst($p["status"]), ENT_QUOTES, "UTF-8"); ?>
                                </span>
                            </td>
                            <td>
                                <div class="row-actions">
                                    <?php foreach ($payment_statuses as $key => $label): ?>
                                        <?php if ($key !== $p["status"]): ?>
                                            <form action="admin_payments.php<?php echo $preselected_loan_id > 0 ? "?loan_id=" . $preselected_loan_id : ""; ?>" method="POST" class="inline-form">
                                                <?php csrf_field(); ?>
                                                <input type="hidden" name="update_payment_status" value="1">
                                                <input type="hidden" name="payment_id" value="<?php echo (int) $p["id"]; ?>">
                                                <input type="hidden" name="new_status" value="<?php echo htmlspecialchars($key, ENT_QUOTES, "UTF-8"); ?>">
                                                <button type="submit" class="admin-btn admin-btn-view">Mark <?php echo htmlspecialchars($label, ENT_QUOTES, "UTF-8"); ?></button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
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