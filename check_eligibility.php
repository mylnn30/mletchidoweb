<?php
// Mletchido Financial Group - Quick Eligibility Checker
// Public, no login required. Checks a handful of straightforward
// pass/fail requirements (age, has an income source, minimum
// income, valid amount/term) rather than a credit assessment --
// this is a quick self-check, not a guarantee of approval or denial.

require_once "loan_application_validation.php";

$loan_types = get_loan_types();
$employment_statuses = get_employment_statuses();
$annual_rate = 4.99;
$minimum_monthly_income = 8000;

$age = 30;
$employment_status = "";
$monthly_income = 25000;
$loan_type = "personal";
$amount = 100000;
$term = 12;

$errors = [];
$result = null;

function peso(float $amount): string {
    return '₱' . number_format($amount, 2);
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["check_eligibility"])) {
    $age = filter_input(INPUT_POST, "age", FILTER_VALIDATE_INT);
    $employment_status = $_POST["employment_status"] ?? "";
    $monthly_income = filter_input(INPUT_POST, "monthly_income", FILTER_VALIDATE_FLOAT);
    $loan_type = $_POST["loan_type"] ?? "";
    $amount = filter_input(INPUT_POST, "amount", FILTER_VALIDATE_FLOAT);
    $term = filter_input(INPUT_POST, "term", FILTER_VALIDATE_INT);

    // Basic input sanity -- separate from the eligibility checklist
    // itself, these just mean "the form wasn't filled in properly"
    // rather than "you don't qualify".
    if ($loan_type === "" || !isset($loan_types[$loan_type])) {
        $errors[] = "Please select a loan type.";
    }
    if (
        $term === false || $term === null
        || !isset($loan_types[$loan_type])
        || !in_array($term, $loan_types[$loan_type]["terms"], true)
    ) {
        $errors[] = "Please select a valid repayment term for this loan type.";
    }
    if ($amount === false || $amount === null) {
        $errors[] = "Please enter a loan amount.";
    }

    if (empty($errors)) {
        // Each requirement is a simple pass/fail check -- no scoring,
        // no ratios. Every single one has to pass to be eligible.
        $checklist = [
            [
                "label" => "You're between 18 and 70 years old",
                "pass" => ($age !== false && $age !== null && $age >= 18 && $age <= 70),
            ],
            [
                "label" => "You have a job, business, or other source of income",
                "pass" => ($employment_status !== "" && isset($employment_statuses[$employment_status])),
            ],
            [
                "label" => "Your monthly income is at least " . peso($minimum_monthly_income),
                "pass" => ($monthly_income !== false && $monthly_income !== null && $monthly_income >= $minimum_monthly_income),
            ],
            [
                "label" => "Your requested amount is within our lending range (₱5,000–₱50,000,000)",
                "pass" => ($amount >= 5000 && $amount <= 50000000),
            ],
        ];

        $all_pass = true;
        foreach ($checklist as $item) {
            if (!$item["pass"]) {
                $all_pass = false;
            }
        }

        $monthly_rate = ($annual_rate / 100) / 12;
        $estimated_payment = $monthly_rate > 0
            ? $amount * ($monthly_rate * pow(1 + $monthly_rate, $term)) / (pow(1 + $monthly_rate, $term) - 1)
            : $amount / $term;

        $result = [
            "checklist" => $checklist,
            "all_pass" => $all_pass,
            "estimated_payment" => $estimated_payment,
            "loan_type_label" => $loan_types[$loan_type]["label"],
        ];
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Get an instant, no-obligation estimate of your loan eligibility with Mletchido Financial Group.">
    <title>Check Your Eligibility | Mletchido Financial Group</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/check_eligibility.css">
</head>
<body>
<a class="skip-link" href="#main-content">Skip to content</a>

<header class="site-header">
    <nav class="navbar navbar-expand-lg" aria-label="Primary navigation">
        <div class="container">
            <a class="brand" href="index.php" aria-label="Mletchido Financial Group home">
                <img src="assets/home_01.png" alt="Mletchido Financial Group logo">
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavigation" aria-controls="mainNavigation" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNavigation">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                    <li class="nav-item"><a class="nav-link" href="index.php#home">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php#services">Business Loans</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php#calculator">Loan Calculator</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php#process">Process</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php#about">About Us</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php#faq">FAQ</a></li>
                    <li class="nav-item"><a class="nav-link active" href="check_eligibility.php">Check Eligibility</a></li>
                    <li class="nav-item ms-lg-2"><a class="btn btn-primary btn-sm px-4" href="register.php">Apply Now</a></li>
                </ul>
            </div>
        </div>
    </nav>
</header>

<main id="main-content">

    <section class="eligibility-hero">
        <div class="container">
            <p class="eyebrow">QUICK, NO-OBLIGATION CHECK</p>
            <h1>Find Out If You Qualify — Before You Apply</h1>
            <p>Answer a few quick questions and see instantly whether you meet our basic loan requirements. It won't affect your credit score.</p>
        </div>
    </section>

    <section class="eligibility-section" aria-labelledby="eligibility-form-title">
        <div class="container eligibility-layout">

            <div class="eligibility-intro">
                <p class="card-eyebrow">WHY CHECK FIRST?</p>
                <h2>Know Before You Apply</h2>
                <p>A full loan application asks for documents and takes a few minutes. This quick check tells you where you stand first, so you're not caught off guard.</p>

                <ul class="eligibility-benefits">
                    <li>
                        <span class="icon">⏱</span>
                        <span><strong>Under a minute</strong>Just five quick fields, no account needed.</span>
                    </li>
                    <li>
                        <span class="icon">🛡</span>
                        <span><strong>No credit impact</strong>This is a self-check, not a credit inquiry.</span>
                    </li>
                    <li>
                        <span class="icon">📋</span>
                        <span><strong>Clear requirements</strong>See exactly which boxes you tick and which you don't.</span>
                    </li>
                </ul>

                <div class="eligibility-requirements-note">
                    This tool checks basic requirements only (age, income source, minimum income, and loan amount). It isn't a credit check and doesn't guarantee final approval — every application still goes through document review by our team.
                </div>
            </div>

            <form class="eligibility-card" method="post" action="check_eligibility.php#eligibility-form-title" novalidate>
                <header>
                    <p class="card-eyebrow">Check Your Eligibility</p>
                    <h3 id="eligibility-form-title">Tell us a bit about yourself</h3>
                </header>

                <?php if (!empty($errors)): ?>
                    <p class="eligibility-alert" role="alert">
                        <?php echo htmlspecialchars(implode(" ", $errors), ENT_QUOTES, "UTF-8"); ?>
                    </p>
                <?php endif; ?>

                <div class="eligibility-field">
                    <label for="age">Age</label>
                    <input type="number" id="age" name="age" min="18" max="70" value="<?php echo htmlspecialchars((string) $age, ENT_QUOTES, "UTF-8"); ?>" required>
                </div>

                <div class="eligibility-field">
                    <label for="employment_status">Employment Status</label>
                    <select id="employment_status" name="employment_status" required>
                        <option value="">Select your status</option>
                        <?php foreach ($employment_statuses as $key => $label): ?>
                            <option value="<?php echo htmlspecialchars($key, ENT_QUOTES, "UTF-8"); ?>" <?php echo ($employment_status === $key) ? "selected" : ""; ?>>
                                <?php echo htmlspecialchars($label, ENT_QUOTES, "UTF-8"); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="eligibility-field">
                    <label for="monthly_income">Monthly Income (₱)</label>
                    <input type="number" id="monthly_income" name="monthly_income" min="0" step="0.01" value="<?php echo htmlspecialchars((string) $monthly_income, ENT_QUOTES, "UTF-8"); ?>" required>
                </div>

                <div class="eligibility-field">
                    <label for="loan_type">Loan Type</label>
                    <select id="loan_type" name="loan_type" required>
                        <?php foreach ($loan_types as $key => $type): ?>
                            <option
                                value="<?php echo htmlspecialchars($key, ENT_QUOTES, "UTF-8"); ?>"
                                data-terms="<?php echo htmlspecialchars(implode(",", $type["terms"]), ENT_QUOTES, "UTF-8"); ?>"
                                <?php echo ($loan_type === $key) ? "selected" : ""; ?>
                            >
                                <?php echo htmlspecialchars($type["label"], ENT_QUOTES, "UTF-8"); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="eligibility-field">
                    <div class="eligibility-amount-head">
                        <label for="amount">Loan Amount</label>
                        <output class="eligibility-amount-value" id="amountDisplay" aria-live="polite"><?php echo peso((float) $amount); ?></output>
                    </div>
                    <input type="range" id="amount" name="amount" min="5000" max="50000000" step="5000" value="<?php echo htmlspecialchars((string) $amount, ENT_QUOTES, "UTF-8"); ?>">
                    <div class="eligibility-range-labels"><span>₱5,000</span><span>₱50M</span></div>
                </div>

                <div class="eligibility-field">
                    <fieldset>
                        <legend>Repayment Term</legend>
                        <div class="eligibility-term-grid" id="termOptions">
                            <!-- populated by JS based on the selected loan type -->
                        </div>
                    </fieldset>
                </div>

                <?php if ($result !== null): ?>
                    <ul class="eligibility-checklist">
                        <?php foreach ($result["checklist"] as $item): ?>
                            <li class="eligibility-check-item <?php echo $item["pass"] ? "is-pass" : "is-fail"; ?>">
                                <span class="eligibility-check-icon"><?php echo $item["pass"] ? "✓" : "✕"; ?></span>
                                <span><?php echo htmlspecialchars($item["label"], ENT_QUOTES, "UTF-8"); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="eligibility-verdict eligibility-verdict-<?php echo $result["all_pass"] ? "pass" : "fail"; ?>">
                        <div class="eligibility-verdict-icon"><?php echo $result["all_pass"] ? "✓" : "!"; ?></div>
                        <span class="eligibility-verdict-label">
                            <?php echo $result["all_pass"] ? "You meet our basic requirements" : "You don't meet all requirements yet"; ?>
                        </span>
                        <p class="eligibility-verdict-message">
                            <?php echo $result["all_pass"]
                                ? "You've met every requirement above. You're in a good position to apply."
                                : "Take a look at the requirement(s) marked above. Once those are met, you're welcome to check again."; ?>
                        </p>

                        <div class="eligibility-payment-estimate">
                            <span>Estimated Monthly Payment</span>
                            <strong><?php echo peso($result["estimated_payment"]); ?>/month</strong>
                            <small>Estimated for a <?php echo htmlspecialchars($result["loan_type_label"], ENT_QUOTES, "UTF-8"); ?> · <b><?php echo number_format($annual_rate, 2); ?>% APR</b></small>
                        </div>

                        <?php if ($result["all_pass"]): ?>
                            <a href="register.php" class="btn btn-light">Apply Now</a>
                        <?php else: ?>
                            <a href="register.php" class="btn btn-outline-light">Apply Anyway — Let Our Team Review</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <button class="eligibility-submit" type="submit" name="check_eligibility">Check My Eligibility</button>
                <p class="eligibility-disclaimer">This is a quick self-check only, not a guarantee of approval or denial. Final loan terms are subject to eligibility verification, credit assessment, document review, and approval.</p>
            </form>

        </div>
    </section>

</main>

<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <section>
                <a class="brand footer-brand" href="index.php#home"><img src="assets/home_10.png" alt="Mletchido Financial Group"></a>
                <p>Providing trusted lending solutions that help individuals and businesses achieve their financial goals with confidence.</p>
            </section>
            <nav aria-label="Loan Services"><h2>Loan Services</h2><ul><li><a href="index.php#services">Personal Loans</a></li><li><a href="index.php#services">Home Loans</a></li><li><a href="index.php#services">Business Loans</a></li><li><a href="index.php#services">Asset Backed Loans</a></li></ul></nav>
            <nav aria-label="Resources"><h2>Resources</h2><ul><li><a href="index.php#calculator">Loan Calculator</a></li><li><a href="check_eligibility.php">Check Eligibility</a></li><li><a href="index.php#faq">Frequently Asked Questions</a></li><li><a href="index.php#apply">Contact Support</a></li></ul></nav>
            <nav aria-label="Company"><h2>Company</h2><ul><li><a href="index.php#about">About Us</a></li><li><a href="index.php#apply">Careers</a></li><li><a href="index.php#apply">Privacy Policy</a></li><li><a href="index.php#apply">Terms &amp; Conditions</a></li></ul></nav>
        </div>
        <div class="footer-bottom">
            <p>Mletchido Financial Group is committed to providing responsible and transparent lending solutions. All loan applications are subject to eligibility verification, credit assessment, document review, and final approval. Loan terms, interest rates, and repayment options may vary depending on the applicant's financial profile and applicable regulations.</p>
            <p>© 2026 Mletchido Financial Group. All Rights Reserved. <a href="#">Privacy Policy</a> <a href="#">Terms &amp; Conditions</a> <a href="#">Cookie Policy</a></p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    const peso = (value) => new Intl.NumberFormat('en-PH', {
        style: 'currency', currency: 'PHP', minimumFractionDigits: 2
    }).format(value).replace('PHP', '₱');

    const amountInput = document.querySelector("#amount");
    const amountDisplay = document.querySelector("#amountDisplay");
    amountInput?.addEventListener("input", () => {
        amountDisplay.textContent = peso(Number(amountInput.value));
    });

    const loanTypeSelect = document.querySelector("#loan_type");
    const termOptions = document.querySelector("#termOptions");
    const postedTerm = "<?php echo (int) $term; ?>";

    function populateTerms() {
        const selected = loanTypeSelect.options[loanTypeSelect.selectedIndex];
        const terms = (selected.dataset.terms || "").split(",").filter(Boolean).map(Number);

        termOptions.innerHTML = "";
        terms.forEach((months) => {
            const label = document.createElement("label");
            label.className = "eligibility-term-chip";

            const input = document.createElement("input");
            input.type = "radio";
            input.name = "term";
            input.value = months;
            if (String(months) === postedTerm) {
                input.checked = true;
            }

            const span = document.createElement("span");
            span.textContent = months + " mo";

            label.appendChild(input);
            label.appendChild(span);
            termOptions.appendChild(label);
        });

        if (!termOptions.querySelector("input:checked") && termOptions.firstElementChild) {
            termOptions.firstElementChild.querySelector("input").checked = true;
        }
    }

    loanTypeSelect?.addEventListener("change", populateTerms);
    populateTerms();
})();
</script>
</body>
</html>