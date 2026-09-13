<?php
require_once "require_login.php";
require_once "csrf.php";
require_once "loan_application_validation.php";
require_once "loan_application_function.php";
require_once "profile_function.php";
require_once "admin_function.php";

block_admin_from_customer_area();

$loan_types = get_loan_types();
$id_types = get_id_types();
$employment_statuses = get_employment_statuses();
$employment_lengths = get_employment_lengths();
$business_types = get_business_types();
$payment_frequencies = get_payment_frequencies();
$errors = [];
$success = isset($_GET["success"]) && $_GET["success"] === "1";

// Drives which conditional field groups render visible/hidden on a
// re-render after a validation error (JS takes over from there for
// live toggling as the applicant changes the dropdown).
$current_employment_status = $_POST["employment_status"] ?? "";

$account = get_user_by_id($_SESSION["user_id"]);

// Small inline icon per loan type -- gives the picker a subject-specific
// identity instead of four identical generic cards.
$loan_type_icons = [
    "home" => '<path d="M4 12L16 3l12 9" /><path d="M7 10v14h18V10" /><path d="M13 24v-7h6v7" />',
    "business" => '<rect x="6" y="11" width="20" height="14" rx="1.5" /><path d="M12 11V8a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v3" /><path d="M6 17h20" />',
    "personal" => '<circle cx="16" cy="10" r="4.5" /><path d="M7 25c1.5-5 5-7.5 9-7.5S23.5 20 25 25" />',
    "asset_backed" => '<path d="M16 4l10 4v7c0 6.5-4.2 11-10 13-5.8-2-10-6.5-10-13V8z" /><path d="M12 16l3 3 5-6" />',
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!csrf_verify($_POST["csrf_token"] ?? "")) {
        $errors["general"] = "Your session has expired. Please refresh the page and try again.";
    } else {
    $loan_type               = $_POST["loan_type"] ?? "";
    $id_type                 = $_POST["id_type"] ?? "";
    $employment_status       = $_POST["employment_status"] ?? "";
    $occupation              = trim($_POST["occupation"] ?? "");
    $employer_name           = trim($_POST["employer_name"] ?? "");
    $length_of_employment    = $_POST["length_of_employment"] ?? "";
    $employer_contact_number = trim($_POST["employer_contact_number"] ?? "");
    $business_name           = trim($_POST["business_name"] ?? "");
    $business_type           = $_POST["business_type"] ?? "";
    $monthly_income          = trim($_POST["monthly_income"] ?? "");
    $payment_frequency       = $_POST["payment_frequency"] ?? "";
    $amount                  = trim($_POST["amount"] ?? "");
    $term                    = $_POST["term"] ?? "";
    $purpose                 = trim($_POST["purpose"] ?? "");

    // Fields that don't apply to the chosen employment status are
    // cleared before validation/storage, so a value typed then hidden
    // by switching the dropdown never sneaks into the saved record.
    if ($employment_status !== "employed") {
        $employer_name = "";
        $length_of_employment = "";
        $employer_contact_number = "";
    }
    if ($employment_status !== "self_employed") {
        $business_name = "";
        $business_type = "";
    }

    $errors = validate_loan_application(
        $loan_type,
        $id_type,
        $employment_status,
        $occupation,
        $monthly_income,
        $amount,
        $term,
        $purpose,
        $payment_frequency,
        $employer_name,
        $length_of_employment,
        $employer_contact_number,
        $business_name,
        $business_type
    );

    $document_errors = validate_loan_documents(
        $_FILES["valid_id"] ?? null,
        $_FILES["proof_of_income"] ?? null,
        $_FILES["proof_of_address"] ?? null,
        $_FILES["employment_certificate"] ?? null,
        $employment_status
    );
    $errors = array_merge($errors, $document_errors);

    if (empty($errors)) {
        $result = submit_loan_application(
            $_SESSION["user_id"],
            $loan_type,
            $id_type,
            $employment_status,
            $occupation,
            $employer_name,
            $length_of_employment,
            $employer_contact_number,
            $business_name,
            $business_type,
            (float) $monthly_income,
            $payment_frequency,
            (float) $amount,
            (int) $term,
            $purpose,
            $_FILES["valid_id"],
            $_FILES["proof_of_income"],
            $_FILES["proof_of_address"],
            $_FILES["employment_certificate"] ?? null
        );

        if ($result === true) {
            header("Location: loan_application.php?success=1");
            exit;
        }

        $errors["general"] = $result;
    }
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
        <img src="./assets/home_01.png" alt="Mletchido Financial Group logo" class="topbar-logo">
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
                <p>Complete the three steps below to submit your application for review.</p>
            </header>

            <?php if (isset($errors["general"])): ?>
                <div class="form-error">
                    <p><?php echo htmlspecialchars($errors["general"], ENT_QUOTES, "UTF-8"); ?></p>
                </div>
            <?php endif; ?>

            <form action="loan_application.php" method="POST" enctype="multipart/form-data" class="loan-form" novalidate>

                <?php csrf_field(); ?>

                <!-- STEP 1: LOAN TYPE -->
                <div class="form-section">
                    <div class="section-marker">
                        <span class="section-number">1</span>
                        <span class="section-line"></span>
                    </div>
                    <div class="section-body">
                        <h2 class="section-title">Loan Details</h2>

                        <div class="loan-type-list">
                            <?php foreach ($loan_types as $key => $type): ?>
                                <label class="loan-type-card">
                                    <input
                                        type="radio"
                                        name="loan_type"
                                        value="<?php echo htmlspecialchars($key, ENT_QUOTES, "UTF-8"); ?>"
                                        data-terms="<?php echo htmlspecialchars(implode(",", $type["terms"]), ENT_QUOTES, "UTF-8"); ?>"
                                        <?php echo (($_POST["loan_type"] ?? "") === $key) ? "checked" : ""; ?>
                                        required
                                    >
                                    <svg class="loan-type-icon" width="24" height="24" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                        <?php echo $loan_type_icons[$key] ?? ""; ?>
                                    </svg>
                                    <span class="loan-type-text">
                                        <span class="loan-type-name"><?php echo htmlspecialchars($type["label"], ENT_QUOTES, "UTF-8"); ?></span>
                                        <span class="loan-type-terms"><?php echo (int) $type["terms"][0]; ?>–<?php echo (int) end($type["terms"]); ?> months</span>
                                    </span>
                                    <svg class="loan-type-check" width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M4 10l4 4 8-9"/>
                                    </svg>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <?php if (isset($errors["loan_type"])): ?>
                            <p class="field-error"><?php echo htmlspecialchars($errors["loan_type"], ENT_QUOTES, "UTF-8"); ?></p>
                        <?php endif; ?>

                        <div class="form-row form-row-tight">
                            <div class="form-group">
                                <label for="amount">Loan Amount (₱)<span class="required-mark">*</span></label>
                                <input
                                    type="number"
                                    id="amount"
                                    name="amount"
                                    min="5000"
                                    max="50000000"
                                    step="0.01"
                                    placeholder="e.g. 250000"
                                    value="<?php echo htmlspecialchars($_POST["amount"] ?? "", ENT_QUOTES, "UTF-8"); ?>"
                                    class="<?php echo isset($errors["amount"]) ? "input-error" : ""; ?>"
                                    required
                                >
                                <?php if (isset($errors["amount"])): ?>
                                    <p class="field-error"><?php echo htmlspecialchars($errors["amount"], ENT_QUOTES, "UTF-8"); ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="term">Repayment Term<span class="required-mark">*</span></label>
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

                            <div class="form-group">
                                <label for="payment_frequency">Preferred Payment Frequency<span class="required-mark">*</span></label>
                                <select
                                    id="payment_frequency"
                                    name="payment_frequency"
                                    class="<?php echo isset($errors["payment_frequency"]) ? "input-error" : ""; ?>"
                                    required
                                >
                                    <option value="">Select a frequency</option>
                                    <?php foreach ($payment_frequencies as $key => $label): ?>
                                        <option value="<?php echo htmlspecialchars($key, ENT_QUOTES, "UTF-8"); ?>"
                                            <?php echo (($_POST["payment_frequency"] ?? "") === $key) ? "selected" : ""; ?>>
                                            <?php echo htmlspecialchars($label, ENT_QUOTES, "UTF-8"); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors["payment_frequency"])): ?>
                                    <p class="field-error"><?php echo htmlspecialchars($errors["payment_frequency"], ENT_QUOTES, "UTF-8"); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="purpose">What is this loan for?<span class="required-mark">*</span></label>
                            <textarea
                                id="purpose"
                                name="purpose"
                                rows="3"
                                placeholder="Tell us briefly what you'll use the funds for"
                                class="<?php echo isset($errors["purpose"]) ? "input-error" : ""; ?>"
                                required
                            ><?php echo htmlspecialchars($_POST["purpose"] ?? "", ENT_QUOTES, "UTF-8"); ?></textarea>
                            <?php if (isset($errors["purpose"])): ?>
                                <p class="field-error"><?php echo htmlspecialchars($errors["purpose"], ENT_QUOTES, "UTF-8"); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- STEP 2: WHO YOU ARE -->
                <div class="form-section">
                    <div class="section-marker">
                        <span class="section-number">2</span>
                        <span class="section-line"></span>
                    </div>
                    <div class="section-body">
                        <h2 class="section-title">Applicant Information</h2>

                        <div class="account-info-box">
                            <div class="account-info-field">
                                <span class="field-label">Full name</span>
                                <span class="field-value">
                                    <?php
                                        echo htmlspecialchars(
                                            trim($account["first_name"] . " " . $account["middle_name"] . " " . $account["last_name"]),
                                            ENT_QUOTES,
                                            "UTF-8"
                                        );
                                    ?>
                                </span>
                            </div>
                            <div class="account-info-field">
                                <span class="field-label">Address</span>
                                <span class="field-value"><?php echo htmlspecialchars($account["address"], ENT_QUOTES, "UTF-8"); ?></span>
                            </div>
                            <p class="account-info-note">
                                From your account — <a href="edit_profile.php">edit your profile</a> if this has changed.
                            </p>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="id_type">Type of ID<span class="required-mark">*</span></label>
                                <select
                                    id="id_type"
                                    name="id_type"
                                    class="<?php echo isset($errors["id_type"]) ? "input-error" : ""; ?>"
                                    required
                                >
                                    <option value="">Select an ID type</option>
                                    <?php foreach ($id_types as $key => $label): ?>
                                        <option value="<?php echo htmlspecialchars($key, ENT_QUOTES, "UTF-8"); ?>"
                                            <?php echo (($_POST["id_type"] ?? "") === $key) ? "selected" : ""; ?>>
                                            <?php echo htmlspecialchars($label, ENT_QUOTES, "UTF-8"); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors["id_type"])): ?>
                                    <p class="field-error"><?php echo htmlspecialchars($errors["id_type"], ENT_QUOTES, "UTF-8"); ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="employment_status">Employment Status<span class="required-mark">*</span></label>
                                <select
                                    id="employment_status"
                                    name="employment_status"
                                    class="<?php echo isset($errors["employment_status"]) ? "input-error" : ""; ?>"
                                    required
                                >
                                    <option value="">Select your status</option>
                                    <?php foreach ($employment_statuses as $key => $label): ?>
                                        <option value="<?php echo htmlspecialchars($key, ENT_QUOTES, "UTF-8"); ?>"
                                            <?php echo (($_POST["employment_status"] ?? "") === $key) ? "selected" : ""; ?>>
                                            <?php echo htmlspecialchars($label, ENT_QUOTES, "UTF-8"); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors["employment_status"])): ?>
                                    <p class="field-error"><?php echo htmlspecialchars($errors["employment_status"], ENT_QUOTES, "UTF-8"); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group form-full">
                                <label for="occupation">Occupation / Job Title (or Course, if student)<span class="required-mark">*</span></label>
                                <input
                                    type="text"
                                    id="occupation"
                                    name="occupation"
                                    placeholder="e.g. Nurse, BS Accountancy, Sari-sari store owner"
                                    value="<?php echo htmlspecialchars($_POST["occupation"] ?? "", ENT_QUOTES, "UTF-8"); ?>"
                                    class="<?php echo isset($errors["occupation"]) ? "input-error" : ""; ?>"
                                    required
                                >
                                <?php if (isset($errors["occupation"])): ?>
                                    <p class="field-error"><?php echo htmlspecialchars($errors["occupation"], ENT_QUOTES, "UTF-8"); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- EMPLOYED-ONLY FIELDS -->
                        <div id="employed-fields" class="conditional-fields" style="<?php echo $current_employment_status === "employed" ? "" : "display:none;"; ?>">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="employer_name">Employer / Company Name<span class="required-mark">*</span></label>
                                    <input
                                        type="text"
                                        id="employer_name"
                                        name="employer_name"
                                        placeholder="e.g. ABC Manufacturing Corp."
                                        value="<?php echo htmlspecialchars($_POST["employer_name"] ?? "", ENT_QUOTES, "UTF-8"); ?>"
                                        class="<?php echo isset($errors["employer_name"]) ? "input-error" : ""; ?>"
                                    >
                                    <?php if (isset($errors["employer_name"])): ?>
                                        <p class="field-error"><?php echo htmlspecialchars($errors["employer_name"], ENT_QUOTES, "UTF-8"); ?></p>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group">
                                    <label for="employer_contact_number">Employer Contact Number<span class="required-mark">*</span></label>
                                    <input
                                        type="tel"
                                        id="employer_contact_number"
                                        name="employer_contact_number"
                                        placeholder="e.g. 09171234567"
                                        value="<?php echo htmlspecialchars($_POST["employer_contact_number"] ?? "", ENT_QUOTES, "UTF-8"); ?>"
                                        class="<?php echo isset($errors["employer_contact_number"]) ? "input-error" : ""; ?>"
                                    >
                                    <?php if (isset($errors["employer_contact_number"])): ?>
                                        <p class="field-error"><?php echo htmlspecialchars($errors["employer_contact_number"], ENT_QUOTES, "UTF-8"); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="length_of_employment">Length of Employment<span class="required-mark">*</span></label>
                                <select
                                    id="length_of_employment"
                                    name="length_of_employment"
                                    class="<?php echo isset($errors["length_of_employment"]) ? "input-error" : ""; ?>"
                                >
                                    <option value="">Select a range</option>
                                    <?php foreach ($employment_lengths as $key => $label): ?>
                                        <option value="<?php echo htmlspecialchars($key, ENT_QUOTES, "UTF-8"); ?>"
                                            <?php echo (($_POST["length_of_employment"] ?? "") === $key) ? "selected" : ""; ?>>
                                            <?php echo htmlspecialchars($label, ENT_QUOTES, "UTF-8"); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors["length_of_employment"])): ?>
                                    <p class="field-error"><?php echo htmlspecialchars($errors["length_of_employment"], ENT_QUOTES, "UTF-8"); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- SELF-EMPLOYED-ONLY FIELDS -->
                        <div id="self-employed-fields" class="conditional-fields" style="<?php echo $current_employment_status === "self_employed" ? "" : "display:none;"; ?>">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="business_name">Business Name<span class="required-mark">*</span></label>
                                    <input
                                        type="text"
                                        id="business_name"
                                        name="business_name"
                                        placeholder="e.g. Aling Nena's Sari-Sari Store"
                                        value="<?php echo htmlspecialchars($_POST["business_name"] ?? "", ENT_QUOTES, "UTF-8"); ?>"
                                        class="<?php echo isset($errors["business_name"]) ? "input-error" : ""; ?>"
                                    >
                                    <?php if (isset($errors["business_name"])): ?>
                                        <p class="field-error"><?php echo htmlspecialchars($errors["business_name"], ENT_QUOTES, "UTF-8"); ?></p>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group">
                                    <label for="business_type">Business Type<span class="required-mark">*</span></label>
                                    <select
                                        id="business_type"
                                        name="business_type"
                                        class="<?php echo isset($errors["business_type"]) ? "input-error" : ""; ?>"
                                    >
                                        <option value="">Select a business type</option>
                                        <?php foreach ($business_types as $key => $label): ?>
                                            <option value="<?php echo htmlspecialchars($key, ENT_QUOTES, "UTF-8"); ?>"
                                                <?php echo (($_POST["business_type"] ?? "") === $key) ? "selected" : ""; ?>>
                                                <?php echo htmlspecialchars($label, ENT_QUOTES, "UTF-8"); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (isset($errors["business_type"])): ?>
                                        <p class="field-error"><?php echo htmlspecialchars($errors["business_type"], ENT_QUOTES, "UTF-8"); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group form-full">
                                <label for="monthly_income" id="monthly_income_label">Monthly Income (₱)<span class="required-mark">*</span></label>
                                <input
                                    type="number"
                                    id="monthly_income"
                                    name="monthly_income"
                                    min="0"
                                    max="10000000"
                                    step="0.01"
                                    placeholder="e.g. 25000"
                                    value="<?php echo htmlspecialchars($_POST["monthly_income"] ?? "", ENT_QUOTES, "UTF-8"); ?>"
                                    class="<?php echo isset($errors["monthly_income"]) ? "input-error" : ""; ?>"
                                    required
                                >
                                <?php if (isset($errors["monthly_income"])): ?>
                                    <p class="field-error"><?php echo htmlspecialchars($errors["monthly_income"], ENT_QUOTES, "UTF-8"); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 3: DOCUMENTS -->
                <div class="form-section form-section-last">
                    <div class="section-marker">
                        <span class="section-number">3</span>
                    </div>
                    <div class="section-body">
                        <h2 class="section-title">Supporting Documents</h2>
                        <p class="section-subtitle">JPG or PNG, up to 5MB each.</p>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="valid_id">Valid ID<span class="required-mark">*</span></label>
                                <input
                                    type="file"
                                    id="valid_id"
                                    name="valid_id"
                                    accept="image/jpeg,image/png"
                                    class="<?php echo isset($errors["valid_id"]) ? "input-error" : ""; ?>"
                                    required
                                >
                                <?php if (isset($errors["valid_id"])): ?>
                                    <p class="field-error"><?php echo htmlspecialchars($errors["valid_id"], ENT_QUOTES, "UTF-8"); ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="proof_of_income">Proof of Income<span class="required-mark">*</span></label>
                                <input
                                    type="file"
                                    id="proof_of_income"
                                    name="proof_of_income"
                                    accept="image/jpeg,image/png"
                                    class="<?php echo isset($errors["proof_of_income"]) ? "input-error" : ""; ?>"
                                    required
                                >
                                <?php if (isset($errors["proof_of_income"])): ?>
                                    <p class="field-error"><?php echo htmlspecialchars($errors["proof_of_income"], ENT_QUOTES, "UTF-8"); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="proof_of_address">Proof of Address<span class="required-mark">*</span></label>
                                <input
                                    type="file"
                                    id="proof_of_address"
                                    name="proof_of_address"
                                    accept="image/jpeg,image/png"
                                    class="<?php echo isset($errors["proof_of_address"]) ? "input-error" : ""; ?>"
                                    required
                                >
                                <?php if (isset($errors["proof_of_address"])): ?>
                                    <p class="field-error"><?php echo htmlspecialchars($errors["proof_of_address"], ENT_QUOTES, "UTF-8"); ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="form-group conditional-fields" id="employment-certificate-field" style="<?php echo $current_employment_status === "employed" ? "" : "display:none;"; ?>">
                                <label for="employment_certificate">Employment Certificate<span class="required-mark" id="employment_certificate_mark">*</span></label>
                                <input
                                    type="file"
                                    id="employment_certificate"
                                    name="employment_certificate"
                                    accept="image/jpeg,image/png"
                                    class="<?php echo isset($errors["employment_certificate"]) ? "input-error" : ""; ?>"
                                >
                                <?php if (isset($errors["employment_certificate"])): ?>
                                    <p class="field-error"><?php echo htmlspecialchars($errors["employment_certificate"], ENT_QUOTES, "UTF-8"); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <button type="submit" class="loan-button">Submit Application</button>
                    </div>
                </div>

            </form>

        <?php endif; ?>

    </section>
</main>

<script>
// Term dropdown options depend on which loan type card is selected.
// Each type's exact list of terms comes from data-terms, sourced
// from the same get_loan_types() data used for validation -- so the
// client and server can never disagree on what's allowed.
(function () {
    const radios = document.querySelectorAll('input[name="loan_type"]');
    const cards = document.querySelectorAll('.loan-type-card');
    const termSelect = document.getElementById("term");
    const postedTerm = "<?php echo (int) ($_POST["term"] ?? 0); ?>";

    cards.forEach(function (card) {
        card.addEventListener("click", function () {
            const radio = card.querySelector('input[name="loan_type"]');
            if (radio && !radio.checked) {
                radio.checked = true;
                radio.dispatchEvent(new Event("change", { bubbles: true }));
            }
        });
    });

    function formatTermLabel(months) {
        if (months % 12 === 0) {
            const years = months / 12;
            return months + " Months (" + years + (years === 1 ? " Year" : " Years") + ")";
        }
        return months + " Months";
    }

    function populateTerms(terms) {
        termSelect.innerHTML = "";

        terms.forEach(function (months) {
            const opt = document.createElement("option");
            opt.value = months;
            opt.textContent = formatTermLabel(months);
            if (String(months) === postedTerm) {
                opt.selected = true;
            }
            termSelect.appendChild(opt);
        });
    }

    radios.forEach(function (radio) {
        radio.addEventListener("change", function () {
            const terms = radio.dataset.terms.split(",").map(Number);
            populateTerms(terms);
        });

        if (radio.checked) {
            const terms = radio.dataset.terms.split(",").map(Number);
            populateTerms(terms);
        }
    });
})();

// Show only the employer fields or only the business fields depending
// on the applicant's Employment Status, and switch the Monthly Income
// label to "Estimated Monthly Income" for self-employed applicants.
// The `required` attribute is toggled too so the browser doesn't block
// submission on a hidden field -- the server re-checks all of this
// regardless of what the client sends.
(function () {
    const employmentStatus = document.getElementById("employment_status");
    const employedFields = document.getElementById("employed-fields");
    const selfEmployedFields = document.getElementById("self-employed-fields");
    const employmentCertificateField = document.getElementById("employment-certificate-field");
    const employmentCertificateInput = document.getElementById("employment_certificate");
    const employmentCertificateMark = document.getElementById("employment_certificate_mark");
    const monthlyIncomeLabel = document.getElementById("monthly_income_label");

    if (!employmentStatus) return;

    function setGroupRequired(container, isRequired) {
        if (!container) return;
        container.querySelectorAll("input, select").forEach(function (field) {
            field.required = isRequired;
        });
    }

    function syncEmploymentFields() {
        const status = employmentStatus.value;
        const isEmployed = status === "employed";
        const isSelfEmployed = status === "self_employed";

        employedFields.style.display = isEmployed ? "" : "none";
        setGroupRequired(employedFields, isEmployed);

        selfEmployedFields.style.display = isSelfEmployed ? "" : "none";
        setGroupRequired(selfEmployedFields, isSelfEmployed);

        employmentCertificateField.style.display = isEmployed ? "" : "none";
        if (employmentCertificateInput) {
            employmentCertificateInput.required = isEmployed;
        }
        if (employmentCertificateMark) {
            employmentCertificateMark.style.display = isEmployed ? "" : "none";
        }

        if (monthlyIncomeLabel) {
            monthlyIncomeLabel.firstChild.textContent = isSelfEmployed
                ? "Estimated Monthly Income (₱)"
                : "Monthly Income (₱)";
        }
    }

    employmentStatus.addEventListener("change", syncEmploymentFields);
    syncEmploymentFields();
})();
</script>

</body>
</html>