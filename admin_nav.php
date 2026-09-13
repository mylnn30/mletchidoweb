<?php
// Included by every admin_*.php page. Expects $active_tab to be set
// beforehand to one of: overview, applications, loans, payments,
// borrowers, reports, settings.
$active_tab = $active_tab ?? "";
?>
<header class="dashboard-topbar">
    <div class="topbar-brand">
        <img src="./assets/home_01.png" alt="Mletchido Financial Group logo" class="topbar-logo">
        Mletchido Financial Group
        <span class="admin-badge">Admin</span>
    </div>
    <nav class="topbar-nav">
        <a href="admin_dashboard.php">Admin</a>
        <a href="logout.php">Sign Out</a>
    </nav>
</header>

<nav class="admin-subnav">
    <a href="admin_dashboard.php" class="<?php echo $active_tab === "overview" ? "is-active" : ""; ?>">Overview</a>
    <a href="admin_applications.php" class="<?php echo $active_tab === "applications" ? "is-active" : ""; ?>">Applications</a>
    <a href="admin_loans.php" class="<?php echo $active_tab === "loans" ? "is-active" : ""; ?>">Loans</a>
    <a href="admin_payments.php" class="<?php echo $active_tab === "payments" ? "is-active" : ""; ?>">Payments</a>
    <a href="admin_users.php" class="<?php echo $active_tab === "borrowers" ? "is-active" : ""; ?>">Users</a>
    <a href="admin_reports.php" class="<?php echo $active_tab === "reports" ? "is-active" : ""; ?>">Reports</a>
    <a href="admin_settings.php" class="<?php echo $active_tab === "settings" ? "is-active" : ""; ?>">Settings</a>
</nav>