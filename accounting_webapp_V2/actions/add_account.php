<?php
require_once __DIR__ . '/../includes/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../accounts.php');
    exit;
}
[$ok, $message] = add_account_code($_POST);
flash($ok ? 'success' : 'error', $message);
header('Location: ../accounts.php');
exit;
