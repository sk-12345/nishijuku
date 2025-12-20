<?php
session_start();
require_once '../db.php';

/* ========= ログイン必須 ========= */
if (!isset($_SESSION['user'])) {
    exit('不正アクセス');
}

$myRole = strtoupper($_SESSION['user']['role'] ?? '');

/* ========= 作成権限チェック ========= */
if ($myRole !== 'SYSTEM' && $myRole !== 'ADMIN') {
    exit('アカウント作成権限がありません');
}

/* ========= 入力取得 ========= */
$login_id = $_POST['login_id'] ?? '';
$name     = $_POST['name'] ?? '';
$password = $_POST['password'] ?? '';
$role_id  = (int)($_POST['role_id'] ?? 0);

/* ========= ADMIN の作成制限 ========= */
if ($myRole === 'ADMIN' && ($role_id === 1 || $role_id === 2)) {
    exit('この権限は作成できません');
}

/* ========= ID重複チェック ========= */
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE login_id = ?");
$stmt->execute([$login_id]);
if ($stmt->fetchColumn() > 0) {
    exit('そのログインIDはすでに使用されています');
}

/* ========= 登録 ========= */
$hash = password_hash($password, PASSWORD_DEFAULT);

$sql = "
INSERT INTO users
(login_id, password_hash, name, role_id, created_at, updated_at)
VALUES
(?, ?, ?, ?, NOW(), NOW())
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $login_id,
    $hash,
    $name,
    $role_id
]);

header("Location: ../home/home.php");
exit;
