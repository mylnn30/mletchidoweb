<?php
require_once "require_login.php";
require_once "loan_application_validation.php";
require_once "loan_application_function.php";

$loan_types = get_loan_types();
$errors = [];
$success = isset($_GET["success"]) && $_GET["success"] === "1";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $loan_type = $_POST["loan_type"] ?? "";
    $amount    = trim($_POST["amount"] ?? "");
    $term      = $_POST["term"] ?? "";
    $purpose   = trim($_POST["purpose"] ?? "");

    $errors = validate_loan_application($loan_type, $amount, $term, $purpose);

    if (empty($errors)) {
        $result = submit_loan_application(
            $_SESSION["user_id"],
            $loan_type,
            (float) $amount,
            (int) $term,
            $purpose
        );

        if ($result === true) {
            header("Location: loan-application.php?success=1");
            exit;
        }

        $errors["general"] = $result;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for a Loan | Mletchido Financial Group</title>
    <link rel="stylesheet" href="css/loan-application.css">
</head>
<body>

<header class="dashboard-topbar">
    <div class="topbar-brand">
        <img src="images/logo.png" alt="Mletchido Financial Group logo" class="topbar-logo">
        Mletchido Financial Group
    </div>
    <nav class="topbar-nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="applications.php">My Applications</a>
        <a href="profile.php">Profile</a>
        <a href="logout.php">Sign Out</a>
    </nav>
</header>

<main class="loan-page">
    <section class="loan-container">

        <?php if ($success): ?>

            <div class="loan-success">
                <div class="success-icon">✓</div>
                <h1>Application Submitted</h1>
                <p>Your loan application has been received and is now pending review.</p>
                <a href="applications.php" class="loan-button">View My Applications</a>
            </div>

        <?php else: ?>

            <header class="loan-header">
                <h1>Apply for a Loan</h1>
                <p>Choose a loan type and tell us a bit about what you need.</p>
            </header>

            <?php if (isset($errors["general"])): ?>
                <div class="form-error">
                    <p><?php echo htmlspecialchars($errors["general"], ENT_QUOTES, "UTF-8"); ?></p>
                </div>
            <?php endif; ?>

            <!-- LOAN TYPE CARDS (matches the 4 services on the homepage) -->
            <div class="loan-type-grid">
                <?php foreach ($loan_types as $key => $type): ?>
                    <label class="loan-type-card">
                        <input
                            type="radio"
                            name="loan_type"
                            value="<?php echo htmlspecialchars($key, ENT_QUOTES, "UTF-8"); ?>"
                            data-min="<?php echo (int) $type["min_term"]; ?>"
                            data-max="<?php echo (int) $type["max_term"]; ?>"
                            <?php echo (($_POST["loan_type"] ?? "") === $key) ? "checked" : ""; ?>
                            required
                        >
                        <span class="loan-type-name"><?php echo htmlspecialchars($type["label"], ENT_QUOTES, "UTF-8"); ?></span>
                        <span class="loan-type-terms"><?php echo (int) $type["min_term"]; ?>–<?php echo (int) $type["max_term"]; ?> Months</span>
                    </label>
                <?php endforeach; ?>
            </div>
            <?php if (isset($errors["loan_type"])): ?>
                <p class="field-error"><?php echo htmlspecialchars($errors["loan_type"], ENT_QUOTES, "UTF-8"); ?></p>
            <?php endif; ?>

            <form action="loan-application.php" method="POST" class="loan-form" novalidate>

                <div class="form-row">

                    <div class="form-group">
                        <label for="amount">Loan Amount (₱)<span class="required-mark">*</span></label>
                        <input
                            type="number"
                            id="amount"
                            name="amount"
                            min="5000"
                            max="50000000"
                            step="0.01"
                            value="<?php echo htmlspecialchars($_POST["amount"] ?? "", ENT_QUOTES, "UTF-8"); ?>"
                            class="<?php echo isset($errors["amount"]) ? "input-error" : ""; ?>"
                            required
                        >
                        <?php if (isset($errors["amount"])): ?>
                            <p class="field-error"><?php echo htmlspecialchars($errors["amount"], ENT_QUOTES, "UTF-8"); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="term">Repayment Term (Months)<span class="required-mark">*</span></label>
                        <select
                            id="term"
                            name="term"
                            class="<?php echo isset($errors["term"]) ? "input-error" : ""; ?>"
                            required
                        >
                            <option value="">Select a loan type first</option>
                        </select>
                        <?php if (isset($errors["term"])): ?>
                            <p class="field-error"><?php echo htmlspecialchars($errors["term"], ENT_QUOTES, "UTF-8"); ?></p>
                        <?php endif; ?>
                    </div>

                </div>

                <div class="form-group form-full">
                    <label for="purpose">What is this loan for?<span class="required-mark">*</span></label>
                    <textarea
                        id="purpose"
                        name="purpose"
                        rows="4"
                        class="<?php echo isset($errors["purpose"]) ? "input-error" : ""; ?>"
                        required
                    ><?php echo htmlspecialchars($_POST["purpose"] ?? "", ENT_QUOTES, "UTF-8"); ?></textarea>
                    <?php if (isset($errors["purpose"])): ?>
                        <p class="field-error"><?php echo htmlspecialchars($errors["purpose"], ENT_QUOTES, "UTF-8"); ?></p>
                    <?php endif; ?>
                </div>

                <button type="submit" class="loan-button">Submit Application</button>

            </form>

        <?php endif; ?>

    </section>
</main>

<script>
// Term dropdown options depend on which loan type card is selected.
// The min/max come from data-min / data-max on each radio button,
// which the server populated from the exact same get_loan_types()
// data used for validation -- so the client and server can never disagree.
(function () {
    const radios = document.querySelectorAll('input[name="loan_type"]');
    const termSelect = document.getElementById("term");
    const postedTerm = "<?php echo (int) ($_POST["term"] ?? 0); ?>";

    const commonTerms = [12, 24, 36, 60, 84, 120, 180, 240, 300, 360];

    function populateTerms(min, max) {
        termSelect.innerHTML = "";

        const options = commonTerms.filter(function (t) {
            return t >= min && t <= max;
        });

        if (!options.includes(min)) options.unshift(min);
        if (!options.includes(max)) options.push(max);

        options.sort(function (a, b) { return a - b; });

        options.forEach(function (months) {
            const opt = document.createElement("option");
            opt.value = months;
            opt.textContent = months + " Months";
            if (String(months) === postedTerm) {
                opt.selected = true;
            }
            termSelect.appendChild(opt);
        });
    }

    radios.forEach(function (radio) {
        radio.addEventListener("change", function () {
            populateTerms(parseInt(radio.dataset.min, 10), parseInt(radio.dataset.max, 10));
        });

        if (radio.checked) {
            populateTerms(parseInt(radio.dataset.min, 10), parseInt(radio.dataset.max, 10));
        }
    });
})();
</script>

</body>
</html>