<?php
require_once "require_admin.php";

$type = $_GET["type"] ?? "loans";
$allowed_types = ["loans", "payments", "borrowers", "overdue"];
if (!in_array($type, $allowed_types, true)) {
    $type = "loans";
}

$loan_type_labels = [
    "home" => "Home Loan",
    "business" => "Business Loan",
    "personal" => "Personal Loan",
    "asset_backed" => "Asset-Backed Loan",
];

header("Content-Type: text/csv; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"mletchido_{$type}_report_" . date("Y-m-d") . ".csv\"");

$out = fopen("php://output", "w");

if ($type === "loans" || $type === "overdue") {
    $rows = $type === "overdue" ? get_overdue_loans() : get_active_loans();
    fputcsv($out, ["Loan ID", "Borrower", "Username", "Loan Type", "Amount", "Remaining Balance", "Next Due Date", "Interest Rate"]);
    foreach ($rows as $loan) {
        fputcsv($out, [
            $loan["id"],
            trim($loan["first_name"] . " " . $loan["last_name"]),
            $loan["username"],
            $loan_type_labels[$loan["loan_type"]] ?? $loan["loan_type"],
            number_format((float) $loan["amount"], 2, ".", ""),
            number_format($loan["remaining_balance"], 2, ".", ""),
            $loan["next_due_date"] ?? "",
            $loan["interest_rate"] ?? "",
        ]);
    }
} elseif ($type === "payments") {
    $rows = get_all_payments();
    fputcsv($out, ["Payment ID", "Loan ID", "Borrower", "Date", "Amount Paid", "Method", "Reference", "Status"]);
    foreach ($rows as $p) {
        fputcsv($out, [
            $p["id"],
            $p["loan_id"],
            trim($p["first_name"] . " " . $p["last_name"]),
            $p["payment_date"],
            number_format((float) $p["amount_paid"], 2, ".", ""),
            $p["payment_method"],
            $p["reference_number"] ?? "",
            $p["status"],
        ]);
    }
} elseif ($type === "borrowers") {
    $rows = search_users("");
    fputcsv($out, ["User ID", "Username", "Name", "Email", "Phone", "Role", "Status"]);
    foreach ($rows as $u) {
        fputcsv($out, [
            $u["id"],
            $u["username"],
            trim($u["first_name"] . " " . $u["last_name"]),
            $u["email"],
            $u["phone_number"],
            ((int) $u["is_admin"] === 1) ? "Admin" : "Borrower",
            ((int) $u["is_active"] === 1) ? "Active" : "Deactivated",
        ]);
    }
}

fclose($out);
exit;