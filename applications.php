<?php
require_once "require_login.php";
require_once "loan_application_function.php";

$applications = get_user_loan_applications($_SESSION["user_id"]);

// Matches the labels used on the homepage / apply form, so a stored
// key like "asset_backed" displays as "Asset-Backed Loan" everywhere.
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
    <title>My Applications | Mletchido Financial Group</title>
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

        <header class="applications-header">
            <h1>My Applications</h1>
            <a href="loan_application.php" class="applications-apply-btn">Apply for a Loan</a>
        </header>

        <?php if (empty($applications)): ?>

            <div class="applications-empty">
                <p>You haven't submitted any loan applications yet.</p>
                <a href="loan_application.php" class="applications-apply-btn">Apply Now</a>
            </div>

        <?php else: ?>

            <div class="applications-table">
                <div class="applications-row applications-row-head">
                    <span>Loan Type</span>
                    <span>Amount</span>
                    <span>Term</span>
                    <span>Status</span>
                    <span>Submitted</span>
                    <span></span>
                </div>

                <?php foreach ($applications as $app): ?>
                    <div class="applications-row">
                        <span data-label="Loan Type">
                            <?php echo htmlspecialchars($loan_type_labels[$app["loan_type"]] ?? $app["loan_type"], ENT_QUOTES, "UTF-8"); ?>
                        </span>
                        <span data-label="Amount">
                            ₱<?php echo number_format((float) $app["amount"], 2); ?>
                        </span>
                        <span data-label="Term">
                            <?php echo (int) $app["term_months"]; ?> mo
                        </span>
                        <span data-label="Status">
                            <span class="status-badge status-<?php echo htmlspecialchars($app["status"], ENT_QUOTES, "UTF-8"); ?>">
                                <?php echo htmlspecialchars(ucfirst($app["status"]), ENT_QUOTES, "UTF-8"); ?>
                            </span>
                        </span>
                        <span data-label="Submitted">
                            <?php echo date("M j, Y", strtotime($app["submitted_at"])); ?>
                        </span>
                        <span>
                            <a href="view_application.php?id=<?php echo (int) $app["id"]; ?>" class="applications-view-link">View →</a>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>

    </section>
</main>

</body>
</html>