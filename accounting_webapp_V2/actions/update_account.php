<?php
require_once __DIR__ . '/../includes/bootstrap.php';
[$ok, $message] = update_account_code($_POST);
flash($ok ? 'success' : 'error', $message);
header('Location: ../accounts.php');
exit;
