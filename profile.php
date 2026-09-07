<?php
require_once "require_login.php";
require_once "profile_function.php";

$user = get_user_by_id($_SESSION["user_id"]);

if (!$user) {
    // Session points to a user that no longer exists in the DB.
    header("Location: logout.php");
    exit;
}

$updated = isset($_GET["updated"]) && $_GET["updated"] === "1";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Mletchido Financial Group</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/profile.css">
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
        <a href="profile.php" class="is-active">Profile</a>
        <a href="logout.php">Sign Out</a>
    </nav>
</header>

<main class="profile-page">
    <section class="profile-container">

        <header class="profile-header">
            <h1>My Profile</h1>
            <a href="edit_profile.php" class="profile-edit-btn">Edit Profile</a>
        </header>

        <?php if ($updated): ?>
            <div class="profile-success">
                <p>Your profile was updated successfully.</p>
            </div>
        <?php endif; ?>

        <div class="profile-grid">

            <div class="profile-field">
                <span class="field-label">Username</span>
                <span class="field-value"><?php echo htmlspecialchars($user["username"], ENT_QUOTES, "UTF-8"); ?></span>
            </div>

            <div class="profile-field">
                <span class="field-label">Full Name</span>
                <span class="field-value">
                    <?php
                        echo htmlspecialchars(
                            trim($user["first_name"] . " " . $user["middle_name"] . " " . $user["last_name"]),
                            ENT_QUOTES,
                            "UTF-8"
                        );
                    ?>
                </span>
            </div>

            <div class="profile-field">
                <span class="field-label">Email</span>
                <span class="field-value"><?php echo htmlspecialchars($user["email"], ENT_QUOTES, "UTF-8"); ?></span>
            </div>

            <div class="profile-field">
                <span class="field-label">Phone Number</span>
                <span class="field-value"><?php echo htmlspecialchars($user["phone_number"], ENT_QUOTES, "UTF-8"); ?></span>
            </div>

            <div class="profile-field profile-field-full">
                <span class="field-label">Address</span>
                <span class="field-value"><?php echo htmlspecialchars($user["address"], ENT_QUOTES, "UTF-8"); ?></span>
            </div>

        </div>

    </section>
</main>

</body>
</html>