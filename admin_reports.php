<?php
require_once "require_admin.php";

$active_tab = "reports";
$type = $_GET["type"] ?? "loans";
$allowed_types = ["loans", "payments", "borrowers", "overdue", "summary"];
if (!in_array($type, $allowed_types, true)) {
    $type = "loans";
}

$loan_type_labels = [
    "home" => "Home Loan",
    "business" => "Business Loan",
    "personal" => "Personal Loan",
    "asset_backed" => "Asset-Backed Loan",
];

$month = (int) ($_GET["month"] ?? date("n"));
$year = (int) ($_GET["year"] ?? date("Y"));

switch ($type) {
    case "payments":
        $rows = get_all_payments();
        break;
    case "borrowers":
        $rows = search_users("");
        break;
    case "overdue":
        $rows = get_overdue_loans();
        break;
    case "summary":
        $summary = get_monthly_summary($year, $month);
        $rows = [];
        break;
    case "loans":
    default:
        $rows = get_active_loans();
        break;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports | Mletchido Financial Group</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>

<?php include "admin_nav.php"; ?>

<main class="admin-page">

    <header class="admin-header">
        <div>
            <h1>Reports</h1>
            <p>Pull a report by type, and export it as a CSV if you need to hand it in or archive it.</p>
        </div>
        <?php if ($type !== "summary"): ?>
            <a href="admin_report_export.php?type=<?php echo htmlspecialchars($type, ENT_QUOTES, "UTF-8"); ?>" class="admin-btn admin-btn-view">Export CSV</a>
        <?php endif; ?>
    </header>

    <nav class="report-tabs">
        <a href="admin_reports.php?type=loans" class="report-tab <?php echo $type === "loans" ? "is-active" : ""; ?>">Loan Report</a>
        <a href="admin_reports.php?type=payments" class="report-tab <?php echo $type === "payments" ? "is-active" : ""; ?>">Payment Report</a>
        <a href="admin_reports.php?type=borrowers" class="report-tab <?php echo $type === "borrowers" ? "is-active" : ""; ?>">Borrower Report</a>
        <a href="admin_reports.php?type=overdue" class="report-tab <?php echo $type === "overdue" ? "is-active" : ""; ?>">Overdue Report</a>
        <a href="admin_reports.php?type=summary" class="report-tab <?php echo $type === "summary" ? "is-active" : ""; ?>">Monthly Summary</a>
    </nav>

    <?php if ($type === "summary"): ?>

        <form action="admin_reports.php" method="GET" class="admin-search-bar">
            <input type="hidden" name="type" value="summary">
            <select name="month" class="admin-btn admin-btn-view" style="padding: 10px 16px;">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?php echo $m; ?>" <?php echo $m === $month ? "selected" : ""; ?>><?php echo date("F", mktime(0, 0, 0, $m, 1)); ?></option>
                <?php endfor; ?>
            </select>
            <select name="year" class="admin-btn admin-btn-view" style="padding: 10px 16px;">
                <?php for ($y = (int) date("Y"); $y >= (int) date("Y") - 4; $y--): ?>
                    <option value="<?php echo $y; ?>" <?php echo $y === $year ? "selected" : ""; ?>><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>
            <button type="submit" class="admin-btn admin-btn-approve">View</button>
        </form>

        <section class="stat-row">
            <div class="stat-tile">
                <span class="stat-value">₱<?php echo number_format($summary["disbursed"], 2); ?></span>
                <span class="stat-label">Disbursed (Approved Loans)</span>
            </div>
            <div class="stat-tile">
                <span class="stat-value">₱<?php echo number_format($summary["collected"], 2); ?></span>
                <span class="stat-label">Collected (Confirmed Payments)</span>
            </div>
            <div class="stat-tile">
                <span class="stat-value"><?php echo (int) $summary["new_applications"]; ?></span>
                <span class="stat-label">New Applications</span>
            </div>
            <div class="stat-tile">
                <span class="stat-value"><?php echo (int) $summary["approved"]; ?></span>
                <span class="stat-label">Approved</span>
            </div>
        </section>

    <?php elseif ($type === "loans" || $type === "overdue"): ?>

        <div class="admin-table-wrap">
            <?php if (empty($rows)): ?>
                <div class="empty-state">No loans to show.</div>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Borrower</th>
                            <th>Loan Type</th>
                            <th>Amount</th>
                            <th>Remaining Balance</th>
                            <th>Next Due</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $loan): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars(trim($loan["first_name"] . " " . $loan["last_name"]), ENT_QUOTES, "UTF-8"); ?>
                                    <div class="cell-muted">@<?php echo htmlspecialchars($loan["username"], ENT_QUOTES, "UTF-8"); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($loan_type_labels[$loan["loan_type"]] ?? $loan["loan_type"], ENT_QUOTES, "UTF-8"); ?></td>
                                <td>₱<?php echo number_format((float) $loan["amount"], 2); ?></td>
                                <td>₱<?php echo number_format($loan["remaining_balance"], 2); ?></td>
                                <td class="cell-muted"><?php echo $loan["next_due_date"] ? date("M j, Y", strtotime($loan["next_due_date"])) : "Not set"; ?></td>
                                <td><span class="status-badge status-approved">Active</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    <?php elseif ($type === "payments"): ?>

        <div class="admin-table-wrap">
            <?php if (empty($rows)): ?>
                <div class="empty-state">No payments recorded yet.</div>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Borrower</th>
                            <th>Loan</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $p): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(trim($p["first_name"] . " " . $p["last_name"]), ENT_QUOTES, "UTF-8"); ?></td>
                                <td>#<?php echo (int) $p["loan_id"]; ?> — <?php echo htmlspecialchars($loan_type_labels[$p["loan_type"]] ?? $p["loan_type"], ENT_QUOTES, "UTF-8"); ?></td>
                                <td><?php echo date("M j, Y", strtotime($p["payment_date"])); ?></td>
                                <td>₱<?php echo number_format((float) $p["amount_paid"], 2); ?></td>
                                <td class="cell-muted"><?php echo htmlspecialchars(ucfirst(str_replace("_", " ", $p["payment_method"])), ENT_QUOTES, "UTF-8"); ?></td>
                                <td>
                                    <span class="status-badge <?php
                                        echo $p["status"] === "confirmed" ? "status-approved"
                                            : ($p["status"] === "failed" ? "status-rejected" : "status-pending");
                                    ?>">
                                        <?php echo htmlspecialchars(ucfirst($p["status"]), ENT_QUOTES, "UTF-8"); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    <?php else /* borrowers */: ?>

        <div class="admin-table-wrap">
            <?php if (empty($rows)): ?>
                <div class="empty-state">No borrowers found.</div>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $u): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($u["username"], ENT_QUOTES, "UTF-8"); ?></td>
                                <td><?php echo htmlspecialchars(trim($u["first_name"] . " " . $u["last_name"]), ENT_QUOTES, "UTF-8"); ?></td>
                                <td class="cell-muted"><?php echo htmlspecialchars($u["email"], ENT_QUOTES, "UTF-8"); ?></td>
                                <td><span class="role-badge <?php echo ((int) $u["is_admin"] === 1) ? "is-admin" : ""; ?>"><?php echo ((int) $u["is_admin"] === 1) ? "Admin" : "Borrower"; ?></span></td>
                                <td><span class="status-badge <?php echo ((int) $u["is_active"] === 1) ? "status-approved" : "status-rejected"; ?>"><?php echo ((int) $u["is_active"] === 1) ? "Active" : "Deactivated"; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    <?php endif; ?>

</main>

</body>
</html>