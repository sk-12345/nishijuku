<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../permissions.php';
$actor = requirePermission($pdo, 'account_flg', false);
$targetId = (int)($_POST['user_id'] ?? 0);
if ($targetId <= 0) denyRequest(400, '削除対象がありません', false);
$target = roleByUserId($pdo, $targetId);
if (!$target) denyRequest(404, '対象ユーザーが存在しません', false);
if (!canManageUser($actor, $target)) denyRequest(403, 'このユーザーは削除できません', false);
$stmt = $pdo->prepare('DELETE FROM users WHERE id=?');
$stmt->execute([$targetId]);
header('Location: account.html');
