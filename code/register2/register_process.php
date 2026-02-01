<?php
session_start();
require_once '../db.php';

// ✅ ログイン必須を撤廃（ここ消す）
// if (!isset($_SESSION['user'])) { exit('不正アクセス'); }

// ✅ 権限チェックも撤廃（誰でも作れる）
// $myRoleId = ...
// if (!in_array(...)) { exit(...) }

$login_id = trim($_POST['login_id'] ?? '');
$name     = trim($_POST['name'] ?? '');
$password = $_POST['password'] ?? '';

// ✅ role_id は必ず GENERAL 固定（ユーザー入力を信用しない）
$role_id  = 4;

if ($login_id === '' || $name === '' || $password === '') {
    exit('入力が不足しています');
}

/* ID重複チェック */
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE login_id = ?");
$stmt->execute([$login_id]);
if ((int)$stmt->fetchColumn() > 0) {
    exit('そのログインIDはすでに使用されています');
}

/* 登録 */
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
  INSERT INTO users (login_id, password_hash, name, role_id, created_at, updated_at)
  VALUES (?, ?, ?, ?, NOW(), NOW())
");
$stmt->execute([$login_id, $hash, $name, $role_id]);

header("Location: ../login/login.html"); // or homeへ
exit;
