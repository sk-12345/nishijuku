<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user'])) {
    exit('不正アクセス');
}

$myId   = $_SESSION['user']['id'];
$myRole = $_SESSION['user']['role']; // SYSTEM / ADMIN / GENERAL / PHOTO

if (!isset($_POST['user_id'])) {
    exit('削除対象がありません');
}

$targetId = $_POST['user_id'];

/* =========================
   ✅ ① 自分自身は削除不可
========================= */
if ($myId == $targetId) {
    exit('自分自身のアカウントは削除できません');
}

/* =========================
   ✅ ② 削除対象の権限取得
========================= */
$stmt = $pdo->prepare("
    SELECT r.role_name
    FROM users u
    JOIN roles r ON u.role_id = r.id
    WHERE u.id = ?
");
$stmt->execute([$targetId]);
$target = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$target) {
    exit('対象ユーザーが存在しません');
}

$targetRole = $target['role_name'];

/* =========================
   ✅ ③ SYSTEMアカウントは誰も削除不可
========================= */
if ($targetRole === 'SYSTEM') {
    exit('SYSTEMアカウントは削除できません');
}

/* =========================
   ✅ ④ 削除権限チェック
========================= */
$canDelete = false;

// SYSTEM → ADMIN / GENERAL / PHOTO 削除可
if ($myRole === 'SYSTEM') {
    $canDelete = true;
}

// ADMIN → GENERAL / PHOTO のみ削除可
if ($myRole === 'ADMIN' && ($targetRole === 'GENERAL' || $targetRole === 'PHOTO')) {
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
