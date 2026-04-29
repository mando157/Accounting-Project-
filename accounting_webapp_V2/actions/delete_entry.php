<?php
require_once __DIR__ . '/../includes/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../journal.php');
    exit;
}
$id = trim((string)($_POST['entry_id'] ?? ''));
if ($id === '') {
    flash('error', 'Missing entry id.');
    header('Location: ../journal.php');
    exit;
}
if (remove_entry($id)) {
    flash('success', 'Entry deleted successfully. All pages were updated instantly.');
} else {
    flash('error', 'Entry not found.');
}
header('Location: ../journal.php');
exit;
