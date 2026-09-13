<?php
require_once "require_admin.php";

$active_tab = "overview";
$stats = get_admin_stats();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Mletchido Financial Group</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>

<?php include "admin_nav.php"; ?>

<main class="admin-page">

    <header class="admin-header">
        <div>
            <h1>Admin Dashboard</h1>
            <p>Overview of borrowers, loans, and payments across the whole system.</p>
        </div>
    </header>

    <section class="stat-row">
        <div class="stat-tile">
            <span class="stat-value"><?php echo (int) $stats["total_borrowers"]; ?></span>
            <span class="stat-label">Total Borrowers</span>
        </div>
        <div class="stat-tile">
            <span class="stat-value"><?php echo (int) $stats["active_loans"]; ?></span>
            <span class="stat-label">Active Loans</span>
        </div>
        <div class="stat-tile">
            <span class="stat-value"><?php echo (int) $stats["pending_applications"]; ?></span>
            <span class="stat-label">Pending Applications</span>
        </div>
        <div class="stat-tile">
            <span class="stat-value"><?php echo (int) $stats["approved_applications"]; ?></span>
            <span class="stat-label">Approved</span>
        </div>
        <div class="stat-tile">
            <span class="stat-value"><?php echo (int) $stats["rejected_applications"]; ?></span>
            <span class="stat-label">Rejected</span>
        </div>
        <div class="stat-tile">
            <span class="stat-value"><?php echo (int) $stats["overdue_loans"]; ?></span>
            <span class="stat-label">Overdue Loans</span>
        </div>
        <div class="stat-tile">
            <span class="stat-value">₱<?php echo number_format($stats["total_payments_collected"], 2); ?></span>
            <span class="stat-label">Total Payments Collected</span>
        </div>
    </section>

</main>

</body>
</html>