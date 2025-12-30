<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../login/login.php");
    exit();
}

$myId   = (int)$_SESSION['user']['id'];
$myRole = (int)$_SESSION['user']['role_id']; // 1=SYSTEM, 2=ADMIN

if (!in_array($myRole, [1, 2], true)) {
    exit('権限がありません');
}

$userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$newRoleId = isset($_POST['role_id']) ? (int)$_POST['role_id'] : 0;

if ($userId <= 0 || $newRoleId <= 0) {
    exit('不正な値です');
}

// ✅ 自分自身は変更不可
if ($userId === $myId) {
    exit('自分自身の権限は変更できません');
}

// ✅ 対象ユーザーの現在権限取得
$stmt = $pdo->prepare("SELECT role_id FROM users WHERE id = ?");
$stmt->execute([$userId]);
$target = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$target) exit('対象ユーザーが存在しません');

$targetRole = (int)$target['role_id'];

// ✅ 変更ルール
if ($myRole === 1) {
    // SYSTEM：SYSTEM(1)は触らない（事故防止） + 変更先に1は不可（例）
    if ($targetRole === 1) exit('SYSTEM権限の変更はできません');
    if ($newRoleId === 1) exit('SYSTEM権限には変更できません');
} elseif ($myRole === 2) {
    // ADMIN：GENERAL(3)/PHOTO(4)だけ操作可、変更先も3/4のみ
    if (!in_array($targetRole, [3, 4], true)) exit('変更できる対象ではありません');
    if (!in_array($newRoleId, [3, 4], true)) exit('その権限には変更できません');
}

// ✅ 更新
$upd = $pdo->prepare("UPDATE users SET role_id = ? WHERE id = ?");
$upd->execute([$newRoleId, $userId]);

header("Location: account.html");
exit();
