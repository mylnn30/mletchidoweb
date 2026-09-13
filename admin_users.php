<?php
require_once "require_admin.php";
require_once "csrf.php";

$active_tab = "borrowers";
$errors = [];
$success_message = null;

if (isset($_GET["created"])) {
    $success_message = "Account created successfully.";
} elseif (isset($_GET["updated"])) {
    $success_message = "User updated successfully.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!csrf_verify($_POST["csrf_token"] ?? "")) {
        $errors["general"] = "Your session has expired. Please refresh and try again.";
    } else {
        $target_user_id = (int) ($_POST["user_id"] ?? 0);
        $action = $_POST["action"] ?? "";

        if ($action === "make_admin" || $action === "remove_admin") {
            $result = set_user_admin_status($target_user_id, (int) $_SESSION["user_id"], $action === "make_admin");
        } elseif ($action === "activate" || $action === "deactivate") {
            $result = set_user_active_status($target_user_id, (int) $_SESSION["user_id"], $action === "activate");
        } else {
            $result = "Unknown action.";
        }

        if ($result === true) {
            $success_message = "User updated successfully.";
        } else {
            $errors["general"] = $result;
        }
    }
}

$search = trim($_GET["q"] ?? "");
$users = search_users($search);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users | Mletchido Financial Group</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>

<?php include "admin_nav.php"; ?>

<main class="admin-page">

    <header class="admin-header">
        <div>
            <h1>Users</h1>
            <p>Every registered borrower and admin account.</p>
        </div>
        <a href="admin_user_form.php" class="admin-btn admin-btn-approve">+ Add User</a>
    </header>

    <?php if (isset($errors["general"])): ?>
        <div class="form-error" role="alert" aria-live="polite">
            <p><?php echo htmlspecialchars($errors["general"], ENT_QUOTES, "UTF-8"); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="form-success">
            <p><?php echo htmlspecialchars($success_message, ENT_QUOTES, "UTF-8"); ?></p>
        </div>
    <?php endif; ?>

    <form action="admin_users.php" method="GET" class="admin-search-bar">
        <input
            type="text"
            name="q"
            placeholder="Search by username, name, or email..."
            value="<?php echo htmlspecialchars($search, ENT_QUOTES, "UTF-8"); ?>"
        >
        <button type="submit" class="admin-btn admin-btn-view">Search</button>
    </form>

    <div class="admin-table-wrap">
        <?php if (empty($users)): ?>
            <div class="empty-state">No users found.</div>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($u["username"], ENT_QUOTES, "UTF-8"); ?></td>
                            <td><?php echo htmlspecialchars(trim($u["first_name"] . " " . $u["last_name"]), ENT_QUOTES, "UTF-8"); ?></td>
                            <td class="cell-muted"><?php echo htmlspecialchars($u["email"], ENT_QUOTES, "UTF-8"); ?></td>
                            <td class="cell-muted"><?php echo htmlspecialchars($u["phone_number"], ENT_QUOTES, "UTF-8"); ?></td>
                            <td>
                                <span class="role-badge <?php echo ((int) $u["is_admin"] === 1) ? "is-admin" : ""; ?>">
                                    <?php echo ((int) $u["is_admin"] === 1) ? "Admin" : "Borrower"; ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge <?php echo ((int) $u["is_active"] === 1) ? "status-approved" : "status-rejected"; ?>">
                                    <?php echo ((int) $u["is_active"] === 1) ? "Active" : "Deactivated"; ?>
                                </span>
                            </td>
                            <td>
                                <div class="row-actions">
                                    <a href="admin_user_form.php?id=<?php echo (int) $u["id"]; ?>" class="admin-btn admin-btn-view">Edit</a>

                                    <?php if ((int) $u["id"] === (int) $_SESSION["user_id"]): ?>
                                        <span class="cell-muted">You</span>
                                    <?php else: ?>
                                        <?php if ((int) $u["is_admin"] === 1): ?>
                                            <form action="admin_users.php" method="POST" class="inline-form">
                                                <?php csrf_field(); ?>
                                                <input type="hidden" name="user_id" value="<?php echo (int) $u["id"]; ?>">
                                                <input type="hidden" name="action" value="remove_admin">
                                                <button type="submit" class="admin-btn admin-btn-reject">Remove Admin</button>
                                            </form>
                                        <?php else: ?>
                                            <form action="admin_users.php" method="POST" class="inline-form">
                                                <?php csrf_field(); ?>
                                                <input type="hidden" name="user_id" value="<?php echo (int) $u["id"]; ?>">
                                                <input type="hidden" name="action" value="make_admin">
                                                <button type="submit" class="admin-btn admin-btn-approve">Make Admin</button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ((int) $u["is_active"] === 1): ?>
                                            <form action="admin_users.php" method="POST" class="inline-form">
                                                <?php csrf_field(); ?>
                                                <input type="hidden" name="user_id" value="<?php echo (int) $u["id"]; ?>">
                                                <input type="hidden" name="action" value="deactivate">
                                                <button type="submit" class="admin-btn admin-btn-reject">Deactivate</button>
                                            </form>
                                        <?php else: ?>
                                            <form action="admin_users.php" method="POST" class="inline-form">
                                                <?php csrf_field(); ?>
                                                <input type="hidden" name="user_id" value="<?php echo (int) $u["id"]; ?>">
                                                <input type="hidden" name="action" value="activate">
                                                <button type="submit" class="admin-btn admin-btn-approve">Activate</button>
                                            </form>
                                        <?php endif; ?>
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