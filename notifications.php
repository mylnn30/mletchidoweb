<?php
require_once "require_login.php";
require_once "csrf.php";
require_once "admin_function.php";

block_admin_from_customer_area();

$user_id = (int) $_SESSION["user_id"];
$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["mark_notification_read"])) {
    if (!csrf_verify($_POST["csrf_token"] ?? "")) {
        $errors["general"] = "Your session has expired. Please refresh and try again.";
    } else {
        mark_notification_read((int) ($_POST["notification_id"] ?? 0), $user_id);
        header("Location: notifications.php");
        exit;
    }
}

$notifications = get_notifications_for_user($user_id, false);
$unread_count = 0;
foreach ($notifications as $notification) {
    if ((int) $notification["is_read"] === 0) {
        $unread_count++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | Mletchido Financial Group</title>
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
<header class="dashboard-topbar">
    <div class="topbar-brand">
        <img src="./assets/home_01.png" alt="Mletchido Financial Group logo" class="topbar-logo">
        Mletchido Financial Group
    </div>
    <nav class="topbar-nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="applications.php">My Applications</a>
        <a href="payments.php">Payments</a>
        <a href="notifications.php" class="is-active">Notifications<?php echo $unread_count > 0 ? " (" . $unread_count . ")" : ""; ?></a>
        <a href="profile.php">Profile</a>
        <a href="logout.php">Sign Out</a>
    </nav>
</header>

<main class="dashboard-page">
    <section class="dashboard-welcome">
        <div>
            <h1>Notifications</h1>
            <p>Updates about your loan applications and payments.</p>
        </div>
    </section>

    <?php if (isset($errors["general"])): ?>
        <div class="dashboard-card" role="alert">
            <p><?php echo htmlspecialchars($errors["general"], ENT_QUOTES, "UTF-8"); ?></p>
        </div>
    <?php endif; ?>

    <section class="dashboard-card">
        <?php if (empty($notifications)): ?>
            <p class="card-empty">You have no notifications.</p>
        <?php else: ?>
            <ul class="activity-list">
                <?php foreach ($notifications as $notification): ?>
                    <li>
                        <span>
                            <?php echo htmlspecialchars($notification["message"], ENT_QUOTES, "UTF-8"); ?>
                            <small><?php echo date("M j, Y g:i A", strtotime($notification["created_at"])); ?></small>
                        </span>
                        <?php if ((int) $notification["is_read"] === 0): ?>
                            <form action="notifications.php" method="POST" class="inline-form">
                                <?php csrf_field(); ?>
                                <input type="hidden" name="mark_notification_read" value="1">
                                <input type="hidden" name="notification_id" value="<?php echo (int) $notification["id"]; ?>">
                                <button type="submit" class="card-link" style="background:none;border:none;cursor:pointer;">Mark as read</button>
                            </form>
                        <?php else: ?>
                            <span class="cell-muted">Read</span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
