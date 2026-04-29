<?php
require_once __DIR__ . '/../includes/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../accounts.php');
    exit;
}
[$ok, $message] = delete_account_code((string)($_POST['code'] ?? ''));
flash($ok ? 'success' : 'error', $message);
header('Location: ../accounts.php');
exit;
