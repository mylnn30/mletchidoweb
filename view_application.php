<?php
require_once "require_login.php";
require_once "loan_application_function.php";
require_once "loan_application_validation.php";

$application_id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

// Scoped to the logged-in user's own id, so no one can view another
// user's application just by changing the ?id= in the URL.
$application = get_loan_application_by_id($application_id, $_SESSION["user_id"]);

if (!$application) {
    header("Location: applications.php");
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Details | Mletchido Financial Group</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/applications.css">
</head>
<body>

<header class="dashboard-topbar">
    <div class="topbar-brand">
        <img src="./assets/home_01.png" alt="Mletchido Financial Group logo" class="topbar-logo">
        Mletchido Financial Group
    </div>
    <nav class="topbar-nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="applications.php" class="is-active">My Applications</a>
        <a href="profile.php">Profile</a>
        <a href="logout.php">Sign Out</a>
    </nav>
</header>

<main class="applications-page">
    <section class="applications-container">

        <a href="applications.php" class="back-link">← Back to My Applications</a>

        <header class="applications-header">
            <h1><?php echo htmlspecialchars($loan_type_display, ENT_QUOTES, "UTF-8"); ?></h1>
            <span class="status-badge status-<?php echo htmlspecialchars($application["status"], ENT_QUOTES, "UTF-8"); ?>">
                <?php echo htmlspecialchars(ucfirst($application["status"]), ENT_QUOTES, "UTF-8"); ?>
            </span>
        </header>

        <div class="detail-grid">

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
                <span class="field-label">Occupation</span>
                <span class="field-value"><?php echo htmlspecialchars($application["occupation"], ENT_QUOTES, "UTF-8"); ?></span>
            </div>

            <div class="detail-field">
                <span class="field-label">Monthly Income</span>
                <span class="field-value">₱<?php echo number_format((float) $application["monthly_income"], 2); ?></span>
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

        </div>

    </section>
</main>

</body>
</html>