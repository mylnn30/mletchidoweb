<?php
require_once "require_admin.php";
require_once "csrf.php";

$active_tab = "applications";
$errors = [];
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!csrf_verify($_POST["csrf_token"] ?? "")) {
        $errors["general"] = "Your session has expired. Please refresh and try again.";
    } else {
        $application_id = (int) ($_POST["application_id"] ?? 0);
        $new_status = $_POST["new_status"] ?? "";

        $result = update_loan_application_status($application_id, $new_status);

        if ($result === true) {
            $success = true;
        } else {
            $errors["general"] = $result;
        }
    }
}

$loan_type_labels = [
    "home" => "Home Loan",
    "business" => "Business Loan",
    "personal" => "Personal Loan",
    "asset_backed" => "Asset-Backed Loan",
];

$applications = get_all_loan_applications();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Applications | Mletchido Financial Group</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>

<?php include "admin_nav.php"; ?>

<main class="admin-page">

    <header class="admin-header">
        <div>
            <h1>Loan Applications</h1>
            <p>Every application submitted across all users.</p>
        </div>
    </header>

    <?php if (isset($errors["general"])): ?>
        <div class="form-error" role="alert" aria-live="polite">
            <p><?php echo htmlspecialchars($errors["general"], ENT_QUOTES, "UTF-8"); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="form-success">
            <p>Application status updated.</p>
        </div>
    <?php endif; ?>

    <div class="admin-table-wrap">
        <?php if (empty($applications)): ?>
            <div class="empty-state">No loan applications have been submitted yet.</div>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Applicant</th>
                        <th>Loan Type</th>
                        <th>Amount</th>
                        <th>Term</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars(trim($app["first_name"] . " " . $app["last_name"]), ENT_QUOTES, "UTF-8"); ?>
                                <div class="cell-muted">@<?php echo htmlspecialchars($app["username"], ENT_QUOTES, "UTF-8"); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($loan_type_labels[$app["loan_type"]] ?? $app["loan_type"], ENT_QUOTES, "UTF-8"); ?></td>
                            <td>₱<?php echo number_format((float) $app["amount"], 2); ?></td>
                            <td><?php echo (int) $app["term_months"]; ?> mo</td>
                            <td class="cell-muted"><?php echo date("M j, Y", strtotime($app["submitted_at"])); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo htmlspecialchars($app["status"], ENT_QUOTES, "UTF-8"); ?>">
                                    <?php echo htmlspecialchars(ucfirst($app["status"]), ENT_QUOTES, "UTF-8"); ?>
                                </span>
                            </td>
                            <td>
                                <div class="row-actions">
                                    <a href="admin_view_application.php?id=<?php echo (int) $app["id"]; ?>" class="admin-btn admin-btn-view">View</a>

                                    <?php if ($app["status"] !== "approved"): ?>
                                        <form action="admin_applications.php" method="POST" class="inline-form">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="application_id" value="<?php echo (int) $app["id"]; ?>">
                                            <input type="hidden" name="new_status" value="approved">
                                            <button type="submit" class="admin-btn admin-btn-approve">Approve</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($app["status"] !== "rejected"): ?>
                                        <form action="admin_applications.php" method="POST" class="inline-form">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="application_id" value="<?php echo (int) $app["id"]; ?>">
                                            <input type="hidden" name="new_status" value="rejected">
                                            <button type="submit" class="admin-btn admin-btn-reject">Reject</button>
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