<?php
require_once "require_login.php";
require_once "loan_application_function.php";
require_once "loan_application_validation.php";

$first_name = $_SESSION["first_name"] ?? "";
$last_name  = $_SESSION["last_name"] ?? "";
$username   = $_SESSION["username"] ?? "";
$email      = $_SESSION["email"] ?? "";

$loan_type_labels = get_loan_types();
$applications = get_user_loan_applications($_SESSION["user_id"]);

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

$stats = [
    ["label" => "Active Applications", "value" => (string) $active_count],
    ["label" => "Approved Loans", "value" => (string) $approved_count],
    ["label" => "Total Borrowed", "value" => "₱" . number_format($total_borrowed, 2)],
];

// Show the 3 most recent applications on the dashboard card.
$recent_activity = array_map(function ($app) use ($loan_type_labels) {
    return [
        "title" => $loan_type_labels[$app["loan_type"]]["label"] ?? $app["loan_type"],
        "status" => $app["status"],
    ];
}, array_slice($applications, 0, 3));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Mletchido Financial Group</title>
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>

<header class="dashboard-topbar">
    <div class="topbar-brand">
        <img src="./assets/home_01.png" alt="Mletchido Financial Group logo" class="topbar-logo">
        Mletchido Financial Group
    </div>

    <nav class="topbar-nav">
        <a href="dashboard.php" class="is-active">Dashboard</a>
        <a href="applications.php">My Applications</a>
        <a href="profile.php">Profile</a>
        <a href="logout.php">Sign Out</a>
    </nav>
</header>

<main class="dashboard-page">

    <section class="dashboard-welcome">
        <div>
            <h1>
                Welcome back,
                <?php echo htmlspecialchars($first_name !== "" ? $first_name : $username, ENT_QUOTES, "UTF-8"); ?>
            </h1>
            <p>Here's what's happening with your account today.</p>
        </div>
        <a href="loan_application.php" class="welcome-cta">Apply for a Loan</a>
    </section>

    <section class="stat-row">
        <?php foreach ($stats as $stat): ?>
            <div class="stat-tile">
                <span class="stat-value"><?php echo htmlspecialchars($stat["value"], ENT_QUOTES, "UTF-8"); ?></span>
                <span class="stat-label"><?php echo htmlspecialchars($stat["label"], ENT_QUOTES, "UTF-8"); ?></span>
            </div>
        <?php endforeach; ?>
    </section>

    <section class="dashboard-cards">

        <div class="dashboard-card">
            <div class="card-icon">◒</div>
            <h2>Account</h2>
            <p><strong>Username:</strong> <?php echo htmlspecialchars($username, ENT_QUOTES, "UTF-8"); ?></p>
            <p><strong>Name:</strong> <?php echo htmlspecialchars(trim("$first_name $last_name"), ENT_QUOTES, "UTF-8"); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($email, ENT_QUOTES, "UTF-8"); ?></p>
            <a href="profile.php" class="card-link">Edit Profile →</a>
        </div>

        <div class="dashboard-card">
            <div class="card-icon">▤</div>
            <h2>Loan Applications</h2>

            <?php if (empty($recent_activity)): ?>
                <p class="card-empty">You haven't submitted any applications yet.</p>
            <?php else: ?>
                <ul class="activity-list">
                    <?php foreach ($recent_activity as $item): ?>
                        <li>
                            <span><?php echo htmlspecialchars($item["title"], ENT_QUOTES, "UTF-8"); ?></span>
                            <span class="status-badge status-<?php echo htmlspecialchars($item["status"], ENT_QUOTES, "UTF-8"); ?>">
                                <?php echo htmlspecialchars(ucfirst($item["status"]), ENT_QUOTES, "UTF-8"); ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <a href="applications.php" class="card-link">View Applications →</a>
        </div>

        <div class="dashboard-card dashboard-card-highlight">
            <div class="card-icon">✦</div>
            <h2>Apply for a Loan</h2>
            <p>Ready to take the next step? Start a new loan application in minutes.</p>
            <a href="loan_application.php" class="card-link">Apply Now →</a>
        </div>

    </section>

</main>

</body>
</html>