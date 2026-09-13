<?php
require_once "require_admin.php";
require_once "csrf.php";
require_once "loan_application_validation.php";

$active_tab = "applications";
$application_id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;
$errors = [];
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!csrf_verify($_POST["csrf_token"] ?? "")) {
        $errors["general"] = "Your session has expired. Please refresh and try again.";
    } elseif (isset($_POST["update_terms"])) {
        $interest_rate = (float) ($_POST["interest_rate"] ?? 0);
        $next_due_date = $_POST["next_due_date"] ?? "";

        $result = set_loan_terms($application_id, $interest_rate, $next_due_date !== "" ? $next_due_date : null);

        if ($result === true) {
            $success = true;
        } else {
            $errors["general"] = $result;
        }
    } else {
        $new_status = $_POST["new_status"] ?? "";
        $result = update_loan_application_status($application_id, $new_status);

        if ($result === true) {
            $success = true;
        } else {
            $errors["general"] = $result;
        }
    }
}

$application = get_loan_application_by_id_admin($application_id);

if (!$application) {
    header("Location: admin_applications.php");
    exit;
}

$loan_type_labels = [
    "home" => "Home Loan",
    "business" => "Business Loan",
    "personal" => "Personal Loan",
    "asset_backed" => "Asset-Backed Loan",
];

$loan_type_display = $loan_type_labels[$application["loan_type"]] ?? $application["loan_type"];

$id_type_labels = get_id_types();
$employment_status_labels = get_employment_statuses();
$employment_length_labels = get_employment_lengths();
$business_type_labels = get_business_types();
$payment_frequency_labels = get_payment_frequencies();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application #<?php echo (int) $application["id"]; ?> | Mletchido Financial Group</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>

<?php include "admin_nav.php"; ?>

<main class="admin-page">

    <a href="admin_applications.php" class="back-link">← Back to All Applications</a>

    <header class="admin-header">
        <div>
            <h1><?php echo htmlspecialchars($loan_type_display, ENT_QUOTES, "UTF-8"); ?></h1>
            <p>
                Submitted by
                <?php echo htmlspecialchars(trim($application["first_name"] . " " . $application["last_name"]), ENT_QUOTES, "UTF-8"); ?>
                (@<?php echo htmlspecialchars($application["username"], ENT_QUOTES, "UTF-8"); ?>)
            </p>
        </div>
        <span class="status-badge status-<?php echo htmlspecialchars($application["status"], ENT_QUOTES, "UTF-8"); ?>">
            <?php echo htmlspecialchars(ucfirst($application["status"]), ENT_QUOTES, "UTF-8"); ?>
        </span>
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

    <div class="detail-grid">

        <div class="detail-field">
            <span class="field-label">Applicant Email</span>
            <span class="field-value"><?php echo htmlspecialchars($application["email"], ENT_QUOTES, "UTF-8"); ?></span>
        </div>

        <div class="detail-field">
            <span class="field-label">Applicant Phone</span>
            <span class="field-value"><?php echo htmlspecialchars($application["phone_number"], ENT_QUOTES, "UTF-8"); ?></span>
        </div>

        <div class="detail-field detail-field-full">
            <span class="field-label">Applicant Address</span>
            <span class="field-value"><?php echo htmlspecialchars($application["address"], ENT_QUOTES, "UTF-8"); ?></span>
        </div>

        <div class="detail-field">
            <span class="field-label">Loan Amount</span>
            <span class="field-value">₱<?php echo number_format((float) $application["amount"], 2); ?></span>
        </div>

        <div class="detail-field">
            <span class="field-label">Repayment Term</span>
            <span class="field-value"><?php echo (int) $application["term_months"]; ?> Months</span>
        </div>

        <div class="detail-field">
            <span class="field-label">ID Type</span>
            <span class="field-value"><?php echo htmlspecialchars($id_type_labels[$application["id_type"]] ?? $application["id_type"], ENT_QUOTES, "UTF-8"); ?></span>
        </div>

        <div class="detail-field">
            <span class="field-label">Employment Status</span>
            <span class="field-value"><?php echo htmlspecialchars($employment_status_labels[$application["employment_status"]] ?? $application["employment_status"], ENT_QUOTES, "UTF-8"); ?></span>
        </div>

        <div class="detail-field">
            <span class="field-label">Occupation / Job Title</span>
            <span class="field-value"><?php echo htmlspecialchars($application["occupation"], ENT_QUOTES, "UTF-8"); ?></span>
        </div>

        <?php if ($application["employment_status"] === "employed"): ?>

            <div class="detail-field">
                <span class="field-label">Employer / Company Name</span>
                <span class="field-value"><?php echo htmlspecialchars($application["employer_name"] ?? "", ENT_QUOTES, "UTF-8"); ?></span>
            </div>

            <div class="detail-field">
                <span class="field-label">Length of Employment</span>
                <span class="field-value"><?php echo htmlspecialchars($employment_length_labels[$application["length_of_employment"]] ?? ($application["length_of_employment"] ?? ""), ENT_QUOTES, "UTF-8"); ?></span>
            </div>

            <div class="detail-field">
                <span class="field-label">Employer Contact Number</span>
                <span class="field-value"><?php echo htmlspecialchars($application["employer_contact_number"] ?? "", ENT_QUOTES, "UTF-8"); ?></span>
            </div>

        <?php elseif ($application["employment_status"] === "self_employed"): ?>

            <div class="detail-field">
                <span class="field-label">Business Name</span>
                <span class="field-value"><?php echo htmlspecialchars($application["business_name"] ?? "", ENT_QUOTES, "UTF-8"); ?></span>
            </div>

            <div class="detail-field">
                <span class="field-label">Business Type</span>
                <span class="field-value"><?php echo htmlspecialchars($business_type_labels[$application["business_type"]] ?? ($application["business_type"] ?? ""), ENT_QUOTES, "UTF-8"); ?></span>
            </div>

        <?php endif; ?>

        <div class="detail-field">
            <span class="field-label"><?php echo $application["employment_status"] === "self_employed" ? "Estimated Monthly Income" : "Monthly Income"; ?></span>
            <span class="field-value">₱<?php echo number_format((float) $application["monthly_income"], 2); ?></span>
        </div>

        <div class="detail-field">
            <span class="field-label">Preferred Payment Frequency</span>
            <span class="field-value"><?php echo htmlspecialchars($payment_frequency_labels[$application["payment_frequency"]] ?? $application["payment_frequency"], ENT_QUOTES, "UTF-8"); ?></span>
        </div>

        <div class="detail-field">
            <span class="field-label">Date Submitted</span>
            <span class="field-value"><?php echo date("F j, Y g:i A", strtotime($application["submitted_at"])); ?></span>
        </div>

        <div class="detail-field detail-field-full">
            <span class="field-label">Purpose</span>
            <span class="field-value"><?php echo nl2br(htmlspecialchars($application["purpose"], ENT_QUOTES, "UTF-8")); ?></span>
        </div>

        <div class="detail-field">
            <span class="field-label">Valid ID</span>
            <a href="<?php echo htmlspecialchars($application["valid_id_path"], ENT_QUOTES, "UTF-8"); ?>" target="_blank" rel="noopener">
                <img src="<?php echo htmlspecialchars($application["valid_id_path"], ENT_QUOTES, "UTF-8"); ?>" alt="Uploaded Valid ID" class="document-thumb">
            </a>
        </div>

        <div class="detail-field">
            <span class="field-label">Proof of Income</span>
            <a href="<?php echo htmlspecialchars($application["proof_of_income_path"], ENT_QUOTES, "UTF-8"); ?>" target="_blank" rel="noopener">
                <img src="<?php echo htmlspecialchars($application["proof_of_income_path"], ENT_QUOTES, "UTF-8"); ?>" alt="Uploaded Proof of Income" class="document-thumb">
            </a>
        </div>

        <div class="detail-field">
            <span class="field-label">Proof of Address</span>
            <a href="<?php echo htmlspecialchars($application["proof_of_address_path"], ENT_QUOTES, "UTF-8"); ?>" target="_blank" rel="noopener">
                <img src="<?php echo htmlspecialchars($application["proof_of_address_path"], ENT_QUOTES, "UTF-8"); ?>" alt="Uploaded Proof of Address" class="document-thumb">
            </a>
        </div>

        <?php if (!empty($application["employment_certificate_path"])): ?>
            <div class="detail-field">
                <span class="field-label">Employment Certificate</span>
                <a href="<?php echo htmlspecialchars($application["employment_certificate_path"], ENT_QUOTES, "UTF-8"); ?>" target="_blank" rel="noopener">
                    <img src="<?php echo htmlspecialchars($application["employment_certificate_path"], ENT_QUOTES, "UTF-8"); ?>" alt="Uploaded Employment Certificate" class="document-thumb">
                </a>
            </div>
        <?php endif; ?>

    </div>

    <?php if ($application["status"] === "approved"): ?>
        <div class="status-update-box" style="margin-bottom: 24px;">
            <h2>Loan Terms</h2>
            <form action="admin_view_application.php?id=<?php echo (int) $application["id"]; ?>" method="POST" class="admin-form" style="padding: 0; border: none; background: none;">
                <?php csrf_field(); ?>
                <input type="hidden" name="update_terms" value="1">
                <div class="form-row">
                    <div class="form-group">
                        <label for="interest_rate">Interest Rate (% per annum)</label>
                        <input
                            type="number"
                            id="interest_rate"
                            name="interest_rate"
                            step="0.01"
                            min="0"
                            value="<?php echo htmlspecialchars($application["interest_rate"] ?? "", ENT_QUOTES, "UTF-8"); ?>"
                        >
                    </div>
                    <div class="form-group">
                        <label for="next_due_date">Next Due Date</label>
                        <input
                            type="date"
                            id="next_due_date"
                            name="next_due_date"
                            value="<?php echo htmlspecialchars($application["next_due_date"] ?? "", ENT_QUOTES, "UTF-8"); ?>"
                        >
                    </div>
                </div>
                <button type="submit" class="admin-btn admin-btn-approve" style="align-self:flex-start;">Save Loan Terms</button>
            </form>
        </div>

        <div class="status-update-box" style="margin-bottom: 24px;">
            <h2>Payments</h2>
            <p class="cell-muted" style="margin: 0 0 14px;">
                Remaining balance: ₱<?php
                    $paid_total = array_sum(array_map(function ($p) {
                        return $p["status"] === "confirmed" ? (float) $p["amount_paid"] : 0;
                    }, get_payments_for_loan($application["id"])));
                    echo number_format((float) $application["amount"] - $paid_total, 2);
                ?>
            </p>
            <a href="admin_payments.php?loan_id=<?php echo (int) $application["id"]; ?>" class="admin-btn admin-btn-view">Record / View Payments</a>
        </div>
    <?php endif; ?>

    <div class="status-update-box">
        <h2>Update Status</h2>
        <div class="row-actions">
            <?php if ($application["status"] !== "pending"): ?>
                <form action="admin_view_application.php?id=<?php echo (int) $application["id"]; ?>" method="POST" class="inline-form">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="new_status" value="pending">
                    <button type="submit" class="admin-btn admin-btn-pending">Mark Pending</button>
                </form>
            <?php endif; ?>

            <?php if ($application["status"] !== "approved"): ?>
                <form action="admin_view_application.php?id=<?php echo (int) $application["id"]; ?>" method="POST" class="inline-form">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="new_status" value="approved">
                    <button type="submit" class="admin-btn admin-btn-approve">Approve</button>
                </form>
            <?php endif; ?>

            <?php if ($application["status"] !== "rejected"): ?>
                <form action="admin_view_application.php?id=<?php echo (int) $application["id"]; ?>" method="POST" class="inline-form">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="new_status" value="rejected">
                    <button type="submit" class="admin-btn admin-btn-reject">Reject</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

</main>

</body>
</html>