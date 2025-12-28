<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../login/login.php");
    exit();
}

$userId = (int)($_SESSION['user']['id'] ?? 0);
if ($userId <= 0) {
    header("Location: password.html?err=failed");
    exit();
}

$current = $_POST['current_password'] ?? '';
$new     = $_POST['new_password'] ?? '';
$confirm = $_POST['new_password_confirm'] ?? '';

if (strlen($new) < 6) {
    header("Location: password.html?err=short");
    exit();
}

if ($new !== $confirm) {
    header("Location: password.html?err=mismatch");
    exit();
}

// 現在のハッシュ取得
$stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
$stmt->execute([$userId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || !password_verify($current, $row['password_hash'])) {
    header("Location: password.html?err=current");
    exit();
}

// 更新（ハッシュ化）
$newHash = password_hash($new, PASSWORD_DEFAULT);

$upd = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
$ok = $upd->execute([$newHash, $userId]);

if ($ok) {
    // セッション固定対策：念のため更新後にID再生成もOK
    session_regenerate_id(true);
    header("Location: password.html?ok=1");
    exit();
}

header("Location: password.html?err=failed");
exit();
