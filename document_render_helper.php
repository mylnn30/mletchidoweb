<?php
/**
 * Renders one "document" field as either a real thumbnail (the file
 * was actually uploaded AND still exists on disk) or a clear
 * "not uploaded" placeholder -- never a broken image icon.
 *
 * Usage inside view_application.php, in place of the old
 * hand-written <img> markup for each document:
 *
 *   render_document_field("Valid ID", $application["valid_id_path"]);
 *   render_document_field("Proof of Income", $application["proof_of_income_path"]);
 *   render_document_field("Proof of Address", $application["proof_of_address_path"]);
 *
 *   if (!empty($application["employment_certificate_path"])) {
 *       render_document_field("Employment Certificate", $application["employment_certificate_path"]);
 *   }
 */
function render_document_field($label, $path) {
    // file_exists() is the important part here -- it checks the real
    // file on disk, not just whether the database column happens to
    // have a non-empty string in it. A stale/renamed/deleted file
    // still falls through to the placeholder instead of a broken icon.
    $has_file = !empty($path) && file_exists(__DIR__ . "/" . $path);

    echo '<div class="detail-field">';
    echo '<span class="field-label">' . htmlspecialchars($label, ENT_QUOTES, "UTF-8") . '</span>';

    if ($has_file) {
        $safe_path = htmlspecialchars($path, ENT_QUOTES, "UTF-8");
        echo '<a href="' . $safe_path . '" target="_blank" rel="noopener">';
        echo '<img src="' . $safe_path . '" alt="Uploaded ' . htmlspecialchars($label, ENT_QUOTES, "UTF-8") . '" class="document-thumb">';
        echo '</a>';
    } else {
        echo '<div class="document-missing">No document uploaded</div>';
    }

    echo '</div>';
}