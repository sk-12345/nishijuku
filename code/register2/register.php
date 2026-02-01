<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../login/login.php");
    exit;
}

$myRoleId = (int)($_SESSION['user']['role_id'] ?? 0);

if (!in_array($myRoleId, [1, 2], true)) {
    exit('このページにアクセスする権限がありません');
}

header("Location: register.html");
exit;
