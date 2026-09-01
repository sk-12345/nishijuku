<?php
session_start();
require_once '../db.php';
require_once '../permissions.php';

// ✅ ログイン必須を撤廃（ここ消す）
// if (!isset($_SESSION['user'])) { exit('不正アクセス'); }

$actor = requirePermission($pdo, 'create_account_flg', false);

$login_id = trim($_POST['login_id'] ?? '');
$name     = trim($_POST['name'] ?? '');
$password = $_POST['password'] ?? '';

// ✅ role_id は必ず GENERAL 固定（ユーザー入力を信用しない）
$role_id  = 4;
$stmt = $pdo->prepare('SELECT id AS role_id, system_flg, account_flg FROM roles WHERE id=?');
$stmt->execute([$role_id]);
$newRole = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
if (!canAssignRole($actor, $newRole)) denyRequest(403, 'この権限は作成できません', false);

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
  INSERT INTO users (
      login_id, password_hash, name, role_id,
      append_datetime, append_user_id, append_func_id,
      update_datetime, update_user_id, update_func_id, lock_timestamp
  )
  VALUES (?, ?, ?, ?, NOW(), ?, 'register2', NOW(), ?, 'register2', CURRENT_TIMESTAMP)
");
$stmt->execute([$login_id, $hash, $name, $role_id, $login_id, $login_id]);

header("Location: ../login/login.html"); // or homeへ
exit;
