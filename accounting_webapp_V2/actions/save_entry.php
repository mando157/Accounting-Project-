<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../journal.php');
    exit;
}

[$ok, $result] = validate_entry_payload($_POST);
if (!$ok) {
    flash('error', $result);
    header('Location: ../journal.php');
    exit;
}

add_entry($result);
flash('success', 'Journal entry saved successfully. All reports updated.');
header('Location: ../journal.php');
exit;
