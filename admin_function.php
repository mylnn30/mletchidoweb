<?php

require_once "database/config.php";

/**
 * Admin accounts are admin-only -- they never see the customer side
 * of the site (dashboard, applications, profile, apply-for-loan).
 * Call this at the top of every customer-facing page, right after
 * require_login.php, to send admins straight to the admin panel if
 * they try to reach a customer page directly by URL.
 */
function block_admin_from_customer_area() {
    if (user_is_admin($_SESSION["user_id"])) {
        header("Location: admin_dashboard.php");
        exit;
    }
}


/**
 * True if the given user_id currently has the admin flag set.
 * Always checked live against the DB -- never trust a cached/session
 * copy of this for an access-control decision.
 */
function user_is_admin($user_id) {
    global $conn;

    $sql = "SELECT is_admin FROM `user` WHERE id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    return $row !== null && (int) $row["is_admin"] === 1;
}


/**
 * High-level counts for the admin dashboard's stat tiles.
 */
function get_admin_stats() {
    global $conn;

    $stats = [
        "total_users" => 0,
        "total_borrowers" => 0,
        "total_applications" => 0,
        "pending_applications" => 0,
        "approved_applications" => 0,
        "rejected_applications" => 0,
        "active_loans" => 0,
        "overdue_loans" => 0,
        "total_payments_collected" => 0.0,
    ];

    $user_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM `user`");
    if ($user_result) {
        $stats["total_users"] = (int) mysqli_fetch_assoc($user_result)["total"];
    }

    $borrower_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM `user` WHERE is_admin = 0");
    if ($borrower_result) {
        $stats["total_borrowers"] = (int) mysqli_fetch_assoc($borrower_result)["total"];
    }

    $status_result = mysqli_query(
        $conn,
        "SELECT status, COUNT(*) AS total FROM `loan_applications` GROUP BY status"
    );
    if ($status_result) {
        while ($row = mysqli_fetch_assoc($status_result)) {
            $stats["total_applications"] += (int) $row["total"];
            if (isset($stats[$row["status"] . "_applications"])) {
                $stats[$row["status"] . "_applications"] = (int) $row["total"];
            }
        }
    }

    $stats["active_loans"] = $stats["approved_applications"];
    $stats["overdue_loans"] = count(get_overdue_loans());

    $payments_result = mysqli_query(
        $conn,
        "SELECT COALESCE(SUM(amount_paid), 0) AS total FROM `payments` WHERE status = 'confirmed'"
    );
    if ($payments_result) {
        $stats["total_payments_collected"] = (float) mysqli_fetch_assoc($payments_result)["total"];
    }

    return $stats;
}


/**
 * Every registered user, most recently registered first (by id).
 */
function get_all_users() {
    global $conn;

    $sql = "SELECT id, username, first_name, middle_name, last_name,
                   email, phone_number, is_admin
            FROM `user`
            ORDER BY id DESC";

    $result = mysqli_query($conn, $sql);

    return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
}


/**
 * Flip a user's admin flag on/off. Refuses to let an admin remove
 * their own access by mistake -- that would otherwise lock every
 * admin out with no way back in short of editing the database
 * directly.
 */
function set_user_admin_status($target_user_id, $acting_user_id, $is_admin) {
    global $conn;

    if ($target_user_id === $acting_user_id && !$is_admin) {
        return "You can't remove your own admin access.";
    }

    $sql = "UPDATE `user` SET is_admin = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return "Something went wrong. Please try again.";
    }

    $is_admin_int = $is_admin ? 1 : 0;
    mysqli_stmt_bind_param($stmt, "ii", $is_admin_int, $target_user_id);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return true;
    }

    mysqli_stmt_close($stmt);

    return "Could not update this user. Please try again.";
}


/**
 * Every loan application across every user, most recent first, with
 * the applicant's name/username attached so the admin table doesn't
 * need a separate lookup per row.
 */
function get_all_loan_applications() {
    global $conn;

    $sql = "SELECT
                la.id, la.user_id, la.loan_type, la.amount, la.term_months,
                la.status, la.submitted_at,
                u.username, u.first_name, u.last_name
            FROM `loan_applications` la
            INNER JOIN `user` u ON u.id = la.user_id
            ORDER BY la.submitted_at DESC";

    $result = mysqli_query($conn, $sql);

    return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
}


/**
 * A single loan application by id, with no user_id scoping -- unlike
 * get_loan_application_by_id() in loan_application_function.php, an
 * admin is allowed to open any applicant's record. The applicant's
 * profile fields are joined in for display.
 */
function get_loan_application_by_id_admin($application_id) {
    global $conn;

    $sql = "SELECT
                la.*,
                u.username, u.first_name, u.middle_name, u.last_name,
                u.email, u.phone_number, u.address
            FROM `loan_applications` la
            INNER JOIN `user` u ON u.id = la.user_id
            WHERE la.id = ?
            LIMIT 1";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, "i", $application_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $application = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    return $application ?: null;
}


/**
 * Approves or rejects an application. $new_status must be one of the
 * three statuses the rest of the app already renders badges for.
 */
function update_loan_application_status($application_id, $new_status) {
    global $conn;

    $allowed_statuses = ["pending", "approved", "rejected"];

    if (!in_array($new_status, $allowed_statuses, true)) {
        return "Invalid status.";
    }

    $sql = "UPDATE `loan_applications` SET status = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return "Something went wrong. Please try again.";
    }

    mysqli_stmt_bind_param($stmt, "si", $new_status, $application_id);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);

        // Let the borrower know their status changed. Failure to
        // notify isn't treated as a failure to update the status --
        // the status change itself already succeeded.
        $application = get_loan_application_by_id_admin($application_id);
        if ($application) {
            if ($new_status === "approved") {
                create_notification(
                    $application["user_id"],
                    "Your " . $application["loan_type"] . " loan application has been approved.",
                    "approval"
                );
            } elseif ($new_status === "rejected") {
                create_notification(
                    $application["user_id"],
                    "Your " . $application["loan_type"] . " loan application was not approved.",
                    "rejection"
                );
            }
        }

        return true;
    }

    mysqli_stmt_close($stmt);

    return "Could not update this application. Please try again.";
}


/* =========================================================================
   BORROWER MANAGEMENT
   Borrowers and admins both live in `user` -- these functions work on
   any account, filtered/searched by the caller.
   ========================================================================= */

/**
 * All users, optionally filtered by a search term matched against
 * username, name, or email. Powers both the Users list and the
 * "add admin" picker.
 */
function search_users($search = "") {
    global $conn;

    $search = trim($search);

    if ($search === "") {
        $sql = "SELECT id, username, first_name, middle_name, last_name,
                       email, phone_number, is_admin, is_active
                FROM `user`
                ORDER BY id DESC";
        $result = mysqli_query($conn, $sql);
        return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
    }

    $sql = "SELECT id, username, first_name, middle_name, last_name,
                   email, phone_number, is_admin, is_active
            FROM `user`
            WHERE username LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR email LIKE ?
            ORDER BY id DESC";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return [];
    }

    $like = "%" . $search . "%";
    mysqli_stmt_bind_param($stmt, "ssss", $like, $like, $like, $like);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $users = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);

    return $users;
}


/**
 * Turns a borrower/admin account on or off. A deactivated account
 * still exists (and its loan history is preserved) but can no longer
 * log in -- enforced in login_function.php.
 */
function set_user_active_status($target_user_id, $acting_user_id, $is_active) {
    global $conn;

    if ($target_user_id === $acting_user_id && !$is_active) {
        return "You can't deactivate your own account.";
    }

    $sql = "UPDATE `user` SET is_active = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return "Something went wrong. Please try again.";
    }

    $is_active_int = $is_active ? 1 : 0;
    mysqli_stmt_bind_param($stmt, "ii", $is_active_int, $target_user_id);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return true;
    }

    mysqli_stmt_close($stmt);
    return "Could not update this account. Please try again.";
}


/* =========================================================================
   LOAN MANAGEMENT
   ========================================================================= */

/**
 * Every approved application, i.e. every "active loan", with its
 * remaining balance computed from confirmed payments rather than
 * stored, so it's always accurate.
 */
function get_active_loans() {
    global $conn;

    $sql = "SELECT
                la.id, la.user_id, la.loan_type, la.amount, la.term_months,
                la.interest_rate, la.payment_frequency, la.next_due_date,
                la.status, la.submitted_at,
                u.username, u.first_name, u.last_name,
                COALESCE((
                    SELECT SUM(p.amount_paid) FROM payments p
                    WHERE p.loan_id = la.id AND p.status = 'confirmed'
                ), 0) AS total_paid
            FROM `loan_applications` la
            INNER JOIN `user` u ON u.id = la.user_id
            WHERE la.status = 'approved'
            ORDER BY la.next_due_date IS NULL, la.next_due_date ASC";

    $result = mysqli_query($conn, $sql);
    $loans = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];

    foreach ($loans as &$loan) {
        $loan["remaining_balance"] = (float) $loan["amount"] - (float) $loan["total_paid"];
    }

    return $loans;
}


/**
 * Loans whose next_due_date has already passed and still have a
 * remaining balance -- i.e. genuinely overdue, not just due today.
 */
function get_overdue_loans() {
    $loans = get_active_loans();

    return array_values(array_filter($loans, function ($loan) {
        return $loan["next_due_date"] !== null
            && $loan["next_due_date"] < date("Y-m-d")
            && $loan["remaining_balance"] > 0;
    }));
}


/**
 * Loans due within the next $days days (default a week) -- used for
 * the "upcoming payments" view and for deciding who to remind.
 */
function get_upcoming_payments($days = 7) {
    $loans = get_active_loans();
    $today = date("Y-m-d");
    $cutoff = date("Y-m-d", strtotime("+{$days} days"));

    return array_values(array_filter($loans, function ($loan) use ($today, $cutoff) {
        return $loan["next_due_date"] !== null
            && $loan["next_due_date"] >= $today
            && $loan["next_due_date"] <= $cutoff
            && $loan["remaining_balance"] > 0;
    }));
}


/**
 * Sets the interest rate and next due date on a loan -- done once,
 * typically right after approval, from the application detail page.
 */
function set_loan_terms($application_id, $interest_rate, $next_due_date) {
    global $conn;

    $sql = "UPDATE `loan_applications`
            SET interest_rate = ?, next_due_date = ?
            WHERE id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return "Something went wrong. Please try again.";
    }

    mysqli_stmt_bind_param($stmt, "dsi", $interest_rate, $next_due_date, $application_id);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return true;
    }

    mysqli_stmt_close($stmt);
    return "Could not update this loan's terms. Please try again.";
}


/* =========================================================================
   PAYMENT MANAGEMENT
   ========================================================================= */

function get_payment_methods() {
    return [
        "cash" => "Cash",
        "gcash" => "GCash",
        "bank_transfer" => "Bank Transfer",
    ];
}

function get_payment_statuses() {
    return [
        "pending" => "Pending",
        "confirmed" => "Confirmed",
        "failed" => "Failed",
    ];
}


/**
 * Records a payment against a loan and pushes the loan's next due
 * date forward by one billing cycle (based on its payment
 * frequency), so "next due date" always reflects what's actually
 * still owed going forward.
 */
function record_payment($loan_id, $payment_date, $amount_paid, $payment_method, $reference_number, $status) {
    global $conn;

    $sql = "INSERT INTO `payments`
                (loan_id, payment_date, amount_paid, payment_method, reference_number, status)
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return "Something went wrong. Please try again.";
    }

    mysqli_stmt_bind_param(
        $stmt,
        "isdsss",
        $loan_id,
        $payment_date,
        $amount_paid,
        $payment_method,
        $reference_number,
        $status
    );

    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return "Could not record this payment. Please try again.";
    }

    mysqli_stmt_close($stmt);

    if ($status === "confirmed") {
        advance_next_due_date($loan_id);
    }

    return true;
}


/**
 * Moves a loan's next_due_date forward by one billing cycle
 * (weekly/biweekly/monthly, matching the applicant's chosen payment
 * frequency) after a confirmed payment is recorded.
 */
function advance_next_due_date($loan_id) {
    global $conn;

    $sql = "SELECT payment_frequency, next_due_date FROM `loan_applications` WHERE id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return;
    }
    mysqli_stmt_bind_param($stmt, "i", $loan_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $loan = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$loan || $loan["next_due_date"] === null) {
        return;
    }

    $interval_map = [
        "weekly" => "+7 days",
        "biweekly" => "+14 days",
        "monthly" => "+1 month",
    ];
    $interval = $interval_map[$loan["payment_frequency"]] ?? "+1 month";
    $new_due_date = date("Y-m-d", strtotime($loan["next_due_date"] . " " . $interval));

    $update_sql = "UPDATE `loan_applications` SET next_due_date = ? WHERE id = ?";
    $update_stmt = mysqli_prepare($conn, $update_sql);
    if ($update_stmt) {
        mysqli_stmt_bind_param($update_stmt, "si", $new_due_date, $loan_id);
        mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);
    }
}


/**
 * Records a late-payment penalty as a negative payment -- this
 * increases the remaining balance (amount - SUM(payments)) without
 * needing a separate "penalty" column anywhere.
 */
function apply_late_penalty($loan_id, $penalty_amount) {
    return record_payment(
        $loan_id,
        date("Y-m-d"),
        -abs($penalty_amount),
        "cash",
        "Late payment penalty",
        "confirmed"
    );
}


function update_payment_status($payment_id, $new_status) {
    global $conn;

    $allowed = ["pending", "confirmed", "failed"];
    if (!in_array($new_status, $allowed, true)) {
        return "Invalid payment status.";
    }

    $sql = "UPDATE `payments` SET status = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return "Something went wrong. Please try again.";
    }

    mysqli_stmt_bind_param($stmt, "si", $new_status, $payment_id);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return true;
    }

    mysqli_stmt_close($stmt);
    return "Could not update this payment. Please try again.";
}


/**
 * Every payment across every loan, most recent first, joined with
 * the loan and borrower so the admin table needs no extra lookups.
 */
function get_all_payments() {
    global $conn;

    $sql = "SELECT
                p.id, p.loan_id, p.payment_date, p.amount_paid, p.payment_method,
                p.reference_number, p.status, p.recorded_at,
                la.loan_type, u.username, u.first_name, u.last_name
            FROM `payments` p
            INNER JOIN `loan_applications` la ON la.id = p.loan_id
            INNER JOIN `user` u ON u.id = la.user_id
            ORDER BY p.payment_date DESC, p.id DESC";

    $result = mysqli_query($conn, $sql);
    return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
}


function get_payments_for_loan($loan_id) {
    global $conn;

    $sql = "SELECT id, payment_date, amount_paid, payment_method, reference_number, status, recorded_at
            FROM `payments`
            WHERE loan_id = ?
            ORDER BY payment_date DESC, id DESC";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return [];
    }

    mysqli_stmt_bind_param($stmt, "i", $loan_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $payments = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);

    return $payments;
}


/* =========================================================================
   REPORTS
   ========================================================================= */

function get_monthly_summary($year, $month) {
    global $conn;

    $summary = ["disbursed" => 0.0, "collected" => 0.0, "new_applications" => 0, "approved" => 0];

    $sql = "SELECT
                COALESCE(SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END), 0) AS disbursed,
                COUNT(*) AS new_applications,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved
            FROM `loan_applications`
            WHERE YEAR(submitted_at) = ? AND MONTH(submitted_at) = ?";

    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $year, $month);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        if ($row) {
            $summary["disbursed"] = (float) $row["disbursed"];
            $summary["new_applications"] = (int) $row["new_applications"];
            $summary["approved"] = (int) $row["approved"];
        }
    }

    $sql2 = "SELECT COALESCE(SUM(amount_paid), 0) AS collected
             FROM `payments`
             WHERE status = 'confirmed' AND YEAR(payment_date) = ? AND MONTH(payment_date) = ?";
    $stmt2 = mysqli_prepare($conn, $sql2);
    if ($stmt2) {
        mysqli_stmt_bind_param($stmt2, "ii", $year, $month);
        mysqli_stmt_execute($stmt2);
        $result2 = mysqli_stmt_get_result($stmt2);
        $row2 = mysqli_fetch_assoc($result2);
        mysqli_stmt_close($stmt2);
        if ($row2) {
            $summary["collected"] = (float) $row2["collected"];
        }
    }

    return $summary;
}


/* =========================================================================
   NOTIFICATIONS
   ========================================================================= */

function create_notification($user_id, $message, $type) {
    global $conn;

    $sql = "INSERT INTO `notifications` (user_id, message, type) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "iss", $user_id, $message, $type);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $ok;
}

function get_notifications_for_user($user_id, $unread_only = false) {
    global $conn;

    $sql = "SELECT id, message, type, is_read, created_at FROM `notifications` WHERE user_id = ?";
    if ($unread_only) {
        $sql .= " AND is_read = 0";
    }
    $sql .= " ORDER BY created_at DESC LIMIT 20";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return [];
    }

    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $notifications = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);

    return $notifications;
}

function mark_notification_read($notification_id, $user_id) {
    global $conn;

    // Scoped to user_id too, so one borrower can't mark another's
    // notification as read by guessing an id.
    $sql = "UPDATE `notifications` SET is_read = 1 WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "ii", $notification_id, $user_id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $ok;
}


/* =========================================================================
   SYSTEM SETTINGS
   ========================================================================= */

function get_system_settings() {
    global $conn;

    $settings = [];
    $result = mysqli_query($conn, "SELECT setting_key, setting_value FROM `system_settings`");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $settings[$row["setting_key"]] = $row["setting_value"];
        }
    }

    return $settings;
}

function update_system_setting($key, $value) {
    global $conn;

    $sql = "INSERT INTO `system_settings` (setting_key, setting_value)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "ss", $key, $value);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $ok;
}


/**
 * Like get_user_by_id() in profile_function.php, but includes the
 * admin-only columns (is_admin, is_active) that the customer-facing
 * profile page has no business selecting.
 */
function get_user_full_by_id($user_id) {
    global $conn;

    $sql = "SELECT id, username, first_name, middle_name, last_name,
                   email, address, phone_number, is_admin, is_active
            FROM `user`
            WHERE id = ?
            LIMIT 1";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return $user ?: null;
}


/**
 * Looks a user up by username -- used right after admin-created
 * registration, since register_user() doesn't return the new id.
 */
function get_user_by_username($username) {
    global $conn;

    $sql = "SELECT id, username, is_admin FROM `user` WHERE username = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return $user ?: null;
}