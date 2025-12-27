<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user'])) {
    exit('不正アクセス');
}

$myId   = (int)$_SESSION['user']['id'];
$myRole = (int)$_SESSION['user']['role_id']; // 1=SYSTEM, 2=ADMIN, 3=PHOTO, 4=GENERAL

if (!isset($_POST['user_id'])) {
    exit('削除対象がありません');
}

$targetId = (int)$_POST['user_id'];

/* =========================
   ✅ ① 自分自身は削除不可
========================= */
if ($myId === $targetId) {
    exit('自分自身のアカウントは削除できません');
}

/* =========================
   ✅ ② 削除対象の role_id（数値）取得
========================= */
$stmt = $pdo->prepare("SELECT role_id FROM users WHERE id = ?");
$stmt->execute([$targetId]);
$target = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$target) {
    exit('対象ユーザーが存在しません');
}

$targetRoleId = (int)$target['role_id'];

/* =========================
   ✅ ③ SYSTEM(1) は誰も削除不可
========================= */
if ($targetRoleId === 1) {
    exit('SYSTEMアカウントは削除できません');
}

/* =========================
   ✅ ④ 削除権限チェック
========================= */
$canDelete = false;

// SYSTEM(1) → SYSTEM以外は削除可（③でSYSTEMは弾いてるからここはtrueでOK）
if ($myRole === 1) {
    $canDelete = true;
}

// ADMIN(2) → GENERAL(4) / PHOTO(3) のみ削除可
if ($myRole === 2 && ($targetRoleId === 4 || $targetRoleId === 3)) {
    $canDelete = true;
}

if (!$canDelete) {
    exit('このユーザーを削除する権限はありません');
}

/* =========================
   ✅ ⑤ 削除実行
========================= */
$stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
$stmt->execute([$targetId]);

header("Location: account_list.php");
exit;
