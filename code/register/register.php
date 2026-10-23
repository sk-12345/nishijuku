<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../permissions.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../login/login.php");
    exit;
}

requirePermission($pdo, 'create_account_flg', false);

header("Location: register.html");
exit;
