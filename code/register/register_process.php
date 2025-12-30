<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user'])) {
    exit('不正アクセス');
}

$myRoleId = (int)($_SESSION['user']['role_id'] ?? 0);

if (!in_array($myRoleId, [1,2], true)) {
    exit('アカウント作成権限がありません');
}

$login_id = trim($_POST['login_id'] ?? '');
$name     = trim($_POST['name'] ?? '');
$password = $_POST['password'] ?? '';
$role_id  = (int)($_POST['role_id'] ?? 0);

if ($login_id === '' || $name === '' || $password === '' || $role_id === 0) {
    exit('入力が不足しています');
}

/* 作成できる権限制限 */
if ($myRoleId === 2) { // ADMIN
    if (!in_array($role_id, [3,4], true)) {
        exit('この権限は作成できません');
    }
}
if ($myRoleId === 1) { // SYSTEM
    if (!in_array($role_id, [2,3,4], true)) {
        exit('この権限は作成できません');
    }
}

/* ID重複チェック */
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE login_id = ?");
$stmt->execute([$login_id]);
if ($stmt->fetchColumn() > 0) {
    exit('そのログインIDはすでに使用されています');
}

/* 登録 */
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
INSERT INTO users (login_id, password_hash, name, role_id, created_at, updated_at)
VALUES (?, ?, ?, ?, NOW(), NOW())
");
$stmt->execute([$login_id, $hash, $name, $role_id]);

header("Location: ../home/home.html");
exit;
